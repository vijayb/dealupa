#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) March, 2012
#
# THIS FILE WAS CREATED TO BACKPOPULATE IMAGE_CRAWLING WORK
# SO THAT WE HAD IMAGES STORED ON AMAZON S3 FOR OLDER DEALS
#
# IT CAN PROBABLY SAFELY BE DELETED...
#

package main;

use strict;
use warnings;
use workqueue;
use dealsdbutils;

use constant {
    IMAGE_CRAWLER_WORK_TYPE => 9,
    WORK_TYPE => 12,
};


workqueue::registerWorker(\&doWork, WORK_TYPE, 5, 0, 60) ||
    die "Unable to register worker\n";
workqueue::run();





sub doWork {
    my $work_ref = shift;
    my $workqueue_dbh = shift;
    my $output_dbh = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $deal_url = ${$work_ref}{"work"}; 

    
    my $sql = "select deal_id from Images777  where on_s3=0 GROUP BY deal_id";
    my $sth = $output_dbh->prepare($sql);
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$output_dbh->errstr;
        return;        
    }
    
    while (my @result = $sth->fetchrow_array()) {
        my $deal_id = $result[0];
	

	my @array = getURL($output_dbh, $deal_id);

	if (@array) {
	    #print "$deal_id $array[1] $array[0]\n";

	    workqueue::addWork($array[0], IMAGE_CRAWLER_WORK_TYPE,
			       $array[1], 0, 
			       # Image crawler info should be put in same
			       # database as deal
			       ${$work_ref}{"output_server"},
			       ${$work_ref}{"output_database"},
			       0);
	} else {
	    print "********** FAIL on deal $deal_id\n";
	}
	#last;
    }


    
    $sth->finish();
}


sub getURL {
    my $dbh = shift;
    my $deal_id = shift;

    my $sql = "select url,company_id from Deals777 where id=$deal_id";
    my $sth = $dbh->prepare($sql);
    if (!$sth->execute()) {
	return;        
    }
    if (my @result = $sth->fetchrow_array()) {
	my $url = $result[0];
	my $company_id = $result[1];
	my @array;
	push(@array, $url);
	push(@array, $company_id);
	return @array;
    }
}
