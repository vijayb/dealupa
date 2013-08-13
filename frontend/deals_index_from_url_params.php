<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("refresh_deals_indexes.php");
require_once("helpers.php");

$deals_con_g;
$memcache_g;
$cache_life_g;

$zip_lat_g;
$zip_lng_g;

if (isset($_GET['debug'])) {
	global $deals_con;
	$url_params = $_SERVER["QUERY_STRING"];
	echo($url_params);

	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	print_r(deals_index_from_url_params($url_params, $deals_con, null, $memcache, 86400));
}




function deals_index_from_url_params($url_params_arr, $deals_con, $users_con, $memcache, $cache_life) {

	require("array_constants.php");

	global $deals_con_g;
	global $memcache_g;
	global $cache_life_g;
	
	global $zip_lat;
	global $zip_lng;
	
	$deals_con_g = $deals_con;
	$memcache_g = $memcache;
	$cache_life_g = $cache_life;
	
	
	
	if (gettype($url_params_arr) == "string") {
		// Remove leading ? if it's there because parse_str doesn't like leading
		// question marks
		if (substr($url_params_arr, 0, 1) == "?") {
			$url_params_arr = substr($url_params_arr, 1);
		}
	
		parse_str($url_params_arr, $params_arr);
		$url_params_arr = $params_arr;
	
	}


	// Set defaults
	
	if (!isset($url_params_arr['c'])) $url_params_arr['c'] = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53";
	if (!isset($url_params_arr['y'])) $url_params_arr['y'] = 0;
	if (!isset($url_params_arr['o'])) $url_params_arr['o'] = 0;
	if (!isset($url_params_arr['i'])) $url_params_arr['i'] = 3;
	if (!isset($url_params_arr['v'])) $url_params_arr['v'] = "LIST";
	if (!isset($url_params_arr['s'])) $url_params_arr['s'] = "SOLD";
	if (!isset($url_params_arr['d'])) $url_params_arr['d'] = 0;
	if (!isset($url_params_arr['e'])) $url_params_arr['e'] = 0;

	
	// If the i parameter is an alphabetic string like "seattle", look up the
	// corresponding city ID
	if (!preg_match("/^[0-9]+$/", $url_params_arr['i'])) {
		$url_params_arr['i'] = $citiesReverse[$url_params_arr['i']];
	}
	
	
	$url_params_arr['c'] = explode(',', $url_params_arr['c']);
	
	$zip = "";
	$zip_lat = "0";
	$zip_lng = "0";	

	$cache_life = 86400;
	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	$deals_index = array();




	// If we're running a query and getting search results...
	if (isset($url_params_arr['q'])) {

		require_once('Solr/Service.php');
		$solr = new Apache_Solr_Service('10.182.130.173', '8983', '/solr');

		if (!$solr->ping()) {
			echo 'Solr service not responding.';
			exit;
		}

		$offset = 0;
		$limit = 10000;

		$query = $url_params_arr['q'];
		$url_params_arr['x'] = 0;

		$two_weeks_ago = time() - (3600 * 24 * 14);
				
		if ($url_params_arr['i'] == 2) {
			$query = $query . " AND city_id:2 AND dup:0 AND last_updated:[" . $two_weeks_ago . " TO *]";
		} else {
			$query = $query . " AND -city_id:2 AND dup:0 AND last_updated:[" . $two_weeks_ago . " TO *]";
		}
		
		$expired_deals_index = array();
		$current_deals_index = array();
		
		$response = $solr->search($query, $offset, $limit );

		if ($response->getHttpStatus() == 200) {
			if ($response->response->numFound > 0) {	
			
				foreach ($response->response->docs as $doc) {
					
					$deal = getDealById($doc->id, $deals_con, $memcache, $cache_life);
					if (inBoundingBox($deal, $url_params_arr['i'])) {
						if (has_expired($deal["deadline"]) || $deal["expired"] == 1 || $deal["upcoming"] == 1) {
							array_push($expired_deals_index, $doc->id);
						} else {
							array_push($current_deals_index, $doc->id);
						}
					}
					
				}
			}
		}
		else {
			echo $response->getHttpStatusMessage();
		}

		// Use the line below if you want to show expired deals in search results
		// $deals_index = array_merge($current_deals_index, $expired_deals_index);

		// Use the line below if you do NOT want to show expired deals in the search results
		$deals_index = $current_deals_index;



	
	
	// If we're getting all deals in an edition...
	} else if (isset($url_params_arr['i'])) {

		if ($url_params_arr['s'] == "DISTANCE") {
			// If we need a deals index sorted by distance, first get the SOLD index
			// and then sort it by distance here
			$deals_index = getDealsIndex("SOLD", $url_params_arr['i'], $deals_con, $memcache, $cache_life);
		} else {
			$deals_index = getDealsIndex($url_params_arr['s'], $url_params_arr['i'], $deals_con, $memcache, $cache_life);
		}
		
		
		// Sort by distance if that's what the user wanted...
		if ($url_params_arr['s'] == "DISTANCE") {
			$zip = $url_params_arr['z'];
			
			
			$zip_info = getZipInfo($zip, $deals_con, $memcache);

			$bucket_1 = array();
			$bucket_2 = array();
			$bucket_3 = array();
			$bucket_4 = array();
			$bucket_5 = array();
			
			for ($q = 0; $q < count($deals_index); $q++) {
				$deal = getDealById($deals_index[$q], $deals_con, $memcache, $cache_life);
				if (isset($deal["Addresses"])) {
					$deal_lat = $deal["Addresses"][0]["latitude"];
					$deal_lng = $deal["Addresses"][0]["longitude"];
				} else {
					$deal_lat = 0;
					$deal_lng = 0;
				}
				
				$distance = distance($deal_lat, $deal_lng, $zip_info['latitude'], $zip_info['longitude']);
				
			
				if ($distance <= 1) {
					array_push($bucket_1, $deals_index[$q]);
				
				} else if ($distance <= 3) {
					array_push($bucket_2, $deals_index[$q]);
				
				} else if ($distance <= 5) {
					array_push($bucket_3, $deals_index[$q]);
				
				} else if ($distance <= 10) {
					array_push($bucket_4, $deals_index[$q]);
				
				} else {
					array_push($bucket_5, $deals_index[$q]);
				}
			}
			$deals_index = array_merge($bucket_1, $bucket_2, $bucket_3, $bucket_4, $bucket_5);

			
		}

	}

	
	// Filter out deals that are too far from the user
	
	if ($url_params_arr['d'] > 0 && $url_params_arr['e'] == $url_params_arr['i'] && $url_params_arr['uz'] != -1) {
		$nearby_deals = array();
		$zip_info = getZipInfo($url_params_arr['uz'], $deals_con, $memcache);
		
		for ($q = 0; $q < count($deals_index); $q++) {
			$deal = getDealById($deals_index[$q], $deals_con, $memcache, $cache_life);
			if (isset($deal["Addresses"])) {
				$deal_lat = $deal["Addresses"][0]["latitude"];
				$deal_lng = $deal["Addresses"][0]["longitude"];
			}
			$distance = distance($deal_lat, $deal_lng, $zip_info['latitude'], $zip_info['longitude']);
			
			if ($distance < $url_params_arr['d']) {
				array_push($nearby_deals, $deals_index[$q]);
			}
		}
		
		$deals_index = $nearby_deals;
	}

	
	
	
	
	
	// Apply fiters like category, Yelp review, etc.

	$deals_index_with_filters_applied = array();

	for ($j = 0; $j < count($deals_index); $j++) {

		$deal = getDealById($deals_index[$j], $deals_con, $memcache, $cache_life);
		
		if (isset($deal["Categories"])) {
			$category_arr = array();
			for($k = 0; $k < count($deal["Categories"]); $k++) {
				$category_arr[$k] = $deal["Categories"][$k]["category_id"];
			}	
		} else {
			$category_arr[0] = 0;
		}
		
		$discovered_days_ago = days_ago($deal["discovered"]);

		if ($deal["upcoming"] == 1) {
			$expired_attr = 0;
		} else if (($deal["deadline"] != "" && has_expired($deal["deadline"])) || $deal["expired"] == 1) {
			$expired_attr = 1;
		} else if ($deal["deadline"] == "") {
			$expired_attr = 0;
		} else {
			$expired_attr = 0;
		}
		

		if ($url_params_arr['v'] == "SINGLE-CATEGORY") {
			$skip_this_deal = 1;
			for ($q = 0; $q < count($category_arr); $q++) {
				if (in_array($category_arr[$q], $url_params_arr['c'])) $skip_this_deal = 0;
			}
			if ($skip_this_deal) {
				continue;
			}
		
		} else {

			$skip_this_deal = 0;
			for ($q = 0; $q < count($category_arr); $q++) {
				if (!in_array($category_arr[$q], $url_params_arr['c'])) $skip_this_deal = 1;
			}
			if ($skip_this_deal) {
				continue;
			}
		}
			
		
		
		
		if ($url_params_arr['y'] != 0 && $deal["yelp_rating"] < $url_params_arr['y']) continue;
		if ($url_params_arr['o'] != 0 && $url_params_arr['o'] != $deal["company_id"]) continue;

		array_push($deals_index_with_filters_applied, $deals_index[$j]);
	}
	
	$deals_index = $deals_index_with_filters_applied;

	return $deals_index;
	
}


function getZipInfo($zip, $deals_con, $memcache) {

	$zip_key = "zip_info_" . $zip;
	$zip_info = $memcache->get($zip_key);
	
	if ($zip_info) {
		return $zip_info;
	} else {
		$query = "SELECT latitude, longitude FROM Zipcodes WHERE zip=" . $zip;
		$result = mysql_query($query, $deals_con);

		if (!$result) die('Invalid query: ' . mysql_error());
		
		if ($zip_info = @mysql_fetch_assoc($result)) {
			$memcache->set($zip_key, $zip_info, false, 14*3600*24);
		} else {
			// If there's an error in getting the zip's lat/lng, set it to the
			// center of the U.S.
			$zip_info = array();
			$zip_info['latitude'] = 39.828329;
			$zip_info['longitude'] = -98.579425;
		}

		return $zip_info;

	}
}


?>