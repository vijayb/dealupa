#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) September, 2011
#

package main;

use strict;
use warnings;
use workqueue;
use dealsdbutils;

use Geo::Coder::Googlev3;
my $geocoder = Geo::Coder::Googlev3->new;
 
use constant {
    WORK_TYPE => 4,
};


workqueue::registerWorker(\&doWork, WORK_TYPE, 5, 0, 60) ||
    die "Unable to register worker\n";
workqueue::run();


sub doWork {
    my $work_ref = shift;
    my $workqueue_dbh = shift;
    my $output_dbh = shift;
    my $status_ref = shift;
    my $status_message_ref = shift;
    
    my $deal_url = ${$work_ref}{"work"}; 
    my $deal_id = &dealsdbutils::getDealId($output_dbh, $deal_url);

    # This should never happen, but just in case:
    if ($deal_id == 0) {
        $$status_ref = 2;
        $$status_message_ref = "Couldn't find deal ID for url: ".$deal_url;
        return;
    }
    
    my $sql = "select id, raw_address from Addresses777 where deal_id=?";
    my $sth = $output_dbh->prepare($sql);
    $sth->bind_param(1, $deal_id);
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$output_dbh->errstr;
        return;        
    }
    
    my $num_geocoded = 0;
    my $total_attempted = 0;
    $$status_message_ref = "";
    while (my @result = $sth->fetchrow_array()) {
        $total_attempted++;
        my $address_id = $result[0];
        my $raw_address = $result[1];

	# To handle junk encoding characters: only allow
	# essentially alpha-numeric for addresses:
	$raw_address =~ s/[^\sA-Za-z0-9-\.,]+//g;

        print "Geocoding id $address_id, address [$raw_address]\n";
        my $location = $geocoder->geocode(location => $raw_address);

        if (!defined($location)) {
            $$status_ref = 1;
            $$status_message_ref = $$status_message_ref."[$raw_address] ";
            next; 
        }

        my ($street_number, %address_components);
	
        foreach my $component (@{$location->{address_components}}) {
            if (defined($component->{'types'}[0])) {
                if ($component->{'types'}[0] eq "street_number") {
                    $street_number = $component->{long_name};
                }
		
                if ($component->{'types'}[0] eq "route") {
                    $address_components{"street"} = $component->{long_name};
                }
		
                if ($component->{'types'}[0] eq "locality") {
                    $address_components{"city"} = $component->{long_name};
                }
		
                if ($component->{'types'}[0] eq "administrative_area_level_1") {
                    $address_components{"state"} = $component->{short_name};
                }
		
                if ($component->{'types'}[0] eq "country") {
                    $address_components{"country"} = $component->{long_name};
                }

                if ($component->{'types'}[0] eq "postal_code") {
                    $address_components{"zipcode"} = $component->{short_name};
                }
            }
        }
        if (defined($street_number) && defined($address_components{"street"})) {
            $address_components{"street"} =
		$street_number." ".$address_components{"street"};
        }
        $address_components{"latitude"} =
	    $location->{geometry}->{location}->{lat};
        $address_components{"longitude"} =
	    $location->{geometry}->{location}->{lng};
        

        if (scalar(keys(%address_components)) > 0) {
            if (insertGeoInformation($output_dbh, $deal_id, $address_id,
				     \%address_components,
				     $status_ref, $status_message_ref)) {
                $num_geocoded++;    
            }
        } else {
            $$status_ref = 1;
            $$status_message_ref = $$status_message_ref."{$raw_address} ";
        }

	################################ PUT THIS BACK IN AFTER
	# REMOVING OLD ADDRESS INSERTION CODE:
	# print "Sleeping before geocoding next address...\n";
	# sleep(5);
    }

    if ($$status_ref == 3) {
        $$status_ref = 0;
    }
    $$status_message_ref = "Geocoded $num_geocoded out of ".
	"$total_attempted attempted. ".$$status_message_ref;
    
    $sth->finish();
}


sub insertGeoInformation {
    my $dbh = shift;
    my $deal_id = shift;
    my $address_id = shift;
    my $address_components_ref = shift;

    my $status_ref = shift;
    my $status_message_ref = shift;

    my @set_values;
    my @insert_params;
    foreach my $key (keys %{$address_components_ref}) {
        push(@set_values, "$key=?");
        push(@insert_params, ${$address_components_ref}{$key});
    }
    
    # Insert the address information for the given $address_id
    $" = ",";
    my $sql = "update Addresses777 set @set_values where id=$address_id";
    my $sth = $dbh->prepare($sql);
    for (my $i=0; $i <= $#insert_params; $i++) {
        $sth->bind_param($i+1, $insert_params[$i]);
    }
    
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$dbh->errstr;
        return 0;
    }

    # Since we updated address information for a deal, which should
    # set the last_updated field for the deal (otherwise the frontends
    # won't know this deal has new information)
    $sql = "update Deals777 set last_updated=UTC_TIMESTAMP() where id=?";
    $sth = $dbh->prepare($sql);
    $sth->bind_param(1, $deal_id);
    
    if (!$sth->execute()) {
        $$status_ref = 2;
        $$status_message_ref = "Failed database query: ".$dbh->errstr;
        return 0;
    }
    
    
    return 1;
}
