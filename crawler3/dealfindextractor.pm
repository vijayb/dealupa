#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) January, 2012
#
{
    package dealfindextractor;
    
    use strict;
    use warnings;
    use deal;
    use genericextractor;
    use HTML::TreeBuilder;
    use Encode;


    my %month_map = (
	"Jan" => 1,
	"Feb" => 2,
	"Mar" => 3,
	"Apr" => 4,
	"May" => 5,
	"Jun" => 6,
	"Jul" => 7,
	"Aug" => 8,
	"Sep" => 9,
	"Oct" => 10,
	"Nov" => 11,
	"Dec" => 12
    );

    sub extract {
	my $tree = HTML::TreeBuilder->new;
	my $deal = shift;
	my $deal_content_ref = shift;
	
	$tree->parse(decode_utf8 $$deal_content_ref);
	$tree->eof();


	my @titlediv = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "mainDeal")});

	if (@titlediv) {
	    my @title = $titlediv[0]->look_down(
		sub{$_[0]->tag() eq 'h1'});
	    if (@title) {
		$deal->title($title[0]->as_text());
	    }

	    my @subtitle = $titlediv[0]->look_down(
		sub{$_[0]->tag() eq 'h2'});
	    if (@subtitle) {
		$deal->subtitle($subtitle[0]->as_text());
	    }
	}


	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "dealPrice")});
	if (@price && $price[0]->as_text() =~ /\$([0-9,\.]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	}

	my @value = $tree->look_down(
	    sub{defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "pricePanel"});
	if (@value && $value[0]->as_text() =~ /\$([0-9,\.]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);
	}



	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "dealAbout")});

	if (@text) {
	    my $clean_text = $text[0]->as_HTML();
	    $clean_text =~ s/<\/?div[^>]*>//g;
	    $deal->text($clean_text);
	}



	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "whatKnow")});

	if (!@fine_print) {
	    @fine_print = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "travelDesc")});
	}

	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


       my @num_purchased = $tree->look_down(
            sub{defined($_[0]->attr('id')) &&
                    ($_[0]->attr('id') eq "purchases")});

        if (@num_purchased &&
            $num_purchased[0]->as_text() =~ /([0-9,]+)/i) {
            my $num_purchased = $1;
            $num_purchased =~ s/,//g;
            $deal->num_purchased($num_purchased);
        }



	my @image_a = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "dealImage")});

	if (!@image_a) {
	    @image_a = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "slider")});
	}

	if (@image_a) {
	    my @image = $image_a[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src'))});

	    if (@image) {
		$deal->image_urls($image[0]->attr('src'));
	    }
	}


	if ($tree->as_HTML() =~ /var\s*DealSoldOut[^A-Za-z]*true/i ||
	    $tree->as_HTML() =~ /var\s*DealEnded[^A-Za-z]*true/i) {
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my $seconds_total;
	    my $seconds_elapsed;
	    my $offset;
	    if ($tree->as_HTML() =~ /DealSeconds_Total[^0-9]+([0-9]+)/) {
		$seconds_total = $1;
	    }
	    if ($tree->as_HTML() =~ /DealSeconds_Elapsed[^0-9]+([0-9]+)/) {
		$seconds_elapsed = $1;
	    }

	    if (defined($seconds_total) && defined($seconds_elapsed)) {
		$offset = $seconds_total - $seconds_elapsed;
	    }

	    if (defined($offset)) {
		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime(time()+$offset))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);
		$deal->deadline($deadline);
	    }
	}


	# Deal find
	if (defined($deal->fine_print())) {
	    if ($deal->fine_print() =~ 
		/Expires\s*on\s*([0-9]{1,2})-([A-Za-z]{3})-([0-9]{4})/i) {
		my $day = $1;
		my $month = $2;
		my $year = $3;
		
		if (defined($month_map{$month})) {
		    my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
					  $month_map{$month}, $day);
		    $deal->expires($expires);
		}
	    }

	    # Deal find puts the phone number in the fine print
	    if ($deal->fine_print() =~/call\s*([0-9][^\s]+)/) {
		my $phone = $1;
	    
		my $tmpphone = $phone;
		$tmpphone =~ s/[^0-9]//g;
		if (length($tmpphone) > 8 &&
		    length($phone) -length($tmpphone) <=4) {
		    $phone =~ s/[^0-9]//g;
		    $deal->phone($phone);
		}
	    }
	}
	
	
	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "featMiddle"});

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
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }

	    
	    # Address
	    my @addresses = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /maps\?q=/});

	    if (!@addresses) {
		my @street = $biz_info[0]->look_down(
		    sub{defined($_[0]->attr('itemprop')) && 
			    $_[0]->attr('itemprop') eq "description"});


		my @locality = $biz_info[0]->look_down(
		    sub{defined($_[0]->attr('itemprop')) && 
			    $_[0]->attr('itemprop') eq "addressLocality"});


		my @zip = $biz_info[0]->look_down(
		    sub{defined($_[0]->attr('itemprop')) && 
			    $_[0]->attr('itemprop') eq "postalCode"});
		
		if (@street && @locality && @zip) {
		    my $address = $street[0]->as_text().",".
			$locality[0]->as_text().",".
			$zip[0]->as_text();
		    $deal->addresses($address);

		}
	    }

	    foreach my $address (@addresses) {
		if ($address->attr('href') =~ /maps\?q=([^&=]+)/) {
		    my $clean_address = $1;
		    $clean_address =~ s/%[0-9][A-Z]/ /g;
		    $clean_address =~ s/\+/ /g;
		    $deal->addresses($clean_address);
		}
	    }
	}



	$tree->delete();
    }
  
    1;
}
