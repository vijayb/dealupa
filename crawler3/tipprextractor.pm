#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July-November, 2011
#
{
    package tipprextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use Time::Local;
    use Encode;

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

	if ($deal->url() =~ /\/offer\/([^\/]+)/) {
	    my $slug = $1;
	    my $tracking_url = "http://jump.tippr.com/aff_c?".
		"offer_id=2&aff_id=1594&params=%2526offer%253D";

	    $deal->affiliate_url($tracking_url.$slug);
	}


	my $title = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, "<h1>([^<]+)<");
	if (!defined($title)) {
	    $title = &genericextractor::extractFirstPatternMatched(
		$deal_content_ref,
		"<h1\\s+id=[\'\"]offer-view-headline[\'\"]>([^<]+)<");
	}
	if (defined($title)) {
	    $deal->title($title);
	}

	my $subtitle = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, "<h2>([^<]+)<");
	if (!defined($subtitle)) {
	    $subtitle = &genericextractor::extractFirstPatternMatched(
		$deal_content_ref,
		"<h2\\s+id=[\'\"]offer-view-tagline[\'\"]>([^<]+)<");
	}
	if (defined($subtitle)) {
	    $deal->subtitle($subtitle);
	}


	my $price = &genericextractor::extractBetweenPatternsN(
	    2, $deal_content_ref, "<span\\s+class=[\'\"]usd[\'\"]>\\\$",
	    "<\\/div>");
	if (defined($price) && $price =~ /([0-9]*\.?[0-9]+)/) {
	    $deal->price($1);
	} else {
	    my $price = &genericextractor::extractFirstPatternMatched(
		$deal_content_ref, "<span\\s+class=[\'\"]usd[\'\"]>\\\$(.*)");
	    if (defined($price) && $price =~ /([0-9]*\.?[0-9]+)/) {
		$deal->price($1);
	    }
	}

	my $value = &genericextractor::extractBetweenPatternsN(
	    2, $deal_content_ref, "<dt>Value", "<\\/div>");
	if (defined($value) && $value =~ /([0-9]*\.?[0-9]+)/) {
	    $deal->value($1);
	}

	my $num_purchased = &genericextractor::extractBetweenPatternsN(
	    5, $deal_content_ref, "class=[\'\"]simple-scale-activated",
	    "</div>", "<[^>]+>");
	if (defined($num_purchased) && $num_purchased =~ /([0-9]+)\s+bought/) {
	    $deal->num_purchased($1);
	}

	$num_purchased = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, "<span>([0-9]+)\\s+[Bb]ought");
	if (defined($num_purchased)) {
	    $deal->num_purchased($num_purchased);
	}


	my $expired_regex = "id=[\'\"]offer-view-expired-button[\'\"]";
	if (&genericextractor::containsPattern($deal_content_ref,
					       $expired_regex)) {
	    $deal->expired(1);
	}

	# Getting the deal deadline for tippr is a bit of a pain!
	if (!defined($deal->expired()) && !$deal->expired()) {
	    my $deadline_regex = "until:\\s+new\\s+Date(.*)";
	    my $deadline = &genericextractor::extractFirstPatternMatched(
		$deal_content_ref, $deadline_regex);
	    if (defined($deadline) &&
		$deadline =~ /([A-Z][a-z]+)\s+([0-9]{1,2}),\s+([0-9]{4})\s+([0-9]{1,2}):([0-9]{1,2})\s+([A-Z]{2})\s+[-+]?([0-9]{2})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		my $hour = $4;
		my $minute = $5;
		my $ampm = $6;
		my $offset = 0 + $7; # offset from UTC time

		if ($ampm eq "PM") { $hour +=12; }

		if (defined($month_map{$month})) {
		    my $time = timelocal(1,$minute,$hour,$day,
					 # Our month map is 1..12 whereas
					 # timelocal uses 0..11 for months
					 $month_map{$month}-1,$year);
		    # Tippr annoyingly gives local time, so we need
		    # to convert it to UTC:
		    ($year, $month, $day, $hour, $minute) =
		    (localtime($time + ($offset*60*60)))[5,4,3,2,1];

		    $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					1900+$year, $month+1, $day,
					$hour, $minute);
		    $deal->deadline($deadline);
		}
	    }
	}

	
	my $expires_regex = "Promotion\\s+[Ee]xpires\\s+o?n?\\s*(.*)";
	my $expires = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, $expires_regex);
	if (defined($expires) &&
	    $expires =~ /([A-Z][a-z]+)\s*([0-9]{1,2}),\s*([0-9]{4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    if (defined($month_map{$month})) {
		$expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				   $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}

	my $image_url = &genericextractor::extractBetweenPatternsN(
	    3, $deal_content_ref, "<div\\s+id=[\'\"]offer-banner", "<\\/div>");

	if (!defined($image_url)) {
	    $image_url = &genericextractor::extractBetweenPatternsN(
		3, $deal_content_ref,
		"<div\\s+id=[\'\"]offer-view-image-container", "<\\/div>");
	}

	if (defined($image_url) &&
	    $image_url =~ /src=[\'\"](http[s]?:\/\/[^\'\"]+)/) {
	    $deal->image_urls($1);
	}

	my $text = &genericextractor::extractBetweenPatterns(
	    $deal_content_ref, "<h3\\s+class=[\'\"]header deal-information",
	    "<[\\/]?div");
	if (defined($text)) {
	    $text =~ s/<a class=[\'\"]action.*//;
	    $text =~ s/^\s+//;
	    $text =~ s/\s+$//;
	    $deal->text($text);
	} else {
	    my $text_regex =
		"<div\\s+id=[\'\"]offer-view-description[\'\"]>(.*)<\\/div>";
	    $text = &genericextractor::extractFirstPatternMatched(
		$deal_content_ref, $text_regex);
	    if ($text) {
		$deal->text($text);
	    }
	}
	
	my $fine_print = &genericextractor::extractBetweenPatterns(
	    $deal_content_ref, "<h3 class=[\'\"]header the-fine-print",
	    "<[\\/]?div");

	if (!defined($fine_print)) {
	    $fine_print = &genericextractor::extractBetweenPatterns(
		$deal_content_ref, "<h3>The\\s+[Ff]ine\\s+[Pp]rint",
		"<\\/div");
	}

	if (defined($fine_print)) {
	    $fine_print =~ s/<a class=[\'\"]action.*//;
	    $fine_print =~ s/^\s+//;
	    $fine_print =~ s/\s+$//;
	    $deal->fine_print($fine_print);
	}


	my $website = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref,
	    "href=[\'\"]([^\'\"]+)[\'\"]>[Cc]ompany\\s+[Ww]ebsite");
	if (defined($website)) {
	    $deal->website($website);
	}


	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'p' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "map-marker-name")});
	if (@name) {
	    $deal->name($name[0]->as_text());
	}

	my @p_tags = $tree->look_down(sub{$_[0]->tag() eq 'p'});
	foreach my $phone (@p_tags) {
	    if ($phone->as_text() =~ /[Pp]hone\s*:?\s*([0-9\(\)-\.\s]+)/) {
		my $phone = $1;
		if (length($phone) > 10 && length($phone) < 20) {
		    $deal->phone($phone);
		}
	    }
	}




	# Extract at most 10 addresses
	my @addresses = &genericextractor::extractMBetweenPatternsN(
	    10, 5, $deal_content_ref, "<li\\s+class=[\'\"]map-marker",
	    "<\\/li>");
	if ($#addresses >= 0) {
	    foreach my $address (@addresses) {
		$address =~ s/<p\s+class=[\'\"]map-marker-name[^<]+<\/p>//;
		$address =~ s/<[^>]+>/ /g;
		$address =~ s/\s+/ /g;
		$address =~ s/\s+$//;
		$address =~ s/^\s+//;

		if ($address =~ /\s([A-Za-z]{2}),?\s+[0-9]{5}/ &&
		    &genericextractor::isState($1)) {
		    $deal->addresses($address);
		}
	    }
	}


	$tree->delete();
    }

    1;
}
