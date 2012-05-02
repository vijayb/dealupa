#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) April, 2012
#
{
    package weforiaextractor;
    
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

	$deal->affiliate_url("http://www.anrdoezrs.net/click-5498612-10880915?url=".
			     $deal->url());
	
	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' &&
		    defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:title")});
	
	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}
	
	
	my @price = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "detailsPageDealInfoPrice")});
	if (@price && $price[0]->as_text() =~ /\$\s*([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}
	
	my @value = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /origPriceValue/)});
	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}
	
	
	
	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /prodDescriptionText/)});
	
	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $clean_text =~ s/<\/?span[^>]*>//g;
	    $deal->text($clean_text);
	}
	
	
	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /prodDetailsText/)});
	
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $fine_print =~ s/<\/?span[^>]*>//g;
	    $deal->fine_print($fine_print);
	}
	
	
        my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' &&
		    defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:image")});
	
	if (@image) {
	    $deal->image_urls($image[0]->attr('content'));
	}



	my @expired = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /expiredBtn/});
	if (@expired) {
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my $offset = 0;
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq "div" && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "timeLeftSection"});

	    if (@deadline) {
		my $deadline = $deadline[0]->as_text();
		$deadline =~ s/\s*//g;

		if ($deadline =~ /([0-9]+)d/) {
		    $offset += $1 * 3600 *24;
		}

		if ($deadline =~ /([0-9]+)h/) {
		    $offset += $1 * 3600;
		}

		if ($deadline =~ /([0-9]+)m/) {
		    $offset += $1 * 60;
		}
	    }

	    if ($offset > 0) {
		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Weforia put expiry information in the fine print
	if (defined($deal->fine_print()) &&
	    $deal->fine_print() =~ /expires\s*([0-9]+)\s*months/i) {
	    my $offset = $1 * 30 * 24 * 3600;

	    my ($year, $month, $day, $hour, $minute);
	    ($year, $month, $day, $hour, $minute) =
		(gmtime(time()+$offset))[5,4,3,2,1];
	    
	    my $expires = sprintf("%d-%02d-%02d %02d:%02d:01",
				   1900+$year, $month+1, $day,
				   $hour, $minute);
	    $deal->expires($expires);
	}
	
	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq "div" && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /companyInfoSection/});

	if (@biz_info) {
	    # Name:
	    my @name = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') =~ /merchantName/i});
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }
	    
	    # Website:
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /^http/ &&
			defined($_[0]->attr('class')) &&
			$_[0]->attr('class') =~ /MerchantSiteLink/i});
	    
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	    
	    # Addresses:
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') =~ /formattedAddress/});
	    
	    foreach my $address (@addresses) {
		if (length($address->as_text()) > 7) {
		    $deal->addresses($address->as_text());
		}
	    }
	    
	    # Phone
	    my @phone = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "googleMapAddressPhoneValue"});

	    if (@phone) {
		$deal->phone($phone[0]->as_text());
	    }
	}


	if (!defined($deal->name())) {
	    my @name = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') =~ /merchantDescTitle/i});

	    if (@name) {
		my $clean_name = $name[0]->as_text();
		$clean_name =~ s/About\s*//;
		$deal->name($clean_name);
	    }
	}

	$tree->delete();
    }
  
    1;
}
