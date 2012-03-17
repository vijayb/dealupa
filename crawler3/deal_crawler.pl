#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) September, 2011
#

package main;

use strict;
use warnings;
use workqueue;

use downloader;
use deal;
use dealextractor;
use dealclassifier;
use dealsdbutils;

my $start_time = time();

use constant {
    WORK_TYPE => 3,
    GEOCODER_WORK_TYPE => 4,
    YELP_REVIEWER_WORK_TYPE => 5,
    # We don't set the yelp frequency to 0, which we do for geocoding. The
    # reason is that we may have to make multiple attempts at finding
    # the yelp review based on whether the geocoder has finished running
    # for a given deal.
    YELP_WORK_FREQUENCY => 300,
    MAX_CRAWLABLE_AGE => 864000, # 10 days in seconds

    IMAGE_CRAWLER_WORK_TYPE => 9,
    # Since the deal crawler seems to slowly grow in memory usage
    # we'll retire it every 5 hours. It gets restarted by the worker_restarter
    # so it doesn't matter if they retire, they'll just get respawned.
    RETIREMENT_AGE => 18000
};


workqueue::registerWorker(\&doWork, WORK_TYPE, 10, 2, 7) ||
    die "Unable to register worker\n";
workqueue::run();


sub doWork {
    if (time() - $start_time > RETIREMENT_AGE) {
	print "Process getting old, requesting shutdown...\n";
	workqueue::requestShutdown();
    }

    my $work_ref = shift;
    my $workqueue_dbh = shift;
    my $output_dbh = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $deal_url = ${$work_ref}{"work"};    
    
    my $sql = "select Companies.use_cookie, Companies.crawl_ajax, ".
	"Companies.use_phantom, Companies.use_password from WorkQueue, Companies where ".
	"WorkQueue.company_id=Companies.id and WorkQueue.work=?";

    my $sth = $workqueue_dbh->prepare($sql);
    $sth->bind_param(1, $deal_url);
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$workqueue_dbh->errstr;
        return;        
    }
    
    if (my @result = $sth->fetchrow_array()) {
        my $use_cookie = $result[0];
	my $crawl_ajax = $result[1];
	my $use_phantom = $result[2];
	my $use_password = $result[3];

        my $company_id = ${$work_ref}{"company_id"};
	
        my $response;
        if ($use_cookie) {
            $response = downloader::getURLWithCookie($deal_url);            
        } elsif ($use_phantom) {
	    $response = downloader::getURLWithPhantom($deal_url);
	} elsif ($use_password) {
	    $response = downloader::getURLWithPassword($deal_url, $company_id);
	} elsif ($crawl_ajax) {
	    $response = downloader::getAJAXURL($deal_url);
	} else {
            $response = downloader::getURL($deal_url);
        }
	
        if ($response->is_success && defined($response->content()) &&
            length($response->content()) > 0)
	{
            my $deal_content = $response->content();
            print "Succeeded in downloading ".$deal_url." content length: ".
		length($deal_content)." extracting...\n";
            
	    # Extract and classify deal:
            my $deal = deal->new();
            $deal->url($deal_url);
            $deal->company_id($company_id);
            dealextractor::extractDeal($deal, \$deal_content);
            my @errors = $deal->check_for_extraction_error();
            dealclassifier::classifyDeal($deal, \$deal_content);

	    my $insert_status =	insertDeal($output_dbh, ${$work_ref}{"output_server"},
					   $deal, $status_ref, $status_message_ref);

	    my $deal_expired = 0;

	    if ($insert_status == 0) {
		return;
	    } elsif ($insert_status == 2) {
		# insert_status==2 means the deal was detected as a dup.
		# We don't want to keep recrawling dups, so we'll mark
		# the deal as expired, which will retire the job on the workqueue
		$deal_expired = 1;
	    }

	    if (defined($deal->expired()) && $deal->expired()) {
		$deal_expired = 1;
	    }
	    
	    # If we're past the deadline by a day, then expire
	    # the deal, so that we don't keep recrawling it.
	    if (defined($deal->deadline()) &&
		crawlerutils::diffDatetimesInSeconds(
		    $deal->deadline(), crawlerutils::gmtNow(-24*60*60)) < 0) {
		$deal_expired = 1;
	    }

	    # In case we didn't extract deadline or expired above
	    # we want to make sure we don't keep crawling old work:
	    if (${$work_ref}{"age_seconds"} > MAX_CRAWLABLE_AGE) {
		$deal_expired = 1;
	    }

	    # Make sure this deal isn't crawled again if it has expired:
	    if ($deal_expired) {
		workqueue::updateFrequency(${$work_ref}{"id"}, 0);
	    }
            
            # If deal has any addresses, we need to add geocoding
            # work to the WorkQueue
            if (!$deal_expired && scalar(keys(%{$deal->addresses()})) > 0)
	    {
		unless (
		    workqueue::addWork($deal->url(), GEOCODER_WORK_TYPE,
				       $deal->company_id(), 0, 
				       # Geocoded info should be put in same
				       # database as deal being geocoded:
				       ${$work_ref}{"output_server"},
				       ${$work_ref}{"output_database"},
				       0))
		{
		    $$status_ref = 2;
		    $$status_message_ref = "Unable to insert geo work onto ".
			"work queue for deal: ".$deal_url;
		    return;
		}
            }
	    
	    # If the deal has a business name and either a phone number or
	    # some addresses, then we need to add yelp reviewing to the
	    # workqueue
	    if (!$deal_expired && defined($deal->name()) &&
		(defined($deal->phone()) ||
		 scalar(keys(%{$deal->addresses()})) > 0) )
	    {
		unless (
		    workqueue::addWork($deal->url(), YELP_REVIEWER_WORK_TYPE,
				       $deal->company_id(),
				       YELP_WORK_FREQUENCY, 
				       # Yelp info should be put in same
				       # database as deal we just worked on:
				       ${$work_ref}{"output_server"},
				       ${$work_ref}{"output_database"},
				       0))
		{
		    $$status_ref = 2;
		    $$status_message_ref =
			"Unable to insert yelp reviewing work ".
			"onto work queue for deal: ".$deal_url;
		    return;
		}		
	    }
	    

            # If deal has any images, we need to add image crawling
            # work to the WorkQueue
            if (scalar(keys(%{$deal->image_urls()})) > 0)
	    {
		unless (
		    workqueue::addWork($deal->url(), IMAGE_CRAWLER_WORK_TYPE,
				       $deal->company_id(), 0, 
				       # Image crawler info should be put in same
				       # database as deal
				       ${$work_ref}{"output_server"},
				       ${$work_ref}{"output_database"},
				       0))
		{
		    $$status_ref = 2;
		    $$status_message_ref = "Unable to insert image crawling work onto ".
			"work queue for deal: ".$deal_url;
		    return;
		}
            }
	    
	    # If we've gotten this far, then we've successfully
	    # extracted and inserted the deal, plus added any
	    # attendant work for it (geocoding/yelp reviewing)
            $$status_ref = 0;
	    if ($insert_status == 2) {
		$$status_message_ref =
		    "This deal was marked as a dup, stopping crawling of it.";
	    } else {
		$$status_message_ref =
		    "Successfully downloaded, extracted and inserted";
		if (@errors) {
		    $" = ","; # array separator
		    $$status_message_ref =
			$$status_message_ref."; unable to extract (@errors)";
		}
	    }
        } else {
            $$status_ref = 2;
            $$status_message_ref = "Error while crawling. Code: ".
		$response->code."] Message [".$response->message."]";
            print "Failed downloading ".$deal_url." Code: [".$response->code.
		"] Message [".$response->message."]\n";
        }
    }
    
    $sth->finish();
}


sub insertDeal {
    my $dbh = shift;
    my $dup_server = shift;
    my $deal = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $deal_id = &dealsdbutils::getDealId($dbh, $deal->url());

    if ($deal_id == 0) {
	$deal_id =
	    &dealsdbutils::createDealId($dbh, $deal->url(), $deal->company_id());
    }
    # Shouldn't happen, but just in case:
    if ($deal_id == 0) { return 0; } 

    my @dup_company_ids;

    # Right now we only check for dups across company_ids for MSN Offers (c_id: 39),
    # and Google Offers(c_id 18)
    # because they share a lot of content with Tippr (c_id: 4), but unlike Tippr
    # they don't share num_purchased information, so we'd rather mark
    # MSN deals as dups than Tippr deals.
    # TODO: This information shouldn't be in code, but somewhere in the database
    # (e.g., in the Company table in the WorkQueue database)
    if ($deal->company_id() == 39 || $deal->company_id() == 18) { 
	push(@dup_company_ids, 4);
    }

    my $dup_id = &dealsdbutils::isDup($deal, $deal_id, $dup_server, \@dup_company_ids);
    if ($dup_id == -1) {
	# if $dup_id==-1 this means we failed to connect to the dup_server
	# Question: should we just die completely here? If we don't then
	# there will be lots of deals that aren't marked as dups. However dieing 
	# here would be bad in the sense that the crawl would basically stop.
	$$status_ref = 2;
        $$status_message_ref = "Failed connecting to dup server: ".$dup_server;

	return 0;
    } elsif ($dup_id > 0) {
	#print "Is a dup of [$dup_id]\n";
	markDup($dbh, $deal_id, $dup_id);
	return 2;
    }


    if (!&dealsdbutils::inTable($dbh, $deal_id, "Addresses")) {
	insertAddresses($dbh, $deal, $deal_id,
			$status_ref, $status_message_ref) || return 0;
    }

    if (!&dealsdbutils::inTable($dbh, $deal_id, "Images")) {
	insertImages($dbh, $deal, $deal_id, 
		     $status_ref, $status_message_ref) || return 0;
    }

    if (!&dealsdbutils::inTable($dbh, $deal_id, "Categories")) {
	# If the deal hasn't been classified, and it has categories
	# AND no addresses, we will assume it's a national deal.
	# If we don't assume this the local editions start getting
	# polluted with lots of national deals that were automatically
	# classified
	if (defined($deal->category_id()) &&
	    scalar(keys(%{$deal->addresses()})) == 0) {
	    insertNational($dbh, $deal, $deal_id,
			   $status_ref, $status_message_ref) || return 0;
	}

	insertCategories($dbh, $deal, $deal_id,
			 $status_ref, $status_message_ref) || return 0;
    }

    &dealsdbutils::setFBInfo($deal);

    my $update_values = "last_updated=UTC_TIMESTAMP()";
    my @update_params;

    if (defined($deal->affiliate_url())) {
        $update_values = $update_values.", affiliate_url=?";
        push(@update_params, $deal->affiliate_url());
    }
    if (defined($deal->title())) {
        $update_values = $update_values.", title=?";
        push(@update_params, $deal->title());
    }
    if (defined($deal->subtitle())) {
        $update_values = $update_values.", subtitle=?";
        push(@update_params, $deal->subtitle());
    }
    if (defined($deal->price())) {
        $update_values = $update_values.", price=?";
        push(@update_params, $deal->price());
    }
    if (defined($deal->value())) {
	$update_values = $update_values.", value=?";
        push(@update_params, $deal->value());
    }
    if (defined($deal->num_purchased()) && $deal->num_purchased != -1) {
        $update_values = $update_values.", num_purchased=?";
        push(@update_params, $deal->num_purchased());

	recordHistory($dbh, $deal_id, "NumPurchased", "num_purchased",
		      $deal->num_purchased());
    }
    if (defined($deal->fb_likes())) {
	$update_values = $update_values.", fb_likes=?";
        push(@update_params, $deal->fb_likes());

	recordHistory($dbh, $deal_id, "FBLikes", "fb_likes",
		      $deal->fb_likes());
    }
    if (defined($deal->fb_shares())) {
	$update_values = $update_values.", fb_shares=?";
        push(@update_params, $deal->fb_shares());

	recordHistory($dbh, $deal_id, "FBShares", "fb_shares",
		      $deal->fb_shares());
    }
    if (defined($deal->text())) {
	$update_values = $update_values.", text=?";
        push(@update_params, $deal->text());
    }
    if (defined($deal->fine_print())) {
	$update_values = $update_values.", fine_print=?";	
        push(@update_params, $deal->fine_print());
    }
    if (defined($deal->expired())) {
        $update_values = $update_values.", expired=?";
        push(@update_params, $deal->expired());
    }
    if (defined($deal->upcoming())) {
        $update_values = $update_values.", upcoming=?";
        push(@update_params, $deal->upcoming());
    }    
    if (defined($deal->deadline())) {
        $update_values = $update_values.", deadline=?";
        push(@update_params, $deal->deadline());
    }
    if (defined($deal->expires())) {
        $update_values = $update_values.", expires=?";
        push(@update_params, $deal->expires());
    }
    if (defined($deal->name())) {
        $update_values = $update_values.", name=?";
        push(@update_params, $deal->name());
    }
    if (defined($deal->website())) {
        $update_values = $update_values.", website=?";
        push(@update_params, $deal->website());
    }
    if (defined($deal->phone())) {
        $update_values = $update_values.", phone=?";
        push(@update_params, $deal->phone());
    }
    
    my $sql = "UPDATE Deals set $update_values where id=$deal_id";

    my $sth = $dbh->prepare($sql);
    for (my $i=0; $i <= $#update_params; $i++) {
        $sth->bind_param($i+1, $update_params[$i]);
    }


    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$dbh->errstr;
        return 0;
    }

    return 1;
}

sub markDup {
    my $dbh = shift;
    my $deal_id = shift;
    my $dup_id = shift;

    my $sql = "update Deals set dup=true,dup_id=? where id=?";
    my $sth = $dbh->prepare($sql);
    $sth->bind_param(1, $dup_id);
    $sth->bind_param(2, $deal_id);
    if (!$sth->execute()) {
	return 0;
    }
    return 1;
}

sub insertAddresses {
    my $dbh = shift;
    my $deal = shift;
    my $deal_id = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;

    my $addresses_ref = $deal->addresses();
    foreach my $address (keys %{$addresses_ref}) {
	my $sql = "insert into Addresses (deal_id, raw_address) ".
	    "values (?,?)";
	
	my $sth = $dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $address);
	if (!$sth->execute()) {
	    $$status_ref = 2;
	    $$status_message_ref = "Failed database query: ".$dbh->errstr;
	    return 0;
	}
    }
    
    return 1;
}


# Insert (potentially) multiple images per deal
sub insertImages {
    my $dbh = shift;
    my $deal = shift;
    my $deal_id = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $image_urls_ref = $deal->image_urls();
    foreach my $image_url (keys %{$image_urls_ref}) {
	my $sql = "insert into Images (deal_id, image_url) ".
	    "values (?,?) on duplicate key update id=id";
	
	my $sth = $dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $image_url);
	if (!$sth->execute()) {
	    $$status_ref = 2;
	    $$status_message_ref = "Failed database query: ".$dbh->errstr;
	    return 0;
	}
    }
    
    return 1;
}




# For now we only have one category
# TODO: allow extraction of multiple categories
sub insertCategories {
    my $dbh = shift;
    my $deal = shift;
    my $deal_id = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;

    if (defined($deal->category_id())) {
        my $sql = "insert into Categories (deal_id, category_id) ".
               "values (?, ?) on duplicate key update id=id";

	my $sth = $dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $deal->category_id());
	if (!$sth->execute()) {
	    $$status_ref = 2;
	    $$status_message_ref = "Failed database query: ".$dbh->errstr;
	    return 0;
	}
    }
    
    return 1;
}


sub insertNational {
    my $dbh = shift;
    my $deal = shift;
    my $deal_id = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;

    my $sql = "insert into Cities (deal_id, city_id) ".
	"values (?, 2) on duplicate key update id=id";
    
    my $sth = $dbh->prepare($sql);
    $sth->bind_param(1, $deal_id);
    if (!$sth->execute()) {
	$$status_ref = 2;
	$$status_message_ref = "Failed database query: ".$dbh->errstr;
	return 0;
    }
    
    return 1;
}


# Insert the field_value into one of the tables which records history
# of data (NumPurchased, FBClicks, FBShares) in the deals table.
# We assume that the caller sends us a defined $field_value.
sub recordHistory {
    my $dbh = shift;
    my $deal_id = shift;
    my $table_name = shift;
    my $field_name = shift;
    my $field_value = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;

    # Don't record any fields that are undefined or zero.
    if (!defined($field_value) || $field_value == 0) { return 1; }


    my $curr_field_value =
	&dealsdbutils::getField($dbh, $deal_id, $field_name);

    # Only insert field_value if it's different to the current value
    # in the database. It's a waste of data to keep reinserting the same value
    # if it doesn't change
    if (!defined($curr_field_value) ||
	$curr_field_value != $field_value) {
	insertField($dbh, $deal_id, $table_name, $field_name, $field_value,
		    $status_ref, $status_message_ref) || return 0;
    }

    return 1;
}

sub insertField {
    my $dbh = shift;
    my $deal_id = shift;
    my $table_name = shift;
    my $field_name = shift;
    my $field_value = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;

    if (defined($field_value)) {
        my $sql = "insert into $table_name (deal_id, $field_name, time) ".
               "values (?, ?, CURRENT_TIMESTAMP) on duplicate key update id=id";

	my $sth = $dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $field_value);
	if (!$sth->execute()) {
	    $$status_ref = 2;
	    $$status_message_ref = "Failed database query: ".$dbh->errstr;
	    return 0;
	}
    }
    
    return 1;
}
