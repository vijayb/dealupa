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
    UNIX_TIMESTAMP(discovered) as discovered,
    UNIX_TIMESTAMP(last_updated) as last_updated,
    company_id,
    recommend,
    title,
    subtitle,
    price,
    value,
    text,
    num_purchased,
    expired,
    UNIX_TIMESTAMP(deadline) as deadline,
    UNIX_TIMESTAMP(expires) as expires,
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
    Deals
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
    $attributes["NumPurchased"] = "num_purchased,UNIX_TIMESTAMP(time) as time";
    
    foreach ($attributes as $key => $value) {
      $query = "SELECT $value FROM $key WHERE deal_id=$id";
      $result = mysql_query($query);
      
      if ($result && mysql_num_rows($result) > 0) {
	$deal[$key] = array();
	while ($row = @mysql_fetch_assoc($result)) {
	  array_push($deal[$key], $row);
	}
      }
    }
    setTrending($deal);

  } else {
		// ID was not valid; a deal with this ID did not exist in the DB
		return false;
}


  $memcache->set($id, $deal, false, $cache_life);

  return $deal;
}




// Based on a deal's purchase history determining if it's trending (code 1),
// super-trending (code 2) or ultra-trending (code 3).
// Code 1 is probably in the order of 10% of current deals
// Code 2 is probably in the order of 2% of current deals
// Code 3 is very rare. Deals which have it probably should get a huge ranking boost.
function setTrending(&$deal) {
  $time_multiplier = 9;
  // We're stricter with Groupon and LivingSocial in determining
  // if their deals are trending, because they sell so many deals
  if ($deal["company_id"] == 1 || $deal["company_id"] == 2 || $deal["company_id"] == 35) {
    $time_multiplier = 3.7;
  } else if ($deal["company_id"] == 5 || $deal["company_id"] == 12 || $deal["company_id"] == 17) {
    // Travelzoo/Amazon local (second tier sites)
    $time_multiplier = 7;
  }

  if (!isset($deal["num_purchased"])) {
    return;
  }
  $purchased = $deal["num_purchased"];
  if ($purchased > 5000) {
    $growth = 0.3;
    $time_window = 12*3600;
  } else if ($purchased > 1000) {
    $growth = 0.4;
    $time_window = 8*3600;
  } else if ($purchased > 500) {
    $growth = 0.5;
    $time_window = 5*3600;
  } else if ($purchased > 200) {
    $growth = 0.6;
    $time_window = 4*3600;
  } else if ($purchased >= 100) {
    $growth = 0.7;
    $time_window = 3*3600;
  } else {
    return;
  }
  
  $time_window = $time_window * $time_multiplier;
  
  if (isset($deal["NumPurchased"])) {
    for ($i=count($deal["NumPurchased"]) -1; $i >=0; $i--) {

      // If 100 vouchers were sold for the deal in the last 3 hours
      // it's super-trending (code 2).
      if (time() - $deal["NumPurchased"][$i]["time"] < 3*3600) {
	if ($deal["NumPurchased"][$i]["num_purchased"] <= ($purchased - 100)) {
	  $deal["trending"] = 2;
	  break;
	}
      }

      // If 1500 vouchers were sold for the deal in the last 12 hours
      // it's ultra-trending (code 3). This will be very rare.
      if (time() - $deal["NumPurchased"][$i]["time"] < 12*3600) {
	if ($deal["NumPurchased"][$i]["num_purchased"] <= ($purchased - 1500)) {
	  $deal["trending"] = 3;
	  break;
	}
      }


      if (time() - $deal["NumPurchased"][$i]["time"] < $time_window) {
	if ($deal["NumPurchased"][$i]["num_purchased"] <= ((1-$growth)*$purchased)) {
	  $deal["trending"] = 1;
	  //echo "Set trending for deal, company id: ".$deal["company_id"]." with id:".$deal["id"]."  $growth, $time_window, ".(time() - $deal["NumPurchased"][$i]["time"]).",$purchased - ".$deal["NumPurchased"][$i]["num_purchased"]."<BR>\n";
	  break;
	}
      } else {
	break;
      }

    }
  }
}


?>
