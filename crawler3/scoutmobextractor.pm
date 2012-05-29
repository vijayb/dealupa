#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
{
    package scoutmobextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;


    my %month_map = (
	"Jan" => 1,
	"Feb" => 2,
	"Mar" => 3,
	"Apr" => 4,
	"May" => 5,
	"Jun" => 6,
	"Jul" => 7,
	"Aug" => 8,
	"Sep" => 9,
	"Oct" => 10,
	"Nov" => 11,
	"Dec" => 12
    );

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	$tree->ignore_unknown(0);	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:title")});
	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}



	
	# ScoutMob doesn't give us a price and a value. Instead they give
	# you a max discount, assuming the discount percentage is 50%
	if ($tree->as_text() =~ /\$([0-9,]+)\s+max/i) {
	    my $price = $1;
	    $price =~ s/,//g;
	    
	    my $value = 2*$price;
	    $deal->price($price);
	    $deal->value($value);
	} elsif ($tree->as_HTML() =~ /percentageOff[^0-9]*100/) {
	    $deal->price(0);
	}

	if ($tree->as_HTML() =~ /number_used[^0-9]+([0-9]+)/) {
	    $deal->num_purchased($1);
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:description")});
	if (@text) {
	    $deal->text($text[0]->attr('content'));
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() =~ /h[0-9]/ && $_[0]->as_text() =~ /the\s*skinny/i});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->parent->as_HTML();
	    $fine_print =~ s/<\/?section[^>]*>//g;
	    $fine_print =~ s/^\s+//;
	    $fine_print =~ s/\s+$//;
	    $deal->fine_print($fine_print);
	}

	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('content') =~ /^http/ &&
		    ($_[0]->attr('property') eq "og:image")});
	
	if (@image) {
	    $deal->image_urls($image[0]->attr('content'));
	}


	if ($tree->as_text() =~ /deal.{1,20}expired/i) {
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~
		/siteEndDate[^\']*\'[A-Za-z]+\s*([A-Za-z]+)\s*([0-9]{2})\s*([^\s]*)\s*[^\s]+\s*([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $time = $3;
		my $year = $4;

		if (defined($month_map{$month})) {
		    my $deadline = sprintf("%d-%02d-%02d %s",
					   $year, $month_map{$month}, $day, $time);
		    $deal->deadline($deadline);
		}
	    }
	}


	# ScoutMob puts the expiry information in the fine print.
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /Expires[^0-9]+([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    
	    my $expires = sprintf("%d-%02d-%02d 01:01:01", $year, $month, $day);
	    $deal->expires($expires);
	}


	# Name
	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'title'});
	if (@name) {
	    $deal->name($name[0]->as_text());
	}

	# Website
	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && $_[0]->as_text() =~ /^website$/i});
	if (@website) {
	    $deal->website($website[0]->attr('href'));


	    my $phone_address_tag = $website[0]->parent;
	    my $address = $phone_address_tag->as_HTML();
	    $address =~ s/<\/?section>//g;
	    $address =~ s/<h[0-9]>[^<]*<\/h[0-9]>//g;
	    $address =~ s/<a\s.*//;
	    $address =~ s/>([0-9\-\.\(\)\s]{9,20})</></;
	    $address =~ s/<[^>]*>/ /g;
	    $address =~ s/^\s*//;
	    $address =~ s/\s*$//;
	    if (length($address) > 7) {
		$deal->addresses($address);
	    }

	    # Phone
	    my $phone = $phone_address_tag->as_HTML();
	    $phone =~ s/\s//g;
	    if ($phone =~ />([0-9\-\.\(\)\s]{9,20})/) {
		$phone = $1;

		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($tmpphone) < 20 &&
		    length($phone) - length($tmpphone) <=4) {
		    $phone =~ s/[^0-9]//g;
		    $deal->phone($phone);
		}
	    }
	}


	$tree->delete();
    }
  

    1;
}
