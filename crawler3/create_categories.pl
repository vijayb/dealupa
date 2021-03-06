#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) March, 2012
#
# THIS FILE WAS CREATED TO DO large-scale classification via
# regular expressions on the title/url
#
# Be very careful using it because it's easy to create regexs
# which have high recall but very low precision.
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

    open(FILE, "/home/vijay/classify.html");
    my $count = 0;
    while (my $line = <FILE>) {
	chomp($line);
	if ($line =~ /^([0-9]+)/) {
	    my $deal_id= $1;

	    if ($line =~ /\(33\):/i) {
		$count++;
		print $line,"\n";

		my $sql =
		    "insert into Categories (deal_id, category_id, rank) ".
		    "values ($deal_id, 22, 0) on duplicate key update id=id";
		doSQL($output_dbh, $sql) || last;

		#$sql =
		#    "insert into Categories (deal_id, category_id, rank) ".
		#    "values ($deal_id, 21, 0) on duplicate key update id=id";
		#doSQL($output_dbh, $sql) || last;



		#my $sql =
		#    "delete from Categories where deal_id=$deal_id and category_id=31";
		#doSQL($output_dbh, $sql) || last;



		$sql = "update Deals set last_updated=UTC_TIMESTAMP() ".
		    "where id=$deal_id";
		doSQL($output_dbh, $sql) || last;

		#last;
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
