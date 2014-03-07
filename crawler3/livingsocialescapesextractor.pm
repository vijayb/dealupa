#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) October, 2011
#
{
    package livingsocialescapesextractor;
    
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

	my @escapes_title = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "header")});
	if (@escapes_title) {
	    my @title = $escapes_title[0]->look_down(sub{$_[0]->tag() eq 'h1'});
	    if (@title) {
		$deal->title($title[0]->as_text());
	    }
	    my @subtitle =
		$escapes_title[0]->look_down(sub{$_[0]->tag() eq 'h3'});
	    if (@subtitle) {
		my $clean_subtitle = $subtitle[0]->as_text();
		$clean_subtitle =~ s/[^A-Za-z0-9\s,.].*$//;
		if (length($clean_subtitle) > 3) {
		    $deal->subtitle($clean_subtitle);
		}
	    }	    
	}

	my @buy_box = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal-buy-box" ||
		     $_[0]->attr('id') eq "big-price")});

	if (@buy_box) {
	    if ($buy_box[0]->as_HTML() =~ />([0-9\.]+)<sup/) {
		$deal->price($1);
	    }

	    if ($buy_box[0]->as_HTML() =~ />\$([0-9\.]+)<\/strike/) {
		$deal->value($1);
	    }

	    my @price = $buy_box[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') =~ /^deal-price/)});
	    if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }
	    
	    my @value = $buy_box[0]->look_down(
		sub{$_[0]->tag() eq 'p' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "original-price")});
	    if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
		my $value = $1;
		$value =~ s/,//g;
		$deal->value($value);
	    }

	    my @num_purchased = $buy_box[0]->look_down(
		sub{$_[0]->tag() eq 'li' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "purchased") &&
			$_[0]->as_text() !~ /left/i});
	    if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
		my $num_purchased = $1;
		$num_purchased =~ s/,//g;
		$deal->num_purchased($num_purchased);
	    }
	}
	



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && 
		    ((defined($_[0]->attr('class')) &&
		     $_[0]->attr('class') eq "description") ||
		     (defined($_[0]->attr('id')) &&
		     $_[0]->attr('id') eq "hotel-description"))});
	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $deal->text($text);

	    # LivingSocial puts the website in the text with a target=_blank
	    my @website = $text[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /^http/ &&
			defined($_[0]->attr('target')) &&
			$_[0]->attr('target') =~ /blank/});
	    
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "fine-print")});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @images = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('style')) &&
		    defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "slide")});
	if (@images) {
	    foreach my $image_container (@images) {
		my @image = $image_container->look_down(
		    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			    ($_[0]->attr('src') =~ /^\/\//)});
		
		if (@image) {
		    my $image_url = "http:".$image[0]->attr('src');
		    $deal->image_urls($image_url);
		}
	    }
	} else {
	    my @image_container = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "other-images")});
	    if (@image_container) {
		my @images = $image_container[0]->look_down(
		    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			    ($_[0]->attr('src') =~ /^http/)});

		my $count = 0;
		foreach my $image (@images) {
		    $deal->image_urls($image->attr('src'));
		    $count++;
		    if ($count >= 7) {
			last;
		    }
		}
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /sold.{1,3}out/i)});

	if (@expired) {
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'li' && defined($_[0]->attr('id')) &&
			defined($_[0]->attr('data-ends-at')) &&
			($_[0]->attr('id') eq "time-left")});
	    if (@deadline &&
		$deadline[0]->attr('data-ends-at') =~ /([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2})/) {
		my $deadline = $1;
		$deadline =~ s/T/ /g;
		$deal->deadline($deadline);
	    }
	}


	# LivingSocialEscapes puts the expiry information in the fine print.
	# This regex will only work for United States format. E.g.,
	# April 30, 2012. In Australia they do 1st February, 2012
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /expires\s+on\s+([A-Z][a-z]+)\s+([0-9]{1,2}),\s+([0-9]{4})/i) {
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
		    defined($_[0]->attr('data-merchant')) &&
		    ($_[0]->attr('class') =~ /^deal-description/)});

	if (@name) {
	    my $name = $name[0]->attr('data-merchant');
	    $name =~ s/\s+$//;
	    $name =~ s/-.*+$//;
	    $deal->name($name);
	} else {
	    @name = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "property-name")});
	    if (@name) {
		my $name = $name[0]->as_text();
		$deal->name($name);
		if (defined($deal->text()) && $deal->text() =~
		    /href=\"(http[^\"]+)\">/) {
		    $deal->website($1);
		}
	    }
	}



	my @location = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /^location/});

	if (@location) {
	    my @meta = $location[0]->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "meta"});
	    
	    if (@meta) {
		my $address = $meta[0]->as_HTML();
		$address =~ s/<span\s+class=[\'\"]phone[\'\"]>[^<]+<\/span>//;
		$address =~ s/<[^>]+>//g;
		$address =~ s/\s*get\s+directions\s*//gi;
		$deal->addresses($address);

		my @phone = $location[0]->look_down(
		    sub{$_[0]->tag() eq 'span' &&
			    defined($_[0]->attr('class')) &&
			    $_[0]->attr('class') eq "phone"});

		if (@phone) {
		    my $phone = $phone[0]->as_text();
		    $phone =~ s/[^0-9]//g;
		    $deal->phone($phone);
		}
	    }
	} else {
	    my @address =  $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "street_1"});
	    if (@address) {
		my $clean_address = $address[0]->as_HTML();
		$clean_address =~ s/<[^>]*>/ /g;
		$clean_address =~ s/^\s*//;
		$clean_address =~ s/\s*$//;
		$deal->addresses($clean_address);
	    }
	}


	$tree->delete();
    }
  
    1;
}
