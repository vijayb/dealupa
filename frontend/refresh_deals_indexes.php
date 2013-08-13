<?php

set_time_limit(0);

require_once("array_constants.php");
require("db.php");
require("get_deal.php");
require("dealupa_score.php");

$cache_life = 86400;

$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

if (!$memcache) {
  die ("Error: Unable to connect to memcache\n");
}


if (isset($_GET["reload_cache"])) {
  getDealsIndex("SOLD", 3, $deals_con, $memcache, $cache_life);
}


function getDealsIndex($sort_order, $requested_city_id, $deals_con, $memcache, $cache_life) {
	require("array_constants.php");
	global $deals_con;
	global $memcache;
	global $cache_life;
	
	$return_deals_index = $memcache->get("deals_index_" . $sort_order . "_" . $requested_city_id);

	//	if (!$return_deals_index || isset($_GET["reload_cache"])) {
	if (isset($_GET["reload_cache"])) {

		$query = "
			SELECT id from Deals
			WHERE
			((expired=0) AND (upcoming=0) AND (dup=0) AND 
			 (deadline IS NULL or 
				((TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), deadline))) < 3600)) AND 
			 (deadline IS NOT NULL or
				((TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), discovered))) < 7*24*3600) ))";

			
		$result = mysql_query($query, $deals_con);
		if (!$result) {
		 die('Invalid query 1: ' . mysql_error());
		}

		$deals_index_SOLD = array();
		$deals_index_NEW = array();
		$deals_index_PRICE = array();
		$deals_index_DEADLINE = array();
		
		
		$i = 0;
		while ($row = @mysql_fetch_assoc($result)) {
		
			array_push($deals_index_SOLD, $row['id']);
			array_push($deals_index_NEW, $row['id']);
			array_push($deals_index_PRICE, $row['id']);
			array_push($deals_index_DEADLINE, $row['id']);
			
			$i++;
		}		
		
		usort($deals_index_SOLD, "sort_sold");
		usort($deals_index_NEW, "sort_new");
		usort($deals_index_PRICE, "sort_price");
		usort($deals_index_DEADLINE, "sort_deadline");


		$count = 0;
		foreach ($cities as $city_id => $city_name) {
		  $count++;
		  //if ($count > 3) { break; }

		  $city_index = array();
		  for ($j=0; $j < count($deals_index_SOLD); $j++) {
		    if (inEdition(getDealById($deals_index_SOLD[$j], $deals_con, $memcache, $cache_life), $city_id)) {
		      array_push($city_index, $deals_index_SOLD[$j]);
		    }
		  }
		  $memcache->set("deals_index_SOLD_".$city_id, $city_index, false, $cache_life);
		  echo("deals_index_SOLD_".$city_id.": " . count($city_index) . " items (first: " . $city_index[0] . ", last: " . end($city_index) . " )\n<br>");

		  $city_index = array();
		  for ($j=0; $j < count($deals_index_NEW); $j++) {
		    if (inEdition(getDealById($deals_index_NEW[$j], $deals_con, $memcache, $cache_life), $city_id)) {
		      array_push($city_index, $deals_index_NEW[$j]);
		    }
		  }
		  $memcache->set("deals_index_NEW_".$city_id, $city_index, false, $cache_life);
		  echo("deals_index_NEW_".$city_id.": " . count($city_index) . " items (first: " . $city_index[0] . ", last: " . end($city_index) . " )\n<br>");


		  $city_index = array();
		  for ($j=0; $j < count($deals_index_PRICE); $j++) {
		    if (inEdition(getDealById($deals_index_PRICE[$j], $deals_con, $memcache, $cache_life), $city_id)) {
		      array_push($city_index, $deals_index_PRICE[$j]);
		    }
		  }
		  $memcache->set("deals_index_PRICE_".$city_id, $city_index, false, $cache_life);
		  echo("deals_index_PRICE_".$city_id.": " . count($city_index) . " items (first: " . $city_index[0] . ", last: " . end($city_index) . " )\n<br>");


		  $city_index = array();
		  for ($j=0; $j < count($deals_index_DEADLINE); $j++) {
		    if (inEdition(getDealById($deals_index_DEADLINE[$j], $deals_con, $memcache, $cache_life), $city_id)) {
		      array_push($city_index, $deals_index_DEADLINE[$j]);
		    }
		  }
		  $memcache->set("deals_index_DEADLINE_".$city_id, $city_index, false, $cache_life);
		  echo("deals_index_DEADLINE_".$city_id.": " . count($city_index) . " items (first: " . $city_index[0] . ", last: " . end($city_index) . " )\n<br>");
		}

	
		// Needed for map view, which looks for index with key "deals_index"
		$memcache->set("deals_index", $deals_index_SOLD, false, $cache_life);
		echo("deals_index: " . count($deals_index_SOLD) . " items (first: " . $deals_index_SOLD[0] . ", last: " . end($deals_index_SOLD) . " )\n<br>");
		
		/*
		echo("deals_index_SOLD: " . count($deals_index_SOLD) . " items (first: " . $deals_index_SOLD[0] . ", last: " . end($deals_index_SOLD) . " )\n<br>");
		echo("deals_index_NEW: " . count($deals_index_NEW) . " items (first: " . $deals_index_NEW[0] . ", last: " . end($deals_index_NEW) . " )\n<br>");
		echo("deals_index_PRICE: " . count($deals_index_PRICE) . " items (first: " . $deals_index_PRICE[0] . ", last: " . end($deals_index_PRICE) . " )\n<br>");
		echo("deals_index_DEADLINE: " . count($deals_index_DEADLINE) . " items (first: " . $deals_index_DEADLINE[0] . ", last: " . end($deals_index_DEADLINE) . " )\n<br>");
		
		$memcache->set("deals_index_SOLD", $deals_index_SOLD, false, $cache_life);
		$memcache->set("deals_index_NEW", $deals_index_NEW, false, $cache_life);
		$memcache->set("deals_index_PRICE", $deals_index_PRICE, false, $cache_life);
		$memcache->set("deals_index_DEADLINE", $deals_index_DEADLINE, false, $cache_life);
		*/
		
	} elseif (!$return_deals_index) {
	  print "No index currently available, please wait\n";
	  return;
	}
	
	$return_deals_index = $memcache->get("deals_index_" . $sort_order . "_" . $requested_city_id);
	
	return $return_deals_index;
}




function inEdition(&$deal, $city_id) {
  if ($city_id == 1) {
    // For city_id==1 (i.e., unknown edition) we'll only allow it into the edition index
    // if that's the only edition it has. Many border city deals (e.g., baltimore/washington dc)
    // might also have an edition of 1 too, and we don't really want to add them to the index,
    // which would become very large if we did let them in. This means that the unknown edition
    // might be missing some deals. We'll live with that.
    return isset($deal["Cities"]) && count($deal["Cities"]) == 1 && hasCityId($deal, 1);
  } else if ($city_id == 2) {
    return hasCityId($deal, 2);
  } else {
    return inBoundingBox($deal, $city_id);
  }
}


// Check whether the deal falls in the provided bounding box
// For this to be true the deal has to have at least one address
// that has latitude and longitude defined.
function inBoundingBox(&$deal, $city_id) {
    
  global $swLat, $swLng, $neLat, $neLng;    
    
  // Category 43 is "Road trip". We only allow Road trip deals to be
  // "inBoundingBox" if they're within the lat-long for the city.
  // We do this because some deal sites put Road Trip deals in many
  // editions, and we don't want to show them in all those Dealupa
  // editions. We let "Around the World" (cat 42) through tho, because
  // those deals are actually truly interesting in all editions.
  //
  // Also we only do the hasCityId check if the deal has no addresses.
  // So for example, a deal which has an address in San Francisco
  // but also has Seattle as a city_id, is NOT going to be allowed
  // into the Seattle edition. We only pay attention to the city_id
  // if a deal has no address.
  if (!isset($deal["Addresses"]) && hasCityId($deal, $city_id) && !containsCat($deal, 43)) {
    return 1;
  }

  if (isset($deal["Addresses"])) {
    $addresses = $deal["Addresses"];
    for ($k=0; $k < count($addresses); $k++) {
      $address = $addresses[$k];

      if (isset($address["latitude"]) && isset($address["longitude"]) &&
                    $swLat[$city_id] < $address["latitude"] && $address["latitude"] < $neLat[$city_id] &&
	  $swLng[$city_id] < $address["longitude"] && $address["longitude"] < $neLng[$city_id])
	{
	  return 1;
	}
    }
  }

  return 0;
}


function hasCityId(&$deal, $city_id) {
  
  if (isset($deal["Cities"])) {
    $cities = $deal["Cities"];
    $seen_city = 0;
    $seen_national = 0;
	
    for ($k=0; $k < count($cities); $k++) {
      $city = $cities[$k];
      
      if ($city["city_id"] == $city_id) {
		$seen_city = 1;
      }

      if ($city["city_id"] == 2) {
		$seen_national = 1;
      }
    }
    
    if ($seen_city && (!$seen_national || $city_id==2)) {
      return 1;
    }
  }


  return 0;
}


function containsCat(&$deal, $category_id) {

  if (!isset($deal["Categories"])) {
    return 0;
  }

  $categories = $deal["Categories"];
  for ($k=0; $k < count($categories); $k++) {
    if ($categories[$k]["category_id"] == $category_id) {
      return 1;
    }
  }

  return 0;
}


function sort_sold($a, $b) {
  global $deals_con;
  global $memcache;
  global $cache_life;
  
  $deal_a = getDealById($a, $deals_con, $memcache, $cache_life);
  $deal_b = getDealById($b, $deals_con, $memcache, $cache_life);
  
  $score_a = dealupaScore($deal_a);
  $score_b = dealupaScore($deal_b);
  
  return ($score_b - $score_a);
}




function sort_new($a, $b) {
  global $deals_con;
  global $memcache;
  global $cache_life;
  
  $deal_a = getDealById($a, $deals_con, $memcache, $cache_life);
  $deal_b = getDealById($b, $deals_con, $memcache, $cache_life);
  
  $discovered_a = $deal_a["discovered"];
  $discovered_b = $deal_b["discovered"];
  
  return ($discovered_b - $discovered_a);
}



function sort_price($a, $b) {
  global $deals_con;
  global $memcache;
  global $cache_life;
  
  $deal_a = getDealById($a, $deals_con, $memcache, $cache_life);
  $deal_b = getDealById($b, $deals_con, $memcache, $cache_life);
  
  $price_a = $deal_a["price"];
  $price_b = $deal_b["price"];
  
  if (!isset($price_a)) $price_a = 1000000;
  if (!isset($price_b)) $price_b = 1000000;
  
  return ($price_a - $price_b);
}

function sort_deadline($a, $b) {
  global $deals_con;
  global $memcache;
  global $cache_life;
  
  $deal_a = getDealById($a, $deals_con, $memcache, $cache_life);
  $deal_b = getDealById($b, $deals_con, $memcache, $cache_life);
  
  $deadline_a = $deal_a["deadline"];
  $deadline_b = $deal_b["deadline"];
  
  if ($deadline_a == "") $deadline_a = 1000000000000;
  if ($deadline_b == "") $deadline_b = 1000000000000;
  
  return ($deadline_a - $deadline_b);
}


?>
