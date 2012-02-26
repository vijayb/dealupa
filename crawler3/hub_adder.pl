#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) September, 2011
#
package main;

use strict;
use warnings;
use workqueue;

use constant {
    WORK_TYPE => 1,
    HUB_CRAWL_WORK_TYPE => 2
};


workqueue::registerWorker(\&doWork, WORK_TYPE, 1, 0, 60) || 
    die "Unable to register worker\n";
workqueue::run();


sub doWork {
    my $work_ref = shift; # We don't need to use this for hub_adder.pl
    my $workqueue_dbh = shift;
    my $output_dbh = shift; # We also don't need to use this for hub_adder.pl
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $sql = "select Hubs.url, Hubs.company_id,  ".
	"Companies.hub_crawl_frequency, ".
	"Companies.output_server, Companies.output_database ".
	"from Hubs,Companies WHERE ".
	"Hubs.company_id=Companies.id order by rand()";
    my $sth = $workqueue_dbh->prepare($sql);
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref =
	    "Failed database query: ".$workqueue_dbh->errstr;
        return;        
    }
    
    my $inserted_count = 0;
    while (my @hub_info = $sth->fetchrow_array()) {
        if (defined($hub_info[0]) && defined($hub_info[1]) &&
            defined($hub_info[2])) {
	    workqueue::addWork($hub_info[0], HUB_CRAWL_WORK_TYPE,
			       $hub_info[1], $hub_info[2],
			       $hub_info[3], $hub_info[4], 1) || next;
            $inserted_count++;
        }
    }

    $$status_ref = 0;
    $$status_message_ref =
        "Inserted $inserted_count hubs into WorkQueue.";
    $sth->finish();    
}


























