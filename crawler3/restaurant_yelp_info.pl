#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) January, 2012
#

package main;

use strict;
use warnings;
use workqueue;
use dealsdbutils;

use LWP;
use LWP::UserAgent;
use LWP::Simple;
use Digest::MD5 qw(md5_hex);
use Term::ANSIColor;
use genericextractor;
use URI::Escape;
use JSON::XS;
use XML::TreeBuilder;
use Geo::Coder::Googlev3;


my $geocoder = Geo::Coder::Googlev3->new;
my $json_coder = JSON::XS->new->utf8->allow_nonref;

use constant {
    WORK_TYPE => 7,
    RESTAURANT_CACHE_DIRECTORY => "./restaurant_cache/",
    CACHE_EXPIRATION => 86400, # 1 day
    YELP_INFO_EXPIRATION => 1814400, # 3 weeks
    YELP_ID => "wu6Vp9sa7z62Wpv7H-7GwA",
    FEED_RECRAWL_AGE => 18000, # 10 hours
    CJ_USERNAME => "3500744",
    CJ_PASSWORD => "7ucNNhi3",
    MAX_OFFERS_TO_PROCESS => 10
};


createCacheDirectory();

workqueue::registerWorker(\&doWork, WORK_TYPE, 1, 0, 30) ||
    die "Unable to register worker\n";
workqueue::run();

sub doWork {
    my $work_ref = shift;
    my $workqueue_dbh = shift;
    my $output_dbh = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $restaurant_feed = ${$work_ref}{"work"};
    my $feed_xml = getFeedXML($restaurant_feed);
    
    if (!$feed_xml) {
	$$status_ref = 2;
	$$status_message_ref = "Unable to obtain feed content";
	return;
    }

    print "Retrieving list of previously processed merchants...\n";
    my %processed_merchants;
    getProcessedMerchants(\%processed_merchants, $output_dbh);
    my $num_processed = scalar keys %processed_merchants;
    print "$num_processed merchants previously processed\n";

    print "Parsing Feed XML...\n";
    my $tree = XML::TreeBuilder->new;
    $tree->parse($feed_xml);
    my @offer_tags = $tree->look_down(sub{$_[0]->tag() eq "product"});
    
    print "Processing feed...\n";
    my $offers_processed = 0;
    my $total_offers = 0;
    my $num_offers_with_yelp_info = 0;

    foreach my $offer_tag (@offer_tags) {
	$total_offers++;
	if ($offers_processed >= MAX_OFFERS_TO_PROCESS) {
	    last;
	}

	my $offer_url = getInfoFromTag($offer_tag, "buyurl");
	if ($offer_url =~ /url=(http.*)/) {
	    $offer_url = uri_unescape($1);
	} else {
	    $offer_url = undef;
	}
	my $merchant_id;
	if ($offer_url =~ /rid=([0-9]+)/) {
	    $merchant_id=$1;
	}

	my $zipcode = getInfoFromTag($offer_tag, "manufacturerid");
	my $name = getInfoFromTag($offer_tag, "name");
	my $info = getInfoFromTag($offer_tag, "keywords");


	unless ($offer_url && $merchant_id && $name && $info &&
		!defined($processed_merchants{$merchant_id})) { next; }
	$name =~ s/\&amp;/\&/g;
	$name =~ s/\&#39;/'/g;

	
	my %address_info;

	if ($info =~ /(.*),([^,]+),([A-Z]{2}),([0-9]{5})/) {
	    $address_info{"street"} = $1;
	    $address_info{"city"} = $2;
	    $address_info{"state"} = $3;
	    $address_info{"zipcode"} = $4;
	    
	    $address_info{"raw_address"} =
		$address_info{"street"}.", ".$address_info{"city"}.", ".
		$address_info{"state"}.", ".$address_info{"zipcode"}.", usa";
	    print "Geocoding [".$address_info{"raw_address"}."]\n";
	    my $location = $geocoder->geocode(location => $address_info{"raw_address"});
	    if (defined($location)) {
		print "$name [$merchant_id]\n";
		$address_info{"latitude"} = $location->{geometry}->{location}->{lat};
		$address_info{"longitude"} = $location->{geometry}->{location}->{lng};
		print "Got latitude [".$address_info{"latitude"}.",".
		    $address_info{"longitude"}."]\n";
	    }
	    
	    if ($info =~ /$address_info{"zipcode"},(\(?[0-9][0-9-\.\s\)]{8,14}),/) {
		$address_info{"phone"} = $1;
	    }
	}


	my %yelp_info;

	my $yelpstatus =
	    getYelpInfo(\%yelp_info, $name, $offer_url,
			$address_info{"phone"},
			$address_info{"latitude"},
			$address_info{"longitude"});

	if ($yelpstatus > 0) {
	    $num_offers_with_yelp_info++;
	}

	insertMerchantInfo($output_dbh, $merchant_id, $name, $offer_url,
			   \%address_info, \%yelp_info);

	
	print "Yelp status [$yelpstatus]\n";
	if ($yelpstatus > 0) {
	    print color 'yellow';
	    print "Rating: ".$yelp_info{"yelp_rating"},"\n";
	    print "Count:  ".$yelp_info{"yelp_review_count"},"\n";
	    print color 'white';
	}


	$processed_merchants{$merchant_id} = 1;
	$offers_processed++;
	sleep(7);
    }

    $tree->delete();

    $$status_ref = 0;
    $$status_message_ref = "Total offers: $total_offers. Offers processed: ".
	"$offers_processed. Offers with yelp reviews: $num_offers_with_yelp_info.";
}


sub getInfoFromTag {
    my $tag = shift;
    my $info_name = shift;
    my $info;

    $info = $tag->look_down(sub{$_[0]->tag() eq $info_name});
    if (defined($info)) {
	$info = $info->as_text();
    }
    return $info;
}

sub getFeedXML {
    my $feed_url = shift;
    my $feed_file = RESTAURANT_CACHE_DIRECTORY."feed.xml";

    #print " ", (time() - (stat($feed_file))[9]), " ***\n";
    if (!(-e $feed_file) || (time() - (stat($feed_file))[9]) > FEED_RECRAWL_AGE) {
	print "Downloading feed [$feed_url]\n";
	my $ua = LWP::UserAgent->new;
	my $req = HTTP::Request->new(GET => $feed_url);
	$req->authorization_basic(CJ_USERNAME, CJ_PASSWORD);

	print "Decompressing feed file [$feed_file.gz]\n";
	open(FILE, ">$feed_file.gz");
	print FILE $ua->request($req)->content;
	close(FILE);
	system("gunzip -f $feed_file.gz");
    }

    my $feed_xml;
    local $/;
    open(FILE, $feed_file) || return;
    $feed_xml = <FILE>;
    close(FILE);
    return $feed_xml;
}


sub getProcessedMerchants {
    my $processed_merchants_ref = shift;
    my $dbh = shift;
    my $sql = "select merchant_id from YelpInfo where ".
	"TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(),last_updated)) < ".YELP_INFO_EXPIRATION;
    #print "[$sql]\n";
    my $sth = $dbh->prepare($sql);
    $sth->execute() || return 0;
    while (my $merchant_id =$sth->fetchrow_array()) { 
	${$processed_merchants_ref}{$merchant_id} = 1;
    }
}


sub getYelpInfo {
    my $yelp_info_ref = shift;
    my $name = shift;
    my $url = shift;
    my $phone = shift;
    my $latitude = shift;
    my $longitude = shift;
    

    # First try find yelp review by looking up phone number:
    #
    if (defined($phone) && length($phone) >=9) {
	my $yelp_request =
	    "http://api.yelp.com/phone_search?phone=$phone".
	    "&ywsid=".YELP_ID;
	
	if (checkBusinessForMatch($yelp_request, $name, $url, $yelp_info_ref)) {
	    return 1;
	}
    }
    
    # If phone number lookup didn't yield a match, then look
    # up the businesses based on lat/long in a 0.1 mile radius
    #
    if (defined($latitude) && defined($longitude)) {
	my $yelp_request =
	    "http://api.yelp.com/business_review_search?".
	    "lat=$latitude&long=$longitude&radius=0.1&limit=20".
	    "&ywsid=".YELP_ID;
	if (checkBusinessForMatch($yelp_request, $name, $url, $yelp_info_ref)) {
	    return 2;
	}
	
	my $modified_name = $name;
	$modified_name =~ s/\s+/%20/g;
	$modified_name =~ s/[^A-Za-z'%0-9]//g;
	
	# If the lat-long didn't work by themselves, we may
	# have an area that is dense with businesses. Since
	# yelp only returns 20 results we can try combining
	# the lat-long with the business name to see if that
	# gets a match. When using the business name we will
	# relax the search radius a bit to 1 mile.
	#
	$yelp_request =
	    "http://api.yelp.com/business_review_search?".
	    "term=$modified_name".
	    "&lat=$latitude&long=$longitude&radius=1&limit=20".
	    "&ywsid=".YELP_ID;
	
	if (checkBusinessForMatch($yelp_request, $name, $url, $yelp_info_ref)) {
	    return 3;
	}        
    }

    return 0;
}




sub insertMerchantInfo {
    my $dbh = shift;
    my $merchant_id = shift;
    my $name = shift;
    my $url = shift;
    my $address_info_ref = shift;
    my $yelp_info_ref = shift;


    my @keys = ("merchant_id", "name", "url");
    my @set_keys = ("merchant_id=?", "name=?", "url=?");
    my @values = ($merchant_id, $name, $url);
    
    foreach my $key (keys %{$address_info_ref}) {
        push(@keys, $key);
	push(@set_keys, "$key=?");
        push(@values, ${$address_info_ref}{$key});
    }

    foreach my $key (keys %{$yelp_info_ref}) {
        push(@keys, $key);
	push(@set_keys, "$key=?");
        push(@values, ${$yelp_info_ref}{$key});
    }
    
    my @key_place_holders;
    foreach my $key (@keys) {
	push(@key_place_holders, "?");
    }

    $" = ",";
    my $sql = "insert into YelpInfo (last_updated,@keys) ".
	"values (UTC_TIMESTAMP(),@key_place_holders) ".
	"on duplicate key update last_updated=UTC_TIMESTAMP(),@set_keys";
    #print "[$sql]\n";
    my $sth = $dbh->prepare($sql);
    my $i;
    for ($i=0; $i <= $#values; $i++) {
        $sth->bind_param($i+1, $values[$i]);
    }

    for ($i=0; $i <= $#values; $i++) {
        $sth->bind_param($i+$#values+2, $values[$i]);
    }

    $sth->execute() || return 0;

    return 1;
}






sub checkBusinessForMatch {
    if ($#_ != 3) {
	die "Incorrect usage of checkBusinessForMatch\n";
    }
    
    my $yelp_request = shift;
    my $name = shift;
    my $deal_url = shift;
    my $yelp_info_ref = shift;
    
    %{$yelp_info_ref} = ();
    
    my ($yelp_page, %json, @businesses);
    $yelp_page = getYelpPage($yelp_request, $deal_url, $name) || return 0;
    %json = %{$json_coder->decode($yelp_page)};
    
    # If we go over the yelp query limit the return code will not be
    # 0. We want to check for this code, because continuing processing
    # empty yelp requests is bad, especially since we cache them.
    if (!defined($json{"message"}{"code"}) ||
	$json{"message"}{"code"} eq "4") {
	die "Error in return yelp request [$yelp_request]\n$yelp_page\n";
    }
    
    if (defined($json{"message"}{"code"}) && $json{"message"}{"code"} ne "0") {
	return 0;
    }
    @businesses = @{$json{"businesses"}};
    
    my $best_score = 1000;
    my $best_candidate;
    my $found_match = 0;
    my $highest_review_count = -1;
    
    foreach my $business (@businesses) {
	my $yelp_name = ${$business}{"name"};
	my $yelp_rating = ${$business}{"avg_rating"};
	my $yelp_url = ${$business}{"url"};
	my $yelp_review_count = ${$business}{"review_count"};
	my @reviews = @{${$business}{"reviews"}};
	
	
	my @categories = @{${$business}{"categories"}};
	my $yelp_categories;
	foreach my $category (@categories) {
	    if (!defined($yelp_categories)) { 
		$yelp_categories = $category->{"name"};
	    } else {
		$yelp_categories =
		    $yelp_categories.",".$category->{"name"};
	    }
	}
	
	my $tmp_score = 0;
	# Insert yelp information into database if the yelp name
	# is similar enough to the business name in the database.
	if (genericextractor::similarEnough($yelp_name, $name, \$tmp_score) &&
	    $yelp_review_count > $highest_review_count) {
	    $highest_review_count = $yelp_review_count;
	    print color 'yellow';
	    print "[$yelp_name] and [$name] are similar enough\n";
	    print color 'white';
	    
	    if (defined($yelp_rating)) {
		${$yelp_info_ref}{"yelp_rating"} = $yelp_rating;
	    }
	    if (defined($yelp_url)) {
		${$yelp_info_ref}{"yelp_url"} = $yelp_url;
	    }
	    if (defined($yelp_review_count)) {
		${$yelp_info_ref}{"yelp_review_count"} = $yelp_review_count;
	    }
	    if (defined($yelp_categories)) {
		${$yelp_info_ref}{"yelp_categories"} = $yelp_categories;
	    }
	    
	    foreach (my $i=0; $i <= $#reviews && $i <= 2; $i++) {
		my $j = $i+1;
		if (defined(${$reviews[$i]}{"text_excerpt"})) {
		    ${$yelp_info_ref}{"yelp_excerpt$j"} = 
			${$reviews[$i]}{"text_excerpt"};
		}
		if (defined(${$reviews[$i]}{"url"})) {
		    ${$yelp_info_ref}{"yelp_review_url$j"} = 
			${$reviews[$i]}{"url"};
		}
		if (defined(${$reviews[$i]}{"user_name"})) {
		    ${$yelp_info_ref}{"yelp_user$j"} =
			${$reviews[$i]}{"user_name"};
		}
		if (defined(${$reviews[$i]}{"rating"})) {
		    ${$yelp_info_ref}{"yelp_rating$j"} =
			${$reviews[$i]}{"rating"};
		}
		if (defined(${$reviews[$i]}{"user_url"})) {
		    ${$yelp_info_ref}{"yelp_user_url$j"} =
			${$reviews[$i]}{"user_url"};
		}
		if (defined(${$reviews[$i]}{"user_photo_url"})) {
		    ${$yelp_info_ref}{"yelp_user_image_url$j"} =
			${$reviews[$i]}{"user_photo_url"};
		}
	    }
	    
	    # Since we found the yelp review, we can skip the
	    # rest of @businesses
	    $found_match = 1;
	} else {
	    #print "[$name] and [$yelp_name] are not similar enough\n";
	}
	
	if ($tmp_score < $best_score) {
	    $best_score = $tmp_score;
	    $best_candidate = $yelp_name;
	}
    }

    if (!defined($best_candidate)) {
	$best_candidate = "*No best candidate available*";
    }
    print color 'yellow';
    print "[$name] was closest to [$best_candidate] score: $best_score\n";
    print color 'white';
    
    return $found_match;
}



sub getYelpPage {
    my ($deal_url,$name,$phone,$yelp_request);
    if ($#_ == 2) { 
	$yelp_request = shift;
	$deal_url = shift;
	$name = shift;
    } else { die "Incorrect usage of getYelpPage\n"; }
    
    print "Yelp request: [$yelp_request]\n";
    my $filename = RESTAURANT_CACHE_DIRECTORY.md5_hex($yelp_request).".html";
    my $yelp_page;
    if ((-e $filename) && (time() - (stat($filename))[9]) < CACHE_EXPIRATION &&
	open(FILE, $filename))
    {
	print "Getting from cache: [$filename]\n";
	my $line = <FILE>;
	
	while ($line = <FILE>) {
	    $yelp_page = $yelp_page.$line;
	}
	close(FILE);
    } else {
	print "Not in cache, downloading and inserting into cache: ".
	    "[$filename]\n";
	$yelp_page = get $yelp_request;
	if (defined($yelp_page)) {
	    open(FILE, ">$filename");
	    print FILE "$name,$deal_url,$yelp_request\n";
	    print FILE $yelp_page;
	    close(FILE);
	}
    }
    
    return $yelp_page;
}



sub createCacheDirectory {
    unless (-d RESTAURANT_CACHE_DIRECTORY) {
	mkdir(RESTAURANT_CACHE_DIRECTORY, 0777) || die "Couldn't create" . 
	    RESTAURANT_CACHE_DIRECTORY . "\n";
    }
}
