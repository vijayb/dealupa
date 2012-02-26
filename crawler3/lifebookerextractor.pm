#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package lifebookerextractor;
    
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

	# LifeBooker doesn't give us num_purchased info
	$deal->num_purchased(-1);

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h2' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "loot-title")});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^the-price/)});
	if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "regular-price")});
	if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}


	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "loot-description-copy")});

	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "what-you-need")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}



	my @images = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    defined($_[0]->attr('rel')) &&
		    ($_[0]->attr('rel') eq "lightbox-loot_photo_gallery")});
	foreach my $image (@images) {
	    my $stripped_image = $image->attr('href');
	    $stripped_image =~ s/\?.*$//;
	    $deal->image_urls($stripped_image);
	}

	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('style')) &&
		    defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "expired")});
	if (@expired && length($expired[0]->attr('style')) == 0) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    if ($tree->as_HTML() =~ /countdown\(\{\s*until:\s+([0-9]+)/) {
		my $deadline_seconds = $1;

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
                    (gmtime(time() + $deadline_seconds))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				    1900+$year, $month+1, $day,
				    $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# LifeBooker puts the expiry information in the fine print.
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /Valid\s+for\s+([0-9]+)\s+([a-z]+)/) {
	    my $num_periods = $1;
	    my $period_type = $2;

	    my $offset=0;

	    if ($period_type =~ /^week/) {
		$offset=$num_periods * 60*60*24*7;
	    }
	    if ($period_type =~ /^month/) {
		$offset=$num_periods * 60*60*24*30;
	    }
	    if ($period_type =~ /^year/) {
		$offset=$num_periods * 60*60*24*365;
	    }

	    my ($year, $month, $day);
	    ($year, $month, $day) = (gmtime(time() + $offset))[5,4,3];
    
	    my $expires = sprintf("%d-%02d-%02d 01:01:01",
				  1900+$year, $month+1, $day);
	    $deal->expires($expires);
	}

	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "sp_details"});

	if (@biz_info) {
	    # Name:
	    if ($biz_info[0]->as_HTML() =~ /<\/h3>\s*([^<]+)/) {
		$deal->name($1);
	    }

	    my @a_tags = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

	    foreach my $a_tag (@a_tags) {
		# Website:
		if ($a_tag->as_text() =~ /website/i) {
		    $deal->website($a_tag->attr('href'));
		}

		# Addresses:
		if ($a_tag->as_text() =~ /map/i &&
		    $a_tag->attr('href') =~ /maps.google.com\/\?q=([^\"]+)/) {
		    $deal->addresses($1);
		}
	    }
	}


	$tree->delete();
    }
  
    1;
}
