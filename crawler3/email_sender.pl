#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2012
#
package main;

use strict;
use warnings;
use workqueue;
use downloader;

use constant {
    WORK_TYPE => 103,
};

# Since sending email can take a long time,
# turn off timeouts on the requests to build it.
downloader::setTimeout(0);

workqueue::registerWorker(\&doWork, WORK_TYPE, 1, 0, 300) || 
    die "Unable to register worker\n";
workqueue::run();


sub doWork {
    my $work_ref = shift;
    my $workqueue_dbh = shift; # We don't need to use this for email_sender
    my $output_dbh = shift; # We don't need to use this for email_sender
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $send_emails_url = ${$work_ref}{"work"};

    my $start_time = time();
    my $response = downloader::getURL($send_emails_url);
    my $time_taken = time() - $start_time;

    if ($response->is_success && defined($response->content())) {
	$$status_ref = 0;
	$$status_message_ref = $response->content()." Time taken: $time_taken seconds";
    } else {
	$$status_ref = 2;
	$$status_message_ref =
	    "Failed sending emails, time taken: $time_taken seconds";
    }
}


























