#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package eversaveextractor;
    
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
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	my @title = $tree->look_down(sub{$_[0]->tag() eq 'h1'});
	if (@title) {
	    my $text = $title[0]->as_text();
	    $text =~ s/^[Tt]oday's\s+[Ss]ave:\s+//;
	    $deal->title($text);
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "qa_dollarOnBuyBtn")});
	if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'font' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "qa_value")});
	if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}


	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "tab_view_content_1")});

	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "saveDetailsWrap")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $deal->fine_print($fine_print);
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "saveTimeWrap")});
	
	foreach my $num_purchased (@num_purchased) {
	    if ($num_purchased->as_text() =~ /sold/i &&
		$num_purchased->as_text() =~ /([0-9,]+)/) {
		$num_purchased = $1;
		$num_purchased =~ s/,//g;
		$deal->num_purchased($num_purchased);
		last;
	    }
	}
	
	my @slides = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "slides_container")});
	
	if (@slides) {
	    my @images = $slides[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src'))});
	    
	    # has multiple images. TODO: add all of them
	    foreach my $image (@images) {
		$deal->image_urls($image->attr('src'));
	    }
	}

	# If no other images extracted:
	if (scalar(keys(%{$deal->image_urls()})) == 0) {
	    my @image = $tree->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			$_[0]->attr('src') =~ /websites/ &&
			$_[0]->attr('src') !~ /newsletter/ &&
			defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "shareSaveLeftContentImg")});

	    if (@image) {
		$deal->image_urls($image[0]->attr('src'));
	    }
	}

	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('alt')) &&
		    ($_[0]->attr('alt') =~ /expired/i)});
	if (@expired) {
	    $deal->expired(1);
	}
	@expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'input' && defined($_[0]->attr('value')) &&
		    defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "timeCountDownSoldOut")});
	if (@expired && $expired[0]->attr('value') eq "true") {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'input' && defined($_[0]->attr('value')) &&
			defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "timeCountDownSeconds")});

	    if (@deadline && $deadline[0]->attr('value') =~ /^([0-9]+)$/) {
		my $deadline_seconds = $1;

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
                    (gmtime(time() + $deadline_seconds))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				    1900+$year, $month+1, $day,
				    $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# EverSave puts the expiry information in the fine print.
	# The format is assumed to be American. E.g., Aug 31, 2012
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /expires[^o]+on\s+([A-Za-z]+)\s*([0-9]{1,2}),\s*([0-9]{4})/) {
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
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "businessInfo"});

	if (@biz_info) {
	    # name
	    my $biz_html = $biz_info[0]->as_HTML();
	    $biz_html =~ s/<\/?div[^>]*>//g;

	    if ($biz_html =~ /\s*([A-Za-z][a-z][^<]+)/ &&
		$biz_html !~ /redeem\s+online/i &&
		$biz_html !~ /buy\snow/i &&
		$biz_html !~ /shipped/i &&
		$biz_html !~ /purchase/i &&
		$biz_html !~ /reservations/i) {
		$deal->name($1);
	    }

	    # phone
	    if ($biz_html =~ /([0-9]{3}.[0-9]{3}.[0-9]{4})/) {
		my $phone = $1;
		$phone =~ s/[^0-9]+//g;
		$deal->phone($phone);
	    }
	    if ($biz_html =~ /(\([0-9]{3}\).?[0-9]{3}.[0-9]{4})/) {
		my $phone = $1;
		$phone =~ s/[^0-9]+//g;
		$deal->phone($phone);
	    }

	    # address
	    if ($biz_html =~ /<br\s*\/?>(.*[0-9]{5})<br\s*\/?>/) {
		my $address = $1;

		$address =~ s/&nbsp;/ /g;
		$address =~ s/<[^>]*>//g;

		if ($address =~ /([A-Z]{2}),?\s*[0-9]{5}$/ &&
		    genericextractor::isState($1)) {
		    $deal->addresses($address);
		}
	    }

	    # website
	    my @a_tag = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

	    if (@a_tag) {
		$deal->website($a_tag[0]->attr('href'));
	    }
	}


	$tree->delete();
    }
  
    1;
}
