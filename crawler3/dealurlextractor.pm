#!/usr/bin/perl -w
# Copyright (c) 2011, 2012 All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011 - March,2012
#
{
    package dealurlextractor;
    
    use strict;
    use warnings;
    use hub;
    use HTML::TreeBuilder;
    use Encode;
    use URI::Escape; # needed for Eversave

    my %company_to_extractor_map;

    $company_to_extractor_map{1} = \&GrouponURLExtractor;
    $company_to_extractor_map{2} = \&LivingSocialURLExtractor;
    $company_to_extractor_map{3} = \&BuyWithMeURLExtractor;
    $company_to_extractor_map{4} = \&TipprURLExtractor;
    $company_to_extractor_map{5} = \&TravelZooURLExtractor;
    $company_to_extractor_map{6} = \&AngiesListURLExtractor;
    $company_to_extractor_map{7} = \&GiltCityURLExtractor;
    $company_to_extractor_map{8} = \&YollarURLExtractor;
    $company_to_extractor_map{9} = \&ZoziURLExtractor;
    $company_to_extractor_map{10} = \&BloomspotURLExtractor;
    $company_to_extractor_map{11} = \&ScoutMobURLExtractor;
    $company_to_extractor_map{12} = \&AmazonLocalURLExtractor;
    $company_to_extractor_map{13} = \&KGBURLExtractor;
    $company_to_extractor_map{14} = \&LifeBookerURLExtractor;
    $company_to_extractor_map{15} = \&DealOnURLExtractor;
    $company_to_extractor_map{16} = \&EverSaveURLExtractor;
    $company_to_extractor_map{17} = \&LivingSocialEscapesURLExtractor;
    $company_to_extractor_map{18} = \&GoogleOffersURLExtractor;
    $company_to_extractor_map{19} = \&GetMyPerksURLExtractor;
    $company_to_extractor_map{20} = \&VoiceDailyDealsURLExtractor;
    $company_to_extractor_map{21} = \&MunchOnMeURLExtractor;
    $company_to_extractor_map{22} = \&DoodleDealsURLExtractor;
    $company_to_extractor_map{23} = \&JuiceInTheCityURLExtractor;
    $company_to_extractor_map{24} = \&SchwaggleURLExtractor;
    $company_to_extractor_map{25} = \&HomeRunURLExtractor;
    $company_to_extractor_map{26} = \&BargainBeeURLExtractor;
    $company_to_extractor_map{30} = \&DealFindURLExtractor;
    $company_to_extractor_map{32} = \&PinchitURLExtractor;
    $company_to_extractor_map{33} = \&GoldStarURLExtractor;
    $company_to_extractor_map{34} = \&OnSaleURLExtractor;
    $company_to_extractor_map{35} = \&LivingSocialAdventuresURLExtractor;
    $company_to_extractor_map{36} = \&EntertainmentURLExtractor;
    $company_to_extractor_map{37} = \&ThrillistURLExtractor;
    $company_to_extractor_map{38} = \&SavoredURLExtractor;
    $company_to_extractor_map{39} = \&MSNOffersURLExtractor;
    $company_to_extractor_map{40} = \&CBSLocalURLExtractor;
    $company_to_extractor_map{41} = \&CrowdSavingsURLExtractor;
    $company_to_extractor_map{43} = \&MamapediaURLExtractor;
    $company_to_extractor_map{44} = \&DailyCandyURLExtractor;

    sub extractDealURLs {
        if ($#_ != 2) {
            die "Incorrect usage of extractDealURLs, need 3 ".
                "arguments\n";
        }

        my $deal_urls_ref = shift;
        my $hub_content_ref = shift;
        my $hub_properties = shift;

        my $tree = HTML::TreeBuilder->new;
	$tree->ignore_unknown(0);
        $tree->parse(decode_utf8 $$hub_content_ref);
        $tree->eof();

        my @array = keys %{$deal_urls_ref};
        my $tmp_deal_urls_size = $#array;

        if (!defined($$hub_content_ref) || length($$hub_content_ref) == 0) {
            return 0;
        }

        if (defined($company_to_extractor_map{$hub_properties->company_id()}))
        {
            &{$company_to_extractor_map{$hub_properties->company_id()}}
            ($deal_urls_ref, $hub_properties, \$tree);
        } else {
            print "Warning: no deal url extractor registered for company_id : ".
                  $hub_properties->company_id(), "\n";
            return 0;
        }

        @array = keys %{$deal_urls_ref};
        my $num_deals_extracted = $#array - $tmp_deal_urls_size;

        $tree->delete();
        return $num_deals_extracted;
    }


    
    sub addToDealUrls {
        if (!$#_ == 1) { die "Incorrect usage of addToDealUrls.\n"; }
        my $deal_urls_ref = shift;
        my $url = shift;
        if (!defined($$deal_urls_ref{$url})) {
            $$deal_urls_ref{$url} = 1;
        }
    }
    
    #####################################################################
    # Individual Deal URL extractors
    #

    sub GrouponURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of GrouponURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
        foreach my $deal (@deal_urls) {
            if ($deal->attr('href') !~
		/categories|set_location_sort|special_content|discussion|options/)
	    {
                if ($deal->attr('href') =~ /(http:\/\/www.groupon.com\/deals\/[^\?]+)/) {
                    addToDealUrls($_[0], $1);
                }
                if ($deal->attr('href') =~ /(http:\/\/www.groupon.com\/ch\/[^\?]+)/) {
		    addToDealUrls($_[0], $1);
                }
            }
        }
    }

    sub LivingSocialURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of LivingSocialURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
	my @links = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^\// &&
		    $_[0]->as_text() =~ /view\s*deal/i});
	

	foreach my $link (@links) {
	    my $url = "http://www.livingsocial.com".$link->attr('href');
	    print "[$url]\n";
	    addToDealUrls($_[0], $url);
	}
    }

    sub BuyWithMeURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of BuyWithMeURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
        foreach my $deal (@deal_urls) {
            if ($deal->attr('href') =~ /^(\/[A-Za-z]+\/deals\/[0-9]+[^\?]+)$/) {
                addToDealUrls($_[0], "http://www.buywithme.com$1");
            }
        }
    }


    sub TipprURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of TipprURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
        foreach my $deal (@deal_urls) {
            if ($deal->attr('href') =~ /^(\/offer[^\?]+)$/) {
                addToDealUrls($_[0], "http://tippr.com$1");
            }
        }
    }

    sub TravelZooURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of TravelZooURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
        foreach my $deal (@deal_urls) {
            if ($deal->attr('href') =~
            /(http:\/\/www.travelzoo.com\/local-deals\/.*\/[0-9]+$)/)
            {
                addToDealUrls($_[0], $1);
            }
        }
    }

    sub AngiesListURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of AngiesListURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
        foreach my $deal (@deal_urls) {
            if ($deal->attr('href') =~
            /(http[s]?:\/\/my.angieslist.com\/thebigdeal\/default.aspx\?itemid=.+)/)
            {
                addToDealUrls($_[0], $1);
            }
        }
    }

    sub GiltCityURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of GiltCityURLExtractor.\n"; }
        my $deal_urls_ref = $_[0];
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    defined($_[0]->attr('class')) &&
		    ($_[0]->attr('class') eq "offer-photo")});
        foreach my $deal_url (@deal_urls) {
            if ($deal_url->attr('href') !~ /national=1/ &&
		$deal_url->attr('href') !~ /\/collection\// &&
		$deal_url->attr('href') !~ /jetsetter/ &&
		$deal_url->attr('href') =~ /(^\/.*)/) {

                my $url = "http://www.giltcity.com$1";
                addToDealUrls($_[0], $url);
            }
        }
    }


    sub YollarURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of YollarURLExtractor.\n"; }
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
                ($_[0]->attr('property') eq "og:url")});

        if (@deal_urls && defined($deal_urls[0]->attr('content')) &&
            $deal_urls[0]->attr('content') =~ /^http/) {
            my $url = $deal_urls[0]->attr('content');
            addToDealUrls($_[0], $url);
        }
        
        @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
                ($_[0]->attr('class') eq
                 "offer-view-concurrent-view-deal-button")});

        foreach my $deal (@deal_urls) {
            if ($deal->as_HTML() =~ /href=[\'\"](\/offer\/[^\'\"]+)/) {
                addToDealUrls($_[0], "http://yollar.com$1");
            }
        }
    }

    sub ZoziURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of ZoziURLExtractor.\n"; }
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') =~ /learn_more/ &&
		    ($_[0]->attr('href') =~ 
		     /^\/experiences\/.*\/[0-9]+$/)});
        foreach my $deal (@deal_urls) {
            addToDealUrls($_[0], "http://www.zozi.com".
			  $deal->attr('href'));
        }
    }

    sub BloomspotURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of BloomspotURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];

	
	my @deal_urls = ${$tree_ref}->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

	foreach my $deal (@deal_urls) {
	    if ($deal->attr('href') =~ /^http:\/\/www.bloomspot/&&
		$deal->as_HTML() =~ /list_cta.png/) {
		#print $deal->attr('href'),"\n";
		addToDealUrls($_[0], $deal->attr('href'));
	    }
	}

    }

    sub ScoutMobURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of ScoutMobURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
        foreach my $deal (@deal_urls) {
            if ($deal->attr('href') =~ /^(\/[^\/]+\/deal\/[0-9]+)$/) {
                addToDealUrls($_[0], "http://www.scoutmob.com$1");
            }
        }
    }


    sub AmazonLocalURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of AmazonLocalURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
                defined($_[0]->attr('class')) && 
                $_[0]->attr('class') eq "deal_title"});

        if (@deal_urls) {
            if ($deal_urls[0]->attr('href') =~ /^(\/.*)/) {
                addToDealUrls($_[0], "http://local.amazon.com".
                              $deal_urls[0]->attr('href'));
            }
        }
    }


    sub KGBURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of KGBURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

        foreach my $deal_url (@deal_urls) {
            if ($deal_url->attr('href') =~ /^(\/[^\/]+\/deals\/[0-9]+\/.*)/) {
                addToDealUrls($_[0], "http://kgbdeals.com".
                              $deal_url->attr('href'));
            }
            if ($deal_url->attr('href') =~
		/^(\/[^\/]+\/every-day-deals\/[0-9]+\/.*)/) {
                addToDealUrls($_[0], "http://kgbdeals.com".
                              $deal_url->attr('href'));
            }
        }
    }

    sub LifeBookerURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of LifeBookerURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

        foreach my $deal_url (@deal_urls) {
            if ($deal_url->attr('href') =~ /^(\/getloot\/.*)/) {
                addToDealUrls($_[0], "http://lifebooker.com".
                              $deal_url->attr('href'));
            }
        }
    }

    sub DealOnURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of DealOnURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @divs = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "deal-title"});

        foreach my $div (@divs) {
	    my @deal_url = $div->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

	    if (@deal_url) {
                addToDealUrls($_[0], "http://www.dealon.com".
                              $deal_url[0]->attr('href'));
            }
        }
    }

    sub EverSaveURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of EverSaveURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @divs = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('onclick'))});

        foreach my $div (@divs) {
	    if ($div->attr('onclick') =~ /location.href=[\'\"](\/[^\'\"]+)/) {
		my $url = $1;
		$url =~ s/;.*$//;
                addToDealUrls($_[0], "http://www.eversave.com".$url);
	    }
        }


	my @fblike = ${$tree_ref}->look_down(
            sub{$_[0]->tag() =~ 'div' && defined($_[0]->attr('id')) &&
		    $_[0]->attr('id') =~ /fbLikeButton/i});

	if (@fblike &&
	    $fblike[0]->as_HTML() =~ /href=[\'\"](http[^\'\"]+)/) {
	    my $url = uri_unescape($1);
	    $url =~ s/\?.*$//;
	    addToDealUrls($_[0], $url);
	}
    }


    sub LivingSocialEscapesURLExtractor {
        if (!$#_ == 2)
	{ die "Incorrect usage of LivingSocialEscapesURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});
        foreach my $deal (@deal_urls) {
            if ($deal->attr('href') =~
		/^(\/escapes\/[0-9]+.*)/)
            {
                addToDealUrls($_[0], "http://livingsocial.com$1");
            }            
        }
    }


    sub GoogleOffersURLExtractor {
        if (!$#_ == 2)
	{ die "Incorrect usage of GoogleOffersURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
	if (defined($hub_properties->redirect_url())) {
	    my $deal_url = $hub_properties->redirect_url();
	    addToDealUrls($_[0], $deal_url);
	}
    }


    sub GetMyPerksURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of GetMyPerksURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

        foreach my $deal_url (@deal_urls) {
            if ($deal_url->as_text() =~ /^view\s+deal$/i) {
                addToDealUrls($_[0], $deal_url->attr('href'));
            }
        }
    }


    sub VoiceDailyDealsURLExtractor {
        if (!$#_ == 2) 
	{ die "Incorrect usage of VoiceDailyDealsURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

        foreach my $deal_url (@deal_urls) {
            if ($deal_url->attr('href') =~ /^\/deals\/.*/i) {
                addToDealUrls($_[0], "http://www.voicedailydeals.com".
			      $deal_url->attr('href'));
            }
        }

	my @curr_deal = ${$tree_ref}->look_down(
	    sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('property') eq "og:url"});
	if (@curr_deal) {
	    addToDealUrls($_[0], $curr_deal[0]->attr('content'));
	}
    }

    sub MunchOnMeURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of MunchOnMeURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "dish-name"});
        foreach my $deal (@deal_urls) {
	    addToDealUrls($_[0], "http://munchonme.com".$deal->attr('href'));
        }
    }


    sub DoodleDealsURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of DoodleDealsURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_divs = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "deal"});
        foreach my $deal (@deal_divs) {
	    my @deal_url = $deal->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->as_text() =~ /view\s+deal/i});
	    
	    if (@deal_url && $deal_url[0]->attr('href') !~ /ext_purchase$/) {
		addToDealUrls($_[0], $deal_url[0]->attr('href'));
	    }
        }

	my @deal_on_hub = ${$tree_ref}->look_down(
	    sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "buy_now"});
	if (@deal_on_hub) {
	    my $deal_url = $deal_on_hub[0]->attr('href');
	    $deal_url =~ s/\/purchase$//;
	    if ($deal_url !~ /ext_purchase$/) {
		addToDealUrls($_[0], $deal_url);
	    }
	}
    }


    sub JuiceInTheCityURLExtractor {
        if (!$#_ == 2)
	{ die "Incorrect usage of JuiceInTheCityURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_url = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('property') eq "og:url"});

	if (@deal_url &&
	    $deal_url[0]->attr('content') =~ /(http:[^\?]+)/) {
	    addToDealUrls($_[0], $1);
	}
    }



    sub SchwaggleURLExtractor {
        if (!$#_ == 2)
	{ die "Incorrect usage of SchwaggleURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('class')) &&
		    defined($_[0]->attr('href')) &&
		    $_[0]->attr('class') eq "btn-see-deal"});

	foreach my $deal_url (@deal_urls) {
	    my $clean_url = $deal_url->attr('href');
	    # Schwaggle postpends the city to the url. E.g.,:
	    # http:// ... deal url ... /seattle
	    # We get rid of the end so we don't have to crawl
	    # different URL versions of the same deal
	    $clean_url =~ s/\/[^\/]+$//;
	    addToDealUrls($_[0], $clean_url);
	}
    }


    sub HomeRunURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of HomeRunURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

        foreach my $deal (@deal_urls) {
	    if ($deal->attr('href') =~ /(^\/deal\/.*)/ &&
		$deal->attr('href') !~ /buy_button/) {
		addToDealUrls($_[0],
			      "http://homerun.com".$deal->attr('href'));
	    }
        }
    }

    sub BargainBeeURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of BargainBeeURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

        foreach my $deal (@deal_urls) {
	    if ($deal->attr('href') =~ /(^\/DailyDeal\/.*)/ &&
		$deal->as_text() =~ /view\s*it/i) {
		addToDealUrls($_[0],
			      "http://bargainbee.com".$deal->attr('href'));
	    }
        }

	# Bargain Bee hub pages direct to a deal url. So we
	# obtain that deal's url via the redirect
	if (defined($hub_properties->redirect_url())) {
	    my $deal_url = $hub_properties->redirect_url();
	    addToDealUrls($_[0], $deal_url);
	}
    }



    sub DealFindURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of DealFindURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^\// &&
		    defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "view-deal"});

        foreach my $deal (@deal_urls) {
	    addToDealUrls($_[0],
			  "http://www.dealfind.com".$deal->attr('href'));
        }

	if (${$tree_ref}->as_HTML() =~
	    /AffiliateLinkURL\s*=\s*[\"\'](http:\/\/www.dealfind.com\/[^\"\']+)/) {
	    addToDealUrls($_[0], $1);
	}

	# Handle travel URLs (not some as normal ones annoyingly):
	@deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "travelSide"});

        foreach my $deal (@deal_urls) {
	    my @deal_url = $deal->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /\/travel\/[^\/]+\/.*/});
	    
	    if (@deal_url) {
		print "http://www.dealfind.com".$deal_url[0]->attr('href')."\n";
		addToDealUrls($_[0],
			      "http://www.dealfind.com".$deal_url[0]->attr('href'));
	    }
        }
    }


   sub PinchitURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of PinchitURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href'))});

        foreach my $deal (@deal_urls) {
	    if ($deal->attr('href') =~ /\/deals\// &&
		$deal->as_text() eq "view" &&
		$deal->attr('href') =~ /(^http:.*)/) 
	    {
		addToDealUrls($_[0], $1);
	    }
        }
    }


   sub GoldStarURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of GoldStarURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    defined($_[0]->attr('itemprop')) &&
		    $_[0]->attr('itemprop') eq "url"});

        foreach my $deal (@deal_urls) {
	    if ($deal->attr('href') =~ /\/events\// &&
		$deal->attr('href') =~ /^\//) 
	    {
		addToDealUrls($_[0], "http://www.goldstar.com".$deal->attr('href'));
	    }
        }
    }


   sub OnSaleURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of OnSaleURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "odDetails"});

        foreach my $deal (@deal_urls) {
	    if ($deal->attr('href') =~ /^\/deals\//) 
	    {
		addToDealUrls($_[0], "http://www.onsale.com".$deal->attr('href'));
	    }
        }
    }

   sub LivingSocialAdventuresURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of LivingSocialAdventuresURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^\/adventures\//});

        foreach my $deal (@deal_urls) {
	    if ($deal->attr('href') !~ /about$/) {
		my $url = "http://www.livingsocial.com".$deal->attr('href');
		addToDealUrls($_[0], $url);
	    }
        }
    }


   sub EntertainmentURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of EntertainmentURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
		    defined($_[0]->attr('content')) &&
		    $_[0]->attr('content') =~ /^http/ &&
		    $_[0]->attr('property') eq "og:url"});

        foreach my $deal (@deal_urls) {
	    my $clean_url = $deal->attr('content');
	    $clean_url =~ s/\?[^\?].*$//;
	    addToDealUrls($_[0], $clean_url);
        }
    }


   sub ThrillistURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of ThrillistURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'section' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "popup"});

        foreach my $deal_container (@deal_urls) {
	    my @href = $deal_container->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /^deal/});
	    if (@href) {
		my $clean_url = "http://rewards.thrillist.com/".$href[0]->attr('href');
		addToDealUrls($_[0], $clean_url);
	    }

        }
    }


   sub SavoredURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of SavoredURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('class')) &&
		    defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^\// &&
		    $_[0]->as_text() =~ /view\s*offer/i &&
		    $_[0]->attr('class') eq "sig-view-offer-link"});

        foreach my $deal (@deal_urls) {
	    my $url = "http://savored.com".$deal->attr('href');
	    addToDealUrls($_[0], $url);
        }
    }


    sub MSNOffersURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of MSNOffersURLExtractor.\n"; }
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'meta' && defined($_[0]->attr('property')) &&
                ($_[0]->attr('property') eq "og:url")});

        if (@deal_urls && defined($deal_urls[0]->attr('content')) &&
            $deal_urls[0]->attr('content') =~ /^http/) {
            my $url = $deal_urls[0]->attr('content');
            addToDealUrls($_[0], $url);
        }
    }


    sub CBSLocalURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of CBSLocalURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
		    $_[0]->attr('class') eq "offer-link"});

        foreach my $deal (@deal_urls) {
	    my @href = $deal->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			$_[0]->attr('href') =~ /^\//});
	    if (@href) {
		my $clean_url = "http://offers.cbslocal.com".$href[0]->attr('href');
		# chop the end off the URL since it's not needed
		# (it has the city name) and it causes more crawling
		# than is necessary
		$clean_url =~ s/[^\/]*$//;
		addToDealUrls($_[0], $clean_url);
	    }

        }
    }


    sub CrowdSavingsURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of CrowdSavingsURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deals = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('onclick')) &&
		    $_[0]->attr('onclick') =~ /href=[\'\"]http:\/\/www.crowdsavings/});

	foreach my $deal (@deals) {
	    if ($deal->attr('onclick') =~ /href=[\'\"]([^\'\"]+)/) {
		my $clean_url = $1;
		$clean_url =~ s/\s//g; # wtf they have spaces in their URLs!
		addToDealUrls($_[0], $clean_url);
	    }
	}


	if (${$tree_ref}->as_HTML() =~ /addthis:url=\"(http:\/\/www.crowd[^\"\?]+)/) {
	    my $clean_url = $1;
	    $clean_url =~ s/\s//g;
	    addToDealUrls($_[0], $clean_url);
	}

    }


    sub MamapediaURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of MamapediaURLExtractor.\n"; }
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'div' && defined($_[0]->attr('class')) &&
                ($_[0]->attr('class') =~ /deal\s*clearfix/)});

	foreach my $deal_url (@deal_urls) {
	    my @a = $deal_url->look_down(
		sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
			($_[0]->attr('href') =~ /^\/deals/)});
	    if (@a) {
		addToDealUrls($_[0], "http://deals.mamapedia.com".$a[0]->attr('href'));
	    }
	}
    }


    sub DailyCandyURLExtractor {
        if (!$#_ == 2) { die "Incorrect usage of DailyCandyURLExtractor.\n"; }
        my $hub_properties = $_[1];
        my $tree_ref = $_[2];
        
        my @deal_urls = ${$tree_ref}->look_down(
            sub{$_[0]->tag() eq 'a' && defined($_[0]->attr('href')) &&
		    $_[0]->attr('href') =~ /^http/ && 
		    $_[0]->attr('href') =~ /\/deal\/[0-9]+\//});

        foreach my $deal_url (@deal_urls) {
            addToDealUrls($_[0], $deal_url->attr('href'));
        }
    }


    1;
}
