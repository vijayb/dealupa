#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
{
    package amazonlocalextractor;
    
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
	    sub{$_[0]->tag() eq 'h2' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal_title")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}

	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "merchant")});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->as_text());
	}

	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') =~ /Price$/)});
	if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal_price_value")});
	if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /deal_statistics_section_data/) &&
		    $_[0]->as_text() =~ /bought/i});
	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}
	

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "subcontent_desc")});
	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);
	}

	my @details = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal_details")});
	if (@details) {
	    my @website = $details[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			defined($_[0]->attr('target')) &&
			($_[0]->attr('href') =~ /^http/)});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal_fineprint")});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/^\s+//;
	    $fine_print =~ s/\s+$//;
	    $deal->fine_print($fine_print);
	}


	my @image_div = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal_image")});
	if (@image_div) {
	    my @image = $image_div[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			$_[0]->attr('src') =~ /^http/});
	    if (@image) {
		$deal->image_urls($image[0]->attr('src'));
	    }
	}

	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "not_for_sale_status")});
	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') =~ "time_left")});

	    if (@deadline) {
		my $deadline = $deadline[0]->as_text();
		my $days = 0;
		my $hours = 0;
		my $minutes = 0;

		if ($deadline =~ /([0-9]+)\s+day/) {
		    $days = $1;
		}

		if ($deadline =~ /([0-9]{2}):([0-9]{2}):([0-9]{2})/) {
		    $hours = $1 + 0;
		    $minutes = $2 + 0;
		}

		if ($days > 0 || $hours > 0 || $minutes > 0) {
		    my $time = time() + ($days*24*60*60) + ($hours*60*60) +
			($minutes*60);
		    my ($year, $month, $day, $hour, $minute);
		    ($year, $month, $day, $hour, $minute) =
			(gmtime($time))[5,4,3,2,1];
		
		    my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					   1900+$year, $month+1, $day,
					   $hour, $minute);

		    $deal->deadline($deadline);
		}
	    }
	}


	# Amazon Local puts the expiry information in the fine print.
	# This regex will only work for United States format. E.g.,
	# February 1st, 2012. In Australia they do 1st February, 2012
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /[Ee]xpire\s+on\s+([A-Z][a-z]+)\s+([0-9]{1,2})[a-z]{0,2},\s+([0-9]{4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    
	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				      $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}




	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "deal_merchant"});
	if (@name) {
	    $deal->name($name[0]->as_text());

	    #my @website = $tree->look_down(
	#	sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
	#		&genericextractor::similarEnough($_[0]->as_text(),
						#	 $deal->name())});
	 #   if (@website) {
	#	$deal->website($website[0]->attr('href'));
	 #   }
	}


	my @addresses = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "deal_location_address"});

	foreach my $address (@addresses) {
	    my $address = $address->as_HTML();
	    $address =~ s/<[^>]*>/ /g;
	    $address =~ s/\s+/ /g;
	    if ($address =~ /(.*)([A-Z]{2})(\s+[0-9]{5})\s*([^a-zA-Z]*)/ &&
		genericextractor::isState($2)) {
		$address = $1." ".$2." ".$3;
		my $phone = $4;

		$address =~ s/^\s+//g;
		$address =~ s/\s+$//g;
		$deal->addresses($address);

		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($phone) - length($tmpphone) <=4 &&
		    length($tmpphone) >= 9) {
		    $deal->phone($phone);
		}
	    }

	}


	$tree->delete();
    }
  
    1;
}
