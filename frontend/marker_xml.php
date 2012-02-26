<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-type: text/xml");

$MAX_SEARCH_RESULTS = 10000;

// Start XML file, create parent node
$dom = new DOMDocument("1.0");
$parnode = $dom->appendChild($dom->createElement("markers"));

$cache_life = 600; //86400;

if (isset($_GET["category_id"])) {
  $category_id = $_GET["category_id"];
} else {
  // no category
  $category_id = 0;
}

$swLat = $_GET["swLat"];
$swLng = $_GET["swLng"];
$neLat = $_GET["neLat"];
$neLng = $_GET["neLng"];




////////////////////////////////////////////////////////////////
///////////////////// MEMCACHE SECTION /////////////////////////
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
  $deals_index = array();
  

  require_once('Solr/Service.php');

  $solr = new Apache_Solr_Service( '10.182.130.173', '8983', '/solr' );

  if ( !$solr->ping() ) {
    echo 'Solr service not responding.';
    exit;
  } else {
    //echo "Success\n";
  }

  $two_weeks_ago = time() - (3600 * 24 * 14);
  
  $query = $_GET['q'] . " AND -city_id:2 AND last_updated:[" . $two_weeks_ago . " TO *]";
	
  
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
  // See if the index is in memcache:
  $deals_index = $memcache->get("deals_index");
}

/////////////////// END MEMCACHE SECTION ///////////////////////
////////////////////////////////////////////////////////////////





////////////////////////////////////////////////////////////////
///////////////////// CREATE XML SECTION ///////////////////////

// Now that we have an index of all the deal available
// we can create an XML document of the deals matching the GET parameters
// supplied to us (swLat, swLng, neLat, neLng, category_id, search_query).

// get_deal.php is required to use the function getDealById, see below
require "get_deal.php";

//echo count($deals_index)."<BR>\n";
// Iterate through all the deal IDs in deal_index
for ($i = 0; $i < count($deals_index); $i++) {
  // Get the deal object, which just an associative array
  // holding its attributes. E.g., $deal["url"], $deal["title] etc.
  // It also contains arrays $deal["address"], $deal["image"], which
  // contain their own attributes. So e.g., if the deal has an address
  // $deal["address"][0]["street"] will give you the street value of that
  // address, if it has been set.
  $deal = getDealById($deals_index[$i], $deals_con, $memcache, $cache_life);

  if (!isset($deal["Addresses"])) {
    continue;
  }

  $addresses = $deal["Addresses"];
  for ($j=0; $j < count($addresses); $j++) {
    $address = $addresses[$j];

    // If the deal matches the GET parameters, then add it to the
    // XML document we're building
    if (inBoundingBox($address, $swLat, $swLng, $neLat, $neLng) &&
	containsCat($deal, $category_id)) {
      $node = $dom->createElement("marker");
      foreach ($deal as $key => $value) {
		if (!is_array($value)) {
		  // Handles parent attributes (e.g. url, title, price etc).
		 // ...but we don't need the full deal text
 		 if ($key != "text") {
			$node->setAttribute($key, $value);
		   }
		} else if (strcmp($key, "Addresses") != 0) {
		  // The arrays inside $deal have the name of the tables
		  // they were populated from (Images, Categories, Cities).
		  // For the sake of backward compatibility we want to map
		  // those names to their singular form in the XML we generated.
		  // $singular is only used once below to create child nodes
		  // for the node associated with this deal.
		  $singular["Images"] = "image";
		  $singular["Categories"] = "category";
		  $singular["Cities"] = "city";
		  
		  // Handles child attributes (the arrays Addresses, Images,
		  // Categories, Cities)

		  $child_array = $value;
		  for ($k=0; $k < count($child_array); $k++) {
			$child_node = $dom->createElement($singular[$key]);

			foreach ($child_array[$k] as $childkey => $childvalue) {
			  $child_node->setAttribute($childkey, $childvalue);
			}

			$node->appendChild($child_node);
		  }

		}
      }	  

      foreach ($address as $key => $value) {
	$node->setAttribute($key, $value);
      }

      $parnode->appendChild($node);
    }

  }
}

echo $dom->saveXML();
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
