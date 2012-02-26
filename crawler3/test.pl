#!/usr/bin/perl -w

use strict;
use warnings;

use downloader;

my $url = 'https://www.google.com/offers/home?_escaped_fragment_=details/c652fabcb4d6c381/';

$url = "http://www.munchonme.com/goto/austin";
#my $url = "http://livingsocial.com/cities/27/deals/96123-40-to-spend-on-food-and-drink";

#my $url = "http://livingsocial.com/deals/96123-40-to-spend-on-food-and-drink";

my $response = downloader::getURLWithCookie($url);

open FILE, ">out.html";

print FILE $response->content(), "\n";
print $response->base(),"\n";

close(FILE);
