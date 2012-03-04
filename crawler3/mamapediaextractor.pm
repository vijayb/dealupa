#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) March, 2012
#
{
    package mamapediaextractor;
    
    use strict;
    use warnings;
    use deal;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;

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

	if ($deal->url() =~ /([^\/]+$)/) {
	    $deal->affiliate_url("http://www.jdoqocy.com/click-5498612-10779141?url=".
				 uri_escape($deal->url().
					    "?utm_campaign=$1&utm_source=cj"));
	}

	my @title = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('name')) &&
			defined($_[0]->attr('content')) &&
			$_[0]->attr('name') eq "title"});

	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}

	my @price = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "deal_price")});
	    
	if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "deal_stats")});
	    
	if (@value && $value[0]->as_text() =~ /([0-9,\.]+)\s*value/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}
	    

	my $text = "";
	my @array = ("deal_highlights", "deal_company");
	foreach my $div_id (@array) {
	    my @text = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			$_[0]->attr('id') eq $div_id});
	    if (@text) {
		my $clean_text = $text[0]->as_HTML();
		$clean_text =~ s/<\/?div[^>]*>//g;
		$text = $text.$clean_text;
	    }
	}
	
	if (length($text) > 10) {
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			$_[0]->attr('id') eq "deal_terms"});
	if (@fine_print) {
	    my $clean_fine_print = $fine_print[0]->as_HTML();
	    $clean_fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($clean_fine_print);
	}


	my @image_container = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			$_[0]->attr('id') eq "deal_photo"});

	if (@image_container) {
	    my @image = $image_container[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			$_[0]->attr('src') =~ /^http/});
	    if (@image) {
		$deal->image_urls($image[0]->attr('src'));
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /buy_now_button\s*unavailable/});
	if (@expired) {
	    $deal->expired(1);
	}

	if (!defined($deal->expired()) && !$deal->expired()) {
	    my $offset;
	    my @deadline = $tree->look_down(
		sub{defined($_[0]->attr('id')) &&
			$_[0]->attr('id') eq "deal_time_to_buy"});
	    if (@deadline && $deadline[0]->as_text() =~
		/([0-9]+)\s*([A-Za-z]+)/) {
		$offset = $1;
		my $period = $2;
		if ($period =~ /day/i) {
		    $offset = $offset * 3600*24;
		} elsif ($period =~ /hour/i) {
		    $offset = $offset * 3600;
		} elsif ($period =~ /minute/i) {
		    $offset = $offset * 60;
		}
	    }

	    if (defined($offset)) {
		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Mamapedia puts expires in fine print
	if (defined($deal->fine_print()) && $deal->fine_print() =~
	    /([A-Za-z]*)\s*([0-9]{1,2}),?\s*([0-9]{4})/i) {
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
		    $_[0]->attr('id') eq "company_name"});
	
	if (@biz_info) {
	    # Name/Website
	    my @namewebsite = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /^http/});
	    if (@namewebsite) {
		$deal->name($namewebsite[0]->as_text());
		$deal->website($namewebsite[0]->attr('href'));
	    } else {
		$deal->name($biz_info[0]->as_text());

	    }
	}

	# Address:
	my @address = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "company_address"});

	if (@address) {
	    my $address = $address[0]->as_HTML();
	    $address =~ s/<[^>]*>/ /g;
	    $address =~ s/\s+/ /g;
	    $address =~ s/^\s*//;
	    $address =~ s/\s*$//;
	    if (length($address) > 7) {
		$deal->addresses($address);
	    }
	}
	

	$tree->delete();
    }
  
    1;
}
