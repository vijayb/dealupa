#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#

package main;

use strict;
use warnings;
use workqueue;

use downloader;
use deal;
use dealsdbutils;
use xmlextractor;

# Used for doing extra extraction for feeds which don't
# provide all the information needed
use dealextractor;


use constant {
    WORK_TYPE => 6,
    GEOCODER_WORK_TYPE => 4,
    YELP_REVIEWER_WORK_TYPE => 5,
    # We don't set the yelp frequency to 0, which we do for geocoding. The
    # reason is that we may have to make multiple attempts at finding
    # the yelp review based on whether the geocoder has finished running
    # for a given deal.
    YELP_WORK_FREQUENCY => 300,
    MAX_CRAWLABLE_AGE => 864000 # 10 days in seconds
};


workqueue::registerWorker(\&doWork, WORK_TYPE, 10, 5, 60) ||
    die "Unable to register worker\n";
workqueue::run();


sub doWork {
    my $work_ref = shift;
    my $workqueue_dbh = shift;
    my $output_dbh = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $xml_url = ${$work_ref}{"work"};    
    
    my $sql = "select Companies.use_cookie from WorkQueue, Companies where ".
	"WorkQueue.company_id=Companies.id and WorkQueue.work=?";

    my $sth = $workqueue_dbh->prepare($sql);
    $sth->bind_param(1, $xml_url);
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$workqueue_dbh->errstr;
        return;        
    }
    
    if (my @result = $sth->fetchrow_array()) {
        my $use_cookie = $result[0];
        my $company_id = ${$work_ref}{"company_id"};
	
        my $response;
        if ($use_cookie) {
            $response = downloader::getURLWithCookie($xml_url);            
        } else {
            $response = downloader::getURL($xml_url);
        }
	
        if ($response->is_success && defined($response->content()) &&
            length($response->content()) > 0)
	{
            my $xml_content = $response->content();
            print "Succeeded in downloading ".$xml_url." content length: ".
		length($xml_content).". Now parsing...\n";
            
	    # Obtain deals from XML:
            my @deals = xmlextractor::extractDeals(\$xml_content, $company_id);

	    print "".($#deals + 1)." deals extracted. Inserting...\n";
	    my $num_inserted = 0;
	    foreach my $deal (@deals) {
		print "\tInserting deal: [".$deal->url(),"]\n";

		# Insert deal into the output database it was assigned to:
		$deal->company_id($company_id);
		if (&dealextractor::hasExtractorForCompanyID($company_id)) {
		    doExtraExtraction($deal);
		}

		insertDeal777($output_dbh, $deal) || next;
		
		# If deal has any addresses, we need to add geocoding
		# work to the WorkQueue
		if (scalar(keys(%{$deal->addresses()})) > 0)
		{
		    unless (
			workqueue::addWork($deal->url(), GEOCODER_WORK_TYPE,
					   $company_id, 0, 
					   # Geocoded info should be
					   # put in same database as
					   # deal being geocoded:
					   ${$work_ref}{"output_server"},
					   ${$work_ref}{"output_database"},
					   0))
		    {
			next;
		    }
		}
		
		# If the deal has a business name and either a phone number or
		# some addresses, then we need to add yelp reviewing to the
		# workqueue
		if (defined($deal->name()) &&
		    (defined($deal->phone()) ||
		     scalar(keys(%{$deal->addresses()})) > 0) )
		{
		    unless (
			workqueue::addWork($deal->url(),
					   YELP_REVIEWER_WORK_TYPE,
					   $company_id,
					   YELP_WORK_FREQUENCY, 
					   # Yelp info should be put
					   # in same database as deal
					   # we just worked on:
					   ${$work_ref}{"output_server"},
					   ${$work_ref}{"output_database"},
					   0))
		    {
			next
		    }		
		}
		
		$num_inserted++;
	    }


	    $$status_ref = 0;
	    $$status_message_ref =
		"Successfully parsed and inserted ".$num_inserted.
		" deals out of a total of ".($#deals +1)." deals";
        } else {
            $$status_ref = 2;
            $$status_message_ref = "Error while crawling: [".$xml_url."]";
            print "Error while crawling ".$xml_url."\n";
        }
    }
    
    $sth->finish();
}


sub doExtraExtraction {
    my $deal = shift;

    my $response = downloader::getURL($deal->url());
    
    if ($response->is_success && defined($response->content()) &&
	length($response->content()) > 0)
    {
	my $deal_content = $response->content();
	print "Succeeded in downloading ".$deal->url()." content length: ".
	    length($deal_content)." extracting...\n";
	
	dealextractor::extractDeal($deal, \$deal_content);
    }
    
    # If we're crawling deals from the feed, we had better put a sleep in
    # to be polite
    sleep(2);
}



sub insertDeal777 {
    my $dbh = shift;
    my $deal = shift;
    my $company_id = shift;

    my $deal_id = &dealsdbutils::getDealId($dbh, $deal->url());
    if ($deal_id == 0) {
	$deal_id = &dealsdbutils::createDealId($dbh, $deal->url(),
					       $deal->company_id());
    }

    # This should never happen, but just in case:
    if ($deal_id == 0) { return 0; }


    if (!&dealsdbutils::inTable($dbh, $deal_id, "Addresses777")) {
	insertAddresses777($dbh, $deal, $deal_id) || return 0;
    }

    if (!&dealsdbutils::inTable($dbh, $deal_id, "Images777")) {
	insertImages777($dbh, $deal, $deal_id) || return 0;
    }

    if (!&dealsdbutils::inTable($dbh, $deal_id, "Categories777")) {
	insertCategories777($dbh, $deal, $deal_id) || return 0;
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

	recordHistory($dbh, $deal_id, "NumPurchased777", "num_purchased",
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
    
    my $sql = "UPDATE Deals777 set $update_values where id=$deal_id";

    my $sth = $dbh->prepare($sql);
    for (my $i=0; $i <= $#update_params; $i++) {
        $sth->bind_param($i+1, $update_params[$i]);
    }


    if (!$sth->execute()) {
        return 0;
    }
    
    return 1;
}


sub insertAddresses777 {
    my $dbh = shift;
    my $deal = shift;
    my $deal_id = shift;

    my $addresses_ref = $deal->addresses();
    foreach my $address (keys %{$addresses_ref}) {
	my $sql = "insert into Addresses777 (deal_id, raw_address) ".
	    "values (?,?)";
	
	my $sth = $dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $address);
	if (!$sth->execute()) {
	    return 0;
	}
    }
    
    return 1;
}


# Insert (potentially) multiple images per deal
sub insertImages777 {
    my $dbh = shift;
    my $deal = shift;
    my $deal_id = shift;
    
    my $image_urls_ref = $deal->image_urls();
    foreach my $image_url (keys %{$image_urls_ref}) {
	my $sql = "insert into Images777 (deal_id, image_url) ".
	    "values (?,?) on duplicate key update id=id";
	
	my $sth = $dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $image_url);
	if (!$sth->execute()) {
	    return 0;
	}
    }
    
    return 1;
}


# For now we only have one category
# TODO: allow extraction of multiple categories
sub insertCategories777 {
    my $dbh = shift;
    my $deal = shift;
    my $deal_id = shift;

    if (defined($deal->category_id())) {
        # No categories previously inserted for this deal, so insert them:
        my $sql = "insert into Categories777 (deal_id, category_id) ".
               "values (?, ?) on duplicate key update id=id";

	my $sth = $dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $deal->category_id());
	if (!$sth->execute()) {
	    return 0;
	}
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
