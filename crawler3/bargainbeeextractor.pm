#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#
{
    package bargainbeeextractor;
    
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


	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:title")});
	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "buy-now")});
	if (@price) {
	    if ($price[0]->as_text() =~ /\$([0-9,\.]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }
	    if ($price[0]->as_text() =~ /worth\s*\$([0-9,\.]+)/i) {
		my $value = $1;
		$value =~ s/,//g;
		$deal->value($value);
	    }
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "content-block")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}



	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "block-02")});

	if (@fine_print && $fine_print[0]->as_text() =~ /fine\s*print/i) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'strong'});
	foreach my $num_purchased (@num_purchased) {
	    if ($num_purchased->as_text() =~ /([0-9,]+)\s* bought/i) {
		my $clean_num_purchased = $1;
		$clean_num_purchased =~ s/,//g;
		$deal->num_purchased($clean_num_purchased);
		last;
	    }
	}


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:image")});

	if (@image && $image[0]->attr('content') =~ /^http:/i) {
	    $deal->image_urls($image[0]->attr('content'));
	}

	my @slideshow = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "slideshow")});

	if (@slideshow) {
	    my @images = $slideshow[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src'))
			&& $_[0]->attr('src') =~ /^\/dealinfo/});

	    foreach my $image (@images) {
		my $image_url =
		    "http://bargainbee.com".$image->attr('src');
		$deal->image_urls($image_url);
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
		    $_[0]->attr('src') =~ /nolongeravailable/i});

	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~
		/TimeCountdown\(([0-9]+),\s*([0-9]+),\s*([0-9]+)/) {
		my $days = $1;
		my $hours = $2;
		my $minutes = $3;
		my $offset = ($days*3600*24) + ($hours*3600) + $minutes*60;

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Bargain Bee puts expiry information in fine print
	if (defined($deal->fine_print())) {
	    if ($deal->fine_print() =~ 
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
	}
	
	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "map-pane"});

	if (@biz_info) {
	    # Name
	    my @name = $biz_info[0]->look_down(
		sub{$_[0]->tag() =~ /^h[0-9]$/i});
	    if (@name) {
		$deal->name($name[0]->as_text());
	    }

	    # Website
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
	    foreach my $website (@website) {
		if ($website->as_text() =~ /website/i) {
		    $deal->website($website->attr('href'));
		    last;
		}
	    }

	    # Phone
	    if ($biz_info[0]->as_HTML() =~
		/<br[^>]*>([0-9\(\)\-\.\s]{10,20})/) {
		my $phone = $1;
		$phone =~ s/\s+//g;
	    
		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($phone) -length($tmpphone) <=4) {
		    $deal->phone($phone);
		}
	    }
	    
	    # Address
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
	    foreach my $address (@addresses) {
		if ($address->attr('href') =~ /addr=(.*)/) {
		    my $clean_address = $1;
		    $clean_address =~ s/%[0-9][A-Z]/ /g;
		    $clean_address =~ s/\+/ /g;
		    $deal->addresses($clean_address);
		}
	    }
	}



	$tree->delete();
    }
  
    1;
}
