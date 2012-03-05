#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2012
#
{
    package onsaleextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;

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
	$tree->ignore_unknown(0);
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	$deal->affiliate_url("http://www.anrdoezrs.net/click-5498612-10922646?url=".
			     uri_escape($deal->url().
					"?utm_source=cj&utm_medium=affiliate".
					"&utm_campaign=cj&sourceid=cj&source=".
					"BWBCJPRODCATDLS"));

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq "h1"});
	if (@title) {
	    my $clean_title = $title[0]->as_text();
	    if ($clean_title =~ /\s*(Plus\s*free.*)$/i) {
		$deal->subtitle($1);
	    }
	    $clean_title =~ s/\s*Plus\s*free.*$//i;
	    $deal->title($clean_title);
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^singleprice/)});

	if (@price && $price[0]->as_text() =~ /([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^off-price/)});

	if (@value && $value[0]->as_text() =~ /([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}
	
	if ($tree->as_text() =~ /([0-9,]+)\s*bought/i) {
	    my $clean_num_purchased = $1;
	    $clean_num_purchased =~ s/,//g;
	    $deal->num_purchased($clean_num_purchased);
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "infoDealurl")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "spiffyfg")});

	if (@fine_print) {
	    my $clean_fine_print = $fine_print[0]->as_HTML();
	    $clean_fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($clean_fine_print);
	}

	my @image_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "mainImg")});

	if (@image_container) {
	    my @image = $image_container[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src'))});
	    if (@image) {
		my $clean_image = $image[0]->attr('src');
		$clean_image =~ s/^\/\//http:\/\//;
		$deal->image_urls($clean_image);
	    }
	}



	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "dealFinished")});

	if (@expired) {
	    $deal->expired(1);
	}

	if ($tree->as_text() =~ /missed\s*out/i) {
	    # Sometimes there are multiple tickets so "sold out"
	    # may not tell us if the deal expired. We have
	    # to be more sophisticated
	    $deal->expired(1);
	}

	
	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~
		/expires\s*([0-9]{1,2})[^0-9]([0-9]{1,2})[^0-9]([0-9]{2})\s*([0-9]{1,2})[^0-9]([0-9]{1,2})/i) {
		my $year = 2000+$3;
		my $month = $1;
		my $day = $2;
		my $hour = $4;
		my $minute = $5;

		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       $year, $month, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}

	if (defined($deal->fine_print()) && 
	    $deal->fine_print() =~ 
	    /Expires[^A-Za-z]*([A-Za-z]+)\s*([0-9]{1,2}),\s*([0-9]{4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",
				      $year, $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}


	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "tpiffyfg"});
	
	if (@biz_info) {
	    # Name:
	    my @name = $biz_info[0]->look_down(sub{$_[0]->tag() eq 'h2'});
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }


	    # Website
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->as_text() eq $_[0]->attr('href') &&
			$_[0]->attr('href') =~ /^http/});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	    

	    # Addresses
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') =~ /^mapMarker/});
	    
	    foreach my $address (@addresses) {
		if ($address->as_HTML() =~ /addrs=[\'\"]([^\'\"]+)/) {
		    $deal->addresses($1);
		}
	    }
	}


	$tree->delete();
    }
 
    1;
}
