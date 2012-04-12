#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2012
#
{
    package entertainmentextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;


    my %month_map = (
	"Jan" => 1,
	"January" => 1,
	"Feb" => 2,
	"February" => 2,
	"Mar" => 3,
	"March" => 3,
	"Apr" => 4,
	"April" => 4,
	"May" => 5,
	"Jun" => 6,
	"June" => 6,
	"Jul" => 7,
	"July" => 7,
	"Aug" => 8,
	"August" => 8,
	"Sep" => 9,
	"September" =>9,
	"Oct" => 10,
	"October" => 10,
	"Nov" => 11,
	"November" => 11,
	"Dec" => 12,
	"December" => 12
    );

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();


	my @title = $tree->look_down(sub{$_[0]->tag() eq 'h1'});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq "div" && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "buy_tag")});
	if (@price && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}
	
	my @value = $tree->look_down(
	    sub{$_[0]->tag eq 'td' && $_[0]->as_text() =~ /value/i});
	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "about_deal")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<h4>[^<]*<\/h4>//;
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^fine_print/)});

	if (@fine_print) {
	    my $clean_fine_print = $fine_print[0]->as_HTML();
	    $clean_fine_print =~ s/<h4>[^<]*<\/h4>//;
	    $clean_fine_print =~ s/<\/?div[^>]*>//g;
	    $clean_fine_print =~ s/<a[^>]*>[^<]*<\/a>//g;
	    $deal->fine_print($clean_fine_print);
	}


	my @image_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal_photo")});

	if (@image_container) {
	    my @image = $image_container[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			($_[0]->attr('src') =~ /http/)});

	    if (@image) {
		$deal->image_urls($image[0]->attr('src'));
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "deal_over" &&
		    !defined($_[0]->attr('style'))});
	if (!@expired) {
	    @expired = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			$_[0]->attr('id') eq "sold_out" &&
			!defined($_[0]->attr('style'))});
	}

	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{defined($_[0]->attr('id')) &&
			$_[0]->attr('id') eq "time_left_to_buy"});

	    if (@deadline && $deadline[0]->as_text() =~
		/([0-9]{1,4})[^0-9]([0-9]{1,2})[^0-9]([0-9]{1,2})/) {
		my $hours = $1;
		my $minutes = $2;
		my $seconds = $3;

		my $offset = ($hours*3600) + $minutes*60 + $seconds;

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Entertainment.com has the expires information in the fineprint.
	if (defined($deal->fine_print())) {
	    if ($deal->fine_print() =~ 
		     /([A-Z][a-z]+)\s+([0-9]{1,2}),?\s+([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		
		if (defined($month_map{$month})) {
		    my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
					  $month_map{$month}, $day);
		    $deal->expires($expires);
		}
	    } elsif ($deal->fine_print() =~ 
		     /expir[^0-9]*([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{2,4})/i) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		if ($year < 2000) {
		    $year += 2000;
		}
		
		my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				      $month, $day);
		$deal->expires($expires);
	    }

	}
	
	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "advertiser"});

	if (@biz_info) {
	    # Name
	    my @name = $biz_info[0]->look_down(
		sub{$_[0]->tag() =~ /strong/i});
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }

	    # Website
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') !~ /maps.google.com/});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }

	    my $phone_info = $biz_info[0]->as_HTML();
	    $phone_info =~ s/\s*//g;
	    # Phone
	    if ($phone_info =~
		/<br[^>]*>([0-9\(\)\-\.\s]{10,20})/) {
		my $phone = $1;
		$phone =~ s/\s+//g;
	    
		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($phone) -length($tmpphone) <=4) {
		    $deal->phone($phone);
		}
	    }
	    
	    # Address
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			$_[0]->attr('id') eq "advertiser_map"});
	    if (@addresses) {

		if ($addresses[0]->as_HTML() =~ /\|([^\&]+)/) {
		    my $addresses = $1;
		    @addresses = split('\|', $addresses);

		    foreach my $address (@addresses) {
			$address = uri_unescape($address);
			$address =~ s/\+/ /g;
			
			$deal->addresses($address);
		    }
		}
		

	    }

	}



	$tree->delete();
    }
  
    1;
}
