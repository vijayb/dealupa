#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) March, 2012
#
{
    package plumdistrictxmlextractor;
    
    use strict;
    use warnings;
    use deal;
    use XML::TreeBuilder;

    sub extract {
	my $xml_content_ref = shift;
	
	my $tree = XML::TreeBuilder->new;
	$tree->parse($$xml_content_ref);
	my @deal_tags = $tree->look_down(sub{$_[0]->tag() eq "item"});


	my @deals;
	foreach my $deal_tag (@deal_tags) {
	    my $deal = deal->new();

	    my $url = $deal_tag->look_down(sub{$_[0]->tag() eq "guid"});
	    if (defined($url)) {
		$deal->url($url->as_text());
	    }

	    my $affiliate_url = $deal_tag->look_down(sub{$_[0]->tag() eq "link"});
	    if (defined($affiliate_url)) {
		$deal->affiliate_url($affiliate_url->as_text());
	    }

	    my $title = $deal_tag->look_down(sub{$_[0]->tag() eq "title"});
	    if (defined($title)) {
		$deal->title($title->as_text());
	    }

	    my $price = $deal_tag->look_down(sub{$_[0]->tag() eq "price"});
	    if (defined($price)) {
		$deal->price($price->as_text());
	    }

	    my $value = $deal_tag->look_down(sub{$_[0]->tag() eq "value"});
	    if (defined($value)) {
		$deal->value($value->as_text());
	    }

	    my $text = $deal_tag->look_down(sub{$_[0]->tag() eq "sales_pitch"});
	    if (defined($text)) {
		$deal->text($text->as_text());
	    }

	    my $fine_print = $deal_tag->look_down(sub{$_[0]->tag() eq "terms"});
	    if (defined($fine_print)) {
		$deal->fine_print($fine_print->as_text());
	    }

	    my $image_url =
		$deal_tag->look_down(sub{$_[0]->tag() eq "image"});
	    if (defined($image_url)) {
		$deal->image_urls($image_url->as_text());
	    }

	    my $deadline = 
		$deal_tag->look_down(sub{$_[0]->tag() eq "end_date"});
	    if (defined($deadline) && $deadline->as_text() =~
		/([0-9]{4}-[0-9]{2}-[0-9]{2})/) {
		$deal->deadline($1." 00:00:00");
	    }

	    my $expires = $deal_tag->look_down(
		sub{$_[0]->tag() eq "voucher_expiration_date"});
	    if (defined($expires) && $expires->as_text() =~
		/([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{2})/) {
		$deal->expires($1." ".$2);
	    }


	    my $name = $deal_tag->look_down(sub{$_[0]->tag() eq "business_name"});
	    if (defined($name)) {
		$deal->name($name->as_text());
	    }
	    
	    my $content_id = $deal_tag->look_down(
		sub{$_[0]->tag() eq "deal_content_id"});
	    
	    # For debugging, to perform extraction on a particular element
	    # in the feed, just uncomment lines below and choose the content id:
	    #if (defined($content_id) && $content_id->as_text() == 20655) {
	    push(@deals, $deal);
	    #}

	}


	$tree->delete();

	return @deals;
    }
  
    1;
}
