#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package dealonextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;


    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	# DealOn doesn't give us num_purchased info
	$deal->num_purchased(-1);

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h1' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal-title")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}


	my @prices = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /deal-discount-cell/)});
	
	foreach my $price (@prices) {
	    if ($price->as_text() =~ /value/i &&
		$price->as_text() =~ /([0-9,]+)/) {
		$deal->value($1);
	    }

	    if ($price->as_text() =~ /savings/i &&
		defined($deal->value()) &&
		$price->as_text() =~ /([0-9,]+)/) {
		$deal->price($deal->value() - $1);
	    }
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal-details-left-column")});

	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'td' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal-highlights-column")});

	foreach my $fine_print (@fine_print) {
	    if ($fine_print->as_text() =~ /expiration\sdate/i) {
		$fine_print = $fine_print->as_HTML();
		$fine_print =~ s/<\/?td[^>]*>//g;
		$deal->fine_print($fine_print);
		last;
	    }
	}



	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:image")});
	
	if (@image) {
	    $deal->image_urls($image[0]->attr('content'));
	}

	my @expired = $tree->look_down(sub{$_[0]->tag() eq 'h2'});
	foreach my $expired (@expired) {
	    if ($expired->as_text() =~ /sold\s+out/i) {
		$deal->expired(1);
	    }
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~ /#countdown'\)([^\}\)]+)/) {
		my $countdown = $1;
		$countdown =~ s/\s+//g;
		if ($countdown =~
		    /([0-9]{4}),([0-9]{1,2}),([0-9]{1,2}),([0-9]{1,2}),([0-9]{1,2})/)
		{
		    my $year = $1;
		    my $month = $2;
		    my $day = $3;
		    my $hour = $4;
		    my $minute = $5;
		    
		    # DealOn gives month in range 0..11
		    my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					   $year, $month+1, $day,
					   $hour, $minute);
		    $deal->deadline($deadline);
		}
	    }
	}



	# DealOn puts the expiry information in the fine print.
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /Expiration\s+Date:\s*([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/i) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
    
	    my $expires = sprintf("%d-%02d-%02d 01:01:01",
				  $year, $month, $day);
	    $deal->expires($expires);
	}

	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "deal-details-right-column"});

	if (@biz_info) {
	    if ($biz_info[0]->as_HTML() =~
		/Company\s+Information<\/h2>\s*<p[^>]*>([^<]+)/i) {
		$deal->name($1);
	    }

	    my @a_tag = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('target')) &&
			defined($_[0]->attr('href'))});
	    if (@a_tag) {
		$deal->website($a_tag[0]->attr('href'));
	    }

	    #print $biz_info[0]->as_HTML(), "\n";
	    if ($biz_info[0]->as_HTML() =~
		/Company\s+Information<\/h2>\s*<p[^>]*>[^<]*<br\s*\/>([^>]+>[^>]+>)/) 
	    {
		my $address = $1;
		$address =~ s/<[^>]*>//g;
		$address =~ s/\s+$//;
		if ($address =~ /([A-Z]{2})\s+[0-9]{5}$/ &&
		    genericextractor::isState($1)) {
		    $deal->addresses($address);
		}
	    }
	}


	$tree->delete();
    }
  
    1;
}
