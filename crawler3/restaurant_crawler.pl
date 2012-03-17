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
use editionbounds;

use XML::TreeBuilder;

use constant {
    WORK_TYPE => 8,
    IMAGE_CRAWLER_WORK_TYPE => 9,
    RESTAURANT_COMPANY_ID => 31,
    MAX_DEALS_TO_ADD => 3000,
    RESTAURANT_CACHE_DIRECTORY => "./restaurant_cache/",	
    FEED_RECRAWL_AGE => 18000, # 10 hours
    CJ_USERNAME => "3500744",
    CJ_PASSWORD => "758v6BY7"
};


createCacheDirectory();

workqueue::registerWorker(\&doWork, WORK_TYPE, 1, 0, 600) ||
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

    ############# GET YELP INFORMATION FOR MERCHANTS #####################
    print "Retrieving list of merchants with Yelp Info...\n";
    my %merchant_info;
    getMerchantInfo(\%merchant_info, $output_dbh);
    my $num_yelp_info = scalar keys %merchant_info;
    print "$num_yelp_info have qualifying yelp scores\n";


    ################### PARSE RESTAURANT.COM FEED #### #####################
    print "Parsing Feed XML...\n";
    my $tree = XML::TreeBuilder->new;
    $tree->parse($feed_xml);
    my @offer_tags = $tree->look_down(sub{$_[0]->tag() eq "product"});
    
    ################### PROCESS RESTAURANT.COM FEED #### #####################
    print "Processing feed...\n";
    foreach my $offer_tag (@offer_tags) {
	my $affiliate_url = getInfoFromTag($offer_tag, "buyurl");
	my $price = getInfoFromTag($offer_tag, "retailprice");
	my $value = getInfoFromTag($offer_tag, "price");

	my $url;
	my $merchant_id;
	if ($affiliate_url =~ /url=(http.*)/) {
	    $url = uri_unescape($1);
	    if ($url =~ /rid=([0-9]+)/) {
		$merchant_id = $1;
	    }
	}

	if (!defined($price) || !defined($value) || !defined($merchant_id) ||
	    !defined($url)) { next; }

	if (defined($merchant_info{$merchant_id})) {
	    my $merchant = $merchant_info{$merchant_id};
	    if (!defined($$merchant{"price"}) || !defined($$merchant{"value"}) || 
		(1.0*$$merchant{"value"})/(1.0*$$merchant{"price"}) <
		(1.0*$value)/(1.0*$price))
	    {
		$$merchant{"price"} = $price;
		$$merchant{"value"} = $value;
	    }

	    my $discount =
		100.0*($$merchant{"value"} - $$merchant{"price"})/$$merchant{"value"};
	    
	    $$merchant{"title"} = $$merchant{"name"};
	    $$merchant{"subtitle"} = sprintf("Eat for %.0f%% off!", $discount);
	    $$merchant{"affiliate_url"} = $affiliate_url;
	    $$merchant{"image_url"} = getInfoFromTag($offer_tag, "imageurl");
	    $$merchant{"text"} = getInfoFromTag($offer_tag, "description");
	    $$merchant{"fine_print"} = getInfoFromTag($offer_tag, "warranty");
	}
    }
    $tree->delete();

    
    ############## SORT THEN INSERT DEALS INTO DB WITH HIGH ENOUGH SCORES #############
    print "Inserting deals with high enough score...\n";
    my $add_total=0;
    my $new_deals=0;
    foreach my $merchant_id (sort {score($merchant_info{$b}) <=> 
				       score($merchant_info{$a})} keys %merchant_info)
    {
	my $merchant = $merchant_info{$merchant_id};	
	if (defined($$merchant{"price"})) {

	    my $yelp_rating = $$merchant{"yelp_rating"};
	    my $yelp_count = $$merchant{"yelp_review_count"};
	    my $lat = $$merchant{"latitude"};
	    my $lng = $$merchant{"longitude"};

	    my $score = score($merchant);

	    if ($score > 4.07 && editionbounds::inLiveEdition($lat, $lng)) {
		$new_deals += insertDeal($merchant, $output_dbh,
					 ${$work_ref}{"output_server"},
					 ${$work_ref}{"output_database"});
		print ".";
		$add_total++;
	    }

	    if ($add_total >= MAX_DEALS_TO_ADD) { last; }
	}
    }

    print "\n$add_total were be added to database of which $new_deals were new\n";

    $$status_ref = 0;
    $$status_message_ref =
	"$add_total deals were added to the database of which $new_deals were new";
}


sub insertDeal {
    my $merchant_ref = shift;
    my $dbh = shift;
    my $output_server = shift;
    my $output_database = shift;

    my $is_deal_new = 0;


    my $deal_id = &dealsdbutils::getDealId($dbh, $$merchant_ref{"url"});
    
    if ($deal_id == 0) {
	$is_deal_new = 1;
	$deal_id =
	    &dealsdbutils::createDealId($dbh, $$merchant_ref{"url"},
					RESTAURANT_COMPANY_ID);

	# If deal has any images, we need to add image crawling
	# work to the WorkQueue
	if (defined($$merchant_ref{"image_url"})) {
	    workqueue::addWork($$merchant_ref{"url"}, IMAGE_CRAWLER_WORK_TYPE,
			       RESTAURANT_COMPANY_ID, 0, 
			       # Image crawler info should be put in same
			       # database as deal
			       $output_server, $output_database, 0);
	}


	if (!&dealsdbutils::inTable($dbh, $deal_id, "Addresses")) {
	    insertAddress($merchant_ref, $dbh, $deal_id);
	}
	
	if (!&dealsdbutils::inTable($dbh, $deal_id, "Images")) {
	    insertImage($merchant_ref, $dbh, $deal_id);
	}
	
	if (!&dealsdbutils::inTable($dbh, $deal_id, "Categories")) {
	    insertCategory($merchant_ref, $dbh, $deal_id);
	}
    }
    
    # The following fields aren't in the Deals table so we should remove them
    # before inserting the other information in the table:
    my @remove_fields = ("merchant_id", "raw_address","street","city","state",
			  "zipcode","latitude","longitude", "image_url");
    foreach my $remove_field (@remove_fields) {
	delete($$merchant_ref{$remove_field});
    }

    my $set = "last_updated=UTC_TIMESTAMP(),".
	"deadline=DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 DAY)";
    my @set_keys = ("company_id=?");
    my @set_values = (RESTAURANT_COMPANY_ID);
    
    foreach my $field (keys %{$merchant_ref}) {
	if (defined($$merchant_ref{$field})) {
	    push(@set_keys, "$field=?");
	    push(@set_values, $$merchant_ref{$field});
	}
    }
    
    $" = ",";
    my $sql = "update Deals set $set,@set_keys where id=$deal_id";
    
    #print "[$sql]\n";
    my $sth = $dbh->prepare($sql);
    my $i;
    for ($i=0; $i <= $#set_values; $i++) {
	$sth->bind_param($i+1, $set_values[$i]);
    }
    
    $sth->execute();
    return $is_deal_new;
}

sub insertAddress {
    my $merchant_ref = shift;
    my $dbh = shift;
    my $deal_id = shift;

    my @address_fields = ("raw_address","street","city","state",
			  "zipcode","latitude","longitude");

    my @keys = ("deal_id");
    my @key_place_holders = ("?");
    my @values = ($deal_id);
    
    foreach my $address_field (@address_fields) {
	if (defined($$merchant_ref{$address_field})) {
	    push(@keys, $address_field);
	    push(@values, $$merchant_ref{$address_field});
	    push(@key_place_holders, "?");
	}
    }

    $" = ",";
    my $sql = "insert into Addresses (@keys) values (@key_place_holders)";

    #print "[$sql]\n";
    my $sth = $dbh->prepare($sql);
    my $i;
    for ($i=0; $i <= $#values; $i++) {
        $sth->bind_param($i+1, $values[$i]);
    }

    $sth->execute();
}

sub insertImage {
    my $merchant_ref = shift;
    my $dbh = shift;
    my $deal_id = shift;

    if (defined($$merchant_ref{"image_url"})) {
	my $sql = "insert into Images (deal_id, image_url) values (?,?) ".
	    "on duplicate key update id=id";
	my $sth = $dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $$merchant_ref{"image_url"});
	$sth->execute();
    } else {
	print "No image for merchant_id [".$$merchant_ref{"merchant_id"}."]";
    }
}

sub insertCategory {
    my $merchant_ref = shift;
    my $dbh = shift;
    my $deal_id = shift;
     
    my $sql = "insert into Categories (deal_id, category_id, rank) ".
	"values ($deal_id,1,0) on duplicate key update id=id";
    my $sth = $dbh->prepare($sql);
    $sth->execute();
}


sub score {
    my $ref = shift;
    my $rating = $$ref{"yelp_rating"};
    my $count =  $$ref{"yelp_review_count"};
    my $divisor;

    if ($rating <=3) {
	$divisor = 200;
    } elsif ($rating > 3 && $rating < 4) {
	$divisor = 150;
    } elsif ($rating >=4 && $rating < 4.5) {
	$divisor = 100;
    } elsif ($rating >= 4.5) {
	$divisor = 50;
    }
    
    return $rating + (1.0*$count)/(1.0*$divisor);
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


sub getMerchantInfo {
    my $merchant_info_ref = shift;
    my $dbh = shift;
    my $sql = "select merchant_id, name, url, phone, raw_address,".
	"street,city,state,zipcode,latitude,longitude,".
	"yelp_rating,yelp_url,yelp_categories,yelp_review_count,".
	"yelp_excerpt1,yelp_review_url1,yelp_user1,yelp_rating1,yelp_user_url1,yelp_user_image_url1,".
	"yelp_excerpt2,yelp_review_url2,yelp_user2,yelp_rating2,yelp_user_url2,yelp_user_image_url2,".
	"yelp_excerpt3,yelp_review_url3,yelp_user3,yelp_rating3,yelp_user_url3,yelp_user_image_url3 ".
  	"from YelpInfo where yelp_rating is not null and ".
	"yelp_review_count is not null and zipcode is not null and ".
	"latitude is not null and longitude is not null and ".
	"yelp_rating >=3 and yelp_review_count >= 5";
    my $sth = $dbh->prepare($sql);
    $sth->execute() || return 0;
    while (my $ref = $sth->fetchrow_hashref()) { 
	${$merchant_info_ref}{$$ref{"merchant_id"}} = $ref;
    }
}




sub createCacheDirectory {
    unless (-d RESTAURANT_CACHE_DIRECTORY) {
	mkdir(RESTAURANT_CACHE_DIRECTORY, 0777) || die "Couldn't create" . 
	    RESTAURANT_CACHE_DIRECTORY . "\n";
    }
}


