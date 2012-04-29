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
    $swLat[0] = 47.532038;
    $swLng[0] = -122.453613;
    $neLat[0] = 47.846344;
    $neLng[0] = -122.044373;

    # Portland
    $swLat[1] = 45.195587;
    $swLng[1] = -123.088074;
    $neLat[1] = 45.884273;
    $neLng[1] = -122.173462;

    # SF
    $swLat[2] = 37.687624;
    $swLng[2] = -122.542191;
    $neLat[2] = 37.826057;
    $neLng[2] = -122.348557;

    # SJ
    $swLat[3] = 37.291535;
    $swLng[3] = -121.997681;
    $neLat[3] = 37.418163;
    $neLng[3] = -121.816406;

    # SD
    $swLat[4] = 32.556074;
    $swLng[4] = -117.619629;
    $neLat[4] = 33.330528;
    $neLng[4] = -116.757202;

    # SV
    $swLat[5] = 37.328673;
    $swLng[5] = -122.478333;
    $neLat[5] = 37.662081;
    $neLng[5] = -121.91803;

    # LA
    $swLat[6] = 33.358062;
    $swLng[6] = -120.025635;
    $neLat[6] = 34.434098;
    $neLng[6] = -116.905518;

    # Tacoma
    $swLat[7] = 47.177112;
    $swLng[7] = -122.584763;
    $neLat[7] = 47.319741;
    $neLng[7] = -122.276459;

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
    $swLat[14] = 38.800654;
    $swLng[14] = -77.177582;
    $neLat[14] = 39.004778;
    $neLng[14] = -76.898804;

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
    $swLat[24] = 33.380999;
    $swLng[24] = -118.243103;
    $neLat[24] = 33.984364;
    $neLng[24] = -117.410889;

    # Baltimore
    $swLat[25] = 39.202994;
    $swLng[25] = -76.738815;
    $neLat[25] = 39.379957;
    $neLng[25] = -76.509476;

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

    # Honolulu
    $swLat[32] = 21.193375;
    $swLng[32] = -158.422852;
    $neLat[32] = 21.884438;
    $neLng[32] = -157.531586;


    # Sacramento
    $swLat[33] = 38.386881;
    $swLng[33] = -121.695557;
    $neLat[33] = 38.808681;
    $neLng[33] = -121.062469;

    # Salt Lake City
    $swLat[34] = 40.670223;
    $swLng[34] = -112.16217;
    $neLat[34] = 40.869911;
    $neLng[34] = -111.739197;
    
    # Tampa
    $swLat[35] = 27.673799;
    $swLng[35] = -82.990723;
    $neLat[35] = 28.210029;
    $neLng[35] = -82.172241;
    
    # Cincinnati
    $swLat[36] = 39.023451;
    $swLng[36] = -84.799347;
    $neLat[36] = 39.310925;
    $neLng[36] = -84.208832;
    
    # Indianapolis
    $swLat[37] = 39.501921;
    $swLng[37] = -86.520081;
    $neLat[37] = 40.080173;
    $neLng[37] = -85.751038;
    
    # Madison
    $swLat[38] = 42.928274;
    $swLng[38] = -89.656677;
    $neLat[38] = 43.247203;
    $neLng[38] = -89.118347;
    
    # Milwaukee
    $swLat[39] = 42.857846;
    $swLng[39] = -88.244934;
    $neLat[39] = 43.179144;
    $neLng[39] = -87.681885;
    
    # Albany
    $swLat[40] = 42.560161;
    $swLng[40] = -74.021759;
    $neLat[40] = 42.850799;
    $neLng[40] = -73.498535;
    
    # Palm Beach
    $swLat[41] = 26.45582;
    $swLng[41] = -80.233154;
    $neLat[41] = 26.990619;
    $neLng[41] = -79.867859;
    
    # Birmingham
    $swLat[42] = 33.316758;
    $swLng[42] = -87.160034;
    $neLat[42] = 33.838483;
    $neLng[42] = -86.412964;
    
    # Columbus
    $swLat[43] = 39.751545;
    $swLng[43] = -83.38623;
    $neLat[43] = 40.231315;
    $neLng[43] = -82.595215;
    
    # Oklahoma City
    $swLat[44] = 35.124402;
    $swLng[44] = -98.12439;
    $neLat[44] = 35.768801;
    $neLng[44] = -97.009277;
    
    # Raleigh
    $swLat[45] = 35.456196;
    $swLng[45] = -79.216919;
    $neLat[45] = 36.217687;
    $neLng[45] = -78.206177;
    
    # Charlotte
    $swLat[46] = 34.980502;
    $swLng[46] = -81.183472;
    $neLat[46] = 35.491984;
    $neLng[46] = -80.386963;
    
    # Charleston
    $swLat[47] = 32.549128;
    $swLng[47] = -80.219421;
    $neLat[47] = 33.112249;
    $neLng[47] = -79.477844;

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

    sub getEditions {
	my $lat = shift;
	my $lng = shift;
	
	my @editions;
	
	for (my $i=0; $i <= $#swLat; $i++) {
	    if ($lat >= $swLat[$i] && $lng >= $swLng[$i] &&
		$lat <= $neLat[$i] && $lng <= $neLng[$i]) {
		push(@editions, $i+3);
	    }
	}

	if ($#editions == -1) {
	    push(@editions, 1);
	}

	return @editions;
    }

    1;
}
