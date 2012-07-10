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
		sub{$_[0]->tag() eq 'meta' &&
			defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			($_[0]->attr('property') eq "og:title")});
	
	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}

	my @subtitle = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "offer-subtitle")});

	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->as_text());
	}

	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "price")});
	if (@price && $price[0]->as_text() =~ /\$\s*([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "original-price")});
	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}


	my @text = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' &&
			defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			($_[0]->attr('property') eq "og:description")});
	
	if (@text) {
	    $deal->text($text[0]->attr('content'));
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /fine-print/)});

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


	my @images = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' &&
			defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			$_[0]->attr('content') =~ /^http/ &&
			($_[0]->attr('property') eq "og:image")});
	foreach my $image (@images) {
	    $deal->image_urls($image->attr('content'));
	}
	

	if ($tree->as_HTML() =~ /<span>deal\s+over<\/span>/i) {
	    $deal->expired(1);
	}



	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "sold-out-overlay"});
	if (@expired) {
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' &&
			defined($_[0]->attr('name')) &&
			defined($_[0]->attr('content')) &&
			($_[0]->attr('name') =~ /expire_date/)});

	    if (@deadline &&
		$deadline[0]->attr('content') =~ 
		/([0-9]{4}-[0-9]{2}-[0-9]{2}\s+[0-9]{2}:[0-9]{2}:[0-9]{2})/) {
		$deal->deadline($1);
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
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /module\s*map/});

	if (@biz_info) {
	    # Name:
	    if ($biz_info[0]->as_HTML() =~ /<strong[^>]*>([^<]+)/i) {
		$deal->name($1);
	    }
	    
	    # Website:
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			defined($_[0]->attr('rel')) && $_[0]->attr('rel') eq "external"});
	    
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	    

	    # Phone:
	    my @phone = $biz_info[0]->look_down(
		sub{defined($_[0]->attr('class')) && $_[0]->attr('class') eq "phone"});
	    if (@phone) {
		my $phone = $phone[0]->as_text();
		$phone =~ s/\s//g;
		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($phone) -length($tmpphone) <=4) {
		    $deal->phone($phone);
		}
	    }


	    # Addresses and phone:
	    my @addresses = $biz_info[0]->look_down(
		sub{defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "physical"});
	    
	    foreach my $address (@addresses) {
		$address = $address->as_HTML();
		$address =~ s/<div\s*class=\"address-name\">[^<]*<\/div>//g;
		$address =~ s/<[^>]*>/ /g;
		$address =~ s/\s+/ /g;
		$address =~ s/^\s*//;
		$address =~ s/\s*$//;
		if (length($address) > 7) {
		    $deal->addresses($address);
		}
	    }
	}



	$tree->delete();
    }
  
    1;
}
