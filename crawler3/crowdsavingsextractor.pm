#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2012
#
{
    package crowdsavingsextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;

    # We use this for setFBInfo:
    use dealsdbutils;

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	$tree->ignore_unknown(0);
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	# This is a bit of a hack that we only do for CrowdSavings:
	# We get the actual url from the deal's fb:like field because
	# the dealurlextractor for CrowdSavings throws away the last part
	# of their URLs, since they're not needed, and stripping the end
	# means no dup detection needs to take place. For example:
	# http://www.crowdsavings.com/deal/6026/boudoir-photo-shoot/sf
	# and
	# http://www.crowdsavings.com/deal/6026/boudoir-photo-shoot/nyc
	# are the same deal. So that last part is stripped in deal url
	# extraction, and the deal that is crawled and eventually passed
	# to this extractor will only have:
	# http://www.crowdsavings.com/deal/6026/boudoir-photo-shoot/
	# as its URL. The problem with that is that the code which normally
	# obtains the FB shares/likes in deal_crawler.pl won't work, because
	# it requires the actual URLs that people share.
	my @actual_url = $tree->look_down(
	    sub{$_[0]->tag() eq "fb:like" && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^http/});

	if (@actual_url) {
	    &dealsdbutils::setFBInfo($deal, $actual_url[0]->attr('href'));
	}

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq "meta" && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:title")});

	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq "div" && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "price")});

	if (@price && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	if (defined($deal->title())) {
	    if ($deal->title() =~ /([0-9,\.]+)\s*(value|worth)/i) {
		my $value = $1;
		$value =~ s/,//g;
		$deal->value($value);
	    } elsif ($deal->title() =~ /\$([0-9,\.]+)[^\$]*\$([0-9,\.]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);

		my $value = $2;
		$value =~ s/,//g;
		$deal->value($value);
	    }
	}


	if ($tree->as_text() =~ /([0-9,]+)\s*sold/i) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}


	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "info-company-description")});

	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "details")});

	if (@fine_print) {
	    my $clean_fine_print = $fine_print[0]->as_HTML();
	    $clean_fine_print =~ s/<\/?div[^>]*>//g;
	    $clean_fine_print =~ s/<\/?span[^>]*>//g;
	    $deal->fine_print($clean_fine_print);
	}


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq "meta" && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('content') =~ /^http/ &&
		    ($_[0]->attr('property') eq "og:image")});

	if (@image) {
	    $deal->image_urls($image[0]->attr('content'));
	}


	if ($tree->as_HTML() =~ /deal\s*ended/i) {
	    $deal->expired(1);
	}

	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~ /deal_countdown\(([0-9]+)/) {
		my $offset = $1;
		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# CrowdSavings puts expires information in fine print
	if (defined($deal->fine_print()) &&
	    $deal->fine_print() =~
	    /expires[^0-9]*([0-9]{1,2}).([0-9]{1,2}).([0-9]{4})/i) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;

	    my $expires = sprintf("%d-%02d-%02d 01:01:01", $year, $month, $day);
	    $deal->expires($expires);
	}

	
	# Name
	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq "div" && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "title bold"});
	if (@name) {
	    $deal->name($name[0]->as_text());
	}
    
	# Website
	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^http/ &&
		    defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "tracked" &&
		    defined($_[0]->attr('target'))});
	if (@website) {
	    $deal->website($website[0]->attr('href'));
	}


	my @addresses = $tree->look_down(
	    sub{$_[0]->tag() eq "div" &&
		    defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "map-address" &&
		    defined($_[0]->attr('onclick'))});

	foreach my $address (@addresses) {
	    if ($address->attr('onclick') =~ /[\'\"]([^\'\"]+)/) {
		my $address = $1;
		if (length($address) > 7) {
		    $deal->addresses($address);
		}
	    }
	}


	my @phone_container = $tree->look_down(
	    sub{$_[0]->tag() eq "table" &&
		    $_[0]->as_text() =~ /larger\s*map/i});

	if (@phone_container) {
	    my @divs = $phone_container[0]->look_down(sub{$_[0]->tag() eq "div"});

	    foreach my $div (@divs) {
		my $phone = $div->as_text();
		
		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($tmpphone) < 20 &&
		    length($phone) - length($tmpphone) <=4) {
		    $phone =~ s/[^0-9]//g;
		    $deal->phone($phone);
		}
	    }
	}


	$tree->delete();
    }
  
    1;
}
