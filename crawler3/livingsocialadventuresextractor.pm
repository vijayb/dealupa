#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2012
#
{
    package livingsocialadventuresextractor;
    
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
	$tree->ignore_unknown(0);
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();


	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:title")});
	if (@title) {
	    my $cleantitle = $title[0]->attr('content');
	    $cleantitle =~ s/:\s*LivingSocial.*$//i;
	    $deal->title($cleantitle);
	}


	my @buy_box = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "buy-box")});

	if (@buy_box) {

	    my @price = $buy_box[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') =~ /^deal-price/)});
	    if (@price) {
		if ($price[0]->as_text() =~ /([0-9,]+)[^0-9]([0-9,]+)/) {
		    my $price = $2;
		    my $value = $1;
		    $price =~ s/,//g;
		    $value =~ s/,//g;
		    $deal->price($price);
		    $deal->value($value);
		} elsif ($price[0]->as_text() =~ /([0-9,]+)/) {
		    my $price = $1;
		    $price =~ s/,//g;
		    $deal->price($price);
		}
	    }
	    

	    my @num_purchased = $buy_box[0]->look_down(
		sub{defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "purchased") &&
			$_[0]->as_text() !~ /left/i});
	    if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
		my $num_purchased = $1;
		$num_purchased =~ s/,//g;
		$deal->num_purchased($num_purchased);
	    }
	}
	



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal-description")});
	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "fine-print")});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @image_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' &&
		    ((defined($_[0]->attr('class')) &&
		     ($_[0]->attr('class') =~ /carousel/)) ||
		     (defined($_[0]->attr('id')) &&
		      ($_[0]->attr('id') =~ /carousel/)))});
	if (@image_container) {
	    my @images = $image_container[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			($_[0]->attr('src') =~ /^http/)});
	    
	    foreach my $image (@images) {
		$deal->image_urls($image->attr('src'));
	    }
	}

	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /sold.{1,3}out/i)});

	if (@expired) {
	    $deal->expired(1);
	}


	# LivingSocial adventures don't have a deadline, so just set it
	# to 10 days from now and hope the expired field catches deals
	# that are over.
	if (!defined($deal->expired()) && !$deal->expired()) {
	    my ($year, $month, $day, $hour, $minute);
	    ($year, $month, $day, $hour, $minute) =
		(gmtime(time() + 3600*24*10))[5,4,3,2,1];
	    
	    my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				   1900+$year, $month+1, $day,
				   $hour, $minute);
	    
	    $deal->deadline($deadline);
	}


	# LivingSocialAdventures puts the expiry information in the fine print.
	# This regex will only work for United States format. E.g.,
	# April 30, 2012. In Australia they do 1st February, 2012
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /expires\s+on\s+([A-Z][a-z]+)\s+([0-9]{1,2}),\s+([0-9]{4})/i) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    
	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				      $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}

	my @location = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /^venue-info/});

	if (@location) {
	    my @name = $location[0]->look_down(
		sub{$_[0]->tag() =~ /^h[0-9]/});
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }

	    my @phone = $location[0]->look_down(
		sub{defined($_[0]->attr('class')) &&
			    $_[0]->attr('class') eq "tel"});
	    if (@phone) {
		$deal->phone($phone[0]->as_text());
	    }


	    my @website = $location[0]->look_down(
		sub{$_[0]->tag() eq "a" &&
			defined($_[0]->attr('href')) &&
			defined($_[0]->attr('target')) &&
			$_[0]->attr('href') !~ /maps.google/i &&
			$_[0]->attr('target') =~ /blank/i});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }

	    my @addresses = $location[0]->look_down(
		sub{$_[0]->tag() eq 'address'});
	    
	    foreach my $address (@addresses) {
		my $clean_address = $address->as_HTML();
		$clean_address =~ s/<span\s*class=[\'\"]tel[\'\"]>.*//;
		$clean_address =~ s/<a\s.*//;
		$clean_address =~ s/<[^>]*>/ /g;
		$clean_address =~ s/^\s*//;
		$clean_address =~ s/\s*$//;
		$deal->addresses($clean_address);
	    }

	}


	$tree->delete();
    }
  
    1;
}
