#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#
{
    package juiceinthecityextractor;
    
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
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('name')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('name') eq "title")});
	if (@title) {
	    $deal->title($title[0]->attr('content'));
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "price-box")});
	if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "value")});
	if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "description")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}



	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "fine-print")});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	# Juice in the City doesn't give us num purchased (boo!)
	$deal->num_purchased(-1);


	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "deal-image")});

	if (@image) {
	    my @image_src = $image[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src'))
			&& $_[0]->attr('src') =~ /http/});

	    if (@image_src) {
		my $clean_image = $image_src[0]->attr('src');
		$clean_image =~ s/\?[^\?]*$//;
		$deal->image_urls($clean_image);
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /expired/i});

	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "ticker")});

	    if (@deadline &&
		$deadline[0]->as_HTML() =~
		/Date.UTC\(\s*([0-9]{4}),\s*([0-9]{1,2}),\s*([0-9]{1,2}),\s*([0-9]{1,2}),\s*([0-9]{1,2})/)
	    {
		my $year = $1;
		my $month = $2;
		my $day = $3;
		my $hour = $4;
		my $minute = $5;
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       $year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Juice in the City puts expiry information in fine print
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
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "address"});

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
	    if ($biz_info[0]->as_HTML() =~ /<p>([0-9\(\)\-\.\s]+)<br/) {
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
	    my @address = $biz_info[0]->look_down(sub{$_[0]->tag() eq "p"});
	    foreach my $p_tag (@address) {
		my $address = $p_tag->as_HTML();
		$address =~ s/<[^>]*>/ /g;
		if ($address =~ /(.*[A-Z]{2}\s*,*[0-9]{5})/) {
		    $deal->addresses($1);
		}
	    }
	}



	$tree->delete();
    }
  
    1;
}
