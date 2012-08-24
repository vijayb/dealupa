#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) April, 2012
#
{
    package dailycandyextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;

    my %month_map = (
	"January" => 1,
	"Jan" => 1,
	"February" => 2,
	"Feb" => 2,
	"March" => 3,
	"Mar" => 3,
	"April" => 4,
	"Apr" => 4,
	"May" => 5,
	"June" => 6,
	"Jun" => 6,
	"July" => 7,
	"Jul" => 7,
	"August" => 8,
	"Aug" => 8,
	"September" => 9,
	"Sep" => 9,
	"Sept" => 9,
	"October" => 10,
	"Oct" => 10,
	"November" => 11,
	"Nov" => 11,
	"December" => 12,
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
	    my $cleantitle = $title[0]->attr('content');
	    $deal->title($cleantitle);
	}

	my @subtitle = $tree->look_down(sub{$_[0]->tag() eq "h2"});
	
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->as_text());
	}

	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq "div" && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "price")});

	if (@price && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}


	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq "span" && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "value")});

	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}


	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "box" &&
		    $_[0]->as_HTML() =~ /class=[\'\"]aside/i)});

	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $text =~ s/<\/?span[^>]*>//g;
	    $text =~ s/^\s*<br[^>]*><br[^>]*>//i;
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^details/)});

	if (@fine_print) {
	    my $clean_fine_print = $fine_print[0]->as_HTML();
	    $clean_fine_print =~ s/<\/?div[^>]*>//g;
	    $clean_fine_print =~ s/<\/?span[^>]*>//g;
	    $deal->fine_print($clean_fine_print);
	}


	my @images = $tree->look_down(
	    sub{$_[0]->tag() eq "meta" && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('content') =~ /^http/ &&
		    ($_[0]->attr('property') eq "og:image")});

	foreach my $image (@images) {
	    $deal->image_urls($image->attr('content'));
	}



	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /^expire/ &&
		    $_[0]->as_text() =~ /no\slonger\savailable/i});
	if (@expired) {
	    $deal->expired(1);
	}

	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') =~ /^expire/});
	    
	    if (@deadline && $deadline[0]->as_text() =~
		/([A-Z][a-z]+)\s([0-9]{1,2})\s([0-9]{1,2})([ap]m)/  &&
		defined($month_map{$1})) {
		my $month = $month_map{$1};
		my $day = $2;
		my $hour = $3 % 12;
		my $ampm = $4;
		if ($ampm eq "pm") {
		    $hour += 12;
		}

		my $curr_year;
		my $curr_month;
		($curr_year, $curr_month) = (localtime(time()))[5,4];
		$curr_year += 1900;
		$curr_month += 1;

		if ($month < $curr_month) {
		    $curr_year += 1;
		}

		my $deadline = sprintf("%d-%02d-%02d %02d:01:01",
				       $curr_year, $month, $day, $hour);
		$deal->deadline($deadline);
	    }
	}


	# DailyCandy puts expires information in fine print
	if (defined($deal->fine_print()) &&
	    $deal->fine_print() =~
	    /redeemed\sby\s([A-Z][a-z]+)\.?\s*([0-9]{1,2})[^0-9]*([0-9]{4})/i) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;

	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",
				      $year, $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}

	
	# Name
	$deal->name($deal->title());


	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^details/)});

	if (@biz_info) {
	    # Website
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /^http/ &&
			$_[0]->attr('href') !~ /maps/i &&
			defined($_[0]->attr('target'))});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	    
	    # Addresses
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq "a" &&
			defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /maps.google.com/});
	    
	    foreach my $address (@addresses) {
		if ($address->attr('href') =~ /q=([^\&;]+)/) {
		    my $address = $1;
		    $address =~ s/\+/ /g;
		    $address =~ s/^[A-Z][^0-9]+//;
		    if (length($address) > 7) {
			$deal->addresses($address);
		    }
		}
	    }
	    
	    # Phone
	    my $phone = $biz_info[0]->as_HTML();
	    $phone =~ s/\s*\&[^;]{1,5};\s*//g;
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
