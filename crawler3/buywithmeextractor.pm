#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
# TODO: fully convert this to new-style treebuilder extractor
# rather than old style genericextractor extractor
{
    package buywithmeextractor;
    
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

	# BuyWithMe doesn't provide this information on its pages
	$deal->num_purchased(-1);

	my $title = &genericextractor::extractBetweenPatternsN(
	    5, $deal_content_ref, "<span\\s+id=[\'\"]main_title_span",
	    "<div");
	if (defined($title) &&
	    $title =~ /<h1>([^<]+)<\/h1>/) {
	    $deal->title($1);  
	}

	my $subtitle_regex = "main_title_span[\'\"][^>]+>([^<]+)<";
	my $subtitle = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, $subtitle_regex);
	$deal->subtitle($subtitle);


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'em' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "main_price_text")});
	if (!@price) {
	    @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "pack_price")});
	}
	if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}
	

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "pack_value")});
	if (@value && $value[0]->as_text() =~ /savings[^0-9]+([0-9,]+)/i) {
	    my $value = $1;
	    $value =~ s/,//g;
	    if (defined($deal->price())) {
		$deal->value($deal->price() + $value);
	    }
	} else {
	    @value = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "value_display")});
	    if (@value &&
		$value[0]->as_text() =~ /value[^0-9]{1,4}([0-9,]+)/i) {
		my $value = $1;
		$value =~ s/,//g;
		$deal->value($value);
	    }
	}
	
	


	my $expired_regex = "<div\\s+class=[\'\"]deal_over";
	if (&genericextractor::containsPattern($deal_content_ref,
					       $expired_regex))
	{
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my $deadline_regex = "<div\\s+class=[\'\"]tri_box\\s+timer[\'\"]\\s+title=[\'\"]([^\'\"]+)";
	    my $deadline = &genericextractor::extractFirstPatternMatched(
		$deal_content_ref, $deadline_regex);

	    if (defined($deadline) && 
		$deadline =~ /[a-zA-Z]+\s+([a-zA-Z]+)\s+([0-9][0-9]?)\s+([0-9][0-9]:[0-9][0-9]:[0-9][0-9])\s+UTC\s+([0-9]{4})/) {
		my $month = $1;
		my $year = $4;
		my $day = $2;
		my $timestamp = $3;
		if (defined($month_map{$month})) {
		    $deadline = sprintf("%d-%02d-%02d %s",
					$year, $month_map{$month}, $day,
					$timestamp);
		    $deal->deadline($deadline);  
		}
	    }
	}

	my $expires = &genericextractor::extractBetweenPatternsN(
	    5, $deal_content_ref, "<h4>Deal Terms", "<\/div");

	if (defined($expires)) {
	    $expires =~ s/[^0-9]+$//;
	    if ($expires =~ /([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/) {
		my $day = $2;
		my $month = $1;
		my $year = $3;
		$expires = sprintf("%d-%02d-%02d %s", $year, $month, $day,
				   "01:01:01");
		$deal->expires($expires); 
	    }
	}

	my $image_url_regex = 
	    "id=[\'\"]main_asset[\'\"]\\s+src=[\'\"]([^\'\">]+)";
	my $image_url = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, $image_url_regex);
	if (defined($image_url)) {
	    $image_url =~ s/\?.*$//;
	    $deal->image_urls("http://buywithme.com$image_url"); 
	}

	my $text = &genericextractor::extractBetweenPatterns(
	    $deal_content_ref, "<div\\s+id=[\'\"]short_description",
	    "<h[0-9]>Deal\\s+Terms", "<\\/?div[^>]*>",
	    "<p\\s+class=[\'\"]link[\'\"]>[^<]*<\\/p>", "^\\s*", "\\s*\$");
	if (defined($text)) {
	    $deal->text($text);
	}

	my $fine_print = &genericextractor::extractBetweenPatternsN(
	    5, $deal_content_ref, "<h4>Deal Terms", "</div", "\\r",
	    "^\\s*", "\\s*\$");
	if (defined($fine_print)) { $deal->fine_print($fine_print); }



	my $name = &genericextractor::extractBetweenPatternsN(
	    3, $deal_content_ref, "<div\\s+id=[\'\"]dealSpot", "</div>");
	if (defined($name) && $name =~ /<em>([^<]+)/) {
	    $name = $1;
	    $name =~ s/^\s+//;
	    $name =~ s/\s+$//;
	    $deal->name($name);
	    
	    my @website = $tree->look_down(
		sub{
		    if ($_[0]->tag() eq 'a' && defined($_[0]->attr('href'))) {
			my $name1 = $_[0]->as_text();
			my $name2 = $deal->name();
			$name1 =~ s/\&amp;/\&/g;
			$name2 =~ s/\&amp;/\&/g;
			return
			    &genericextractor::similarEnough($name1, $name2);
		    }
		    return 0;
		});
	    
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	}

	
	my $address_text = &genericextractor::extractBetweenPatterns(
	    $deal_content_ref, "<div\\s+id=[\'\"]dealSpot", "</div>");

	if (defined($address_text)) {
	    # Extract at most 10 addresses
	    my @addresses = &genericextractor::extractMPatterns(
		10, \$address_text, "<li>([^<]+)");
	    if ($#addresses >= 0) {
		foreach my $address (@addresses) {
		    if ($address =~ /\s([A-Za-z]{2}),?\s+([0-9]{5})/ &&
			&genericextractor::isState($1)) {
			$deal->addresses($address);
		    }
		}
	    }
	}


	$tree->delete();
    }



    1;
}
