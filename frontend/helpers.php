<?php

require_once("array_constants.php");

function days_ago($d) { 
	$ts = time() - strtotime(str_replace("-","/",$d)); 
	if ($ts > 86400) {
		$val = floor($ts/86400);
	} else {
		$val = 0;
	}
	return $val; 
} 


function has_expired($d) {
	$remaining = strtotime(str_replace("-","/",$d)) - time();
	if ($remaining > 0) {
		return false;
	} else {
		return true;
	}
}

function time_left($d) {
	$remaining = strtotime(str_replace("-","/",$d)) - time();
	$days_remaining = floor($remaining / 86400);
	$hours_remaining = floor(($remaining % 86400) / 3600);
	$minutes_remaining = floor(($remaining % 3600) / 60);	
	$time_left_arr = array("d" => $days_remaining, "hr" => $hours_remaining, "min" => $minutes_remaining,);
	return $time_left_arr;
}


// IMPORTANT: ANY CHANGES MADE HERE MUST ALSO BE MADE IN hyphenateTitle IN deelio.js

function hyphenate_title($url_title) {


	$url_title = strtolower($url_title);

	$patterns = array();
	$replacements = array();
	
	
	$patterns[0] = '/\b[a-z]\b/';
	$replacements[0] = '';

	$patterns[1] = '/\b[a-z][a-z]\b/';
	$replacements[1] = '';

	$patterns[2] = '/[0-9] (value|regular)\b/';
	$replacements[2] = '';

	$patterns[3] = '/[0-9] (towards|spend)\b/';
	$replacements[3] = '';

	$patterns[4] = '/\b(for|the|and|are|but|you|reg|your|more)\b/i';
	$replacements[4] = '';

	$patterns[5] = '/&[0-9a-z]+;/';
	$replacements[5] = '-';

	$patterns[6] = '/[^a-z0-9\-]/';
	$replacements[6] = '-';

	$patterns[7] = '/\b[0-9]+\b/';
	$replacements[7] = '-';
	
	$patterns[8] = '/ /';
	$replacements[8] = '-';

	$patterns[9] = '/-+/';
	$replacements[9] = '-';

	$patterns[10] = '/deal-ends-soon/';
	$replacements[10] = '';

	$patterns[11] = '/^-/';
	$replacements[11] = '';

	$patterns[12] = '/-$/';
	$replacements[12] = '';
	
	$url_title = preg_replace($patterns, $replacements, $url_title);
	
	if ($url_title == "") {
		$url_title = "deal";
	}


	return $url_title;
}





function get_discount($price, $value) {
	if ($value == 0) {
		return 0;
	}
	$difference = $value - $price;
	$discount = 100 * ($difference / $value);
	return round($discount);
}


function calculate_city_edition_from_lat_lng($lat, $lng) {

	global $cityLat;
	global $cityLng;
	
	$minDistance = 1000000;
	$currDistance;
	
	$edition;

	for ($i = 0; $i < count($cityLat); $i++) {
		if ($cityLat[$i] != 0 && $i != 500) {
			$currDistance = distance($lat, $lng, $cityLat[$i], $cityLng[$i]);
			if ($currDistance < $minDistance) {
				$minDistance = $currDistance;
				$edition = $i;
			}
		}
	}
	
	return $edition;
}

function distance($lat1, $lon1, $lat2, $lon2) {
        $radlat1 = pi() * $lat1 / 180;
        $radlat2 = pi() * $lat2 / 180;
        $radlon1 = pi() * $lon1 / 180;
        $radlon2 = pi() * $lon2 / 180;
        $theta = $lon1 - $lon2;
        $radtheta = pi() * $theta / 180;
        $dist = sin($radlat1) * sin($radlat2) + cos($radlat1) * cos($radlat2) * cos($radtheta);
 
        $dist = acos($dist);
        $dist = $dist * 180 / pi();
        $dist = $dist * 60 * 1.1515;
        $dist = $dist * 1.609344;
 
        return $dist;
}



?>
