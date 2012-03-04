#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) March, 2012
#
{
    package mamapediaxmlextractor;
    
    use strict;
    use warnings;
    use deal;
    use XML::TreeBuilder;
    use URI::Escape;

    # Couldn't get CJ download authentication to work, so just went with
    # standard hub page/deal page crawler setup...
    # This file isn't used, but keep it just in case we figure out
    # Commission Junction feed authentication.

    sub extract {
	my $xml_content_ref = shift;
	
	my $tree = XML::TreeBuilder->new;
	$tree->parse($$xml_content_ref);
	my @deal_tags = $tree->look_down(sub{$_[0]->tag() eq "item"});


	my @deals;
	foreach my $deal_tag (@deal_tags) {
	    my $deal = deal->new();

	    my $affiliate_url = $deal_tag->look_down(sub{$_[0]->tag() eq "buyurl"});
	    if (defined($affiliate_url)) {
		$deal->affiliate_url($affiliate_url->as_text());
		if ($affiliate_url->as_text() =~ /url=(.*)$/) {
		    my $url = uri_unescape($1);
		    if ($url =~ /^http/) {
			$deal->url($url);
		    } else {
			next;
		    }
		}
	    }

	    my $title = $deal_tag->look_down(sub{$_[0]->tag() eq "name"});
	    if (defined($title)) {
		my $clean_title = $title->as_text();
		$clean_title =~ s/^[^:]*://;
		$deal->title($clean_title);
	    }

	    my $price = $deal_tag->look_down(sub{$_[0]->tag() eq "price"});
	    if (defined($price)) {
		$deal->price($price->as_text());
	    }

	    my $value = $deal_tag->look_down(sub{$_[0]->tag() eq "retailprice"});
	    if (defined($value)) {
		$deal->value($value->as_text());
	    }

	    my $deadline = 
		$deal_tag->look_down(sub{$_[0]->tag() eq "enddate"});
	    if (defined($deadline) && $deadline->as_text() =~
		/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/) {
		$deal->deadline($3."-".$1."-".$2." 00:00:00");
	    }


	    push(@deals, $deal);
	}


	$tree->delete();

	return @deals;
    }
  
    1;
}
