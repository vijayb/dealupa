#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) December, 2011
#
{
    package crowdseatsxmlextractor;
    
    use strict;
    use warnings;
    use deal;
    use XML::TreeBuilder;


    sub extract {
	my $xml_content_ref = shift;
	
	my $tree = XML::TreeBuilder->new;
	$tree->parse($$xml_content_ref);
	my @deal_tags = $tree->look_down(sub{$_[0]->tag() eq "deal"});

	my @deals;
	foreach my $deal_tag (@deal_tags) {
	    my $deal = deal->new();

	    my $url = $deal_tag->look_down(sub{$_[0]->tag() eq "deal_url"});
	    if (defined($url)) {
		$deal->url($url->as_text());
	    }

	    my $title = $deal_tag->look_down(sub{$_[0]->tag() eq "title"});
	    if (defined($title)) {
		$deal->title($title->as_text());
	    }

	    my $price = $deal_tag->look_down(sub{$_[0]->tag() eq "price"});
	    if (defined($price) && $price->as_text() =~ /([0-9\.,]+)/) {
		$price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }

	    my $value = $deal_tag->look_down(sub{$_[0]->tag() eq "value"});
	    if (defined($value) && $value->as_text() =~ /([0-9\.,]+)/) {
		$value = $1;
		$value =~ s/,//g;
		$deal->value($value);
	    }

	    my $num_purchased =
		$deal_tag->look_down(sub{$_[0]->tag() eq "quantity_sold"});
	    if (defined($num_purchased)) {
		$deal->num_purchased($num_purchased->as_text());
	    }

	    my $text =
		$deal_tag->look_down(sub{$_[0]->tag() eq "deal_text"});
	    if (defined($text)) {
		$deal->text($text->as_text());
	    }

	    my $fine_print =
		$deal_tag->look_down(sub{$_[0]->tag() eq "detail"});
	    if (defined($fine_print)) {
		$deal->fine_print($fine_print->as_text());
	    }

	    my $image_url =
		$deal_tag->look_down(sub{$_[0]->tag() eq "large_image_url"});
	    if (defined($image_url)) {
		$deal->image_urls($image_url->as_text());
	    }
	    
	    my $expired =
		$deal_tag->look_down(sub{$_[0]->tag() eq "sold_out"});
	    if (defined($expired) && $expired->as_text() !~ /false/i) {
		$deal->expired(1);
	    }

	    my $deadline = 
		$deal_tag->look_down(sub{$_[0]->tag() eq "end_date"});
	    if (defined($deadline) && $deadline->as_text() =~
		/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})T([0-9]{1,2}):([0-9]{1,2})/)
	    {
		my $year = $1;
		my $month = $2;
		my $day = $3;
		my $hour = $4;
		my $minute = $5;
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       $year, $month, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }

	    # Crowdseats is always activities and events (category 2)
	    $deal->category_id(2);


	    my $name =
		$deal_tag->look_down(sub{$_[0]->tag() eq "vendor_name"});
	    if (defined($name)) {
		$deal->name($name->as_text());
	    }

	    my $website =
		$deal_tag->look_down(sub{$_[0]->tag() eq "vendor_website_url"});
	    if (defined($website)) {
		$deal->website($website->as_text());
	    }

	    my $address =
		$deal_tag->look_down(sub{$_[0]->tag() eq "address"});
	    if (defined($address) && length($address->as_text()) > 10) {
		$deal->addresses($address->as_text());
	    }

	    push(@deals, $deal);
	}


	$tree->delete();

	return @deals;
    }
  
    1;
}
