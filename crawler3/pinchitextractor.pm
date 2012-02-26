#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) January, 2012
#
{
    package pinchitextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use Time::Piece;

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
	    sub{$_[0]->tag() eq "div" && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "title clearfix"});
	if (@title) {
	    my @title_a = $title[0]->look_down(sub{$_[0]->tag() eq 'a'});
	    if (@title_a) {
		$deal->title($title_a[0]->as_text());
	    }

	    my @subtitle = $title[0]->look_down(sub{$_[0]->tag() eq 'h3'});
	    if (@subtitle) {
		$deal->subtitle($subtitle[0]->as_text());
	    }
	}


	my @pricevalue = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "products")});
	if (@pricevalue &&
	    $pricevalue[0]->as_text() =~ /\$([0-9,\.]+)[^\$]+\$([0-9,\.]+)/) {
	    my $price = $1;
	    my $value = $2;
	    $price =~ s/,//g;
	    $value =~ s/,//g;
	    $deal->price($price);
	    $deal->value($value);
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "contents")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^fine-print/)});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $fine_print =~ s/<strong>.*//;
	    
	    $deal->fine_print($fine_print);
	}


	my @slides = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "slides")});
	if (!@slides) {
	    @slides = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "photo-container")});
	}

	if (@slides) {
	    my @images = $slides[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			$_[0]->attr('src') =~ /^http/});
	    foreach my $image (@images) {
		$deal->image_urls($image->attr('src'));
	    }
	}


	if ($tree->as_text() =~ /offer.{1,5}ended/i) {
	    $deal->expired(1);
	}

	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /sold.out/i)});

	if (@expired) {
	    $deal->expired(1);
	}


	# Pinchit provides dates in PST format, so we need some juggling to
	# get it to GMT.
	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~ 
		/countdown_end[^0-9]*([0-9]{4}),([0-9]{1,2}),([0-9]{1,2}),([0-9]{1,2}),([0-9]{1,2})/)
	    {

		my $year = $1;
		my $month = 1+$2;
		my $day = $3;
		my $hour = $4;
		my $minute = $5;
		my $date = "$year:$month:$day:$hour:$minute";

		my $time = Time::Piece->strptime("$year:$month:$day:$hour:$minute",
						 "%Y:%m:%d:%H:%M");

		# Account for difference between PST and GMT:
		$time = $time + 8*3600;

		($year, $month, $day, $hour, $minute) =
		    (gmtime($time))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Pinchit puts expires information in the fine_print
	if (defined($deal->fine_print())) {
	    if ($deal->fine_print() =~
		/expires\s*([A-Za-z]+)\s*([0-9]{1,2})[^0-9]+([0-9]{4})/i) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		
		if (defined($month_map{$month})) {
		    my $expires = sprintf("%d-%02d-%02d 01:01:01",
					  $year, $month_map{$month}, $day);
		    
		    $deal->expires($expires);
		}
	    } elsif ($deal->fine_print() =~
		     /expire[^0-9]*([0-9]{1,2}).([0-9]{1,2}).([0-9]{2,4})/i) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		if ($year < 1000) {
		    $year += 2000;
		}
		
		my $expires = sprintf("%d-%02d-%02d 01:01:01",
				      $year, $month, $day);
		
		$deal->expires($expires);
	    }
	}
	
	# Name
	if (defined($deal->title())) {
	    $deal->name($deal->title());
	}
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /^location/});

	if (@biz_info) {
	    # Website
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			defined($_[0]->attr('target')) && 
			$_[0]->attr('target') =~ /blank/i});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    } elsif (defined($deal->fine_print()) &&
		     $deal->fine_print() =~ /href=[\"\']([^\"\']+)/) {
		$deal->website($1);
	    }


	    # Addresses
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /maps.google/});
	    
	    foreach my $address (@addresses) {
		if ($address->attr('href') =~ /q=(.*)/) {
		    my $clean_address = $1;
		    $clean_address =~ s/%[0-9][A-Z]/ /g;
		    $clean_address =~ s/\+/ /g;
		    $deal->addresses($clean_address);
		}
	    }

	    # Phone:
	    my $phone;
	    if ($biz_info[0]->as_HTML() =~ />([0-9\s\(\)\.-]{10,20})</) {
		$phone = $1;
	    } elsif (defined($deal->fine_print()) &&
		      $deal->fine_print() =~ /call[^0-9]+([0-9\s\(\)\.-]{10,20})/i) {
		$phone = $1;
	    }

	    if (defined($phone)) {
		$phone =~ s/\s+//g;
		
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
