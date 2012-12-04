#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package voicedailydealsextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	$deal->affiliate_url("http://www.anrdoezrs.net/click-5498612-11024443?url=".
			     uri_escape($deal->url()));

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:title")});
	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}

	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:description")});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->attr('content'));
	}

	my @dealInfo = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "dealInfo")});

	if (@dealInfo) {
	    my @price = $dealInfo[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "buyNow")});
	    if (@price && $price[0]->as_text() =~ /\$([0-9,]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }
	    
	    my @value = $dealInfo[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "breakdown")});
	    if (@value && $value[0]->as_text() =~ /\$([0-9,]+)/) {
		my $value = $1;
		$value =~ s/,//g;
		$deal->value($value);

		if (!defined($deal->price())) {
		    my @discount = $dealInfo[0]->look_down(
			sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
				($_[0]->attr('class') eq "right")});
		    if (@discount && $discount[0]->as_text() =~ /([0-9]{1,2})\%/) {
			my $discount = 1.0*$1;
			my $percent = 1.0 - $discount/100.0;
			$deal->price($percent * $deal->value());
		    }
		}

	    }
	}


	my $text = "";
	my @text1 = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "highlights")});

	my @text2 = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "aboutDeal")});
	
	if (@text1) {
	    $text = $text1[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	}
	if (@text2) {
	    $text .= $text2[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	}

	if (length($text) > 7) {
	    $deal->text($text);
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "fineprint")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "purchaseCount")});

	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}

	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'link' && defined($_[0]->attr('rel')) &&
		    defined($_[0]->attr('href')) &&
		    ($_[0]->attr('rel') eq "image_src")});
	if (@image) {
	    $deal->image_urls($image[0]->attr('href'));
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /expired/)});


	if (@expired) {
	    $deal->expired(1);
	}

	@expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
		    ($_[0]->attr('src') =~ /soldout/)});

	if (@expired) {
	    $deal->expired(1);
	}




	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "hidden_countdown")});

	    if (@deadline &&
		$deadline[0]->as_text() =~ /^([0-9]+)$/) {
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


	# Voice Daily Deals puts expiry information in the fine print
	if (defined($deal->fine_print()) &&
	    $deal->fine_print() =~
	    /expires\s+([0-9]{1,2}).([0-9]{1,2}).([0-9]{2,4})/i) {
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
	
	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "companyinfo"});

	if (@biz_info) {
	    # Name:
	    my @name = $biz_info[0]->look_down(sub{$_[0]->tag() eq 'h3'});
	    
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }
	    
	    # Website:
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
	    
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	    
	    # Phone:
	    my @phone = $biz_info[0]->look_down(sub{$_[0]->tag() eq 'span'});
	    
	    foreach my $phone_p (@phone) {
		my $phone = $phone_p->as_text();
		$phone =~ s/\s+//g;
		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($phone) -length($tmpphone) <=4) {
		    $deal->phone($phone);
		}
	    }
	    
	    # Addresses:
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "locationInfo"});
	    
	    foreach my $address_tag (@addresses) {
		my $address = $address_tag->as_HTML();
		$address =~ s/<h[0-9]>[^>]+>//gi;
		$address =~ s/<a[^<]+<[^>]*>//gi;
		$address =~ s/<[^>]*>//g;
		
		if ($address =~ /(.*[A-Z]{2}[^0-9]+[0-9]{5})/) {
		    $deal->addresses($1);
		}
	    }
	}


	$tree->delete();
    }
  
    1;
}
