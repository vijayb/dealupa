#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
{
    package zoziextractor;
    
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

    my %num_map = (
	"one" => 1,
	"two" => 2,
	"three" => 3,
	"four" => 4,
	"five" => 5,
	"six" => 6,
	"seven" => 7,
	"eight" => 8,
	"nine" => 9,
	"ten" => 10,
	"eleven" => 11,
	"twelve" => 12
    );


    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	$tree->ignore_unknown(0);
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	# Zozi doesn't provide this information on its pages
	$deal->num_purchased(-1);

	my @container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "experience-overview"});

	if (@container) {
	    my @title = $container[0]->look_down(
		sub{$_[0]->tag() eq 'h3'});
	    if (@title) {
		$deal->title($title[0]->as_text());
	    }
	    
	    my @price = $container[0]->look_down(
		sub{defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "new-price" ||
			 $_[0]->attr('class') eq "value")});
	    
	    if (@price) {
		my $price = $price[0]->as_text();
		if ($price =~ /([0-9,]+)/) {
		    $price = $1;
		    $price =~ s/,//g;
		    $deal->price($price);
		}
	    }
	    
	    my @value = $container[0]->look_down(
		sub{defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "old-price")});
	    if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
		my $value = $1;
		$value =~ s/,//g;
		$deal->value($value);
	    }

	    
	    my @address = $container[0]->look_down(
		sub{$_[0]->tag() eq 'p' && $_[0]->as_text() =~ /,/});
	    if (@address) {
		my $clean_address = $address[0]->as_text();
		$clean_address =~ s/^near\s*//i;
		if (length($clean_address) > 5) {
		    $deal->addresses($clean_address);
		}
	    }
	}



	my $text = "";
	my @text1 = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "experience-snapshot")});
	my @text2 = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "experience-info")});

	if (@text1) {
	    $text .= $text1[0]->as_HTML();
	}
	if (@text2) {
	    $text .= $text2[0]->as_HTML();
	}
	if (length($text) > 0) {
	    $text =~ s/<\/?section[^>]*>//g;
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "return-policy")});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $fine_print =~ s/<\/?section[^>]*>//g;
	    $fine_print =~ s/<\/?h[0-9][^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @images = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "large-carousel")});

	if (@images) {
	    my @image_src = $images[0]->look_down(
		sub{$_[0]->tag() eq 'img' &&
			defined($_[0]->attr('src')) &&
			$_[0]->attr('src') =~ /^http/});

	    foreach my $image_src (@image_src) {
		my $clean_image = $image_src->attr('src');
		$clean_image =~ s/\?[^\?]*$//;
		$deal->image_urls($clean_image);
	    }
	}


	if ($tree->as_text() =~ /this\s+deal\s+has\s+expired/i) {
	    $deal->expired(1);
	}

	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "buyer-box")});
	if (@expired && $expired[0]->as_text() =~ /sold\s+out/i) {
	    $deal->expired(1);
	}


#	if (!defined($deal->expired()) && !$deal->expired()) {
#	    if ($tree->as_HTML() =~ /until:\s*new\s*Date\(([0-9]{10})/i) {
#		my $time = $1;
#		my ($year, $month, $day, $hour, $minute);
#		($year, $month, $day, $hour, $minute) =
#		    (gmtime($time))[5,4,3,2,1];
#		
#		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
#				    1900+$year, $month+1, $day,
#				    $hour, $minute);
#
#		$deal->deadline($deadline);
#	    }
#	}


	# Sometimes Zozi gives us expiry information in the form
	# Expires 6 months from the date of purchase. Or, even, annoyingly,
	# six months from the data of purchase.
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /[Ee]xpires\s+([^\s]+)\s+([a-z]+)/) {
	    my $num_months = $1;
	    my $period = $2;
	    if ($period =~ /week/) {
		$period = 0.25;
	    } elsif ($period =~ /month/) {
		$period = 1;
	    } elsif ($period =~ /year/) {
		$period = 12;
	    } else {
		$period = 0;
	    }

	    if (defined($num_map{$num_months})) {
		$num_months = $num_map{$num_months};
	    }

	    if ($num_months =~ /^[0-9]+$/) {
		my ($year, $month, $day);
		($year, $month, $day) =
		    (gmtime(time() + $period*$num_months*30*24*60*60))[5,4,3];
		
		my $expires = sprintf("%d-%02d-%02d 01:01:01",
				      1900+$year, $month+1, $day);
		$deal->expires($expires);
	    }
	}


	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /[Ee]xpires\s+o?n?\s*([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{2,4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    if (length($year)==2) {
		$year += 2000;
	    }
	    
	    my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				  $month, $day);
	    $deal->expires($expires);
	}


	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "map" &&
		    defined($_[0]->attr('data-supplier_name'))});
	if (@name) {
	    $deal->name($name[0]->attr('data-supplier_name'));
	}
	    
	$tree->delete();
    }
  

    1;
}
