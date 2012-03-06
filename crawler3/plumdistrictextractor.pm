#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) March, 2012
#
{
    # The plum district extractor is a simple stub extractor.
    # Most of the extraction is done by the XML feed extractor,
    # but since some important fields are missing, it crawls
    # the deals and calls this extractor to get those missing fields.
    package plumdistrictextractor;
    
    use strict;
    use warnings;
    use deal;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;

	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();


	my $text = "";
	if (defined($deal->text())) {
	    $text = $deal->text();
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "company-description"});
	if (@text) {
	    my $clean_text = $text[0]->as_text();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $text = $text.$clean_text;
	}
	
	if (length($text) > 10) {
	    $deal->text($text);
	}


	my @expires = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "fine_print_text"});
	
	if (@expires && $expires[0]->as_text() =~
	    /expires[^0-9]*([0-9]{1,2}).([0-9]{1,2}).([0-9]{2,4})/i) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    if (length($year)==2) { $year += 2000; }

	    my $expires = sprintf("%d-%02d-%02d 01:01:01", $year, $month, $day);
	    $deal->expires($expires);
	}
	
	# Website
	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^http/ &&
		    defined($_[0]->attr('target')) &&
		    $_[0]->as_text() =~ /website/i});
	if (@website) {
	    $deal->website($website[0]->attr('href'));
	}

	# Address:
	my @address_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "location"});

	if (@address_container) {
	    my @addresses = $address_container[0]->look_down(
		sub{$_[0]->tag() eq 'li'});
	    foreach my $address (@addresses) {
		$address = $address->as_HTML();
		$address =~ s/<span[^>]*>[^<]*<\/span>//g;
		$address =~ s/<[^>]*>/ /g;
		$address =~ s/&nbsp;/ /g;
		$address =~ s/\s+/ /g;
		$address =~ s/^\s*//;
		$address =~ s/\s*$//;
		if ($address =~ /(\([0-9].*$)/) {
		    my $phone = $1;
		    my $tmpphone = $phone;
		    $tmpphone =~ s/[^0-9]//g;
		    if (length($tmpphone) > 8 &&
			length($tmpphone) < 20 &&
			length($phone) - length($tmpphone) <=4) {
			$phone =~ s/[^0-9]//g;
			$deal->phone($phone);
			$address =~ s/\(.*$//;
		    }
		}

		if (length($address) > 7) {
		    $deal->addresses($address);
		}
	    }
	}
	

	$tree->delete();
    }
  
    1;
}
