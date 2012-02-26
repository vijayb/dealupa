#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2012
#
{
    package thrillistextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;

    # We use this for setFBInfo:
    use dealsdbutils;

    my %month_map = (
	"Jan" => 1,
	"January" => 1,
	"Feb" => 2,
	"February" => 2,
	"Mar" => 3,
	"March" => 3,
	"Apr" => 4,
	"April" => 4,
	"May" => 5,
	"Jun" => 6,
	"June" => 6,
	"Jul" => 7,
	"July" => 7,
	"Aug" => 8,
	"August" => 8,
	"Sep" => 9,
	"September" =>9,
	"Oct" => 10,
	"October" => 10,
	"Nov" => 11,
	"November" => 11,
	"Dec" => 12,
	"December" => 12
    );

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	# This is a bit of a hack that we only do for Thrillist:
	# We get the actual url from the deal's og:url field because
	# the dealurlextractor for Thrillist throws away the last part
	# of their URLs, since they're not needed, and stripping the end
	# means no dup detection needs to take place. For example:
	# http://rewards.thrillist.com/deal/6026/boudoir-photo-shoot/sf
	# and
	# http://rewards.thrillist.com/deal/6026/boudoir-photo-shoot/nyc
	# are the same deal. So that last part is stripped in deal url
	# extraction, and the deal that is crawled and eventually passed
	# to this extractor will only have:
	# http://rewards.thrillist.com/deal/6026/boudoir-photo-shoot/
	# as its URL. The problem with that is that the code which normally
	# obtains the FB shares/likes in deal_crawler.pl won't work, because
	# it requires the actual URLs that people share.
	my @actual_url = $tree->look_down(
	    sub{$_[0]->tag() eq "meta" && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('property') eq "og:url" &&
		    $_[0]->attr('content') =~ /^http/});

	if (@actual_url) {
	    &dealsdbutils::setFBInfo($deal, $actual_url[0]->attr('content'));
	}

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h2'});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}


	my @subtitle = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "subhead")});

	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->as_text());

	}


	my @amounts = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "amount")});

	if ($#amounts >=0 && $amounts[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq "span" && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "amount" &&
		    $_[0]->parent->as_text() =~ /value/i});

	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "description")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $clean_text =~ s/<\/?span[^>]*>//g;
	    $deal->text($clean_text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "fine-print")});

	if (@fine_print) {
	    my $clean_fine_print = $fine_print[0]->as_HTML();
	    $clean_fine_print =~ s/<\/?div[^>]*>//g;
	    $clean_fine_print =~ s/<\/?span[^>]*>//g;
	    $deal->fine_print($clean_fine_print);
	}


	my @image_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "nivoSlider")});

	if (@image_container) {
	    my @images = $image_container[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			($_[0]->attr('src') =~ /http/)});

	    foreach my $image (@images) {
		$deal->image_urls($image->attr('src'));
	    }
	} else {
	    my @image = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			$_[0]->attr('content') =~ /^http/ &&
			($_[0]->attr('property') eq "og:image")});
	    
	    if (@image) {
		$deal->image_urls($image[0]->attr('content'));
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "sold-out-overlay"});

	if (@expired) {
	    $deal->expired(1);
	}


	# Thrillist doesn't give us a deadline, so just make one up, 15 days from now
	if (!defined($deal->expired()) && !$deal->expired()) {
	    my $offset = 10*3600*24;
	    
	    my ($year, $month, $day, $hour, $minute);
	    ($year, $month, $day, $hour, $minute) =
		(gmtime(time()+$offset))[5,4,3,2,1];
	    
	    my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				   1900+$year, $month+1, $day,
				   $hour, $minute);
	    $deal->deadline($deadline);
	}


	# Entertainment.com has the expires information in the fineprint.
	if (defined($deal->fine_print())) {
	    if ($deal->fine_print() =~ 
		     /([A-Z][a-z]+)\s+([0-9]{1,2})[^0-9]*([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		
		if (defined($month_map{$month})) {
		    my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
					  $month_map{$month}, $day);
		    $deal->expires($expires);
		}
	    } elsif ($deal->fine_print() =~ 
		     /([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		
		my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				      $month, $day);
		$deal->expires($expires);
	    }
	}
	
	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "vendor"});

	if (@biz_info) {
	    # Name
	    my @name = $biz_info[0]->look_down(
		sub{$_[0]->tag() =~ /h[0-9]/i});
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }

	    # Website
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->as_text() =~ /website/i});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }

	    # Thrillist doesn't give us the phone :(
	    
	    # Address
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "mapDisplayAddress"});
	    foreach my $address (@addresses) {
		my $clean_address = $address->as_HTML();
		$clean_address =~ s/<[^>]*>/ /g;
		$clean_address =~ s/\s+/ /g;
		if (length($clean_address) > 5) {
		    $deal->addresses($clean_address);
		}
	    }
	}



	$tree->delete();
    }
  
    1;
}
