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

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h1' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "mainTitle")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	} else {
	    @title = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			($_[0]->attr('property') eq "og:title")});
	    if (@title) {
		$deal->title($title[0]->attr('content'));
	    }

	}

	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:description")});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->attr('content'));
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
	} else {
	    @price = $tree->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /ourprice/i)});
	    if (@price) {
		my $price = $price[0]->as_text();
		if ($price =~ /([0-9,]+)/) {
		    $price = $1;
		    $price =~ s/,//g;
		    $deal->price($price);
		}
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


	
	my @slideshow = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /ProductImageSlider/)});
	if (@slideshow) {
	    my @images = $slideshow[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			$_[0]->attr('src') =~ /^http/});
	    foreach my $image (@images) {
		$deal->image_urls($image->attr('src'));
	    }
	}


	if (scalar(keys(%{$deal->image_urls()})) == 0) {
	    my @images = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' &&
			defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			($_[0]->attr('property') eq "og:image") &&
			$_[0]->attr('content') =~ /^http/});
	    foreach my $image (@images) {
		$deal->image_urls($image->attr('content'));
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
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') =~ /rightDealDetails/)});

	    if (@deadline &&
		$deadline[0]->as_text() =~ /Through\s([A-Za-z]+)\s([0-9]{1,2})[^0-9]{1,5}([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		if (defined($month_map{$month})) {
		    $month = $month_map{$month};
		    my $deadline = sprintf("%d-%02d-%02d 01:01:01",
					$year, $month, $day);

		    $deal->deadline($deadline);
		}

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
		    ($_[0]->attr('id') =~ /merchantName/i)});
	if (@name) {
	    $deal->name($name[0]->as_text());
	}


	my @phone = $tree->look_down(
	    sub{defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /MerchantPhone/)});
	if (@phone) {
	    my $phone = $phone[0]->as_text();
	    $phone =~ s/^[^0-9]*//;
	    $deal->phone($phone);
	}

	my @address = $tree->look_down(
	    sub{defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /MerchantAddress/)});
	if (@address && length($address[0]->as_text()) > 7 &&
	    $address[0]->as_text() !~ /http/) {
	    my $address = $address[0]->as_text();
	    $deal->addresses($address);
	}

	my @website = $tree->look_down(
	    sub{defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /MerchantWebSite/i)});
	if (@website) {
	    if ($website[0]->as_HTML() =~ /href=[\'\"]([^\'\"]+)/) {
		$deal->website($1);
	    }
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
		if ($address =~ /\s[0-9]{5}$/) {
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
