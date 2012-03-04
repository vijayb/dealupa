#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July-September, 2011
#
{
    package downloader;
    use strict;
    use warnings;
    use LWP;
    use HTTP::Cookies;

    use constant {
        COOKIE_DURATION => 6000000,
	CRAWL_CACHE_DIRECTORY => "./crawl_cache/",
	CJ_USERNAME => "3500744",
	CJ_PASSWORD => "jroHW5JS",
    };

    my $browser = LWP::UserAgent->new();
    # Spoof user agent:
    $browser->agent('Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.872.0 Safari/535.2');
    my $cookie_jar = HTTP::Cookies->new();
    my $cookie_age = 0;
    my %domain_cookies = ();
    createCacheDirectory();


    sub setTimeout {
	if (@_) {
	    my $timeout = shift;
	    $browser->timeout($timeout);
	}
    }
    
    sub resetCookieData {
        print "Cookie expired, clearing it.\n";
        $cookie_jar = HTTP::Cookies->new();
        $browser = LWP::UserAgent->new();
        %domain_cookies = ();
        $cookie_age = time();
    }

    sub getURL {
        unless (@_) { die "Incorrect usage of getURL in downloader.\n"; }

        my $url = shift;
        return $browser->get($url);
    }

    sub getAJAXURL {
	unless (@_) { die "Incorrect usage of getAJAXURL in downloader.\n"; }
	
	my $url = shift;
	$url =~ s/#!/\?_escaped_fragment_=/;

	return $browser->get($url);
    }

    sub getURLWithPost {
        unless ($#_ == 1) {
            die "Incorrect usage of getURLWithPost in downloader.\n";
        }

        my $url = shift;
        my $post_form_ref = shift;

        return $browser->post($url, \%{$post_form_ref});
    }

    sub getURLWithPhantom {
	unless (@_) { die "Incorrect usage of getURLWithPhantom in downloader.\n"; }

	my $url = shift;
	my $response = HTTP::Response->new(200);

	my $content = `DISPLAY=:23 /home/vijay/phantomjs/bin/phantomjs getpage.js '$url'`;
	$response->content($content);
	return $response;
    }


    sub getURLWithPassword {
	unless ($#_ == 1) { 
	    die "Incorrect usage of getURLWithPassword in downloader.\n"; 
	}

	my $url = shift;
	my $company_id = shift;
	my $response = HTTP::Response->new(200);

	my $phantom_script = "getpagewithpassword_".$company_id.".js";
	my $content =
	    `DISPLAY=:23 /home/vijay/phantomjs/bin/phantomjs $phantom_script '$url'`;
	$response->content($content);
	return $response;
    }


    sub getURLAsCJFeed {
	unless (@_) {
	    die "Incorrect usage of getURLAsCJFeed in downloader.\n"; 
	}
	my $feed_url = shift;
	my $feed_file = CRAWL_CACHE_DIRECTORY."feed.xml";
	
	print "Downloading feed [$feed_url]\n";
	my $ua = LWP::UserAgent->new;
	my $req = HTTP::Request->new(GET => $feed_url);
	$req->authorization_basic(CJ_USERNAME, CJ_PASSWORD);

	print CJ_USERNAME, "]\n";
	print CJ_PASSWORD, "]\n";

	print "Decompressing feed file [$feed_file.gz]\n";
	open(FILE, ">$feed_file.gz");
	print FILE $ua->request($req)->content;
	close(FILE);
	system("gunzip -f $feed_file.gz");
	
	my $feed_xml;
	local $/;
	open(FILE, $feed_file) || return;
	$feed_xml = <FILE>;
	close(FILE);
	return $feed_xml;
    }


    sub getURLWithCookie {
        unless (@_)
        { die "Incorrect usage of getURLWithCookie in downloader.\n"; }

        my $url = shift; # input url
        my $response; # response is returned

        my $now = time();
        if ($now - $cookie_age > COOKIE_DURATION) {
            resetCookieData();
        }

        if ($url =~ /(http[s]?:\/\/[^\/]+)/i) {
            my $domain = $1;
            if (!defined($domain_cookies{$domain})) {
                print "Couldn't find cookie for $domain setting it now.\n";
                $response = $browser->get($url);
                $cookie_jar->extract_cookies($response);  
                $browser->cookie_jar($cookie_jar);
                $response = $browser->get($url);
                $domain_cookies{$domain} = 1;
                print "Set cookie to [".$cookie_jar->as_string()."]\n";
            }

            $response = $browser->get($url);
        }

        return $response;
    }


    sub createCacheDirectory {
	unless (-d CRAWL_CACHE_DIRECTORY) {
	    mkdir(CRAWL_CACHE_DIRECTORY, 0777) || die "Couldn't create" . 
		CRAWL_CACHE_DIRECTORY . "\n";
	}
    }

    1;
}
