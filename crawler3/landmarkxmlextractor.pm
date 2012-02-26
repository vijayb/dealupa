#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) December, 2011
#
{
    package landmarkxmlextractor;
    
    use strict;
    use warnings;
    use deal;
    use XML::TreeBuilder;
    use crawlerutils;


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
		$url = $url->as_text();
		$url =~ s/\s//g;
		$deal->url($url);
	    }

	    my $title = $deal_tag->look_down(sub{$_[0]->tag() eq "title"});
	    if (defined($title)) {
		$title = $title->as_text();
		$title =~ s/^\s*//;
		$title =~ s/\s*$//;
		$title =~ s/\s+/ /g;
		$deal->title($title);
	    }

	    my $price = $deal_tag->look_down(sub{$_[0]->tag() eq "price"});
	    if (defined($price)) {
		my $price = $price->as_text();
		$price =~ s/\$//g;
		$price =~ s/,//g;
		$price =~ s/\s//g;
		$deal->price($price);
	    }

	    my $value = $deal_tag->look_down(sub{$_[0]->tag() eq "value"});
	    if (defined($value)) {
		my $value = $value->as_text();
		$value =~ s/\$//g;
		$value =~ s/,//g;
		$value =~ s/\s//g;
		$deal->value($value);
	    }

	    my $num_purchased =
		$deal_tag->look_down(sub{$_[0]->tag() eq "quantity_sold"});
	    if (defined($num_purchased) &&
		$num_purchased->as_text() =~ /([0-9]+)/) {
		$deal->num_purchased($1);
	    }

	    my $text = $deal_tag->look_down(sub{$_[0]->tag() eq "description"});
	    if (defined($text)) {
		my $text = $text->as_text();
		$text =~ s/^\s*//;
		$text =~ s/\s*$//;
		$text =~ s/\s+/ /g;
		$deal->text($text);
	    }

	    my $fine_print = $deal_tag->look_down(sub{$_[0]->tag() eq "dealfineprint"});
	    if (defined($fine_print)) {
		my $fine_print = $fine_print->as_text();
		$fine_print =~ s/^\s*//;
		$fine_print =~ s/\s*$//;
		$fine_print =~ s/\s+/ /g;
		$deal->fine_print($fine_print);
	    }

	    my $image_url =
		$deal_tag->look_down(sub{$_[0]->tag() eq "large_image_url"});
	    if (defined($image_url)) {
		$image_url = $image_url->as_text();
		$image_url =~ s/\s//g;
		$deal->image_urls($image_url);
	    }

	    my $expired = 
		$deal_tag->look_down(sub{$_[0]->tag() eq "sold_out"});
	    if (defined($expired) && $expired->as_text() =~ /true/) {
		$deal->expired(1);
	    }

	    my $upcoming = 
		$deal_tag->look_down(sub{$_[0]->tag() eq "tipped"});
	    if (defined($upcoming) && $upcoming->as_text() =~ /false/) {
		$deal->upcoming(1);
	    }

	    my $deadline = 
		$deal_tag->look_down(sub{$_[0]->tag() eq "end_date"});
	    if (defined($deadline)) {
		my $deadline = $deadline->as_text();
		$deadline =~ s/^\s*//;
		$deadline =~ s/\s*$//;
		$deadline =~ s/\s+/ /g;		
		if (crawlerutils::validDatetime($deadline)) {
		    $deal->deadline($deadline);
		}
	    }

	    my $expires = 
		$deal_tag->look_down(sub{$_[0]->tag() eq "expiration_date"});
	    if (defined($expires)) {
		my $expires = $expires->as_text();
		$expires =~ s/^\s*//;
		$expires =~ s/\s*$//;
		$expires =~ s/\s+/ /g;		
		if (crawlerutils::validDatetime($expires)) {
		    $deal->expires($expires);
		}
	    }


	    my $name =
		$deal_tag->look_down(sub{$_[0]->tag() eq "vendor_name"});

	    if (defined($name) && length($name->as_text()) > 0) {
		$name = $name->as_text();
		$name =~ s/^\s*//;
		$name =~ s/\s*$//;
		$name =~ s/\s+/ /g;		
		$deal->name($name);
	    }

	    my $website = $deal_tag->look_down(
		sub{$_[0]->tag() eq "vendor_website_url"});

	    if (defined($website) && length($website->as_text()) > 0) {
		$website = $website->as_text();
		$website =~ s/^\s*//;
		$website =~ s/\s*$//;
		$website =~ s/\s+/ /g;
		$deal->website($website);
	    }

	    my $address =
		$deal_tag->look_down(sub{$_[0]->tag() eq "vendor_address"});

	    if (defined($address) && length($address->as_text()) > 0) {
		$address = $address->as_text();
		$address =~ s/^\s*//;
		$address =~ s/\s*$//;
		$address =~ s/\s+/ /g;		

		$deal->addresses($address);
	    }



	    push(@deals, $deal);
	}


	$tree->delete();

	return @deals;
    }
  
    1;
}
