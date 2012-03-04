#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#
{
    package xmlextractor;
    
    use strict;
    use warnings;
    use deal;

    use signpostxmlextractor;
    use crowdseatsxmlextractor;
    use landmarkxmlextractor;
    use plumdistrictxmlextractor;
    use mamapediaxmlextractor;
    
    my %company_to_extractor_map;

    $company_to_extractor_map{27} = \&signpostxmlextractor::extract;
    $company_to_extractor_map{28} = \&crowdseatsxmlextractor::extract;
    $company_to_extractor_map{29} = \&landmarkxmlextractor::extract;
    $company_to_extractor_map{42} = \&plumdistrictxmlextractor::extract;
    $company_to_extractor_map{43} = \&mamapediaxmlextractor::extract;


    sub extractDeals {
        if ($#_ != 1) { die "Incorrect usage of extractDeals, need 2 ".
                            "arguments\n"; }
        my $xml_content_ref = shift;
	my $company_id = shift;

        if (defined($company_to_extractor_map{$company_id})) {
            return &{$company_to_extractor_map{$company_id}}($xml_content_ref);
        } else {
            print "No xml extractor registered for company_id : ".
		$company_id, "\n";
        }

	return;
    }

    1;
}
