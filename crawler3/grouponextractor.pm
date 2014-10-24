#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
# TODO: fully convert this to new-style treebuilder extractor
# rather than old style genericextractor extractor
{
    package grouponextractor;
    
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
	$tree->ignore_unknown(0);
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();
	
	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:title")});
	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}

	
	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('name')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('name') eq "twitter:description")});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->attr('content'));
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "price")});
	if (@price && $price[0]->as_text() =~ /([0-9,]*\.?[0-9]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);  
	}


	my @value = $tree->look_down(
	    sub{defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "discount-value")});

	if (@value && $value[0]->as_text() =~ /([0-9,]*\.?[0-9]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);  
	}


	my $expired_regex1 = "<h5>This\\s+deal\\s+ended";
	my $expired_regex2 = "<h5>This\\s+deal\\s+sold\\s+out";
	if (&genericextractor::containsPattern($deal_content_ref,
					       $expired_regex1) ||
	    &genericextractor::containsPattern($deal_content_ref,
					       $expired_regex2))
	{
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'li' &&
			defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "countdown-timer"});

	    my $days = 0;
	    my $hours = 0;
	    my $minutes = 0;
	    my $deadline_offset = 0;

	    if (@deadline) {
		my $deadline = $deadline[0]->as_text();
		if ($deadline =~ /([0-9]{1,2})\sday/i) {
		    $days = $1;
		}
		if ($deadline =~ /([0-9]{2}):([0-9]{2}):[0-9]{2}/) {
		    $hours = $1;
		    $minutes = $2;
		}

		$deadline_offset = ($days*24*60*60)+($hours*60*60)+
		    ($minutes*60);
		
		if ($deadline_offset > 0) {
		    $deadline = crawlerutils::gmtNow($deadline_offset);
		    $deal->deadline($deadline);
		}
	    }
	}

	my $expires_regex = "Expires(.*)";
	my $expires = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, $expires_regex);
	my ($year, $month, $day);
	if (defined($expires)) {
	    if ($expires =~ /([a-zA-Z]+)\s+([0-9]{1,2}),\s+([0-9]{4})/) {
		$day = $2;
		$month = $1;
		$year = $3;
		if (defined($month_map{$month})) {
		    $expires = sprintf("%d-%02d-%02d 01:01:01",
				       $year, $month_map{$month}, $day);
		    $deal->expires($expires);  
		}
	    } elsif ($expires =~ /in 1 year/) {
		($year, $month, $day) =
		    (localtime(time()+365*24*60*60))[5,4,3];
		$year += 1900;
		$expires = sprintf("%d-%02d-%02d 01:01:01",
				   $year, $month+1, $day);
		$deal->expires($expires);
	    }
	}

	my @text = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^write-up/)});
	if (!@text) {
	    @text = $tree->look_down(
		sub{$_[0]->tag() eq "div" && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "pitch-content")});
	}
	
	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<[^>]*>/ /g;
	    $clean_text =~ s/\s\s+/ /g;
	    $deal->text($clean_text);  
	}



	my $fine_print = &genericextractor::extractBetweenPatterns(
	    $deal_content_ref, "The Fine Print<\\\/h3>",
	    "<\\\/div>", "^\\s+");
	if (defined($fine_print)) {
	    # Make relative URLs absolute:
	    $fine_print =~ s/<a href="\//<a href="http:\/\/groupon.com\//g;
	    $fine_print =~ s/<a href='\//<a href='http:\/\/groupon.com\//g;
	}
	$deal->fine_print($fine_print);  


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('content') =~ /^http/i &&
		    ($_[0]->attr('property') eq "og:image")});
	if (@image) {
	    $deal->image_urls($image[0]->attr('content'));
	}

	my @images = $tree->as_HTML() =~ m/media[\'\"]:[\'\"](http[s]?:\/\/img.grouponcdn[^\"\']*)/g;
	foreach my $image (@images) {
	    $deal->image_urls($image);
	}

	    
	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^qty-bought/)});
	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)\s*bought/i) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}


	my @name_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "merchant-profile"});
	
	if (@name_container) {
	    my @name = $name_container[0]->look_down(
		sub{$_[0]->tag() =~ /^h[0-9]/});
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }
	}


	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->as_text() =~ /company\s+website/i});
	if (@website && $website[0]->attr('href') =~ /^http/) {
	    $deal->website($website[0]->attr('href'));
	}


	my @addresses_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /^merchant-locations/});

	if (@addresses_container) {
	    # addresses
	    my @addresses = $addresses_container[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "address"});

	    foreach my $address (@addresses) {
		my $clean_address = $address->as_HTML();
		$clean_address =~ s/<strong>[^<]*<\/strong>/ /;
		$clean_address =~ s/<a[^>]*>[^<]*<\/a>/ /g;

		# Phone:
		if ($clean_address =~ /<[^>]*>\s*([0-9\(\)\-\.\s]{10,20})/) {
		    my $phone = $1;
		    $phone =~ s/\s+//g;
		    
		    my $tmpphone = $phone;
		    $tmpphone =~ s/[^0-9]//g;
		    if (length($tmpphone) > 8 &&
			length($phone) -length($tmpphone) <=4) {
			$deal->phone($phone);
		    }
		    $clean_address =~ s/<[^>]*>\s*([0-9\(\)\-\.\s]{10,20})//;
		}
		
		$clean_address =~ s/<[^>]*>/ /g;
		$deal->addresses($clean_address);
	    }
	}



	$tree->delete();
    }
  

    1;
}
