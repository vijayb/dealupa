#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) December, 2011
#
package main;

use strict;
use warnings;
use workqueue;

use constant {
    WORK_TYPE => 202,
    MAX_RESTARTED_JOBS_QUEUE_SIZE => 30
};

# Queue of the jobs that were restarted. Each element is a string
# containing the job name and a datestamp of when the job was
# restarted.
my @restarted_jobs;

my %jobs;
# Crawler 1:
$jobs{"108.166.127.22"} = ["hub_adder", "xml_crawler", "yelp_reviewer",
			   "restaurant_yelp_info", "cache_reloader",
			   "solr_reloader", "email_sender", "work_reaper"];

# Crawler 2:
$jobs{"184.106.175.144"} = ["hub_crawler", "deal_crawler", "geo_coder", "image_crawler",
			    "restaurant_crawler"];

# Crawler 3:
$jobs{"184.106.174.162"} = ["hub_crawler", "deal_crawler", "geo_coder", "image_crawler"];

# Crawler 4:
$jobs{"50.57.36.164"} = ["hub_crawler", "deal_crawler", "geo_coder", "image_crawler"];

# All other machines
$jobs{"any_ip"} = ["hub_adder"];# "crawler", "deal_crawler", "geo_coder"];


my $ip = workqueue::ip();

my @jobs;
if (defined($jobs{$ip})) {
    @jobs = @{$jobs{$ip}};
} else {
    @jobs = @{$jobs{"any_ip"}};
}


workqueue::registerWorker(\&doWork, WORK_TYPE, 1, 0, 30) || 
    die "Unable to register worker\n";
workqueue::run();


sub doWork {
    my $work_ref = shift; # We don't need to use this for worker_restarter.pl
    my $workqueue_dbh = shift;
    my $output_dbh = shift; # We also don't need to use this
    my $status_ref = shift;
    my $status_message_ref = shift;

    if (${$work_ref}{"work"} ne $ip) {
	$$status_ref = 2;
	$$status_message_ref =
	    "No allowed to do this job [".${$work_ref}{"work"}."], ".
	    "IP doesn't match [".$ip."]";
	return;
    }
    
    foreach my $job (@jobs) {
	my $running_jobs = `ps auxwww | grep perl`;
	my @running_jobs = split(/\n/, $running_jobs);
	my $job_is_running = 0;
	foreach my $running_job (@running_jobs) {
	    if ($running_job =~ /$job/) {
		$job_is_running = 1;
	    }
	}

	
	if (!$job_is_running) {
	    my $server = workqueue::server();
	    my $database = workqueue::database();
	    my $user = workqueue::user();
	    my $password = workqueue::password();
	    my $aws_access_key = workqueue::aws_access_key();
	    my $aws_secret_key = workqueue::aws_secret_key();

	    my $last_error_cmd =
		"tail -n 100 logs/$job.log > logs/$job.last_error";
	    system($last_error_cmd);
	    sleep(1);

	    my $restart_cmd = 
		"nohup ./$job.pl --server=$server --database=$database ".
		"--user=$user --password=$password ".
		"--aws_access_key=$aws_access_key ".
		"--aws_secret_key=$aws_secret_key < /dev/null ".
		">> logs/$job.log 2>&1 &";
	    
	    print "[$job] isn't running, restarting\n[$restart_cmd]\n";
	    system($restart_cmd);

	    my ($year, $month, $day, $hour, $minute, $second,);
	    ($year, $month, $day, $hour, $minute, $second) =
                    (gmtime(time()))[5,4,3,2,1,0];
		
	    my $timestring = sprintf("%d-%02d-%02d %02d.%02d.01",
				     1900+$year, $month+1, $day,
				     $hour, $minute, $second);
	    unshift(@restarted_jobs, "$job:$timestring");
	    if ($#restarted_jobs >= MAX_RESTARTED_JOBS_QUEUE_SIZE) {
		pop @restarted_jobs;
	    }
	} else {
	    print "[$job] IS running. No need to restart\n";
	}
    }

    $" = ",";
    $$status_ref = 0;
    $$status_message_ref = sprintf("Restarted jobs [@restarted_jobs]");
}


























