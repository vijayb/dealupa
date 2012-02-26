#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#
package main;

use strict;
use warnings;
use workqueue;

use constant {
    WORK_TYPE => 201,
    # We have a lookup table by work type for how old a piece of work
    # needs to be before being reaped. If a piece of work has a type
    # not in the lookup table the reaping age is set to the default below
    # which is one day (24*60*60)
    DEFAULT_REAPING_AGE => 86400,
    # No work less than this age will be reaped:
    MIN_REAPING_AGE => 3600
};

# Map of work_type => age before work of that type should be reaped.
my %reap_age_map;
$reap_age_map{"2"} = 7200;
$reap_age_map{"3"} = 7200;
$reap_age_map{"4"} = 7200;
$reap_age_map{"5"} = 7200;
$reap_age_map{"6"} = 7200;
$reap_age_map{"7"} = 7200;
$reap_age_map{"8"} = 7200;



workqueue::registerWorker(\&doWork, WORK_TYPE, 1, 0, 300) || 
    die "Unable to register worker\n";
workqueue::run();


sub doWork {
    my $work_ref = shift; # We don't need to use this for work_reaper.pl
    my $workqueue_dbh = shift;
    my $output_dbh = shift; # We also don't need to use this for work_reaper.pl
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $sql = "select id, work, type, ".
	"TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), started)) from WorkQueue ".
	"WHERE started is not null and completed is null and ".
	"TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), started)) > ".MIN_REAPING_AGE;

    my $sth = $workqueue_dbh->prepare($sql);
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref =
	    "Failed database query: ".$workqueue_dbh->errstr;
        return;        
    }
    
    my %num_reaped;
    my $num_reaped = 0;
    my $total_reapable = 0;
    while (my @work_info = $sth->fetchrow_array()) {
	my $work_id = $work_info[0];
	my $work = $work_info[1];
	my $type = $work_info[2];
	my $work_age = $work_info[3];

	my $reap_age = DEFAULT_REAPING_AGE;
	if (defined($reap_age_map{$type})) {
	    $reap_age = $reap_age_map{$type};
	}
	if ($work_age > $reap_age) {
	    $total_reapable++;
	    my $reap_sql =
		"UPDATE WorkQueue set started=null where id=$work_id";
	    my $sth = $workqueue_dbh->prepare($reap_sql);
	    if (!$sth->execute()) {
		next;
	    }

	    if (!defined($num_reaped{$type})) {
		$num_reaped{$type} = 1;
	    } else {
		$num_reaped{$type}++;
	    }
	    $num_reaped++;
	}
    }



    $$status_ref = 0;
    $$status_message_ref =
	"Jobs reaped: $num_reaped of $total_reapable total reapable. ";
    foreach my $reaped_type (keys %num_reaped) {
	$$status_message_ref = $$status_message_ref.
	    "[$num_reaped{$reaped_type} jobs of type $reaped_type]";
    }
    $sth->finish();    
}


























