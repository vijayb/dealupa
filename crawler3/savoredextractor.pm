#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2011
#
{
    package savoredextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;

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


	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:title")});
	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "book-info-head-dollars")});
	if (@price && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "tab-text")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $clean_text =~ s/<\/?span[^>]*>//g;
	    $deal->text($clean_text);
	}



	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "the-fine-print-content")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}

	my @image_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "carousel-images")});

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
		    $_[0]->attr('class') eq "book-info-book-it-btn-inactive"});

	if (@expired || $tree->as_text() =~ /sold\s*out/i) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "book-by"});
	    
	    if (@deadline && $deadline[0]->as_text() =~ /([0-9]+)/) {
		my $val = $1;
		my $hours = 0;
		my $days = 0;
		if ($deadline[0]->as_text() =~ /days\s*left/) {
		    $days = $val;
		} elsif ($deadline[0]->as_text() =~ /hours\s*left/) {
		    $hours = $val;
		}
		
		my $offset = ($days*3600*24) + ($hours*3600);

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Savored puts expiry information in fine print
	if (defined($deal->fine_print())) {
	    if ($deal->fine_print() =~ 
		     /([A-Z][a-z]+)\s+([0-9]{1,2}),?\s+([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;

		if (defined($month_map{$month})) {
		    my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
					  $month_map{$month}, $day);
		    $deal->expires($expires);
		}
	    } elsif ($deal->fine_print() =~ 
		     /([0-9]+)\/([0-9]+)\/([0-9]+)/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		if ($year < 100) {
		    $year = $year + 2000;
		}
		my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				      $month, $day);
		$deal->expires($expires);
	    }
	}
	
	
	
	# Name
	if ($tree->as_HTML() =~ /title:\s*[\'\"]([^\'\"]+)/) {
	    $deal->name($1);
	}


	# Address
	my @addresses = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /maps.google.com/});
	foreach my $address (@addresses) {
	    if ($address->attr('href') =~ /q=(.*)/) {
		my $clean_address = $1;
		$clean_address =~ s/%[0-9][A-Z]/ /g;
		$clean_address =~ s/\+/ /g;
		$deal->addresses($clean_address);
	    }
	}

	
	$tree->delete();
    }
  
    1;
}
