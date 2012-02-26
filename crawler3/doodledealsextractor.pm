#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package doodledealsextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
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


	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h1' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal_title")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "price")});
	if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'li' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /value/)});
	if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "lowdown")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}



	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "need_to_know")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /sold/)});

	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}

	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "editorial")});

	if (@image) {
	    my @image_src = $image[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src'))});

	    if (@image_src) {
		$deal->image_urls($image_src[0]->attr('src'));
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /expired/i});

	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~ /{until[^0-9]+([0-9]+)/i) {
		my $offset_seconds = $1;

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset_seconds))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}

	# Doodle deals is annoying. They don't give us the company
	# name directly. We can get it using two heuristics. Either
	# look in the title, or in URL:
	# MyPleasingPalate.com for example.
	# When looking in the URL, we look at the URL in the fine print
	# (see below)
	if (defined($deal->title()) &&
	    $deal->title() =~ /at\s(?:the)?\s*([A-Z][^\(]+)\(\$/) {
	    $deal->name($1);
	}
	
	if (defined($deal->title()) &&
	    $deal->title() =~ /from\s*([A-Z][^\(]+)\(\$/) {
	    $deal->name($1);
	}


	# Doodle deals puts expiry information in fine print
	if (defined($deal->fine_print())) {
	    if ($deal->fine_print() =~ /expires\s+([0-9]+)\s+([A-Za-z]+)/i) {
		my $offset_seconds = $1;
		my $period_type = $2;
		
		if ($period_type =~ /year/i) {
		    $offset_seconds = $offset_seconds * 3600*24*365;
		} elsif ($period_type =~ /month/i) {
		    $offset_seconds = $offset_seconds * 3600*24*30;
		} elsif ($period_type =~ /week/i) {
		    $offset_seconds = $offset_seconds * 3600*24*7;
		}
		
		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset_seconds))[5,4,3,2,1];
		
		my $expires = sprintf("%d-%02d-%02d %02d:%02d:01",
				      1900+$year, $month+1, $day,
				      $hour, $minute);
		$deal->expires($expires);
	    } elsif ($deal->fine_print() =~ 
		     /([A-Z][a-z]+)\s+([0-9]{1,2}),?\s+([0-9]{4})/g) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		
		if (defined($month_map{$month})) {
		    my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
					  $month_map{$month}, $day);
		    $deal->expires($expires);
		}
	    }


	    if ($deal->fine_print() =~ /href=[\'\"]([^\'\"]+)/) {
		$deal->website($1);
	    }


	    if (!defined($deal->name()) &&
		$deal->fine_print() =~ />([^<]+)<\/a>/) {
		my $name = $1;
		# We want to get rid of the domain suffix, and split
		# on capitalization.
		$name =~ s/\..{1,4}$//;
		if ($name =~ /[A-Z]/) {
		    $name =~ s/([a-z])([A-Z])/$1 $2/g;
		    $deal->name($name);
		}
	    }
	}
	
	
	my @addresses = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "location_map"});

	foreach my $address (@addresses) {
	    if ($address->as_HTML() =~ /maps\?q=([^\'\">]+)/) {
		my $clean_address =$1;
		$clean_address =~ s/%2c/ /gi;
		$clean_address =~ s/\+/ /g;
		$deal->addresses($clean_address);
	    }
	}
	
	my @phones = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "location_details"});

	foreach my $phone_div (@phones) {
	    if ($phone_div->as_HTML() =~ /<br\s*\/>([^<]+)<\/div>/) {
		my $phone = $1;
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
