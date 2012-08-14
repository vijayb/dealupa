#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
# TODO: fully convert this to new-style treebuilder extractor
# rather than old style genericextractor extractor
{
    package grouponextractor;
    
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
	
	my @title = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "deal_title")});
	if (!@title) {
	    # GrouponGetaways puts the title in a different spot to 
	    # regular Groupon
	    @title = $tree->look_down(
		sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
			($_[0]->attr('class') eq "title_container")});
	}

	if (@title) {
	    my @header = $title[0]->look_down(sub{$_[0]->tag() eq 'h2'});
	    if (@header) {
		$deal->title($header[0]->as_text());
	    }
	}
	
	my @subtitle = $tree->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('name')) &&
		    defined($_[0]->attr('content')) &&
		    ($_[0]->attr('name') eq "description")});
	if (@subtitle) {
	    $deal->subtitle($subtitle[0]->attr('content'));
	}



	my $price_regex = "(<span\\s+class=[\'\"]price[\'\"].*)";
	my $price_filter = "<[^>]+>";
	my $price = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, $price_regex, $price_filter, "\\\$");
	if (defined($price) && $price =~ /([0-9,]*\.?[0-9]+)/) {
	    $price = $1;
	    $price =~ s/,//g;
	    $deal->price($price);  
	}


	my @value = $tree->look_down(
	    sub{$_[0]->tag() eq 'span' && defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "value")});
	if (!@value) {
	    @value = $tree->look_down(
		sub{defined($_[0]->attr('id')) &&
			($_[0]->attr('id') eq "discount_details_value")});
	}

	if (@value && $value[0]->as_text() =~ /([0-9,]*\.?[0-9]+)/) {
	    my $value = $1;
	    $value =~ s/,//g;
	    $deal->value($value);  
	}


	my $expired_regex1 = "<h5>This\\s+deal\\s+ended";
	my $expired_regex2 = "<h5>This\\s+deal\\s+sold\\s+out";
	if (&genericextractor::containsPattern($deal_content_ref,
					       $expired_regex1) ||
	    &genericextractor::containsPattern($deal_content_ref,
					       $expired_regex2))
	{
	    $deal->expired(1);
	}


	if (!defined($deal->expired()) && !$deal->expired()) {
	    my @deadline = $tree->look_down(
		sub{$_[0]->tag() eq 'li' &&
			defined($_[0]->attr('data-deadline')) &&
			defined($_[0]->attr('class')) &&
			$_[0]->attr('class') eq "groupon_countdown"});

	    if (@deadline &&
		$deadline[0]->attr('data-deadline') =~ /^([0-9]+)$/) {
		my $timestamp = $1;
		
		my ($year, $month, $day, $hour, $minute);
		($year, $month, $day, $hour, $minute) =
		    (gmtime($timestamp))[5,4,3,2,1];
		
		my $deadline = sprintf("%d-%02d-%02d %02d:%02d:01",
				       1900+$year, $month+1, $day,
				       $hour, $minute);

		$deal->deadline($deadline);
	    }
	}

	my $expires_regex = "Expires(.*)";
	my $expires = &genericextractor::extractFirstPatternMatched(
	    $deal_content_ref, $expires_regex);
	my ($year, $month, $day);
	if (defined($expires)) {
	    if ($expires =~ /([a-zA-Z]+)\s+([0-9]{1,2}),\s+([0-9]{4})/) {
		$day = $2;
		$month = $1;
		$year = $3;
		if (defined($month_map{$month})) {
		    $expires = sprintf("%d-%02d-%02d 01:01:01",
				       $year, $month_map{$month}, $day);
		    $deal->expires($expires);  
		}
	    } elsif ($expires =~ /in 1 year/) {
		($year, $month, $day) =
		    (localtime(time()+365*24*60*60))[5,4,3];
		$year += 1900;
		$expires = sprintf("%d-%02d-%02d 01:01:01",
				   $year, $month+1, $day);
		$deal->expires($expires);
	    }
	}

	my $text = &genericextractor::extractBetweenPatterns(
	    $deal_content_ref, "<div\\s+class=[\'\"]pitch_content[\'\"]",
	    "<\\\/div>");
	$deal->text($text);  

	my $fine_print = &genericextractor::extractBetweenPatterns(
	    $deal_content_ref, "The Fine Print<\\\/h3>",
	    "<\\\/div>", "^\\s+");
	if (defined($fine_print)) {
	    # Make relative URLs absolute:
	    $fine_print =~ s/<a href="\//<a href="http:\/\/groupon.com\//g;
	    $fine_print =~ s/<a href='\//<a href='http:\/\/groupon.com\//g;
	}
	$deal->fine_print($fine_print);  


	my @everyscape = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "everyscape")});
	if (@everyscape) {
	    my @images = $everyscape[0]->look_down(
		sub{$_[0]->tag() eq 'img' && defined($_[0]->attr('src'))});
	    
	    foreach my $image (@images) {
		my $clean_image = $image->attr('src');
		$clean_image =~ s/\?.*$//;
		if ($clean_image !~ /profile/) {
		    $deal->image_urls($clean_image);
		}
	    }
	}

	my $image_url = &genericextractor::extractBetweenPatternsN(
	    5,
	    $deal_content_ref,
	    "<div\\s+class=[\'\"]photos[\'\"]\\s+id=[\'\"]everyscape[\'\"]",
	    "<\\\/div>");
	if (defined($image_url) &&
	    $image_url =~ /src=[\'\"](http:\/\/[^\'\"\?<]+)/) {
	    $deal->image_urls($1);  
	}

	my @images = ($tree->as_HTML() =~ m|\"image\":\"(http:[^\"]+)|g);

	foreach my $image (@images) {
	    $deal->image_urls($image);  
	}


	my @num_purchased = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    ($_[0]->attr('id') eq "number_sold_container")});
	if (@num_purchased && $num_purchased[0]->as_text() =~ /([0-9,]+)\s*bought/i) {
	    my $num_purchased = $1;
	    $num_purchased =~ s/,//g;
	    $deal->num_purchased($num_purchased);
	}


	my @name = $tree->look_down(
	    sub{$_[0]->tag() eq 'h3' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "name"});
	if (@name) {
	    $deal->name($name[0]->as_text());
	}


	my @website = $tree->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->as_text() =~ /company\s+website/i});
	if (@website && $website[0]->attr('href') =~ /^http/) {
	    $deal->website($website[0]->attr('href'));
	}


	my @company_box = $tree->look_down(
	    sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') eq "company_box"});

	if (@company_box) {
	    my $addresses_node = $company_box[0];
	    if ($addresses_node->as_HTML() =~
		/see\s*all\s*[0-9]+\s*locations/i) {
		$addresses_node = $tree;
	    }

	    # addresses
	    my @addresses = $addresses_node->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /maps.google.com/});

	    foreach my $address (@addresses) {
		if ($address->attr('href') =~ /addr=(.*)/) {
		    my $clean_address = $1;
		    $clean_address =~ s/\+/ /g;
		    $clean_address =~ s/\%[0-9][A-Z]/ /g;
		    $deal->addresses($clean_address);
		}
	    }

	    # Phone
	    if (!defined($deal->phone()) && $company_box[0]->as_HTML() =~
		/<br[^>]*>([0-9\(\)\-\.\s]{10,20})/) {
		my $phone = $1;
		$phone =~ s/\s+//g;
	    
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
