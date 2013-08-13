<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


$MAX_SEARCH_RESULTS = 10000;

$cache_life = 600;

if (isset($_GET["category_id"])) {
	$category_id = $_GET["category_id"];
} else {
	$category_id = 0;
}


$swLat = $_GET["swLat"];
$swLng = $_GET["swLng"];
$neLat = $_GET["neLat"];
$neLng = $_GET["neLng"];

$categories = get_simple_categories_array();


////////////////////////////////////////////////////////////////
///////////////////// SEARCH SECTION ///////////////////////////
require 'db.php';

// The point of this section is to populate $deals_index, an array
// containing the ID of all deals that are current (not expired,
// and whose deadline hasn't passed)
$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

if (isset($_GET['q'])) {
  // if called with the GET parameter "search_query", query
  // the index to find which deal URLs match the query and store
  // them in $search_results
	
  require_once('Solr/Service.php');
  $deals_index = array();

  $solr = new Apache_Solr_Service( '10.182.130.173', '8983', '/solr' );

  if ( !$solr->ping() ) {
    echo 'Solr service not responding.';
    exit;
  } else {
    //echo "Success\n";
  }

  $query = $_GET['q'] . " AND -city_id:2 AND last_updated:[-160000 TO 0]";
	
  
  $offset=0;
  $response = $solr->search($query, $offset, $MAX_SEARCH_RESULTS);

  
  
  if ($response->getHttpStatus() == 200) {
    if ($response->response->numFound > 0) {
      foreach ($response->response->docs as $doc) {
	  array_push($deals_index, $doc->id);
      }
    }
  } else {
    echo $response->getHttpStatusMessage();
  }


} else {
	$deals_index = $memcache->get("deals_index");
}

/////////////////// END SEARCH SECTION /////////////////////////
////////////////////////////////////////////////////////////////


require "get_deal.php";

$markers_array = array();

for ($i = 0; $i < count($deals_index); $i++) {

	$deal = getDealById($deals_index[$i], $deals_con, $memcache, $cache_life);

	if (!isset($deal["Addresses"])) {
		continue;
	}

	$addresses = $deal["Addresses"];
	for ($j=0; $j < count($addresses); $j++) {
		
		$address = $addresses[$j];

		if (inBoundingBox($address, $swLat, $swLng, $neLat, $neLng) && containsCat($deal, $category_id)) {

			$marker = array();
			$marker["id"] = $deal["id"];
			$marker["t"] = $address["latitude"];
			$marker["g"] = $address["longitude"];
			$marker["y"] = $deal["yelp_rating"];
			$marker["n"] = 1;
			$marker["p"] = $deal["price"];
			$marker["o"] = $deal["company_id"];
			
			
			if (isset($deal["Categories"])) {
				$marker["c"] = $deal["Categories"][0]["category_id"];
			}
			
			array_push($markers_array, $marker);
		}
	}
}


echo(json_encode($markers_array));


///////////////////// CREATE XML SECTION ///////////////////////
////////////////////////////////////////////////////////////////




////////////////////// HELPER FUNCTIONS ////////////////////////
////////////////////////////////////////////////////////////////


// Check whether the address falls in the provided bounding box
// For this to be true the address has to have both latitude
// and longitude defined
function inBoundingBox($address, $swLat, $swLng, $neLat, $neLng) {
  if (isset($address["latitude"]) && isset($address["longitude"]) &&
      $swLat < $address["latitude"] && $address["latitude"] < $neLat &&
      $swLng < $address["longitude"] && $address["longitude"] < $neLng)
    {
      return 1;
    }

  return 0;
}

// If category_id is provided as a get parameter, we only want to add
// deals in the XML which match the category_id. A category_id of 0
// means that all deals should be accepted.
function containsCat($deal, $category_id) {
  if ($category_id==0) {
    return 1;
  }

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


?>
