#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package kgbextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;


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

	$deal->affiliate_url("http://www.jdoqocy.com/click-5498612-10890422?url=".
			     $deal->url());
	
	my @title = $tree->look_down(sub{$_[0]->tag() eq 'title'});
	if (@title) {
	    my $cleantitle = $title[0]->as_text();
	    $cleantitle =~ s/\s*-\s*kgbdeals$//;
	    $deal->title($cleantitle);
	}

	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "dealDetailTopTitle")});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->as_text());
	}

	my @topLeftdiv = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "topLeft")});

	if (@topLeftdiv) {
	    my @pricediv = $topLeftdiv[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "pbdivprc")});
	    
	    if (@pricediv && $pricediv[0]->as_text() =~ /([0-9,]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		# Sometimes KGB deals has prices like $7.50. The way they
		# format those prices they get extracted as $750. The hack
		# below fixes that:
		if ($pricediv[0]->as_HTML() =~ />50</) {
		    $price = $price / 100.0;
		}
		
		$deal->price($price);
	    }
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'li' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "value")});
	if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "topLeft")});
	if (@num_purchased &&
	    $num_purchased[0]->as_text() =~ /([0-9,]+)\s*sold/i) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}
	



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "dealDetailRightBlock")});
	my $text = "";
	foreach my $tag (@text) {
	    if ($tag->as_HTML() =~ /<h3>need\s+to\s+know<\/h3>/i) {
		$text = $text.$tag->as_HTML();
	    }
	    if ($tag->as_HTML() =~ /<h3>what[^<]+<\/h3>/i) {
		$text = $text.$tag->as_HTML();
	    }
	    if ($tag->as_HTML() =~ /<h3>by\s+the\s+way<\/h3>/i) {
		my $fine_print = $tag->as_HTML();
		$fine_print =~ s/<\/?div[^>]*>//g;
		$deal->fine_print($fine_print);
	    }
	}
	$text =~ s/<\/?div[^>]*>//g;
	if (length($text) > 0) {
	    $deal->text($text);
	}


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('style')) &&
		    defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /dealImage/)});
	
	if (@image && $image[0]->attr('style') =~ /url\(\'(http[^\'\)]+)/) {
	    $deal->image_urls($1);
	}

	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "mainDealExpired")});
	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "deal_end_timestamp")});

	    if (@deadline && $deadline[0]->as_text() =~ /^[0-9]+$/) {
		my ($year, $month, $day, $hour, $minute);

		($year, $month, $day, $hour, $minute) =
		    (gmtime($deadline[0]->as_text()))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# KGB Deals puts the expiry information in the fine print.
	# This regex will only work for United States format. E.g.,
	# February 1st, 2012. In Australia they do 1st February, 2012
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /Valid\s+until\s+([A-Z][a-z]+)\s+([0-9]{1,2}),\s+([0-9]{4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    
	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				      $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}


	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "nameLink"});
	if (@name) {
	    $deal->name($name[0]->as_text());
	    $deal->website($name[0]->attr('href'));
	}


	my @address = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "merchInfo"});

	if (@address) {
	    my @spans = $address[0]->look_down(sub{$_[0]->tag() eq 'span'});

	    if (@spans) {
		my $phone = $spans[$#spans]->as_text();
		my $tmpphone = $phone;
		$phone =~ s/[^0-9]//g;
		# Make sure $phone is mostly numbers
		if (length($phone) >= 8 &&
		    length($tmpphone) - length($phone) <= 3) {
		    $deal->phone($phone);
		}

		if (defined($deal->phone())) {
		    # Remove last span since we extracted it as phone number:
		    pop(@spans);
		}

		# Rest of spans form an address:
		my $address = "";
		foreach my $span (@spans) {
		    $address = $address.$span->as_text()." ";
		}
		if (length($address) > 0) {
		    $deal->addresses($address);
		}
	    }
	}


	$tree->delete();
    }
  
    1;
}
