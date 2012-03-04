#!/usr/bin/perl -w

use strict;
use warnings;
use LWP;
use LWP::UserAgent;
use LWP::Simple;
use downloader;

my $url = 'https://www.google.com/offers/home?_escaped_fragment_=details/c652fabcb4d6c381/';

$url = "http://www.munchonme.com/goto/austin";
#my $url = "http://livingsocial.com/cities/27/deals/96123-40-to-spend-on-food-and-drink";

#my $url = "http://livingsocial.com/deals/96123-40-to-spend-on-food-and-drink";
my $feed_url = "http://datatransfer.cj.com/datatransfer/files/3500744/outgoing/productcatalog/106446/Mamasource-Mamapedia_Product_Catalog.xml.gz";

#my $ua = LWP::UserAgent->new;
#my $req = HTTP::Request->new(GET => $feed_url);
#$req->authorization_basic("3500744", "jroHW5JS");

#print "Decompressing feed file [$feed_file.gz]\n";
#open(FILE, ">out.html.gz");
#print FILE $ua->request($req)->content;
#close(FILE);
#open FILE, ">out.html";

my $username = '3500744';
my $password = 'jroHW5JS';

my $browser = LWP::UserAgent->new;
$browser->credentials("www.datatransfer.cj.com:80","realm-name",$username=>$password);
my $response=$browser->get($feed_url);

print $response->content;

#print FILE $response->content(), "\n";
#print $response->base(),"\n";

#close(FILE);
