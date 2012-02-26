<?php

function getDealById($id, $deals_con, $memcache, $cache_life) {


  if (!isset($memcache)) {
    return;
  }

  $deal = $memcache->get($id);

  if ($deal) {
    $deal["cached"] = 1;
    //echo "Deal $id in cache returning\n";
    return $deal;
  }

  return refreshDealById($id, $deals_con, $memcache, $cache_life);
}

function refreshDealById($id, $deals_con, $memcache, $cache_life) {
  //echo "Deal $id not in cache, looking up in database\n";
  $query = "
  SELECT
    id,
    url,
    affiliate_url,
    discovered,
    last_updated,
    company_id,
    title,
    subtitle,
    price,
    value,
    text,
    num_purchased,
    expired,
    deadline,
    expires,
    name,
    website,
    phone,
    yelp_rating,
    yelp_url,
    yelp_categories,
    yelp_review_count,
    yelp_excerpt1,
    yelp_review_url1,
    yelp_user1,
    yelp_rating1,
    yelp_user_url1,
    yelp_user_image_url1,
    yelp_excerpt2,
    yelp_review_url2,
    yelp_user2,
    yelp_rating2,
    yelp_user_url2,
    yelp_user_image_url2,
    yelp_excerpt3,
    yelp_review_url3,
    yelp_user3,
    yelp_rating3,
    yelp_user_url3,
    yelp_user_image_url3,
    upcoming,
    verified
  FROM
    Deals777
  WHERE
    id=$id";

  $result = mysql_query($query, $deals_con);
  if (!$result) {
    return;
  }



  if ($deal = @mysql_fetch_assoc($result)) {
    $deal["cached"] = 0;
    $attributes["Addresses"] =
      "raw_address,street,city,state,zipcode,latitude,longitude";
    $attributes["Images"] = "image_url";
    $attributes["Categories"] = "category_id,rank";
    $attributes["Cities"] = "city_id";
    
    foreach ($attributes as $key => $value) {
      $query = "SELECT $value FROM $key"."777"." WHERE deal_id=$id";
      $result = mysql_query($query);
      
      if ($result && mysql_num_rows($result) > 0) {
	$deal[$key] = array();
	while ($row = @mysql_fetch_assoc($result)) {
	  array_push($deal[$key], $row);
	}
      }
    }
    

  }

  $memcache->set($id, $deal, false, $cache_life);

  return $deal;
}

?>
