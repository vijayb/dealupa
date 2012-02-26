#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#
{
    package homerunextractor;
    
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

	$deal->affiliate_url($deal->url()."?_a=the-dealmix");

	my @title = $tree->look_down(sub{$_[0]->tag() eq 'title'});
	if (@title) {
	    $deal->title($title[0]->as_text());
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "display-current-deal-price")});
	if (@price && $price[0]->as_text() =~ /([0-9,\.]+)/) {
	    my $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /display-current-deal-value/)});
	if (@value && $value[0]->as_text() =~ /([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}


	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^description/)});

	my $clean_text = "";
	foreach my $text (@text) {
	    $clean_text = $clean_text.$text->as_HTML();
	}

	if (length($clean_text) > 0) {
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}



	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~ /^fine-print/)});

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "standard-middle")});

	if (@num_purchased &&
	    $num_purchased[0]->as_text() =~ /([0-9,]+)\s*bought/i) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}

	my @images = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('class')) &&
		    defined($_[0]->attr('src')) &&
		    ($_[0]->attr('class') eq "deal")});
	foreach my $image (@images) {
	    $deal->image_urls($image->attr('src'));
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /section\s*ended/i});

	if (@expired) {
	    $deal->expired(1);
	}



	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{defined($_[0]->attr('class')) && 
			defined($_[0]->attr('data-time-left')) && 
			$_[0]->attr('class') eq "clock"});

	    if (@deadline &&
		$deadline[0]->attr('data-time-left') =~ /([0-9]+)/) {
		my $offset_seconds = $1;

		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset_seconds))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Home Run puts expires information in the fine_print
	if (defined($deal->fine_print()) &&
	    $deal->fine_print() =~
	    /expires[^0-9]*([0-9]{1,2}).([0-9]{1,2}).([0-9]{2,4})/i) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    if (length($year) == 2) {
		$year = $year + 2000;
	    }
	    my $expires = sprintf("%d-%02d-%02d 01:01:01",
				  $year, $month, $day);
	    $deal->expires($expires);
	}
	
	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') =~ /^merchant-info/});

	if (@biz_info) {
	    # Name
	    my @name = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /^\//});
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


	    # Addresses
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /maps.google/});
	    
	    foreach my $address (@addresses) {
		if ($address->attr('href') =~ /q=(.*)/) {
		    my $clean_address = $1;
		    $clean_address =~ s/%[0-9][A-Z]/ /g;
		    $clean_address =~ s/\+/ /g;
		    $deal->addresses($clean_address);
		}
	    }

	    # Phone:
	    if ($biz_info[0]->as_HTML() =~ />([0-9\s\(\)\.-]{10,20})</) {
		my $phone = $1;
		$phone =~ s/\s+//g;
		
		# If there are multiple phone numbers they do
		# 1) (206) 344-3443
		# 2) (206) 555-5555
		# We want to remove any pre-pended number
		$phone =~ s/^[0-9]\)//;
		
		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($phone) -length($tmpphone) <=4) {
		    $deal->phone($phone);
		}
	    }


	}



	$tree->delete();
    }
  
    1;
}
