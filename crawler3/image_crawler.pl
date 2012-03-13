#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) March, 2012
#

package main;

use strict;
use warnings;
use workqueue;
use dealsdbutils;
use Net::Amazon::S3;
use Digest::SHA1 qw(sha1 sha1_hex);
use LWP::Simple;
use File::Copy;
 
use constant {
    WORK_TYPE => 9,
    IMAGE_CACHE_DIRECTORY => "./image_cache/",
    AWS_ACCESS_KEY_ID => 'AKIAJXSDQXVDAE2Q2GFQ',
    AWS_SECRET_ACCESS_KEY => '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',
    S3_BUCKET => "dealupa_images",
    SMALL_IMAGE_WIDTH => 310,
    SMALL_IMAGE_QUALITY => 75,
    LARGE_IMAGE_PIXEL_AREA => 480000, # 800 x 600 pixels
    LARGE_IMAGE_QUALITY => 60,
    RETIREMENT_AGE => 18000
};

my $start_time = time();
createCacheDirectory();

my $s3 = Net::Amazon::S3->new(
    {   aws_access_key_id     => AWS_ACCESS_KEY_ID,
	aws_secret_access_key => AWS_SECRET_ACCESS_KEY,
	retry                 => 1,
    }
    );

my $s3_bucket = $s3->bucket(S3_BUCKET);

workqueue::registerWorker(\&doWork, WORK_TYPE, 10, 1, 30) ||
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
    my $deal_id = &dealsdbutils::getDealId($output_dbh, $deal_url);

    # This should never happen, but just in case:
    if ($deal_id == 0) {
        $$status_ref = 2;
        $$status_message_ref = "Couldn't find deal ID for url: ".$deal_url;
        return;
    }
    
    my $sql = "select id, image_url from Images777 where deal_id=?";
    my $sth = $output_dbh->prepare($sql);
    $sth->bind_param(1, $deal_id);
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$output_dbh->errstr;
        return;        
    }
    
    print "Crawling images for deal with id [$deal_id] ...\n";
    my $num_processed = 0;
    my $total_attempted = 0;
    $$status_message_ref = "";
    while (my @result = $sth->fetchrow_array()) {
        $total_attempted++;
        my $image_id = $result[0];
        my $image_url = $result[1];

	my $image_file_name = IMAGE_CACHE_DIRECTORY . workqueue::pid();
	my $small_image_file_name = $image_file_name . "_small";
	getstore($image_url, $image_file_name);
	copy($image_file_name, $small_image_file_name);

	my $properties = `identify $image_file_name`;
	if ($properties =~ /^$image_file_name\s*([A-Za-z]+)\s*([0-9]+)x([0-9]+)/ &&
	    ($1 eq "PNG" || $1 eq "JPEG" || $1 eq "GIF" || $1 eq "TIFF" || $1 eq "BMP")) {
	    my $type = lc($1);
	    my $width = $2;
	    my $height = $3;
	    print "$type|$width|$height|\n";

	    my $small_geometry = "";
	    if ($width > SMALL_IMAGE_WIDTH) {
		$small_geometry = "-geometry ".SMALL_IMAGE_WIDTH;
	    }
	    my $mogrify_small =
		"mogrify -strip -compress JPEG -quality ".SMALL_IMAGE_QUALITY." ".
		$small_geometry." ". $small_image_file_name;
	    print "[$mogrify_small]\n";
	    system($mogrify_small);
	    

	    my $large_geometry = "";
	    if ($width * $height > LARGE_IMAGE_PIXEL_AREA) {
		$large_geometry = "-geometry @".LARGE_IMAGE_PIXEL_AREA;
	    }

	    my $mogrify_large =
		"mogrify -strip -compress JPEG -quality ".LARGE_IMAGE_QUALITY." ".
		$large_geometry." ".$image_file_name;
	    print "[$mogrify_large]\n";
	    system($mogrify_large);


	    my $s3_large_key = sha1_hex($image_url);
	    my $s3_small_key = sha1_hex($image_url) . "_small";

	    print "Writing $image_url to s3...\n";
	    my $error = 0;
	    $s3_bucket->add_key_filename($s3_large_key, $image_file_name,
					 { content_type => 'image/$type', },) 
		or $error = 1;

	    $s3_bucket->add_key_filename($s3_small_key, $small_image_file_name,
					 { content_type => 'image/$type', },) 
		or $error = 1;

	    markHasS3($output_dbh, $image_id, $status_ref, $status_message_ref) || last;

	    unlink($image_file_name);
	    unlink($small_image_file_name);

	    if ($error) {
		$$status_ref = 2;
		$$status_message_ref = 
		    "Failed processing image (id: $image_id): $image_url";
		last;
	    }
	} else {
	    unlink($image_file_name);
	    unlink($small_image_file_name);

	    $$status_ref = 2;
	    $$status_message_ref = "Failed processing image (id: $image_id): $image_url";
	    last;
	}

	$num_processed++;
    }

    if ($$status_ref != 2) {
	updateDeal($output_dbh, $deal_id);
    }

    if ($$status_ref == 3) { # No status_ref set
	if ($num_processed==$total_attempted && $total_attempted > 0) {
	    $$status_ref = 0;
	} else {
	    $$status_ref = 1;
	}
    }

    $$status_message_ref = $$status_message_ref . 
	"Crawled and successfully processed $num_processed out of ".
	"$total_attempted attempted. ".$$status_message_ref;
    
    $sth->finish();
}



sub markHasS3 {
    my $dbh = shift;
    my $image_id = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;

    my $sql = "update Images777 set on_s3=true where id=?";
    
    my $sth = $dbh->prepare($sql);
    $sth->bind_param(1, $image_id);
    if (!$sth->execute()) {
	$$status_ref = 2;
	$$status_message_ref = "Failed database query: ".$dbh->errstr;
	return 0;
    }
    
    return 1;
}


sub updateDeal {
    my $dbh = shift;
    my $deal_id = shift;

    my $sql = "update Deals777 set last_updated=UTC_TIMESTAMP() where id=?";
    
    my $sth = $dbh->prepare($sql);
    $sth->bind_param(1, $deal_id);
    $sth->execute();
}




sub createCacheDirectory {
    unless (-d IMAGE_CACHE_DIRECTORY) {
	mkdir(IMAGE_CACHE_DIRECTORY, 0777) || die "Couldn't create" . 
	    IMAGE_CACHE_DIRECTORY . "\n";
    }
}
