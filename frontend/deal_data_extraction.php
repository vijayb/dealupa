<?php

if (isset($deal["affiliate_url"]) && $deal["affiliate_url"] != "") {
	$deal_site_url = $deal["affiliate_url"];
} else {
	$deal_site_url = $deal["url"];
}

$discovered_days_ago = days_ago($deal["discovered"]);
if ($discovered_days_ago == 0) {
	$discovered_string = "Posted today!";
} else if ($discovered_days_ago == 1) {
	$discovered_string = "Posted yesterday";
} else {
	$discovered_string = "Posted " . $discovered_days_ago . " days ago";
}

if ($discovered_days_ago == 0) {
	$today_attr = 1;
} else {
	$today_attr = 0;
}

$price = $deal["price"];
$value = $deal["value"];

if ($price == "") {
	$price = 0;
	$price_attr = 1000000;	
} else {
	$price_attr = $price;
}

if ($value == "") {
	$value = 0;
}

if ($price >= 20) {
	$price = round($price, 0);
	$value = round($value, 0);
} else if ($price < 20) {
	$price = round($price, 2);
	$value = round($value, 2);
}

$discount = round(get_discount($deal["price"], $deal["value"]), 2);

if ($deal["num_purchased"] != "") {
	$num_purchased_string = number_format($deal["num_purchased"]);
} else {
	$num_purchased_string = "";
}

$time_left_arr = time_left($deal["deadline"]);
$time_left_string = "";
if ($time_left_arr["d"] != 0) $time_left_string .= $time_left_arr["d"] . "<span class='expires-in-units'>d</span>&#160;&#160;";
if ($time_left_arr["hr"] != 0) $time_left_string .= $time_left_arr["hr"] . "<span class='expires-in-units'>hr</span>&#160;&#160;";		
if ($time_left_arr["min"] != 0) $time_left_string .= $time_left_arr["min"] . "<span class='expires-in-units'>min</span>&#160;&#160;";
$time_left_string .= "to go";


$opacity = 1;
if ($deal["upcoming"] == 1) {
	$time_left_string = "<span style='color:#659829;font-weight:700'>UPCOMING DEAL</span>";
	$expired_attr = 0;
	$opacity = 0.6;
} else if (($deal["deadline"] != "" && has_expired($deal["deadline"])) || $deal["expired"] == 1) {
	$time_left_string = "<span style='color:#a20000;font-weight:700'>Expired</span>";
	$expired_attr = 1;
	$opacity = 0.6;
} else if ($deal["deadline"] == "") {
	$expired_attr = 0;
	$time_left_string = "Currently available";
} else {
	$opacity = 1;
	$expired_attr = 0;
}


$name = $deal["name"];
if (isset($deal["website"])) {
	$name_linked = "<a href='" . $deal['website'] . "' target='_blank'>" . $deal["name"] . "</a>";
	$business_website = $deal['website'];
} else {
	$name_linked = $deal["name"];
	$business_website = "";
}
		
if (isset($deal["Categories"])) {
	$category_arr = array();
	for($k = 0; $k < count($deal["Categories"]); $k++) {
		$category_arr[$k] = $deal["Categories"][$k]["category_id"];
	}	
	
} else {
	$category_arr[0] = 0;
}

$catgegory_arr_str = implode(",", $category_arr);


$seen_cities_str = "";
if (isset($deal["Addresses"])) {
	$street = $deal["Addresses"][0]["street"];
	$city = $deal["Addresses"][0]["city"];
	$state = $deal["Addresses"][0]["state"];
	$latitude = $deal["Addresses"][0]["latitude"];
	$longitude = $deal["Addresses"][0]["longitude"];
	

	// An array of cities we've already seen for this deal
	$seen_cities = array();
	
	// Push the 0th address since we've seen it...
	array_push($seen_cities, $city);
	
	// Iterate through remaining addresses and add any city we haven't already seen
	for($k = 0; $k < count($deal["Addresses"]); $k++) {
		$current_city = $deal["Addresses"][$k]["city"];
		if (!in_array($current_city, $seen_cities)) {
			array_push($seen_cities, $current_city);
			$seen_cities_str .= $current_city . ", ";
		}
	}
	$seen_cities_str = rtrim($seen_cities_str, ", ");
	if ($seen_cities_str != "") {
		$seen_cities_str = "Also available in " . $seen_cities_str . "<br>";
	}
	
	if ($city != "") {
		$city = $city . ", ";
	}
	
	if ($street != "") {
		$street = $street . ", ";
	}

} else {
	$street = "";
	$city = "";
	$state = "";
	$latitude = "";
	$longitude = "";
}


/*
if (isset($deal["Images"])) {
	$image_url = $deal["Images"][0]["image_url"];
} else {
	$image_url = "";
}
*/

$yelp_rating = str_replace(".", "", $deal["yelp_rating"]);
$yelp_attr = $yelp_rating ? $yelp_rating : -1;

$yelp_excerpt1 = $deal["yelp_excerpt1"];
$yelp_review_url1 = $deal["yelp_review_url1"]; 
$yelp_user1 = $deal["yelp_user1"]; 
$yelp_rating1 = $deal["yelp_rating1"]; 
$yelp_user_url1 = $deal["yelp_user_url1"]; 
$yelp_user_image_url1 = $deal["yelp_user_image_url1"]; 

$yelp_excerpt2 = $deal["yelp_excerpt2"]; 
$yelp_review_url2 = $deal["yelp_review_url2"]; 
$yelp_user2 = $deal["yelp_user2"]; 
$yelp_rating2 = $deal["yelp_rating2"]; 
$yelp_user_url2 = $deal["yelp_user_url2"]; 
$yelp_user_image_url2 = $deal["yelp_user_image_url2"]; 

$yelp_excerpt3 = $deal["yelp_excerpt3"]; 
$yelp_review_url3 = $deal["yelp_review_url3"]; 
$yelp_user3 = $deal["yelp_user3"]; 
$yelp_rating3 = $deal["yelp_rating3"]; 
$yelp_user_url3 = $deal["yelp_user_url3"]; 
$yelp_user_image_url3 = $deal["yelp_user_image_url3"]; 


if (isset($force_edition)) {
	if ($force_edition == 0) {
		$edition = 0;
	} else {
		$edition = $force_edition;
	}
}

$cities_arr_str = "";
if (isset($deal["Cities"])) {
	$cities_arr_str = "[";
	for($k = 0; $k < count($deal["Cities"]); $k++) {
		$cities_arr_str .= "" . $deal["Cities"][$k]["city_id"] . ", ";	

		if (isset($force_edition)) {
			if ($force_edition == 0 && $deal["Cities"][$k]["city_id"] != 1) {
				$edition = $deal["Cities"][$k]["city_id"];
			}
		}

	}
	$cities_arr_str = rtrim($cities_arr_str, ", ");
	$cities_arr_str .= "]";
}



// http://dealupa_images.s3.amazonaws.com/27d9acdfa8797f1a8d62a1090aa410bb2cd0f05d

$image_url = "http://dealupa_images.s3.amazonaws.com/";

$image_arr_str = "";
if (isset($deal["Images"])) {
	$image_arr_str = "[";
	for($k = 0; $k < count($deal["Images"]); $k++) {
		
		$image_arr_str .= "'http://dealupa_images.s3.amazonaws.com/" . sha1($deal["Images"][$k]["image_url"]) . "', ";
	}
	$image_arr_str = rtrim($image_arr_str, ", ");
	$image_arr_str .= "]";
	$image_url .= sha1($deal["Images"][0]["image_url"]) . "_small";
} else {
	$image_url = "";
}



$link_to_single_deal_page = "/" . $cities_url[$edition] . "/daily-deals/" . $deal["id"] . "-" . hyphenate_title($deal["title"]);

$lats_arr_str = "";
$lngs_arr_str = "";

if (isset($deal["Addresses"])) {
	$lats_arr_str = "[";
	$lngs_arr_str = "[";
	for($k = 0; $k < count($deal["Addresses"]); $k++) {
		$lats_arr_str .= "" . $deal["Addresses"][$k]["latitude"] . ", ";
		$lngs_arr_str .= "" . $deal["Addresses"][$k]["longitude"] . ", ";
	}
	$lats_arr_str = rtrim($lats_arr_str, ", ");
	$lngs_arr_str = rtrim($lngs_arr_str, ", ");
	
	$lats_arr_str .= "]";
	$lngs_arr_str .= "]";
}

?>
