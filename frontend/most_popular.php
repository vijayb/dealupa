<meta charset="utf-8" />

<?php

set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);
$MAX_RESULTS = 10;
if (isset($_GET["max_results"])) {
  $MAX_RESULTS = $_GET["max_results"];
}

require('Solr/Service.php');

$solr = new Apache_Solr_Service('50.57.106.212', '8983', '/solr');

if (!isset($_GET['start_time']) || !isset($_GET['end_time'])) {
  echo "No start or end time specified. Need a time range!\n";
  exit;
}

if (!$solr->ping()) {
  echo 'Query failed.';
  exit;
}


$start_time = $_GET['start_time'] - 100;
$end_time = $_GET['end_time'] + 100;

$query = "dup:0 AND num_purchased:[1 TO *] AND discovered:[$start_time TO $end_time]";

if (isset($_GET['yelp_rating']) && $_GET['yelp_rating'] != 0) {
  $query .= " AND yelp_rating:".$_GET['yelp_rating'];
}

if (isset($_GET['low_price']) && isset($_GET['high_price']) &&
    $_GET['high_price'] > 0) {
  $query .= " AND price:[".$_GET['low_price']." TO ".$_GET['high_price']."]";

}

if (isset($_GET['company_id']) && $_GET['company_id'] != "0") {
  if ($_GET['company_id'] == "2") {
    $query .= " AND (company_id:2 OR company_id:17 OR company_id:35)";
  } else {
    $query .= " AND company_id:".$_GET['company_id'];
  }
}
if (isset($_GET['category_id']) && $_GET['category_id'] != "0") {
  $query .= " AND category_id:".$_GET['category_id'];
}

if (isset($_GET['city_id']) && $_GET['city_id'] != "0") {
  if ($_GET['city_id'] != "2") {
    $query .= " AND (city_id:".$_GET['city_id']." AND -city_id:2 AND -category_id:9)";
  } else {
    $query .= " AND city_id:".$_GET['city_id'];
  }
}




//$query = $_GET['q'];
echo "$query -- ".$_GET['start_time'].", ".$_GET['end_time']."<br />\n";
$offset = 0;
$limit = 100000;
$response = $solr->search($query, $offset, $limit);

if ($response->getHttpStatus() == 200) {
  if ($response->response->numFound > 0) {

    
    foreach ( $response->response->docs as $doc ) {
      $time = $doc->discovered;
      if (isset($doc->discovered) &&
	  matchesTime($time, $_GET['start_time'], $_GET['end_time'])) {
	
	if (isset($_GET['num_purchased'])) {
	  if (isset($doc->num_purchased)) {
	    $most_popular[$doc->id] = $doc->num_purchased;
	  }
	} else {
	  if (isset($doc->num_purchased) && isset($doc->price)) {
	    $most_popular[$doc->id] = $doc->num_purchased * $doc->price;
	  }
	}
      }
    }

    require("get_deal.php");
    require("db.php");
    require("deal_html_from_deals_index.php");
    $count = 0;
    arsort($most_popular);
    foreach ($most_popular as $deal_id => $value) {
      $html = deal_html_from_deal_id($deal_id, $deals_con, 0, 0, 1);
      echo "$html";
      $count++;
      if ($count >= $MAX_RESULTS) {
	break;
      }
    }
    
    echo "<br>\n";
  }
} else {
  echo $response->getHttpStatusMessage();
}

function matchesTime($discovered, $start_time, $end_time) {
  if ($start_time <= $discovered && $discovered < $end_time) {
    return 1;
  }
  return 0;

}

?>
