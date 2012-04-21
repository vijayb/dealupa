#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package getmyperksextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    # omg we have to crawl a link from this page to get the
    # deadline field!
    use LWP::Simple;


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


	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h1' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "value_proposition")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal_price")});
	if (@price && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "savings")});
	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "content")});
	
	foreach my $text (@text) {
	    if ($text->as_HTML() =~ /<h2>About/i) {
		my $clean_text = $text->as_HTML();
		$clean_text =~ s/<\/?div[^>]*>//g;
		$deal->text($clean_text);
		last;
	    }
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "fine_print")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deals_sold")});

	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "deal_photo"});
	
	if (@image && $image[0]->as_HTML() =~ /src=[\'\"]([^\'\"]+)/) {
	    $deal->image_urls($1);
	}

	if ($tree->as_HTML() =~ /<span>deal\s+over<\/span>/i) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' &&
			defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			($_[0]->attr('property') eq "og:url")});

	    if (@deadline &&
		$deadline[0]->attr('content') =~ /(\/daily_deals\/[0-9]+)/) {
		my $url = "http://www.getmyperks.com$1.json";
		my $deadline_json = get $url;
		if (defined($deadline_json) &&
		    $deadline_json =~
		    /ending_time_in_milliseconds[\'\"]?:?([0-9]{10})/) {
		    my $gmt_deadline = $1;
		    my ($year, $month, $day, $hour, $minute);
		    ($year, $month, $day, $hour, $minute) =
			(gmtime($gmt_deadline))[5,4,3,2,1];
		
		    my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					   1900+$year, $month+1, $day,
					   $hour, $minute);
		    $deal->deadline($deadline);
		}
	    }
	}


	# GetMyPerks put expiry information in the fine print
	if (defined($deal->fine_print()) &&
	    $deal->fine_print() =~
	    /expires:?\s+([0-9]{1,2}).([0-9]{1,2}).([0-9]{2,4})/i) {
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
		    $_[0]->attr('id') eq "deal_details"});

	if (@biz_info) {
	    # Name:
	    if ($biz_info[0]->as_HTML() =~ /<strong[^>]*>([^<]+)/i) {
		$deal->name($1);
	    }
	    
	    # Website:
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
	    
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	    
	    # Addresses and phone:
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'p' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "location"});
	    
	    foreach my $address_phone_tag (@addresses) {
		my $address_phone = $address_phone_tag->as_HTML();
		$address_phone =~ s/<strong>[^>]+>//gi;
		$address_phone =~ s/<a[^<]+<[^>]*>//gi;
		
		my $phone;
		if ($address_phone =~ /phone:([^<]+)/) {
		    $phone = $1;
		} elsif ($address_phone =~ />\s*([0-9\(\)\-\.\s]{9,17})/) {
		    $phone = $1;
		}
		if (defined($phone)) {
		    $phone =~ s/\s//g;
		    my $tmpphone = $phone;
		    $tmpphone =~ s/[^0-9]//g;
		    if (length($tmpphone) > 8 &&
			length($phone) -length($tmpphone) <=4) {
			$deal->phone($phone);
		    }
		}
		
		$address_phone =~ s/>\s*([0-9\(\)\-\.\s]{9,17})/>/;
		$address_phone =~ s/phone[^<]*//i;
		$address_phone =~ s/<[^>]*>//g;
		
		if ($address_phone =~ /[A-Z]{2}[^0-9]+[0-9]{5}/) {
		    $deal->addresses($address_phone);
		}
	    }
	}



	$tree->delete();
    }
  
    1;
}
