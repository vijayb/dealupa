#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
{
    package bloomspotextractor;
    
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

	# Bloomspot doesn't provide this information on its pages
	$deal->num_purchased(-1);


	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'title'});
	if (@title) {
	    my $clean_title = $title[0]->as_text();
	    $clean_title =~ s/\s*-?\s*bloomspot$//i;
	    $deal->title($clean_title)
	}
	
	my @subtitle_root = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "slideshowModule")});

	if (@subtitle_root) {
	    my @table = $subtitle_root[0]->look_down(sub{$_[0]->tag() eq 'table'});
	    if ($#table >= 1) {
		my @td = $table[1]->look_down(sub{$_[0]->tag() eq 'td'});

		if ($#td >=3) {
		    my $clean_subtitle = $td[3]->as_text();
		    $clean_subtitle =~ s/^\s*//;
		    $clean_subtitle =~ s/\s*$//;
		    $deal->subtitle($clean_subtitle);
		}
	    }
	}

	my @price = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "offer")});
	if (@price) {
	    my $price = $price[0]->as_text();
	    if ($price =~ /\$([0-9,]+)\s+for[^\$]+\$([0-9,]+)/) {
		$price = $1;
		my $value = $2;
		$price =~ s/,//g;
		$value =~ s/,//g;
		$deal->price($price);
		$deal->value($value);
	    } elsif ($price =~ /\$([0-9,]+)/) {
		$price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    }
	}

	# Some vacation deals don't have the "offer" div, so we have
	# to look for price elsewhere.
	if (!defined($deal->price())) {
	    if ($tree->as_HTML() =~ /'price':\s*'([0-9,\.]+)/) {
		my $price = $1;
		$price =~ s/,//g;
		$deal->price($price);
	    } elsif (defined($deal->title()) &&
		     $deal->title() =~ /\$([0-9,]+)[^\$]+\$([0-9,]+)/) {
		$deal->price($1);
		$deal->value($2);
	    }
	}
	

	
	my @text = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "panes")});
	if (@text) {
	    my $text = $text[0]->as_HTML();
	    $text =~ s/<\/?div[^>]*>//g;
	    $text =~ s/<table.*//;
	    $deal->text($text);

	    # Bloomspot puts the name of the business at the start of
	    # the deal text, in bold. E.g., <b>Emerald City Pilates and
	    # Personal Training</b> - by bloomspot Staff Writers"
	    if ($text =~ /<h1>([^<]+)/) {
		$deal->name($1);
	    } elsif ($text =~ /<b>([^<]+)/) {
		$deal->name($1);
	    }
	}


	my @fine_print = $tree->look_down(
	    sub{$_[0]->tag() eq 'td' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') =~
		     /rightcol_content\s*offer_details/)});
	if (@fine_print) {
	    my $fine_print = $fine_print[0]->as_HTML();
	    $fine_print =~ s/<\/?div[^>]*>//g;
	    $deal->fine_print($fine_print);
	}


	my @images = $tree->look_down(
	    sub{defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "slideshowNavigation")});
	if (@images) {
	    my @image_links = $images[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('rel'))});

	    foreach my $image_link (@image_links) {
		if ($image_link->attr('rel') =~ 
		    /(http:\/\/edge.bloomspot.com[^\'\"]+)/) {
		    $deal->image_urls($1);
		}
	    }
	}


	my @expired = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
		    ($_[0]->attr('src') =~ /offers_closed/i)});
	if (@expired) {
	    $deal->expired(1);
	}

	my @upcoming = $tree->look_down(
	    sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src')) &&
		    ($_[0]->attr('src') =~ /offers_upcoming/i)});
	if (@upcoming) {
	    $deal->upcoming(1);
	}


	if (!defined($deal->expired()) && !$deal->expired() &&
	    !defined($deal->upcoming()) && !$deal->upcoming()) {
	    my @deadline = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "dealtimer")});

	    if (@deadline) {
		my $deadline = $deadline[0]->as_HTML();
		$deadline =~ s/<[^>]*>/ /g;
		my $days = 0;
		my $hours = 0;
		my $minutes = 0;

		if ($deadline =~ /([0-9]+)\s+([0-9]+)\s+([0-9]+)/) {
		    $days = $1;
		    $hours = $2;
		    $minutes = $3;
		} elsif ($deadline =~ /([0-9]+)\s+([0-9]+)/) {
		    $hours = $1;
		    $minutes = $2;
		}

		if ($days + $hours + $minutes > 0) {
		    my $offset = ($days*24*3600) + ($hours*3600) + ($minutes*60);
		    
		    my ($year, $month, $day, $hour, $minute);
		    ($year, $month, $day, $hour, $minute) =
			(gmtime(time() + $offset))[5,4,3,2,1];
		    
		    $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
					1900+$year, $month+1, $day,
					$hour, $minute);
		    
		    $deal->deadline($deadline);
		}
	    }
	}

	# If we get the deadline field or expired field we want to 
	# set upcoming to 0. If we don't do this and the deal was
	# previously "upcoming", that field will remain 1 in the database
	# which prevents it being shown to users.
	if (defined($deal->deadline()) || defined($deal->expired())) {
	    $deal->upcoming(0);
	}

	# Bloomspot puts the expiry information in the fine print.
	# This regex will only work for United States format. E.g.,
	# August 9, 2012. In Australia they do 9 August, 2012
	if (defined($deal->fine_print()) && $deal->fine_print() =~ 
	    /([A-Z][a-z]+)\s+([0-9]{1,2}),\s+([0-9]{4})/) {
	    my $month = $1;
	    my $day = $2;
	    my $year = $3;
	    
	    if (defined($month_map{$month})) {
		my $expires = sprintf("%d-%02d-%02d 01:01:01",$year,
				      $month_map{$month}, $day);
		$deal->expires($expires);
	    }
	}




	my @biz_info = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "deal_location_address"});

	if (@biz_info) {
	    # Website
	    my @website = $biz_info[0]->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))
			&& $_[0]->attr('href') =~ /^http/});
	    if (@website) {
		$deal->website($website[0]->attr('href'));
	    }

	    # Phone
	    if ($biz_info[0]->as_HTML() =~ />([0-9\(\)\-\.\s]+)<br/) {
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
	    my @li_tags = $biz_info[0]->look_down(sub{$_[0]->tag() eq 'li'});
	    if (@li_tags) {
		foreach my $li_tag (@li_tags) {
		    if ($li_tag->as_text() =~ /[A-Za-z]+/ &&
			$li_tag->as_text() =~ /,/ &&
			$li_tag->as_text() !~ /between/i &&
			$li_tag->as_text() !~ /when/i &&
			$li_tag->as_text() !~ /neighborhood/i &&
			$li_tag->as_text() !~ /minutes/i) {
			$deal->addresses($li_tag->as_text());
			last;
		    }
		}
	    } else {
		my $address = $biz_info[0]->as_HTML();
		# get rid of a tags:
		$address =~ s/<a[^>]*>[^<]*<[^>]*>//;
		# get rid of phone numbers:
		$address =~ s/<[^>]*>[0-9\(\)\-\.\s]{10,30}<[^>]*>//;
		if ($address =~ />([^,]+,\s*[A-Z]{2})</) {
		    # Handles cases like Kenwood, CA
		    $deal->addresses($1);
		} else {
		    $address =~ s/<[^>]*>/ /g;
		    if ($address =~ /(.*[A-Z]{2}[^0-9]{1,3}[0-9]{5})/) {
			$address = $1;
			$deal->addresses($address);
		    }
		}
	    }
	}

	$tree->delete();
    }
  

    1;
}
