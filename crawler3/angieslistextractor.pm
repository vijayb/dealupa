#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
{
    package angieslistextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use Time::Local;


    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	# Angie's List doesn't provide this information on its pages
	$deal->num_purchased(-1);

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "TheBigDealTitle")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}

	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /Main_NonMemberPriceLabel/)});
	if (@price) {
	    my $price = $price[0]->as_text();
	    if ($price =~ /([0-9,\.]+)/) {
		$price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /Main_ValueLabel/)});
	if (@value) {
	    my $value = $value[0]->as_text();
	    if ($value =~ /([0-9,\.]+)/) {
		$value = $1;
		$value =~ s/,//g;
		$deal->value($value);
	    }
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /TheBigDealDescription/)});
	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $text =~ s/<span[^<]*<\/span>//g;

	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'ul' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /TheBigDealFinePrint/)});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $deal->fine_print($fine_print);
	}


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /Main_DealImage/)});
	if (@image) {
	    $deal->image_urls($image[0]->attr('src'));
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /DealExpired/)});
	if (@expired) {
	    $deal->expired(1);
	}
	@expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
		    ($_[0]->attr('src') =~ /expired-button/)});
	if (@expired) {
	    $deal->expired(1);
	}

	my @upcoming = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
		    ($_[0]->attr('src') =~ /availablesoon-button/)});
	if (@upcoming) {
	    $deal->upcoming(1);
	}

	if ((!defined($deal->expired()) || !$deal->expired()) &&
	    (!defined($deal->upcoming()) || !$deal->upcoming())) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'script' && defined($_[0]->attr('type')) &&
			($_[0]->attr('type') =~ /text\/javascript/)});

	    # Deadline on angies list is put in javascript. The deadline
	    # seems to be relative to US Eastern Standard Time
	    foreach my $deadline (@deadline) {
		if ($deadline->as_HTML() =~ /new\s+Date\([\'\"]([0-9]{2}).([0-9]{2}).([0-9]{4})\s+([0-9]{2}):([0-9]{2}):([0-9]{2})\s+([AP]M)/) {
		    my $month = $1 + 0;
		    my $day = $2 + 0;
		    my $year = $3 + 0;
		    my $hour = $4 - 1;
		    my $minute = $5 + 0;
		    my $second = $6 + 0;
		    my $ampm = $7;
		    if ($ampm eq "PM") { $hour += 12; }
		    if ($hour < 0) { $hour = 0; }

		    my $time = timegm($second,$minute,$hour,$day,
				      # Our month is 1..12 whereas
				      # timelocal uses 0..11 for months
				      $month-1, $year);
		    # Angie's list annoyingly gives us Eastern Standard Time
		    # so we have to convert it to UTC (add 4 hours)
		    ($year, $month, $day, $hour, $minute) =
		    (gmtime($time + (4*60*60)))[5,4,3,2,1];

		    $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					1900+$year, $month+1, $day,
					$hour, $minute);
		    $deal->deadline($deadline);
		}
	    }
	}

	# Travelzoo puts the expiry information in the fine print.
	# This regex will only work for United States format. E.g.,
	# May 5, 2011. In Australia they do 5 May, 2011
	if (defined($deal->fine_print()) && 
	    $deal->fine_print() =~ /expires\s+([0-9]+)\s+([a-z]+)/) {
	    my $num = $1;
	    my $period = $2;
	    my $offset = 0;
	    if ($period =~ /month/) {
		$offset = $num * 30 * 24 * 60 * 60;
	    } elsif ($period =~ /year/) {
		$offset = $num * 365 * 24 * 60 * 60;
	    }


	    my ($year, $month, $day, $expires);
	    ($year, $month, $day) =
		(gmtime(time()+$offset))[5,4,3];

	    $expires = sprintf("%d-%02d-%02d 01:01:01",
			       1900+$year, $month+1, $day);
	    $deal->expires($expires);
	}


	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /BigDealServiceProviderName/)});
	if (@name) {
	    $deal->name($name[0]->as_text());
	}

	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /ContactWebSiteLink/i)});
	if (@website) {
	    $deal->website($website[0]->attr('href'));
	}

	my @addresses = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    # They misspell Address! heh. Put correct spelling in
		    # just in case they fix it later
		    ($_[0]->attr('id') =~ /ContactAddres[s]?Label/)});
	if (@addresses) {
	    my $address = $addresses[0]->as_HTML();
	    $address =~ s/<[^>]+>/ /g;
	    $address =~ s/^\s+//;
	    $address =~ s/\s+$//;
	    $address =~ s/^[Aa]ddress:\s*//;

	    if ($address =~ /([A-Z]{2})\s+[0-9]{5}$/ &&
		genericextractor::isState($1)) {
		$deal->addresses($address);
	    }
	}



	$tree->delete();
    }
  

    1;
}
