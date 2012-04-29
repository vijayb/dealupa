#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July-November, 2011
#
{
    package dealextractor;
    
    use strict;
    use warnings;
    use deal;

    use grouponextractor;
    use livingsocialextractor;
    use buywithmeextractor;
    use tipprextractor;
    use travelzooextractor;
    use angieslistextractor;
    use giltcityextractor;
    use yollarextractor;
    use zoziextractor;
    use bloomspotextractor;
    use scoutmobextractor;
    use amazonlocalextractor;
    use kgbextractor;
    use lifebookerextractor;
    use dealonextractor;
    use eversaveextractor;
    use livingsocialescapesextractor;
    use googleoffersextractor;
    use getmyperksextractor;
    use voicedailydealsextractor;
    use munchonmeextractor;
    use doodledealsextractor;
    use juiceinthecityextractor;
    use schwaggleextractor;
    use homerunextractor;
    use bargainbeeextractor;
    use dealfindextractor;
    use pinchitextractor;
    use goldstarextractor;
    use onsaleextractor;
    use livingsocialadventuresextractor;
    use entertainmentextractor;
    use thrillistextractor;
    use savoredextractor;
    use msnoffersextractor;
    use cbslocalextractor;
    use crowdsavingsextractor;
    use plumdistrictextractor;
    use mamapediaextractor;
    use dailycandyextractor;
    use dealchickenextractor;

    
    my %company_to_extractor_map;

    $company_to_extractor_map{1} = \&grouponextractor::extract;
    $company_to_extractor_map{2} = \&livingsocialextractor::extract;
    $company_to_extractor_map{3} = \&buywithmeextractor::extract;
    $company_to_extractor_map{4} = \&tipprextractor::extract;
    $company_to_extractor_map{5} = \&travelzooextractor::extract;
    $company_to_extractor_map{6} = \&angieslistextractor::extract;
    $company_to_extractor_map{7} = \&giltcityextractor::extract;
    $company_to_extractor_map{8} = \&yollarextractor::extract;
    $company_to_extractor_map{9} = \&zoziextractor::extract;
    $company_to_extractor_map{10} = \&bloomspotextractor::extract;
    $company_to_extractor_map{11} = \&scoutmobextractor::extract;
    $company_to_extractor_map{12} = \&amazonlocalextractor::extract;
    $company_to_extractor_map{13} = \&kgbextractor::extract;
    $company_to_extractor_map{14} = \&lifebookerextractor::extract;
    $company_to_extractor_map{15} = \&dealonextractor::extract;
    $company_to_extractor_map{16} = \&eversaveextractor::extract;
    $company_to_extractor_map{17} = \&livingsocialescapesextractor::extract;
    $company_to_extractor_map{18} = \&googleoffersextractor::extract;
    $company_to_extractor_map{19} = \&getmyperksextractor::extract;
    $company_to_extractor_map{20} = \&voicedailydealsextractor::extract;
    $company_to_extractor_map{21} = \&munchonmeextractor::extract;
    $company_to_extractor_map{22} = \&doodledealsextractor::extract;
    $company_to_extractor_map{23} = \&juiceinthecityextractor::extract;
    $company_to_extractor_map{24} = \&schwaggleextractor::extract;
    $company_to_extractor_map{25} = \&homerunextractor::extract;
    $company_to_extractor_map{26} = \&bargainbeeextractor::extract;
    $company_to_extractor_map{30} = \&dealfindextractor::extract;
    $company_to_extractor_map{32} = \&pinchitextractor::extract;
    $company_to_extractor_map{33} = \&goldstarextractor::extract;
    $company_to_extractor_map{34} = \&onsaleextractor::extract;
    $company_to_extractor_map{35} = \&livingsocialadventuresextractor::extract;
    $company_to_extractor_map{36} = \&entertainmentextractor::extract;
    $company_to_extractor_map{37} = \&thrillistextractor::extract;
    $company_to_extractor_map{38} = \&savoredextractor::extract;
    $company_to_extractor_map{39} = \&msnoffersextractor::extract;
    $company_to_extractor_map{40} = \&cbslocalextractor::extract;
    $company_to_extractor_map{41} = \&crowdsavingsextractor::extract;
    $company_to_extractor_map{42} = \&plumdistrictextractor::extract;
    $company_to_extractor_map{43} = \&mamapediaextractor::extract;
    $company_to_extractor_map{44} = \&dailycandyextractor::extract;
    $company_to_extractor_map{45} = \&dealchickenextractor::extract;


    sub extractDeal {
        if ($#_ != 1) { die "Incorrect usage of extractDeal, need 2 ".
                            "arguments\n"; }
        my $deal = shift;
        my $deal_content_ref = shift;

        if (!defined($deal->company_id())) {
            die "Incorrect usage of extractDeal, company_id of deal ".
                "isn't set\n";
        }

        if (defined($company_to_extractor_map{$deal->company_id()}))
        {
            &{$company_to_extractor_map{$deal->company_id()}}
            ($deal, $deal_content_ref);
            $deal->cleanUp();
        } else {
            print "No deal extractor registered for company_id : ".
                  $deal->company_id(), "\n";
        }
    }


    sub hasExtractorForCompanyID {
	my $company_id = shift;
	return defined($company_to_extractor_map{$company_id});
    }


    1;
}
