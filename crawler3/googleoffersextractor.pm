#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package googleoffersextractor;
    
    use strict;
    use warnings;
    use deal;
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


	my @title = $tree->look_down(sub{$_[0]->tag() eq 'h1'});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}

	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('name')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('name') eq "description")});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->attr('content'));
	}

	if ($tree->as_text() =~ /buy\snow[^\$]{1,10}\$([0-9,]+)/i) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	if (defined($deal->title()) && $deal->title() =~ /([0-9,]+)\s+value/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

	if (defined($deal->price()) &&
	    $tree->as_text() =~ /discount[^0-9]{1,10}([0-9]{1,3})/i) {
	    my $percent = $1/100.0;
	    my $value = sprintf("%.0f", $deal->price()/(1.0-$percent));
	    $deal->value($value);
	}

	if (!defined($deal->price()) || !defined($deal->value())) {
	    if (defined($deal->title()) &&
		$deal->title() =~ /\$([0-9,]+)[^\$]+\$([0-9,]+)/) {
		my $price = $1;
		my $value = $2;
		$price =~ s/,//g;
		$value =~ s/,//g;
		$deal->price($price);
		$deal->value($value);
	    } elsif ($deal->title() =~ /\$([0-9,]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }
	}


	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "mgoh-voucher-text")});

	my $text = "";
	foreach my $snippet (@text) {
	    $text = $text.$snippet->as_HTML();
	}
	$text =~ s/<\/?div[^>]*>//g;
	if (length($text) > 0) {
	    $deal->text($text);
	}

	if (!defined($deal->text())) {
	    my @text = $tree->look_down(
		sub{$_[0]->tag() =~ /^h[0-9]/i && $_[0]->as_text() =~ /the\sdeal/i});
	    if (@text) {
		my $text_tag = $text[0]->parent();
		$deal->text($text_tag->as_text());
	    }

	}

	if (!defined($deal->value()) && defined($deal->text()) &&
	    $deal->text() =~ /\$([0-9,]+)\s*value/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "mgoh-terms")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'table' && $_[0]->as_text() =~ /Sold/ &&
		    $_[0]->as_text() =~ /Total/});
	
	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}
	
	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'link' && defined($_[0]->attr('rel')) &&
		    defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^http/ &&
		    ($_[0]->attr('rel') eq "image_src")});
	
	if (@image) {
	    $deal->image_urls($image[0]->attr('href'));
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "countdown-no-longer-available" &&
		    !defined($_[0]->attr('style'))});
	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "countdown-time")});

	    if (@deadline) {
		my $deadline = $deadline[0]->as_text();
		my $days = 0;
		if ($deadline =~ /([0-9]+)\sday/) {
		    $days = $1;
		    if ($days > 1000) {
			$days = 0;
		    }
		}

		if ($deadline =~ /([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/) {
		    my $hours = $1;
		    my $minutes = $2;
		    my $seconds = $3;
		    my $offset =
			$days*3600*24 + $hours*3600 + $minutes*60 + $seconds;
		    
		    my ($year, $month, $day, $hour, $minute);
		    ($year, $month, $day, $hour, $minute) =
			(gmtime(time() + $offset))[5,4,3,2,1];
		
		    my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					   1900+$year, $month+1, $day,
					   $hour, $minute);
		    $deal->deadline($deadline);
		}
	    }
	}


	my @expires = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /mgoh-redeem/)});

	if (@expires &&
	    $expires[0]->as_text() =~
	    /([A-Za-z]+)\s+([0-9]{1,2}),?\s*([0-9]{4})\s*$/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;

	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",
				      $year, $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}
	
	
	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "mgoh-a-name"});

	if (@name) {
	    $deal->name($name[0]->as_text());
	}

	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('class')) &&
		    defined($_[0]->attr('href')) &&
		    $_[0]->attr('class') eq "mgoh-a-link"});

	if (@website) {
	    $deal->website($website[0]->attr('href'));
	}


	my @addresses = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /mgoh-a-address/});

	foreach my $address_phone (@addresses) {
	    my $address_html = $address_phone->as_HTML();
	    if ($address_phone->as_HTML() =~
		/>\s*([\(\)\s\-\.0-9]{9,17})\s*<\/div>/) {
		$deal->phone($1);
		$address_html =~ 
		    s/>\s*([\(\)\s\-\.0-9]{9,17})\s*<\/div>/><\/div>/;
	    }
	    
	    $address_html =~ s/<[^>]*>/ /g;
	    $address_html =~ s/\s+/ /g;
	    $address_html =~ s/^\s+//;
	    $address_html =~ s/\s+$//g;
	    $address_html =~ s/View\s.*$//;
	    if (length($address_html) > 7) {
		$deal->addresses($address_html);
	    }
	}

	$tree->delete();
    }
  
    1;
}
