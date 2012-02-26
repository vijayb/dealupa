#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) September, 2011
#

package main;

use strict;
use warnings;

use workqueue;

use downloader;
use hub;
use dealurlextractor;
use dealsdbutils;

use constant {
    WORK_TYPE => 2,
    PAGE_CRAWL_WORK_TYPE => 3
};


workqueue::registerWorker(\&doWork, WORK_TYPE, 10, 3, 10) ||
    die "Unable to register worker\n";
workqueue::run();


sub doWork {
    my $work_ref = shift;
    my $workqueue_dbh = shift;
    my $output_dbh = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $sql = "select Hubs.url, Hubs.company_id, Hubs.category_id, ".
	"Companies.use_cookie, Hubs.post_form, ".
	"Companies.page_crawl_frequency, ".
	"Companies.output_server, ".
	"Companies.output_database, Companies.use_password from ".
	"Hubs, Companies where ".
	"Hubs.company_id=Companies.id and strcmp(Hubs.url,?)=0";
    #print "[$sql][".${$work_ref}{"work"}."]\n";
    my $sth = $workqueue_dbh->prepare($sql);
    $sth->bind_param(1, ${$work_ref}{"work"});
    
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$workqueue_dbh->errstr;
        return;        
    }

    if (my @hub_info = $sth->fetchrow_array()) {
        my $hub = hub->new();
        $hub->url($hub_info[0]);
        $hub->company_id($hub_info[1]);
        $hub->category_id($hub_info[2]);
        $hub->use_cookie($hub_info[3]);
        $hub->use_password($hub_info[8]);
        if (defined($hub_info[4])) {
            $hub->post_form($hub_info[4]);
        }
        
        my @hub_cities;
	getHubCities($workqueue_dbh, \@hub_cities, $hub->url(),
		     $status_ref, $status_message_ref) || return;
	
	# Crawl the hub:
        my $response;
        if ($hub->has_post_form()) {
            $response =
		downloader::getURLWithPost($hub->url(),
					   $hub->post_form());
        } elsif ($hub->use_cookie()) {
            $response = downloader::getURLWithCookie($hub->url());            
        } elsif ($hub->use_password()) {
	    $response = downloader::getURLWithPassword($hub->url(), $hub->company_id());
	} else {
            $response = downloader::getURL($hub->url());
        }

        if ($response->is_success && defined($response->content()) &&
            length($response->content()) > 0) {
	    # Store in the hub the URL of the final redirect (if
	    # the hub was indeed redirected). This is useful for sites
	    # like Google offers where the hub immediately redirects
	    # you to the URL of a live deal.
	    if (defined($response->base()) &&
		!($response->base() eq $hub->url())) {
		$hub->redirect_url($response->base());
	    }

            my $hub_content = $response->content();
            print "Succeeded in downloading ".$hub->url().
		" content length: ".length($hub_content)."\n";
	    
	    # Extract deal links from the hub:
            my %deal_urls;
            # pass hub_content by reference to save time.
            # don't want to copy webpages around in memory
            my $num_deals_extracted =
                &dealurlextractor::extractDealURLs(\%deal_urls,
						   \$hub_content,
						   $hub);
            print "Extracted $num_deals_extracted deals!\n";
	    my $num_deals_inserted = 0;
	    my $num_new_deals = 0;
            
            foreach my $deal_url (keys %deal_urls) {
		my %deal_work_info;
		workqueue::addWork($deal_url, PAGE_CRAWL_WORK_TYPE,
				   $hub_info[1], $hub_info[5],
				   $hub_info[6], $hub_info[7],
				   0, \%deal_work_info) || next;
		$num_deals_inserted++;
		
                # This part is important: we collect the work
		# information for the work we just inserted (in
		# %deal_work_info - see above).  The reason is that if
		# the work was previously inserted with different
		# output_server/database values, we want to respect
		# those (this is also why we called addWork with 0
		# (don't overwrite row if it exists).  These values
		# may change with resharding (assigning a company to a
		# different output_server/database), but for work
		# already created it should continue to be output to
		# the original database shard it was assigned to.
		my $deal_output_server = $deal_work_info{"output_server"};
                my $deal_output_database = $deal_work_info{"output_database"};
		my $deal_output_dbh =
                    DBI->connect_cached("DBI:mysql:".$deal_output_database.
					";host=".$deal_output_server.";mysql_ssl=1",
					workqueue::user(),
					workqueue::password(),
					{RaiseError => 1, AutoCommit => 1});
                if (!$deal_output_dbh) {
		    print "ERROR ***: Unable to connect to deals database.\n";
		    next;
                }

		my $deal_id =
		    &dealsdbutils::getDealId($deal_output_dbh, $deal_url);

		if ($deal_id == 0) {
		    # if we're here then the deal doesn't exist in
		    # deals database, so put it in (an empty deal)
		    $deal_id =
			&dealsdbutils::createDealId($deal_output_dbh,
						    $deal_url,
						    $hub->company_id());

		    insertCategory($deal_output_dbh, $deal_id, $hub->category_id());
		    $num_new_deals++;
		}

		# Shouldn't happen, but just in case:
		if ($deal_id == 0) { next; }

		my $dup_id = &dealsdbutils::findDupId($deal_id, $deal_output_server);

		if ($dup_id != $deal_id) {
		    #print "Got ourselves a dup! [$deal_id:$dup_id]@hub_cities\n";
		}

		# Unlike categories, we want to insert the Cities for the
		# deal, even if the deal already existed, because a deal
		# may have multiple cities associated with it. E.g.,
		# both Seattle and Tacoma.
		insertHubCities($deal_output_dbh, $dup_id, \@hub_cities);
            }
            $$status_ref = 0;
            $$status_message_ref =
		"Successfully extracted $num_deals_extracted deal urls. ".
		"Of these $num_deals_inserted were successfully added to workqueue. ".
		"$num_new_deals were new, the rest already existed in the deals db.";
        } else {
            $$status_ref = 2;
	    $$status_message_ref = "Error while crawling. Code: ".
		$response->code."] Message [".$response->message."]";
            print "Failed downloading ".$hub->url()." Code: [".$response->code.
		"] Message [".$response->message."]\n";
        }
    }
    
    $sth->finish();
}



sub insertHubCities {
    my $deal_output_dbh = shift;
    my $deal_id = shift;
    my $hub_cities_ref = shift;

    # Assign cities to this deal:
    foreach my $city_id (@{$hub_cities_ref}) {
	my $sql = "insert into Cities777 (deal_id, city_id) ".
	    "values (?, ?) on duplicate key update id=id";

	my $sth = $deal_output_dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);	
	$sth->bind_param(2, $city_id);
	$sth->execute();
    }
}

sub insertCategory {
    my $deal_output_dbh = shift;
    my $deal_id = shift;
    my $category_id = shift;

    if (&dealsdbutils::inTable($deal_output_dbh, $deal_id, "Categories777")) {
	return;
    }    
    
    # Assign the category to the deal (so long as it isn't 0)
    if ($category_id != 0) {
	my $sql = "insert into Categories777 (deal_id, category_id) ".
	    "values (?, ?) on duplicate key update id=id";
	my $sth = $deal_output_dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->bind_param(2, $category_id);
	$sth->execute();
    }
}


sub getHubCities {    
    my $dbh = shift;    
    my $hub_cities_array_ref = shift;
    my $url = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    my $sql = "select city_id from HubCities where hub_url=?";
    my $sth = $dbh->prepare($sql);
    $sth->bind_param(1, $url);
    
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$dbh->errstr;
        return 0;
    }
    
    while (my $city_id = $sth->fetchrow_array()) {
        push(@{$hub_cities_array_ref}, $city_id);
    }
    
    $sth->finish();
    return 1;
}

























