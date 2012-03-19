#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) March, 2012
#
# THIS FILE WAS CREATED TO DO fix r.com images. can be thrown away
#

package main;

use strict;
use warnings;
use workqueue;
use dealsdbutils;

use constant {
    WORK_TYPE => 11,
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

    my %deleted;

    open(FILE, "/home/vijay/restaurant.txt");
    my $count = 0;
    while (my $line = <FILE>) {
	chomp($line);
	if ($line =~ /(http:[^\s]+)\s*(.*)/) {
	    my $url = $1;
	    my $image_url = $2;

	    my $deal_id = &dealsdbutils::getDealId($output_dbh, $url);
	    print "[$deal_id][$url][$image_url]\n";
	    if ($deal_id > 0) {
		my $sql;
		if (!defined($deleted{$deal_id})) {
		    $sql = "delete from Images where deal_id=$deal_id";
		    print $sql, "\n";
		    doSQL($output_dbh, $sql) || die;

		    $deleted{$deal_id} = 1;
		}

		$sql = "insert into Images (deal_id, image_url) values ($deal_id, '$image_url') on duplicate key update id=id";
		print $sql, "\n";
		doSQL($output_dbh, $sql) || die;

		$sql = "update Deals set last_updated=UTC_TIMESTAMP() where id=$deal_id";
		print $sql, "\n";
		doSQL($output_dbh, $sql) || die;


		$sql = "update WorkQueue set started=null, completed=null, status=null, status_message=null where type=9 and strcmp(work,'$url')=0";
		print $sql, "\n";
		doSQL($workqueue_dbh, $sql) || die;
		#### make sure to pass connection to wq db!!!

		#$sql = "update Deals set last_updated=UTC_TIMESTAMP() ".
#		"where id=$deal_id";
#	    

		$count++;
	    }
	}
    }
    close(FILE);

    $$status_message_ref = "$count deals classified\n";
    $$status_ref = 0;
}


sub doSQL {
    my $output_dbh = shift;
    my $sql = shift;
    print "$sql\n";
    
    my $sth = $output_dbh->prepare($sql);
    if (!$sth->execute()) {
	return 0;
    }
    return 1;
}
