#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) January, 2012
#
{
    package editionbounds;
    
    use strict;
    use warnings;

    my @swLat;
    my @swLng;
    my @neLat;
    my @neLng;

    # Seattle
    $swLat[0] = 46.384833;
    $swLng[0] = -124.991455;
    $neLat[0] = 48.973006;
    $neLng[0] = -119.805908;

    # Portland
    $swLat[1] = 45.195587;
    $swLng[1] = -123.088074;
    $neLat[1] = 45.884273;
    $neLng[1] = -122.173462;

    # SF
    $swLat[2] = 36.022447;
    $swLng[2] = -124.552002;
    $neLat[2] = 38.341656;
    $neLng[2] = -121.794434;

    # SJ
    $swLat[3] = 36.022447;
    $swLng[3] = -124.552002;
    $neLat[3] = 38.341656;
    $neLng[3] = -121.794434;

    # SD
    $swLat[4] = 32.556074;
    $swLng[4] = -117.619629;
    $neLat[4] = 33.330528;
    $neLng[4] = -116.757202;

    # SV
    $swLat[5] = 36.022447;
    $swLng[5] = -124.552002;
    $neLat[5] = 38.341656;
    $neLng[5] = -121.794434;

    # LA
    $swLat[6] = 33.358062;
    $swLng[6] = -120.025635;
    $neLat[6] = 34.434098;
    $neLng[6] = -116.905518;

    # Tacoma
    $swLat[7] = 46.732331;
    $swLng[7] = -123.167725;
    $neLat[7] = 48.9694;
    $neLng[7] = -121.426392;

    # NYC
    $swLat[8] = 40.442767;
    $swLng[8] = -74.514771;
    $neLat[8] = 41.199323;
    $neLng[8] = -73.388672;

    # Chicago
    $swLat[9] = 41.263356;
    $swLng[9] = -88.945312;
    $neLat[9] = 42.482226;
    $neLng[9] = -87.258911;

    # Boston
    $swLat[10] = 42.084974;
    $swLng[10] = -71.516876;
    $neLat[10] = 42.699595;
    $neLng[10] = -70.566559;

    # Atlanta
    $swLat[11] = 33.31675;
    $swLng[11] = -84.863892;
    $neLat[11] = 34.148181;
    $neLng[11] = -81.076355;

    # Orlando
    $swLat[12] = 28.239069;
    $swLng[12] = -81.724548;
    $neLat[12] = 28.948072;
    $neLng[12] = -81.113434;

    # Houston
    $swLat[13] = 29.24327;
    $swLng[13] = -95.822754;
    $neLat[13] = 30.375245;
    $neLng[13] = -94.943848;

    # DC
    $swLat[14] = 38.613651;
    $swLng[14] = -77.546997;
    $neLat[14] = 39.241826;
    $neLng[14] = -76.799927;

    # Miami
    $swLat[15] = 25.435834;
    $swLng[15] = -80.575104;
    $neLat[15] = 26.39433;
    $neLng[15] = -79.95575;

    # Dallas
    $swLat[16] = 32.468061;
    $swLng[16] = -97.580566;
    $neLat[16] = 33.261657;
    $neLng[16] = -96.38855;

    # Denver
    $swLat[17] = 39.470125;
    $swLng[17] = -105.457764;
    $neLat[17] = 40.296287;
    $neLng[17] = -104.523926;

    # Las Vegas
    $swLat[18] = 35.986896;
    $swLng[18] = -115.427856;
    $neLat[18] = 36.383702;
    $neLng[18] = -114.960937;

    # Austin
    $swLat[19] = 30.095237;
    $swLng[19] = -98.072205;
    $neLat[19] = 30.866868;
    $neLng[19] = -97.272949;

    # Philly
    $swLat[20] = 38.946593;
    $swLng[20] = -76.074829;
    $neLat[20] = 40.638967;
    $neLng[20] = -74.767456;	

    # Cleveland
    $swLat[21] = 41.162631;
    $swLng[21] = -82.150269;
    $neLat[21] = 41.778481;
    $neLng[21] = -81.269989;	

    # Minn
    $swLat[22] = 44.647162;
    $swLng[22] = -93.707886;
    $neLat[22] = 45.386877;
    $neLng[22] = -92.807007;	

    # Phoenix
    $swLat[23] = 33.096144;
    $swLng[23] = -112.666168;
    $neLat[23] = 33.958169;
    $neLng[23] = -111.470032;	

    # OC
    $swLat[24] = 33.339707;
    $swLng[24] = -118.184052;
    $neLat[24] = 33.982086;
    $neLng[24] = -117.398529;	

    # Baltimore
    $swLat[25] = 39.027719;
    $swLng[25] = -76.941376;
    $neLat[25] = 39.551707;
    $neLng[25] = -76.315155;	

    # Kansas City
    $swLat[26] = 38.689798;
    $swLng[26] = -95.122375;
    $neLat[26] = 39.675484;
    $neLng[26] = -93.985291;	

    # Detroit
    $swLat[27] = 41.989098;
    $swLng[27] = -83.905334;
    $neLat[27] = 43.283204;
    $neLng[27] = -82.677612;	

    # St. Louis
    $swLat[28] = 38.026459;
    $swLng[28] = -91.538086;
    $neLat[28] = 40.027614;
    $neLng[28] = -89.126587;	

    # Pittsburgh
    $swLat[29] = 40.14214;
    $swLng[29] = -80.491333;
    $neLat[29] = 40.903134;
    $neLng[29] = -79.433899;

    # San Antonio
    $swLat[30] = 29.205518;
    $swLng[30] = -98.833008;
    $neLat[30] = 29.796559;
    $neLng[30] = -98.105164;

    # NOLA
    $swLat[31] = 29.164154;
    $swLng[31] = -91.463362;
    $neLat[31] = 30.971426;
    $neLng[31] = -89.793441;

    sub inLiveEdition {
	my $lat = shift;
	my $lng = shift;

	for (my $i=0; $i <= $#swLat; $i++) {
	    if ($lat >= $swLat[$i] && $lng >= $swLng[$i] &&
		$lat <= $neLat[$i] && $lng <= $neLng[$i]) {
		return 1;
	    }
	}

	return 0;
    }

    1;
}
