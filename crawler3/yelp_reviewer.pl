#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) September, 2011
#

package main;

use strict;
use warnings;
use workqueue;
use dealsdbutils;

use downloader;

use Digest::MD5 qw(md5_hex);
use Term::ANSIColor;
use genericextractor;
use JSON::XS;

my $json_coder = JSON::XS->new->utf8->allow_nonref;

use constant {
    WORK_TYPE => 5,
    YELP_CACHE_DIRECTORY => "./yelp_cache/",	
    YELP_ID => "wu6Vp9sa7z62Wpv7H-7GwA",
    CACHE_EXPIRATION => 86400 # 1 day.
};


createCacheDirectory();

workqueue::registerWorker(\&doWork, WORK_TYPE, 10, 5, 30) ||
    die "Unable to register worker\n";
workqueue::run();



sub doWork {
    my $work_ref = shift;
    my $workqueue_dbh = shift;
    my $output_dbh = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $deal_url = ${$work_ref}{"work"};
    my $deal_id = &dealsdbutils::getDealId($output_dbh, $deal_url);

    # This should never happen, but just in case:
    if ($deal_id == 0) {
        $$status_ref = 2;
        $$status_message_ref = "Couldn't find deal ID for url: ".$deal_url;
        return;
    }
 
    
    my $sql = "SELECT Deals.name, Deals.phone, Addresses.id, ".
	"Addresses.latitude, Addresses.longitude FROM Deals left ".
	"join Addresses on Addresses.deal_id=Deals.id where ".
	"Deals.name is not null and (Addresses.raw_address is ".
	"not null or Deals.phone is not null) and Deals.id=?";
    
    my $sth = $output_dbh->prepare($sql);
    $sth->bind_param(1, $deal_id);
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$output_dbh->errstr;
        return;        
    }
    
    
    my $num_addresses = 0;
    my $num_geocoded = 0;
    
    while (my @result = $sth->fetchrow_array()) {
        my $name = $result[0];
	$name =~ s/\&amp;/\&/g;
	$name =~ s/\&#39;/'/g;        
        
        my $phone = $result[1];
	if (defined($phone)) { $phone =~ s/[^0-9]//g;  }
        
        my $address_id = $result[2];
        my $latitude = $result[3];
        my $longitude = $result[4];
        
        if (defined($address_id)) {
            $num_addresses++;
            if (defined($latitude) && defined($longitude)) {
                $num_geocoded++;
            }
        }
	
	# Used to store the yelp information for the business we're
	# searching for using the yelp API.
	my %yelp_info;
	
	
	# First try find yelp review by looking up phone number:
	#
	if (defined($phone) && length($phone) >=9) {
            my $yelp_request =
                "http://api.yelp.com/phone_search?phone=$phone".
                "&ywsid=".YELP_ID;
	    
            if (checkBusinessForMatch($yelp_request, $name, 
				      $deal_url, \%yelp_info,
				      $status_ref,
				      $status_message_ref)) {
		insertYelpInfo($deal_id, \%yelp_info, $output_dbh,
			       $status_ref, $status_message_ref) || return;
		$$status_ref = 0;
		$$status_message_ref = "Successfully located yelp review.";
		# If found a yelp review set frequency of yelp work to 0
		# so it won't get done again:
		workqueue::updateFrequency(${$work_ref}{"id"}, 0);
		return;
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
            if (checkBusinessForMatch($yelp_request, $name,
				      $deal_url, \%yelp_info,
				      $status_ref,
				      $status_message_ref)) {
		insertYelpInfo($deal_id, \%yelp_info, $output_dbh,
			       $status_ref, $status_message_ref) || return;
		$$status_ref = 0;
		$$status_message_ref = "Successfully located yelp review.";
		# If found a yelp review set frequency of yelp work to 0
		# so it won't get done again:
		workqueue::updateFrequency(${$work_ref}{"id"}, 0);
		return;
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
            
            if (checkBusinessForMatch($yelp_request, $name,
				      $deal_url, \%yelp_info,
				      $status_ref,
				      $status_message_ref)) {
		insertYelpInfo($deal_id, \%yelp_info, $output_dbh,
			       $status_ref, $status_message_ref) || return;
		$$status_ref = 0;
		$$status_message_ref = "Successfully located yelp review.";
		# If found a yelp review set frequency of yelp work to 0
		# so it won't get done again:
		workqueue::updateFrequency(${$work_ref}{"id"}, 0);
		return;
	    }        
        }

	print "Sleeping before yelp reviewing next business...\n";
	sleep(5);
    }
    
    if ($num_addresses==$num_geocoded) {
	workqueue::updateFrequency(${$work_ref}{"id"}, 0);
	if ($$status_ref == 3) { # status not previously set
	    $$status_ref = 1;
	    $$status_message_ref = "Cannot find yelp review.";
	}
    } else {
	$$status_ref = 2;
	$$status_message_ref = "Cannot find yelp review. Only ".
	    "$num_geocoded addresses out of $num_addresses are geocoded. ".
	    "Check to see whether geocoder is running.";
    }
    
    $sth->finish();
}


sub insertYelpInfo {
    my $deal_id = shift;
    my $yelp_info_ref = shift;
    my $output_dbh = shift;
    
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my @set_values;
    my @insert_params;
    foreach my $key (keys %{$yelp_info_ref}) {
        push(@set_values, "$key=?");
        push(@insert_params, ${$yelp_info_ref}{$key});
    }
    
    $" = ",";
    my $sql = "update Deals set ".
	"last_updated=UTC_TIMESTAMP(), @set_values where id=?";
    my $sth = $output_dbh->prepare($sql);
    my $i;
    for ($i=0; $i <= $#insert_params; $i++) {
        $sth->bind_param($i+1, $insert_params[$i]);
    }
    $sth->bind_param($i+1, $deal_id);
    
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$output_dbh->errstr;
        return 0;
    }
    
    return 1;
}






sub checkBusinessForMatch {
    if ($#_ != 5) {
	die "Incorrect usage of checkBusinessForMatch\n";
    }
    
    my $yelp_request = shift;
    my $name = shift;
    my $deal_url = shift;
    my $yelp_info_ref = shift;
    
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    %{$yelp_info_ref} = ();
    
    my ($yelp_page, %json, @businesses);
    $yelp_page = getYelpPage($yelp_request, $deal_url, $name);
    %json = %{$json_coder->decode($yelp_page)};
    
    # If we go over the yelp query limit the return code will not be
    # 0. We want to check for this code, because continuing processing
    # empty yelp requests is bad, especially since we cache them.
    if (!defined($json{"message"}{"code"}) ||
	$json{"message"}{"code"} eq "4") {
	die "Error in return yelp request [$yelp_request]\n$yelp_page\n";
    }
    
    if (defined($json{"message"}{"code"}) &&
	$json{"message"}{"code"} ne "0") {
	$$status_ref = 2;
	$$status_message_ref = "Yelp error code ".$json{"message"}{"code"}.
	    " returned for request [$yelp_request]";
	return 0;
    }
    @businesses = @{$json{"businesses"}};
    
    my $best_score = 1000;
    my $best_candidate;
    
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
	if (genericextractor::similarEnough($yelp_name, $name, \$tmp_score)) {
	    #print color 'yellow';
	    #print "[$yelp_name] and [$name] are similar enough\n";
	    #print color 'white';
	    
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
	    return 1;
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
    #print color 'yellow';
    $$status_ref = 1;
    $$status_message_ref = "No yelp info found: [$name] was closest to ".
	"[$best_candidate] score: $best_score. Request [$yelp_request]";
    #print "[$name] was closest to [$best_candidate] score: $best_score\n";
    #print color 'white';
    
    return 0;
}



sub getYelpPage {
    my ($deal_url,$name,$phone,$yelp_request);
    if ($#_ == 2) { 
	$yelp_request = shift;
	$deal_url = shift;
	$name = shift;
    } else { die "Incorrect usage of getYelpPage\n"; }
    
    print "Yelp request: [$yelp_request]\n";
    my $filename = YELP_CACHE_DIRECTORY.md5_hex($yelp_request).".html";
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
	my $yelp_response = downloader::getURL($yelp_request);
	if ($yelp_response->is_success && defined($yelp_response->content()) &&
            length($yelp_response->content()) > 0)
	{
	    $yelp_page = $yelp_response->content();
	    open(FILE, ">$filename");
	    print FILE "$name,$deal_url,$yelp_request\n";
	    print FILE $yelp_page;
	    close(FILE);
	} else {
	    print "Downloaded page wasn't defined! ".$yelp_response->message."\n";
	}
    }
    
    if (!defined($yelp_page)) {
	die "Couldn't obtain page for [$yelp_request]\n";
    }
    
    return $yelp_page;
}



sub createCacheDirectory {
    unless (-d YELP_CACHE_DIRECTORY) {
	mkdir(YELP_CACHE_DIRECTORY, 0777) || die "Couldn't create" . 
	    YELP_CACHE_DIRECTORY . "\n";
    }
}
