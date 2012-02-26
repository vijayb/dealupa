#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2012
#
{
    package msnoffersextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use Time::Local;

    my %month_map = (
	"January" => 1,
	"February" => 2,
	"March" => 3,
	"April" => 4,
	"May" => 5,
	"June" => 6,
	"July" => 7,
	"August" => 8,
	"September" => 9,
	"October" => 10,
	"November" => 11,
	"December" => 12
    );

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h1' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "offer-view-headline")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}

	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'h2' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "offer-view-tagline")});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->as_text());
	}

	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "unit-cost")});
	if (@price && defined($price[0]->attr('data-price'))) {
	    $deal->price($price[0]->attr('data-price'));
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'dd'});

	if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

	# MSN has no num_purchased
	#my @num_purchased = $tree->look_down(
	#    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
	#	    ($_[0]->attr('id') =~ "offer-view-visualization")});
	#if (@num_purchased &&
	#    $num_purchased[0]->as_text() =~ /([0-9,]+)\s+[Bb]ought/) {
	#    my $num_purchased = $1;
	#    $num_purchased =~ s/,//g;
	#    $deal->num_purchased($num_purchased);
	#}


	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /offer-view-description/)});
	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /offer-view-fine-print/)});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $fine_print =~ s/<\/?h3[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /offer-view-image/)});
	if (@image) {
	    $deal->image_urls($image[0]->attr('src'));
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /offer-view-expired-button/)});
	if (@expired) {
	    $deal->expired(1);
	}


	# Yollar has the same page format as tippr, so same deadline format
	# Raises the question. Should we just use tippr extractor for yollar?
	if (!defined($deal->expired()) && !$deal->expired() &&
	    $tree->as_HTML() =~ /until:\s+new\s+Date\(\s*[\'\"]([^\'\"\)]+)/) {
	    my $deadline = $1;
	    if (defined($deadline) &&
		$deadline =~ /([A-Z][a-z]+)\s+([0-9]{1,2}),\s+([0-9]{4})\s+([0-9]{1,2}):([0-9]{1,2})\s+([A-Z]{2})\s+[-+]?([0-9]{2})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		my $hour = $4;
		my $minute = $5;
		my $ampm = $6;
		my $offset = 0 + $7; # offset from UTC time

		if ($ampm eq "PM") { $hour +=12; }

		if (defined($month_map{$month})) {
		    my $time = timelocal(1,$minute,$hour,$day,
					 # Our month map is 1..12 whereas
					 # timelocal uses 0..11 for months
					 $month_map{$month}-1,$year);
		    # Tippr annoyingly gives local time, so we need
		    # to convert it to UTC:
		    ($year, $month, $day, $hour, $minute) =
		    (localtime($time + ($offset*60*60)))[5,4,3,2,1];

		    $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					1900+$year, $month+1, $day,
					$hour, $minute);
		    $deal->deadline($deadline);
		}
	    }
	}

	if (defined($deal->fine_print()) && 
	    $deal->fine_print() =~ /[Ee]xpires([^<]+)/) {
	    my $expires = $1;
	    if ($expires =~ /([A-Z][a-z]+)\s*([0-9]{1,2}),\s*([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		if (defined($month_map{$month})) {
		    $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				       $month_map{$month}, $day);
		    $deal->expires($expires);
		}
	    }
	}
	

	# Multiple strategies for obtaining business name
	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'p' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "map-marker-name")});
	if (@name) {
	    $deal->name($name[0]->as_text());
	}

	@name = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /advertiser-description-link/)});
	if (@name) {
	    my $name = $name[0]->as_text();
	    $name =~ s/^[Aa]bout\s+//;
	    $deal->name($name);
	}
	


	my @info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /offer-view-learn-more/)});

	if (@info) {
	    my @website = $info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }

	    if ($info[0]->as_HTML =~ /[Pp]hone:\s+([^<]+)/) {
		$deal->phone($1);
	    }
	}


	my @address_group = $tree->look_down(
	    sub{$_[0]->tag() eq 'ol' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /offer-view-map-markers/)});
	if (@address_group) {
	    my @addresses = $address_group[0]->look_down(
		sub{$_[0]->tag() eq 'p' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "map-marker-address"});

	    foreach my $address (@addresses) {
		my $address = $address->as_HTML();

		# Remove superfluous tags and text for deals
		# which can only be redeemed online
		$address =~ s/<[^>]+>/ /g;
		$address =~ s/^\s+//g;
		$address =~ s/\s+$//g;

		$deal->addresses($address);
	    }
	}


	$tree->delete();
    }
  

    1;
}
