#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) January, 2012
#
{
    package goldstarextractor;
    
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

	$deal->affiliate_url("http://findticket.at/thedealmix?CTY=37&DURL=".
			     $deal->url());

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq "title"});
	if (@title) {
	    my $clean_title = $title[0]->as_text();
	    $clean_title =~ s/\s*\|\s*Goldstar\s*$//i;
	    $deal->title($clean_title);
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /event-full-price/)});

	if (@price) {
	    if ($price[0]->as_text() =~ /\$([0-9,\.]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    } elsif ($price[0]->as_text() =~ /sold\sout/i) {
		$deal->expired(1);
	    }
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /event-our-price/)});

	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "event_summary")});

	my @text2 = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "more_information")});

	if (@text || @text2) {
	    my $clean_text = "";

	    if (@text) {
		$clean_text = $text[0]->as_HTML();
	    }

	    if (@text2) {
		$clean_text = $clean_text.$text2[0]->as_HTML();
	    }

	    $clean_text =~ s/<\/?div[^>]*>//g;
	    if (length($clean_text) > 0) {
		$deal->text($clean_text);
	    }
	}

	my @image_url = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:image")});

	if (@image_url) {
	    my $clean_image_url = $image_url[0]->attr('content');
	    if ($clean_image_url =~ /^\/\//) {
		$clean_image_url = "http:".$clean_image_url;
	    }

	    if ($clean_image_url =~ /^http/) {
		$deal->image_urls($clean_image_url);
	    }
	}

	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "expired_event_notice")});

	if (@expired) {
	    $deal->expired(1);
	}

	if ($tree->as_text() =~ /sold\s*out/i) {
	    # Sometimes there are multiple tickets so "sold out"
	    # may not tell us if the deal expired. We have
	    # to be more sophisticated
	    #$deal->expired(1);
	}

	
	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~ /data-time[^0-9]*([0-9]+)/) {
		my $offset = $1;
		my $year;
		my $month;
		my $day;
		my $hour;
		my $minute;

		($year, $month, $day, $hour, $minute) =
		    (gmtime(time() +$offset))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}

	# GoldStar doesn't provide expires information for its tickets


	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "venue_info"});
	
	if (@biz_info) {
	    # Name:
	    my @name = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "name"});
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
	    

	    # Addresses
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /maps.google/});
	    
	    foreach my $address (@addresses) {
		if ($address->attr('href') =~ /q=([^&]*)/) {
		    my $clean_address = $1;
		    $clean_address =~ s/%[0-9][A-Z0-9]/ /g;
		    $clean_address =~ s/\+/ /g;
		    $deal->addresses($clean_address);
		}
	    }

		
	    # Phone:
	    my @phone = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "phone-number"});
	    if (@phone && $phone[0]->as_text() =~ /[0-9]+/) {
		$deal->phone($phone[0]->as_text());
	    }
	    
	}


	$tree->delete();
    }
 
    1;
}
