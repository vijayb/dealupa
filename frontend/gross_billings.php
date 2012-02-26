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
//echo "$query -- ".$_GET['start_time'].", ".$_GET['end_time']."<br />\n";
$offset = 0;
$limit = 100000;
$response = $solr->search($query, $offset, $limit);

$stats = array();
$stats["total_gross_billings"] = 0;
$stats["num_deals"] = 0;
$stats["total_price"] = 0;
$stats["num_priced_deals"] = 0;
$stats["total_coupons"] = 0;
$stats["num_coupon_deals"] = 0;
$stats["total_discount"] = 0;
$stats["num_discount_deals"] = 0;


if ( $response->getHttpStatus() == 200 ) {
  if ( $response->response->numFound > 0 ) {

    
    foreach ( $response->response->docs as $doc ) {
      if (isset($doc->discovered)) {
	$time = $doc->discovered;

	if ($_GET['start_time'] <= $time && $time < $_GET['end_time']) {
	  $stats["num_deals"] += 1;
	  $date = date('Ymd', $time);
	  $nice_date = date('Y-m-d', $time);

	  if (isset($doc->yelp_rating)) {
	    if (isset($yelp_ratings[$doc->yelp_rating])) {
	      $yelp_ratings[$doc->yelp_rating] += 1;
	    } else {
	      $yelp_ratings[$doc->yelp_rating] = 1;
	    }
	  }

	  if (isset($doc->price)) {
	    $stats["total_price"] += $doc->price;
	    $stats["num_priced_deals"] += 1;

	    $price_range = createPrettyPriceRange($doc->price);
	    if (isset($price_distribution[$price_range])) {
	      $price_distribution[$price_range] += 1;
	    } else {
	      $price_distribution[$price_range] = 1;
	    }
	  }

	  if (isset($doc->num_purchased)) {
	    $stats["total_coupons"] += $doc->num_purchased;
	    $stats["num_coupon_deals"] += 1;
	  }

	  if (isset($doc->price) && isset($doc->value) && $doc->price > 0 && $doc->value > $doc->price) {
	    $stats["total_discount"] += $doc->price / $doc->value;
	    $stats["num_discount_deals"] += 1;
	  }

	  if (isset($doc->num_purchased) && isset($doc->price)) {

	    if (isset($gross_billings[$date])) {
	      $gross_billings[$date] += $doc->num_purchased * $doc->price;
	    } else {
	      $gross_billings[$date] = $doc->num_purchased * $doc->price;
	    }
	    $stats["total_gross_billings"] += $doc->num_purchased * $doc->price;
	    $nice_dates[$date] = $nice_date;
	  }
	}
      }

    }


    ksort($gross_billings);
    uksort($price_distribution, "price_cmp");
    $gross_billings_pretty = array();
    foreach ($gross_billings as $date => $billings) {
      $gross_billings_pretty[$nice_dates[$date]] = $billings;
    }
    
    if ($stats["num_priced_deals"] >0) {
      $stats["avg_deal_price"] = round($stats["total_price"] / $stats["num_priced_deals"]);
    }
    if ($stats["num_coupon_deals"] > 0) {
      $stats["avg_coupons_per_deal"] = round($stats["total_coupons"] / $stats["num_coupon_deals"]);
    }

    if ($stats["num_discount_deals"] > 0) {
      $stats["avg_deal_discount"] = round(100*$stats["total_discount"] / $stats["num_discount_deals"]);
    }
    $stats["gross_billings"] = $gross_billings_pretty;
    $stats["price_distribution"] = $price_distribution;
    $stats["yelp_ratings"] = $yelp_ratings;
    
  }
} else {
  echo $response->getHttpStatusMessage();
}

echo json_encode($stats);;

function price_cmp($a, $b) {
  preg_match("/[0-9]+/",$a, $a1);
  preg_match("/[0-9]+/",$b, $b1);

  return $b1 < $a1;
}


function createPrettyPriceRange($price) {
  $range = "$0 - $9.99";
  if ($price >= 10) {
    $var2 = floor(log10($price));
    $var3 = floor($price/pow(10, $var2));
    $low = $var3 * pow(10, $var2);
    $high = ($var3+1) * pow(10, $var2);
    $range = "$".$low." - $".($high-0.01);
  }

  return $range;
}




?>
		
