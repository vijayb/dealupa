#!/usr/bin/perl -w
# Copyright (c) 2010, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2010
#
{
    package giltcityextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use Time::Local;

    my %month_map = (
	"January" => 1,
	"Jan" => 1,
	"February" => 2,
	"Feb" => 2,
	"March" => 3,
	"Mar" => 3,
	"April" => 4,
	"Apr" => 4,
	"May" => 5,
	"May" => 5,
	"June" => 6,
	"Jun" => 6,
	"July" => 7,
	"Jul" => 7,
	"August" => 8,
	"Aug" => 8,
	"September" => 9,
	"Sep" => 9,
	"October" => 10,
	"Oct" => 10,
	"November" => 11,
	"Nov" => 11,
	"December" => 12,
	"Dec" => 12
    );


    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	$tree->ignore_unknown(0);
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	$deal->affiliate_url("http://www.jdoqocy.com/click-5498612-10964503?url=".
			     $deal->url());

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h1' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /offer-name/)});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}

	my @subtitle = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /vendor-blurb/)});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->as_text());
	}

	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "pkg-buy")});

	if (!@price) {
	    @price = $tree->look_down(
		sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "preview-tease")});
	}

	if (@price) {
	    if ($price[0]->as_text() =~ /\$([0-9,]+)[^\$]+\$([0-9,]+)/) {
		my $price = $1;
		my $value = $2;
		$price =~ s/,//g;
		$value =~ s/,//g;
		$deal->price($price);
		$deal->value($value);
	    } elsif ($price[0]->as_text() =~ /\$([0-9,]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }
	}

	my $text = "";
	my @text1 = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "pkg-includes")});
	if (@text1) {
	    $text = $text1[0]->as_HTML();
	}

	my @text2 = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "what-we-love")});
	if (@text2) {
	    $text = $text.$text2[0]->as_HTML();
	}

	my @text3 = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "the-review")});
	if (@text3) {
	    $text = $text.$text3[0]->as_HTML();
	}

	if (length($text) > 0) {
	    $text =~ s/<\/?div[^>]*>//g;
	    $text =~ s/<\/?section[^>]*>//g;
	    $text =~ s/<\/?header[^>]*>//g;
	    $text =~ s/<h2[^<]*<\/h2>//g;
	    
	    $deal->text($text);
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^what-to-know/)});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $fine_print =~ s/<\/?section[^>]*>//g;
	    $fine_print =~ s/<\/?header[^>]*>//g;
	    $fine_print =~ s/<h2[^<]*<\/h2>//g;

	    $deal->fine_print($fine_print);
	}


	my @image_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "offer-photos")});
	if (@image_container) {
	    my @images = $image_container[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src'))});
	    
	    foreach my $image (@images) {
		if ($image->attr('src') =~ /^\/images/)	{
		    my $image_link = "http://www.giltcity.com".
			$image->attr('src'); 
		    $image_link =~ s/\?.*$//;
		    $deal->image_urls($image_link);
		} elsif ($image->attr('src') =~ /^http/) {
		    my $image_link = $image->attr('src');
		    $image_link =~ s/\?.*$//;
		    $deal->image_urls($image_link);
		}
	    }
	}


	
	#my @expired = $tree->look_down(
	#    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
	#	    ($_[0]->attr('class') eq "tab-sold-out")});
	#if (@expired) {
	#    $deal->expired(1);
	#}

	if ($tree->as_text() =~ /offer\s*ended/i) {
	    $deal->expired(1);
	}

	if ($tree->as_text() =~ /begins\s*on\s*:/i) {
	    $deal->upcoming(1);
	}

	my @description = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('content') =~ /unlock\sthis/ &&
		    ($_[0]->attr('property') eq "og:description" || 
		     $_[0]->attr('property') eq "og:title")});
	if (@description) {
	    $deal->upcoming(1);
	}


	if (!defined($deal->expired()) && !$deal->expired() &&
	    !defined($deal->upcoming()) && !$deal->upcoming()) {
	    my @deadline = $tree->look_down(
		sub{defined($_[0]->attr('class')) &&
			($_[0]->attr('class') =~ /^offer-ends/)});
	    if (@deadline) {
		# For the case where gilt gives us the deadline in the format
		# e.g., 2days, 3hrs, 10mins from now.
		my $days = 0;
		if ($deadline[0]->as_text() =~ /([0-9]+)\s*d/) {
		    $days = $1;
		}
		my $hours = 0;
		if ($deadline[0]->as_text() =~ /([0-9]+)\s*h/) {
		    $hours = $1;
		}
		my $minutes = 0;
		if ($deadline[0]->as_text() =~ /([0-9]+)\s*m/) {
		    $minutes = $1;
		}
		
		my $offset = ($days*3600*24) + ($hours*3600) + $minutes*60;
		if ($offset > 0) {
		    my ($year, $month, $day, $hour, $minute);
		    ($year, $month, $day, $hour, $minute) =
			(gmtime(time()+$offset))[5,4,3,2,1];
		    
		    my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					   1900+$year, $month+1, $day,
					   $hour, $minute);
		    $deal->deadline($deadline);
		} elsif ($deadline[0]->as_text() =~ /([A-Z][a-z]+)\s*([0-9]+)/) {
		    # if Gilt gives us the date in the format of Feb, 10th
		    # we have to do some gymnastics to make sure we infer the
		    # correct year. It's usually the current year, but if you're
		    # near the year end boundary, it may be the next year
		    my $month = $1;
		    my $day = $2;
		    if (defined($month_map{$month})) {
			my $year = 1900+(gmtime(time()))[5];
			my $time = timelocal(1,1,1,1,($month_map{$month}-1),$year); 
			
			if (time() - $time > (3600*24*32)) {
			    $year++;
			}
			
			my $deadline = sprintf("%d-%02d-%02d 01:01:01",
					       $year, $month_map{$month}, $day);
			$deal->deadline($deadline);
		    }
		}
	    }
	}

	if (defined($deal->fine_print()) &&
	    $deal->fine_print() =~
	    /([A-Z][a-z]+)\s+([0-9]{1,2})[^A-Za-z]*[A-Za-z]*\s*([0-9]{4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",
				      $year, $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}


	if ($tree->as_HTML() =~ /brand_name[\'\"][^\'\"]+[\'\"]([^\'\"]+)/) {
	    $deal->name($1);
	}

	# GiltCity doesn't seem to provide any website information
	# for businesses. Boo!

	my @address = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^vcard/)});
	if (@address) {
	    my $address = $address[0]->as_HTML();
	    $address =~ s/<[^>]*>/ /g;
	    $address =~ s/\s+/ /g;
	    $address =~ s/\s,/,/g;
	    $deal->addresses($address);
	}



	$tree->delete();
    }
  

    1;
}
