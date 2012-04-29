#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) April, 2012
#
{
    package dealchickenextractor;
    
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
		    ($_[0]->attr('class') eq "price")});
	if (@price && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "la-value")});
	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /deal-detail/)});
	
	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<div id=[\'\"]r-deal-option.*//;
	    $clean_text =~ s/<h3>[^<]*<[^>]*>//i;
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /deal-option/)});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	# Num purchased
	if ($tree->as_text() =~ /([1-9][0-9,]*)\s*bought/i) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}

	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' &&
		    defined($_[0]->attr('id')) &&
		    defined($_[0]->attr('src')) &&
		    $_[0]->attr('src') =~ /^http/ &&
		    ($_[0]->attr('id') =~ /imgDeal/)});
	
	if (@image) {
	    $deal->image_urls($image[0]->attr('src'));
	} else {
	    my @images = $tree->look_down(
		sub{$_[0]->tag() eq 'a' &&
			defined($_[0]->attr('id')) &&
			defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /^http/ &&
			($_[0]->attr('id') =~ /lnkCarousel/)});
	    foreach my $image (@images) {
		$deal->image_urls($image->attr('href'));
	    }
	}


	my @expired = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "dealover"});
	if (@expired) {
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my $offset = 0;
	    if ($tree->as_HTML() =~ /countdown\(\{until:([^,:]+)/) {
		my $deadline = $1;
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


	# DealChicken put expiry information in the fine print
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
	    sub{$_[0]->tag() =~ /^h[0-9]/i && $_[0]->as_text() =~ /the\sbusiness/i});

	if (@biz_info) {
	    my $biz_info = $biz_info[0]->parent();

	    # Name:
	    my @name_candidates = $biz_info->look_down(
		sub{$_[0]->tag() eq 'p'});
	    foreach my $name_candidate (@name_candidates) {
		if ($name_candidate->as_HTML() !~ /time\sremaining/i &&
		    $name_candidate->as_HTML() =~ /<p>([A-Za-z\s0-9\.\-\'\&#;]+)/) {
		    my $clean_name = $1;
		    $clean_name =~ s/\&#39;/'/g;
		    $deal->name($clean_name);
		}
	    }
	    
	    # Website:
	    my @website = $biz_info->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			defined($_[0]->attr('target')) &&
			$_[0]->attr('target') =~ /blank/i});
	    
	    if (@website) {
		    $deal->website($website[0]->attr('href'));
	    }
	    
	    # Addresses:
	    my @addresses = $biz_info->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /maps.google.com/});
	    
	    foreach my $address (@addresses) {
		if ($address->attr('href') =~ /daddr=([^\&\'\"]+)/) {
		    my $clean_address = $1;
		    $deal->addresses($clean_address);
		}
	    }
	    
	    # Phone
	    if ($biz_info->as_HTML() =~ />\s*([0-9\(\)\-\.\s]{9,17})/) {
		my $phone = $1;
		$phone =~ s/\s//g;
		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($phone) -length($tmpphone) <=4) {
		    $deal->phone($phone);
		}
	    }

	}

	$tree->delete();
    }
  
    1;
}
