#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) February, 2012
#
{
    package thrillistextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape;

    # We use this for setFBInfo:
    use dealsdbutils;

    my %month_map = (
	"Jan" => 1,
	"January" => 1,
	"Feb" => 2,
	"February" => 2,
	"Mar" => 3,
	"March" => 3,
	"Apr" => 4,
	"April" => 4,
	"May" => 5,
	"Jun" => 6,
	"June" => 6,
	"Jul" => 7,
	"July" => 7,
	"Aug" => 8,
	"August" => 8,
	"Sep" => 9,
	"Sept" => 9,
	"September" =>9,
	"Oct" => 10,
	"October" => 10,
	"Nov" => 11,
	"November" => 11,
	"Dec" => 12,
	"December" => 12
    );

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();

	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'h1'});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}


	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:description")});
	
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->attr('content'));
	}


	my @price = $tree->look_down(
	    sub{defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "rewardPrice")});

	if ($#price >=0 && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq "span" && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "fullPrice"});

	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "singleDescript")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $clean_text =~ s/<\/?span[^>]*>//g;
	    $deal->text($clean_text);
	}

	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "brassTacksContent")});

	if (@fine_print) {
	    my $clean_fine_print = $fine_print[0]->as_HTML();
	    $clean_fine_print =~ s/<\/?div[^>]*>//g;
	    $clean_fine_print =~ s/<\/?span[^>]*>//g;
	    $deal->fine_print($clean_fine_print);
	}


	my @image_container = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /slideshow/i)});

	if (@image_container) {
	    my @images = $image_container[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
			($_[0]->attr('src') =~ /^http/)});

	    foreach my $image (@images) {
		$deal->image_urls($image->attr('src'));
	    }
	} else {
	    my @image = $tree->look_down(
		sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
			defined($_[0]->attr('content')) &&
			$_[0]->attr('content') =~ /^http/ &&
			($_[0]->attr('property') eq "og:image")});
	    
	    if (@image) {
		$deal->image_urls($image[0]->attr('content'));
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "soldOut"});

	if (@expired) {
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{defined($_[0]->attr('id')) &&
			$_[0]->attr('id') eq "offerEnds"});

	    if (@deadline && $deadline[0]->as_text() =~ 
		/([0-9]{2}).([0-9]{2}).([0-9]{2})/) {
		my $month = $1;
		my $day = $2;
		my $year = 2000+$3;
		my $deadline = sprintf("%d-%02d-%02d 01:01:01",
				       $year, $month, $day);
		$deal->deadline($deadline);
	    }
	}


	# Thrillist has the expires information in the fineprint.
	if (defined($deal->fine_print())) {
	    if ($deal->fine_print() =~ 
		     /([A-Z][a-z]+)\s+([0-9]{1,2})[^0-9]*([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		
		if (defined($month_map{$month})) {
		    my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
					  $month_map{$month}, $day);
		    $deal->expires($expires);
		}
	    } elsif ($deal->fine_print() =~ 
		     /([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})/) {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		
		my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				      $month, $day);
		$deal->expires($expires);
	    }
	}
	

	# Name
	# Unfortunately Thrillist gives us no structure from which to extract
	# the name of the merchant, so we have to use a bunch of heuristics
	my @namephone = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "singleRedeem"});

	if (@namephone) {
	    if ($namephone[0]->as_text() =~ /[Cc]all\s([A-Z].*?)\sat\s*([^a-zA-Z\s]+)/) {
		my $name = $1;
		my $phone = $2;

		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($phone) - length($tmpphone) <=4) {
		    $phone =~ s/[^0-9]//g;
		    $deal->phone($phone);
		    $deal->name($name);
		}
	    } elsif ($namephone[0]->as_text() =~ /(hit|visit)\s([^\']*?)'s\swebsite/) {
		my $name = $2;
		if (length($name) > 3 && length($name) < 30) {
		    $deal->name($name);
		}
	    } elsif ($namephone[0]->as_text() =~ /at\s([A-Z].*?)\son/) {
		my $name = $1;
		$name =~ s/\([^\)]*\)//;
		if (length($name) > 3 && length($name) < 30) {
		    $deal->name($name);
		}
	    } elsif (defined($deal->title()) && $deal->title() =~
		     /(at|from|by)\s([A-Z].*)/) {
		my $name = $2;
		if ($name =~ /([A-Z][^\s]+)$/) {
		    $deal->name($name);
		}
	    } elsif (defined($deal->subtitle()) && $deal->subtitle() =~
		     /(at|from|by)\s([A-Z].*)/) {
		my $name = $2;
		if ($name =~ /([A-Z][^\s]+)$/) {
		    $deal->name($name);
		}
	    }

	    if (!defined($deal->phone())) {
		if ($namephone[0]->as_text() =~ /at\s([\(\)0-9\-\.\s]{9,17})/) {
		    my $phone = $1;
		    $phone =~ s/\s//g;
		    my $tmpphone = $phone;
		    $tmpphone =~ s/[^0-9]//g;
		    if (length($tmpphone) > 8 &&
			length($phone) - length($tmpphone) <=4) {
			$phone =~ s/[^0-9]//g;
			$deal->phone($phone);
		    }
		}
	    }


	    # Website
	    my @website = $namephone[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			defined($_[0]->attr('target')) &&
			$_[0]->attr('href') =~ /^http/});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }

	}
	
	# Website
	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->as_text() =~ /\s*website\s*/i &&
		    $_[0]->attr('href') =~ /^http/});
	if (@website) {
	    $deal->website($website[0]->attr('href'));
	}


	# Address
	my @addresses = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "mapAddress"});
	foreach my $address (@addresses) {
	    my $clean_address = $address->as_text();
	    $clean_address =~ s/\s+/ /g;
	    $clean_address =~ s/^\s+//;
	    $clean_address =~ s/\s+$//;
	    if (length($clean_address) > 5) {
		$deal->addresses($clean_address);
	    }
	}

	$tree->delete();
    }
  
    1;
}
