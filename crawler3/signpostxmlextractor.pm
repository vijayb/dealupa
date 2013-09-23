#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#
{
    package signpostxmlextractor;
    
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

	    my $url = $deal_tag->look_down(sub{$_[0]->tag() eq "url"});
	    if (defined($url)) {
		$deal->url($url->as_text());
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

	    #my $num_purchased =
	    #$deal_tag->look_down(sub{$_[0]->tag() eq "quantity_sold"});
	    #$num_purchased =~ s/\s,//g;
	    #if (defined($num_purchased) && $num_purchased =~ /^[0-9]+$/) {
	    #$deal->num_purchased($num_purchased->as_text());
	    #}

	    my $text = $deal_tag->look_down(sub{$_[0]->tag() eq "description"});
	    if (defined($text)) {
		$deal->text($text->as_text());
	    }

	    my $image_url =
		$deal_tag->look_down(sub{$_[0]->tag() eq "large_image_url"});
	    if (defined($image_url)) {
		if ($image_url->as_text() =~ /^\s*http/i) {
		    $deal->image_urls($image_url->as_text());
		} else {
		    $deal->image_urls("http://signpost.com".$image_url->as_text());
		}
	    }

	    my $deadline = 
		$deal_tag->look_down(sub{$_[0]->tag() eq "sale_end_date"});
	    if (defined($deadline) && $deadline->as_text() =~
		/([0-9]{4}-[0-9]{2}-[0-9]{2})/) {
		$deal->deadline($1." 01:01:01");
	    }

	    my $expires = $deal_tag->look_down(
		sub{$_[0]->tag() eq "redemption_end_date"});
	    if (defined($expires) && $expires->as_text() =~
		/([0-9]{4}-[0-9]{2}-[0-9]{2})/) {
		$deal->expires($1." 01:01:01");
	    }


	    my @locations =
		$deal_tag->look_down(sub{$_[0]->tag() eq "location"});
	    foreach my $location (@locations) {
		my $name =
		    $location->look_down(sub{$_[0]->tag() eq "name"});
		if (defined($name)) {
		    $deal->name($name->as_text());
		}

		my $website =
		    $location->look_down(sub{$_[0]->tag() eq "website"});
		if (defined($website) && length($website->as_text()) > 5) {
		    if ($website->as_text() =~ /^http/i) {
			$deal->website($website->as_text());
		    } else {
			$deal->website("http://".$website->as_text());
		    }
		}

		my $phone =
		    $location->look_down(sub{$_[0]->tag() eq "phone_number"});
		if (defined($phone)) {
		    $deal->phone($phone->as_text());
		}

		my $street =
		    $location->look_down(sub{$_[0]->tag() eq "street"});
		my $city =
		    $location->look_down(sub{$_[0]->tag() eq "city"});
		my $state =
		    $location->look_down(sub{$_[0]->tag() eq "state"});
		my $zip =
		    $location->look_down(sub{$_[0]->tag() eq "zip"});

		my $address = "";
		if (defined($street)) {
		    $address = $address.$street->as_text()." ";
		}
		if (defined($city)) {
		    $address = $address.$city->as_text()." ";
		}
		if (defined($state)) {
		    $address = $address.$state->as_text()." ";
		}
		if (defined($zip)) {
		    $address = $address.$zip->as_text()." ";
		}
		$address =~ s/\s+$//;
		$address =~ s/http:\/\/[^\/]*\///i;
		if (length($address) > 0 && $address !~ /33\s*hudson/i) {
		    $deal->addresses($address);
		}
	    }


	    push(@deals, $deal);
	}


	$tree->delete();

	return @deals;
    }
  
    1;
}
