#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#
{
    package schwaggleextractor;
    
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


	my @title = $tree->look_down(sub{$_[0]->tag() eq 'title'});
	if (@title) {
	    my $clean_title = $title[0]->as_text();
	    $clean_title =~ s/\s*by\s*Active.com//;
	    $deal->title($clean_title);
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "price-box")});
	if (@price && $price[0]->as_text() =~ /([0-9,]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'strong' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "value")});
	if (@value && $value[0]->as_text() =~ /([0-9,]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}

        my @text = $tree->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
                    ($_[0]->attr('class') eq "box-alt")});

        if (@text) {
            my $clean_text = $text[0]->as_HTML();
            $clean_text =~ s/<\/?div[^>]*>//g;
            $clean_text =~ s/<h3>[^<]*<\/h3>//gi;
            $deal->text($clean_text);
        }



        my @fine_print = $tree->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
                    ($_[0]->attr('class') eq "column")});

        foreach my $fine_print (@fine_print) {
            if ($fine_print->as_HTML() =~ /fine\s*print/i) {
                $fine_print = $fine_print->as_HTML();
                $fine_print =~ s/<\/?div[^>]*>//g;
                $deal->fine_print($fine_print);
            }
        }


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "bought")});

	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)/) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}

	my @image = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('property') eq "og:image")});

	if (@image) {
	    $deal->image_urls($image[0]->attr('content'));
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "availability"});

	if (@expired && $expired[0]->as_text() =~ /expired/i) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "timerSource")});

	    if (@deadline &&
		$deadline[0]->as_text() =~
		/([A-Z][a-z]+)\s*([0-9]{2}),\s*([0-9]{4})\s*([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/)
	    {
		my $month = $1;
		my $day = $2;
		my $year = $3;
		my $hour = $4;
		my $minute = $5;
		if (defined($month_map{$month})) {
		    my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					   $year, $month_map{$month}, $day,
					   $hour, $minute);
		    $deal->deadline($deadline);
		}
	    }
	}


	my @expires = $tree->look_down(
		sub{$_[0]->tag() =~ /h[0-9]/i &&
			defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "expires"});

	if (@expires &&
	    $expires[0]->as_text() =~ /([0-9]{1,2}).([0-9]{1,2}).([0-9]{4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    
	    my $expires = sprintf("%d-%02d-%02d 01:01:01",
				  $year, $month, $day);
	    $deal->expires($expires);
	}
	
	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "map-holder"});

	if (@biz_info) {
	    # Name
	    my @name = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq "strong"});
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

	    # Address/phone
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq "address"});
	    foreach my $address (@addresses) {
		my $address = $address->as_HTML();
		
		$address =~ s/<a[^>]*>[^<]*<\/a>//g;
		$address =~ s/<[^>]*>/ /g;
		$address =~ s/\|//g;
		
		my $phone;
		if ($address =~ /(.*[A-Z]{2}\s*,*[0-9]{5})(.*)/) {
		    $deal->addresses($1);
		    $phone = $2;
		} elsif ($address =~ /(.*,\s*)([A-Z]{2})(.*)/ &&
			 genericextractor::isState($2)) {
		    $deal->addresses($1.$2);
		    $phone = $3;
		}
		
		if (defined($phone)) {
		    $phone =~ s/\s+//g;
		    my $tmpphone = $phone;
		    $tmpphone =~ s/[^0-9]//g;
		    if (length($tmpphone) > 8 &&
			length($phone) -length($tmpphone) <=4) {
			$deal->phone($phone);
		    }
		}
	    }
	}



	$tree->delete();
    }
  
    1;
}
