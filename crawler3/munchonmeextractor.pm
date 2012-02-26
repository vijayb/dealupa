#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package munchonmeextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;


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
	    my $cleantitle = $title[0]->attr('content');
	    $cleantitle =~ s/^Munch\s*On\s* Me\s*-\*//i;
	    $deal->title($cleantitle);
	}

	my @subtitle = $tree->look_down(sub{$_[0]->tag() eq 'h2'});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->as_text());

	    my @website = $subtitle[0]->look_down(
		sub{$_[0]->tag() eq 'a' &&
			defined($_[0]->attr('href'))});

	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "price")});
	if (@price) {
	    if ($price[0]->as_text() =~ /([0-9,\.]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    } elsif ($price[0]->as_text() =~ /free/i) {
		$deal->price(0);
	    }
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "savings")});
	if (@value && $value[0]->as_text() =~ /([0-9]{1,3})%/) {
	    my $percent_off = 1.0 * $1 / 100.0;
	    if (defined($deal->price()) && $deal->price() > 0) {
		my $value = (1.0*$deal->price()) / (1.0 - $percent_off);
		$deal->value($value);
	    }
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "about-the-dish")});


	my @text2 = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "about-the-merchant")});

	my $clean_text = "";
	if (@text) {
	    $clean_text = $text[0]->as_HTML();
	}
	if (@text2) {
	    $clean_text = $clean_text.$text2[0]->as_HTML();
	    
	    my @name = $text2[0]->look_down(sub{$_[0]->tag() =~ /h[0-9]/});
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }
	}
	if (length($clean_text) > 0) {
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}



	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "fine-print")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "purchased")});

	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}

	my @images = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('class')) &&
		    defined($_[0]->attr('src')) &&
		    ($_[0]->attr('class') eq "ss-thumb")});
	foreach my $image (@images) {
	    $deal->image_urls($image->attr('src'));
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "add-to-cart" &&
		    $_[0]->as_text() =~ /too\s+late/i});

	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~ /javascript_countdown.init\(([0-9]+)/i) {
		my $offset_seconds = $1;

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset_seconds))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	my @expires = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "redeemable"});

	if (@expires && $expires[0]->as_text() =~
	    /([0-9]{1,2}).([0-9]{1,2}).([0-9]{2,4})/i) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    if (length($year) == 2) {
		$year = $year + 2000;
	    }
	    my $expires = sprintf("%d-%02d-%02d 01:01:01",
				  $year, $month, $day);
	    $deal->expires($expires);
	}
	
	
	my @venue_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "venue-info"});


	my @phone = $venue_info[0]->look_down(sub{$_[0]->tag() eq 'p'});


	if (@phone && $phone[0]->as_HTML() =~ /<br\s*\/>([^>]+)$/) {
	    my $phone = $1;
	    $phone =~ s/\s+//g;
	    
	    my $tmpphone = $phone;
	    $tmpphone =~ s/[^0-9]//g;
	    if (length($tmpphone) > 8 &&
		length($phone) -length($tmpphone) <=4) {
		$deal->phone($phone);
	    }
	}

	my @address = $venue_info[0]->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /maps.google/});

	if (@address && $address[0]->attr('href') =~ /\?q=(.*)/) {
	    my $address = $1;
	    $address =~ s/\+/ /g;
	    $address =~ s/%[0-9][A-Z]/ /g;

	    $deal->addresses($address);
	}

	$tree->delete();
    }
  
    1;
}
