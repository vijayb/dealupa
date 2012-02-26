#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
{
    package travelzooextractor;
    
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
	"June" => 6,
	"July" => 7,
	"Aug" => 8,
	"Sept" => 9,
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

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h1' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "mainTitle")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}

	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "thePrice")});
	if (@price) {
	    my $price = $price[0]->as_text();
	    if ($price =~ /([0-9,]+)/) {
		$price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /Main_PriceValue/)});
	if (@value) {
	    my $value = $value[0]->as_text();
	    if ($value =~ /([0-9,]+)/) {
		$value = $1;
		$value =~ s/,//g;
		$deal->value($value);
	    }
	}

	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ "Main_LabelBought")});
	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}


	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /theDealDesc/)});
	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $text =~ s/<span[^<]*<\/span>//g;
	    $text =~ s/<\/?h2[^>]*>//g;

	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /finePrint/)});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $fine_print =~ s/<span[^<]*<\/span>//g;
	    $fine_print =~ s/<\/?h2[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('id')) &&
		    defined($_[0]->attr('src')) &&
		    ($_[0]->attr('id') =~ /Main_ProductImage/)});
	if (@image) {
	    $deal->image_urls($image[0]->attr('src'));
	} else {
	    my @slideshow = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "dealSlider")});
	    if (@slideshow) {
		my @images = $slideshow[0]->look_down(
		    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			    $_[0]->attr('src') =~ /^http/});
		foreach my $image (@images) {
		    $deal->image_urls($image->attr('src'));
		}
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /soldOutSecondLine/)});
	if (@expired) {
	    $deal->expired(1);
	}
	@expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /LabelExpiredLightboxMessage/)});
	if (@expired) {
	    $deal->expired(1);
	}

	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') =~ /Main_TimeLeft/)});
	    # Deadline on travelzoo is in relative format: E.g.,
	    # 3 days, 2 hours, 46 minutes, 35 seconds. /annoying
	    if (@deadline) {
		my $deadline = $deadline[0]->as_text();
		my $offset_days = 0;
		my $offset_hours = 0;
		my $offset_minutes = 0;

		if ($deadline =~ /([0-9]+)\s+day/i) {
		    $offset_days = $1;
		}
		if ($deadline =~ /([0-9]+)\s+hour/i) {
		    $offset_hours = $1;
		}
		if ($deadline =~ /([0-9]+)\s+minute/i) {
		    $offset_minutes = $1;
		}

		my $offset = $offset_days*24*60*60 +
		    $offset_hours*60*60 + $offset_minutes*60;

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time() + $offset))[5,4,3,2,1];
		
		$deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				    1900+$year, $month+1, $day,
				    $hour, $minute);

		$deal->deadline($deadline);
	    }
	}

	# Travelzoo puts the expiry information in the fine print.
	# This regex will only work for United States format. E.g.,
	# May 5, 2011. In Australia they do 5 May, 2011
	if (defined($deal->fine_print()) && 
	    $deal->fine_print() =~ 
	    m/([A-Z][a-z]+\.?\s+[0-9]{1,2},\s+[0-9]{4})/g) {
	    my $last_date = $1;

	    if ($last_date =~ 
		/([A-Z][a-z]+)\.?\s+([0-9]{1,2}),\s+([0-9]{4})/g) {
		my $month = $1;
		my $day = $2;
		my $year = $3;

		if (defined($month_map{$month})) {
		    my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				       $month_map{$month}, $day);
		    $deal->expires($expires);
		}
	    }
	}

	my @name = $tree->look_down(
	    sub{defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /Main_MerchantInMapBox/)});
	if (@name) {
	    $deal->name($name[0]->as_text());
	}

	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /Main_MerchantWebSite/i)});
	if (@website) {
	    $deal->website($website[0]->attr('href'));
	}

	my @addresses = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /smallMap/)});
	if (@addresses) {
	    my @ptags = $addresses[0]->look_down(
		sub{$_[0]->tag() eq 'p' && !defined($_[0]->attr('id'))});

	    foreach my $ptag (@ptags) {
		my $address = $ptag->as_HTML();
		if ($address =~ /([^>]+)$/) {
		    my $end = $1;
		    $end =~ s/\s+//g;
		    $end =~ s/Ext\.?//i;
		    my $end2 = $end;
		    $end2 =~ s/[^0-9]+//g;

		    # Check if end of string is a phone number
		    # (most of its content is numbers)
		    if (length($end2) > 8 &&
			length($end) - length($end2) <= 3) {
			$deal->phone($end);
			$address =~ s/<[^>]+>[^>]+$//;
		    }
		}
		
		# Remove superfluous tags and text for deals
		# which can only be redeemed online
		$address =~ s/<[^>]+>/ /g;
		$address =~ s/online redemption only//gi;
		$address =~ s/^\s+//g;

		# US addresses:
		if ($address =~ /,\s+(.*)\s+[0-9]{5}$/ &&
		    genericextractor::isState($1)) {
		    $deal->addresses($address);
		}

		# Canadian addresses:
		if ($address =~ /,\s+(.*)\s+[A-Z0-9]{3}\s+[A-Z0-9]{3}$/ &&
		    genericextractor::isState($1)) {
		    $deal->addresses($address);
		}
	    }
	}



	$tree->delete();
    }
  

    1;
}
