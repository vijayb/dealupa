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
	
	if (!isset($url_params_arr['c'])) $url_params_arr['c'] = "0,1,1,1,1,1,1,1,1,0";
	if (!isset($url_params_arr['n'])) $url_params_arr['n'] = 0;
	if (!isset($url_params_arr['y'])) $url_params_arr['y'] = 0;
	if (!isset($url_params_arr['o'])) $url_params_arr['o'] = 0;
	if (!isset($url_params_arr['i'])) $url_params_arr['i'] = 3;
	if (!isset($url_params_arr['v'])) $url_params_arr['v'] = "MAP";
	if (!isset($url_params_arr['s'])) $url_params_arr['s'] = "SOLD";
	
	if (!preg_match("/^[0-9]+$/", $url_params_arr['i'])) {
		$url_params_arr['i'] = $citiesReverse[$url_params_arr['i']];
	}
	if (isset($url_params_arr['q']) && isset($categoriesReverse[$url_params_arr['q']])) {
		eval("\$url_params_arr['c'] = array(" . $categoriesReverse[$url_params_arr['q']] . ");");
		unset($url_params_arr['q']);
	} else {
		eval("\$url_params_arr['c'] = array(" . $url_params_arr['c'] . ");");
	}
	

	$zip = "";
	$zip_lat = "0";
	$zip_lng = "0";

	$sorting_by_distance = false;
	if ($url_params_arr['s'] == "DISTANCE") {
		$url_params_arr['s'] = "SOLD";
		$sorting_by_distance = true;
	}
	



	$get_all_deals = isset($url_params_arr['all']) ? 1 : 0;

	$cache_life = 86400;
	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	$deals_index = array();




	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////
	

	
	

	if (isset($url_params_arr['u'])) {
		$query_saved = "SELECT deal_id FROM Saved777 WHERE user='". $url_params_arr['u'] ."'";
		$result_saved = mysql_query($query_saved, $users_con);

		if (!$result_saved) die('Invalid query (1): ' . mysql_error());
		
		while ($row_saved = @mysql_fetch_assoc($result_saved)) array_push($deals_index, $row_saved["deal_id"]);
 



	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////
	

	
	

	} else if (isset($url_params_arr['m']) && $url_params_arr['v'] == "SINGLE") {

		array_push($deals_index, $_GET["deal_id"]);




	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////
	

	
	
	
	} else if (isset($url_params_arr['q'])) {

		require_once('Solr/Service.php');

		$solr = new Apache_Solr_Service( '10.182.130.173', '8983', '/solr' );

		if ( !$solr->ping() ) {
			echo 'Solr service not responding.';
			exit;
		} else {
		}



		$offset = 0;
		$limit = 10000;

		$query = $url_params_arr['q'];
		$get_all_deals = 1;
		$url_params_arr['x'] = 0;

		$two_weeks_ago = time() - (3600 * 24 * 14);
				
		if ($url_params_arr['i'] == 2) {
			$query = $query . " AND city_id:2 AND last_updated:[" . $two_weeks_ago . " TO *]";
		} else {
			$query = $query . " AND -city_id:2 AND last_updated:[" . $two_weeks_ago . " TO *]";
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

		$deals_index = array_merge($current_deals_index, $expired_deals_index);




	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////
	

	
	

	} else if (isset($url_params_arr['i'])) {

	    $deals_index = getDealsIndex($url_params_arr['s'], $url_params_arr['i'], $deals_con, $memcache, $cache_life);
		
		if ($sorting_by_distance) {
			$zip = $url_params_arr['z'];
			$query = "SELECT * FROM Zipcodes WHERE zip=" . $zip;
			$result = mysql_query($query, $deals_con);

			if (!$result) die('Invalid query: ' . mysql_error());
			
			while ($row = @mysql_fetch_assoc($result)){
				$zip_lat = $row['latitude'];
				$zip_lng = $row['longitude'];
			}
			
			$zip_lat_g = $zip_lat;
			$zip_lat_g = $zip_lng;
			usort($deals_index, "sort_distance");
		}

	}


	
	
	// Apply fiters like category, Yelp review, etc.

	$deals_index_with_filters_applied = array();

	for ($j = 0; $j < count($deals_index); $j++) {

		$deal = getDealById($deals_index[$j], $deals_con, $memcache, $cache_life);
		
		if (isset($deal["Categories"])) {
			$category_id = $deal["Categories"][0]["category_id"];
		} else {
			$category_id = 0;
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
		
		if (!$url_params_arr['c'][$category_id] == 1) continue;
		if ($url_params_arr['n'] == 1 && $discovered_days_ago != 0) continue;
		if ($url_params_arr['y'] != 0 && $deal["yelp_rating"] < $url_params_arr['y']) continue;
		if ($url_params_arr['o'] != 0 && $url_params_arr['o'] != $deal["company_id"]) continue;

		array_push($deals_index_with_filters_applied, $deals_index[$j]);
	}

	
	
	
	
	$deals_index = $deals_index_with_filters_applied;
	


	return $deals_index;
	
}




function sort_distance($a, $b) {

	global $deals_con_g;
	global $memcache_g;
	global $cache_life_g;
	
	global $zip_lat;
	global $zip_lng;
	
	$deal_a = getDealById($a, $deals_con_g, $memcache_g, $cache_life_g);
	$deal_b = getDealById($b, $deals_con_g, $memcache_g, $cache_life_g);

	if (isset($deal_a["Addresses"])) {
		$lat_a = $deal_a["Addresses"][0]["latitude"];
		$lng_a = $deal_a["Addresses"][0]["longitude"];
	} else {
		$lat_a = 0;
		$lng_a = 0;
	}

	if (isset($deal_b["Addresses"])) {
		$lat_b = $deal_b["Addresses"][0]["latitude"];
		$lng_b = $deal_b["Addresses"][0]["longitude"];
	} else {
		$lat_b = 0;
		$lng_b = 0;
	}
	
	$dist_a_to_zip = distance($lat_a, $lng_a, $zip_lat, $zip_lng);
	$dist_b_to_zip = distance($lat_b, $lng_b, $zip_lat, $zip_lng);



	return ($dist_a_to_zip - $dist_b_to_zip);


}




