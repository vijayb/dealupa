<?php
/////////////////////////////////////////////////////////////////////////////////////////////
//////// Builds an index based on the schema found in dealmix/solr/conf/schema.xml //////////


// Building an index can take time, so don't let the script timeout:
set_time_limit(0);

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('Solr/Service.php');

$solr = new Apache_Solr_Service('localhost', '8983', '/solr');

if (!$solr->ping()) {
  echo 'Error: Unable to connect to Solr server.';
  exit;
}

// If we haven't previously updated the cache, we default to refreshing
// all deals which have been updated within the last $default_update_age
// seconds in the database.
$default_update_age = 3600*14*24; // 2 weeks

$cache_life = $default_update_age + 86400; // + one day



////////////////////////////////////////////////////////////////
///////////////////// MEMCACHE SECTION /////////////////////////
require 'db.php';
require 'get_deal.php';

$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);
if (!$memcache) {
  die ("Error: Unable to connect to memcache.\n");
}


// See if last_updated (the last time we refreshed the cache
// with all the latest deals) is in memcache.
$last_updated = $memcache->get("solr_last_updated");

// reload_cache can be used as a parameter to force a load
// on all deals updated since $default_update_age
if (!$last_updated || isset($_GET["reload_cache"])) {
  $last_updated = time() - $default_update_age;
}

$seconds_since_last_updated = (time() - $last_updated) + 60;



$solr_docs = array();

$query = 
  "SELECT id from Deals777
   WHERE TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), last_updated)) < 
   $seconds_since_last_updated";

$result = mysql_query($query);
if (!$result) {
  die('Error: failed query ' . mysql_error());
}

$count = 0;


while ($row = @mysql_fetch_assoc($result)) {
  $deal = refreshDealById($row['id'], $memcache, $cache_life);
  $solr_doc = new Apache_Solr_Document();

  setAttribute("id", $solr_doc, $deal, 0);
  setAttribute("url", $solr_doc, $deal, 0);
  setAttribute("company_id", $solr_doc, $deal, 0);
  setAttribute("title", $solr_doc, $deal, 1);
  setAttribute("subtitle", $solr_doc, $deal, 1);
  setAttribute("text", $solr_doc, $deal, 1);
  setAttribute("name", $solr_doc, $deal, 1);

  setAttribute("num_purchased", $solr_doc, $deal, 0);
  setAttribute("fb_likes", $solr_doc, $deal, 0);
  setAttribute("fb_shares", $solr_doc, $deal, 0);

  setAttribute("price", $solr_doc, $deal, 0);
  setAttribute("value", $solr_doc, $deal, 0);
  if (isset($deal['price']) && isset($deal['value']) && $deal['value'] > 0) {
    $solr_doc->discount = ($deal['value'] - $deal['price']) / $deal['value'];
  }

  setAttribute("dup", $solr_doc, $deal, 0);
  setAttribute("dup_id", $solr_doc, $deal, 0);

  setAttribute("expired", $solr_doc, $deal, 0);
  setAttribute("upcoming", $solr_doc, $deal, 0);

  setAttribute("yelp_rating", $solr_doc, $deal, 0);
  setAttribute("yelp_review_count", $solr_doc, $deal, 0);

  setTimeAttribute("discovered", $solr_doc, $deal);
  setTimeAttribute("last_updated", $solr_doc, $deal);
  setTimeAttribute("deadline", $solr_doc, $deal);
  
  setMultiValueAttribute("Categories", "category_id", $solr_doc, $deal);
  setMultiValueAttribute("Cities", "city_id", $solr_doc, $deal);
  setMultiValueAttribute("Addresses", "zipcode", $solr_doc, $deal);
  // setMultiValueAttribute("Addresses", "city", $solr_doc, $deal);
  // setMultiValueAttribute("Addresses", "state", $solr_doc, $deal);
  setLocationAttribute($solr_doc, $deal);

  $solr_docs[] = $solr_doc;
  $count++;
}


echo "$count documents to be added to index. ";


try {
  $solr->addDocuments($solr_docs);
  $solr->commit();
  $solr->optimize();
} catch (Exception $e) {
  echo $e->getMessage();
  exit;
}

$memcache->set("solr_last_updated", time(), false, $default_update_age);
echo "Success.";

///////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////// HELPER FUNCTIONS /////////////////////////////////////////
function setAttribute($attribute, &$solr_doc, &$deal, $clean_text) {
  if (isset($deal[$attribute])) {
    if ($clean_text) {
      $solr_doc->$attribute = preg_replace("/[^a-zA-Z0-9\s]/", " ", $deal[$attribute]);
    } else {
      $solr_doc->$attribute = $deal[$attribute];
    }
  }
}


function setTimeAttribute($attribute, $solr_doc, $deal) {
  if (isset($deal[$attribute])) {
    $time = strtotime($deal[$attribute]);
    if ($time != FALSE) {
      $solr_doc->$attribute = $time;
    }
  }
}

function setMultiValueAttribute($field_name, $attribute, &$solr_doc, &$deal) {
  if (isset($deal[$field_name])) {
    $fields = $deal[$field_name];
    for ($k=0; $k < count($fields); $k++) {
      if (isset($fields[$k][$attribute])) {
	$solr_doc->setMultiValue($attribute, $fields[$k][$attribute]);
      }
    }
  }
}

function setLocationAttribute(&$solr_doc, &$deal) {
  if (isset($deal["Addresses"])) {
    $addresses = $deal["Addresses"];
    for ($k=0; $k < count($addresses); $k++) {
      if (isset($addresses[$k]["latitude"]) && isset($addresses[$k]["longitude"])) {
	$solr_doc->setMultiValue("location", $addresses[$k]["latitude"].",".$addresses[$k]["longitude"]);
      }
    }
  }
}


?>
