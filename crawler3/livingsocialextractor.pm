#!/usr/bin/perl -w
# Copyright (c) 2011, 2012 All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011 - March, 2012
#
{
    package livingsocialextractor;

    use strict;
    use warnings;
    use deal;
    use crawlerutils;
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


	my @title_div = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal-title")});
	if (@title_div) {
	    my @title = $title_div[0]->look_down(
		sub{$_[0]->tag() =~ /h[0-9]/});
	    if (@title) {
		$deal->title($title[0]->as_text());
	    }

	    my @subtitle = $title_div[0]->look_down(
		sub{$_[0]->tag() eq "p"});

	    if (@subtitle) {
		$deal->subtitle($subtitle[0]->as_text());
	    }
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^deal-price/)});

	if (@price && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}


	# LivingSocial doesn't directly give us the value, only
	# the discount percentage. We have to infer the value.
	if (defined($deal->price())) {
	    my @percent = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "percentage")});
	    
	    if (@percent && $percent[0]->as_text() =~ /([1-9][0-9]?)%/) {
		my $percent = $1/100.0;
		my $value = sprintf("%.0f", $deal->price()/(1.0-$percent));
		$deal->value($value);
	    }
	}

	my @num_purchased = $tree->look_down(
	    sub{defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal-purchase-count")});

	if (!@num_purchased) {
	    @num_purchased = $tree->look_down(
		sub{defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "purchased")});
	}

	if (@num_purchased &&
	    $num_purchased[0]->as_text() !~ /left/i &&
	    $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}

	
	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal-over-alert")});
	if (@expired) {
	    $deal->expired(1);
	}

	@expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /sold.{1,3}out/i)});

	if (@expired) {
	    $deal->expired(1);
	}

	if (!defined($deal->expired()) && !$deal->expired()) {
	    my $deadline;
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "countdown")});
	    if (@deadline) {
		$deadline = $deadline[0]->as_text();
		$deadline =~ s/\s//g;
	    } else {
		@deadline = $tree->look_down(
		    sub{defined($_[0]->attr('class')) &&
			    ($_[0]->attr('class') =~ /clearfix\s*deal-info/)});
		if (@deadline) {
		    $deadline = $deadline[0]->as_text();
		}
	    }

	    my $days = 0;
	    my $hours = 0;
	    my $minutes = 0;
	    my $seconds = 0;
	    my $deadline_offset = 0;
	    # LivingSocial only gives us a countdown timer, not a date,
	    # so we have to infer it. It is either has the format
	    # "N days remaining" or e.g., 15:27:55 remaining.
	    # We compute the UTC time by adding the above countdown offset
	    # to the current UTC time. The greater the time between
	    # crawling the page and performing this calculation, the more
	    # inaccurate will be the computed deadline. But hopefully
	    # it won't be off by more than a few seconds.
	    if (defined($deadline)) {
		if ($deadline =~ /([0-9]{1,2})\s*days?/) {
		    $days = $1;
		} elsif ($deadline =~ /([0-9]{2}):([0-9]{2}):([0-9]{2})/) {
		    $hours = $1;
		    $minutes = $2;
		    $seconds = $3;
		}
		$deadline_offset = ($days*24*60*60)+($hours*60*60)+
		    ($minutes*60) + $seconds;
		
		if ($deadline_offset > 0) {
		    $deadline = crawlerutils::gmtNow($deadline_offset);
		    $deal->deadline($deadline);
		}
	    }
	}


	my @images = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('style')) &&
		    defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^slide/)});
	
	foreach my $image (@images) {
	    if ($image->attr('style') =~ /url\(\'?(http[^\'\)]+)/) {
		$deal->image_urls($1);
	    }
	}


	if (!@images) {
	    my @image = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			($_[0]->attr('property') eq "og:image") &&
			$_[0]->attr('content') =~ /^http/});
	    if (@image) {
		$deal->image_urls($image[0]->attr('content'));
	    }
	}
	

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "view-details-full")});
	if (!@text) {
	    @text = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "sfwt_full_1")});
	}

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $clean_text =~ s/<[^<]+>[^>]+<\/a>\s*$//;
	    $clean_text =~ s/<script[^>]*>//g;
	    $clean_text =~ s/\{\{[^\}]*\}\}>//g;
	    $deal->text($clean_text);
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "fine-print")});
	if (@fine_print) {
	    my $clean_fine_print = $fine_print[0]->as_HTML();
	    $clean_fine_print =~ s/<\/?div[^>]*>//g;
	    $clean_fine_print =~ s/<a[^>]+>[^<]+<\/a>//g;

	    $deal->fine_print($clean_fine_print);
	}


	if (defined($deal->fine_print()) &&
	    $deal->fine_print() =~
	    /promotional\svalue\sexpires\son\s([A-Z][a-z]+)\s+([0-9]{1,2}),\s+([0-9]{4})/i) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",
				      $year, $month_map{$month}, $day);
		
		$deal->expires($expires);
	    }
	}

	# Name
	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:merchant")});
	if (@name) {
	    $deal->name($name[0]->attr('content'));
	}

	# Website
	my @website_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "view-details-full")});
	if (!@website_container) {
	    @website_container = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "deal-description")});
	}

	if (@website_container) {
	    my @website = $website_container[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			($_[0]->attr('href') =~ /^http/)});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	}


	my @address_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /alpha\slocation/)});
	
	if (@address_container) {
	    my @addresses = $address_container[0]->look_down(
		sub{defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "address")});
	    
	    foreach my $address (@addresses) {
		if ($address->as_HTML() =~ /(<span.*?<span[^>]*>)/) {
		    my $clean_address = $1;
		    $clean_address =~ s/<[^>]*>/ /g;
		    $clean_address =~ s/\s+/ /g;
		    $clean_address =~ s/^\s*//;
		    $clean_address =~ s/\s*$//;
		    $deal->addresses($clean_address);
		}
		
		my @phone = $address->look_down(
		    sub{defined($_[0]->attr('class')) &&
			    ($_[0]->attr('class') eq "phone")});

		if (@phone) {
		    my $clean_phone = $phone[0]->as_text();
		    $clean_phone =~ s/\s*\|//;
		    $deal->phone($clean_phone);
		}
	    }
	}


	$tree->delete();
    }

    1;
}
