<?php
$time1 = microtime(true);
ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once("array_constants.php");
require_once("helpers.php");


$cache_life = 86400;
$MAX_SEARCH_RESULTS = 300;

$prefix = "";


// Remember, $m is set in index.php if index.php is fetching a single deal view

if (isset($_GET["m"])) {
	$single_deal_to_display = $_GET["m"];
}



$edition = -1;
if (isset($_GET["i"])) {
	$i_param = $_GET["i"];
	if (!preg_match("/^[0-9]+$/", $i_param)) {
		$edition = $citiesReverse[$i_param];
	} else {
		$edition = $i_param;
	}
}



$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

require_once("refresh_deals_indexes.php");

$deal = getDealById($single_deal_to_display, $deals_con, $memcache, $cache_life);
$deal_discovered = days_ago($deal["discovered"]);


$discovered_attr = strtotime(str_replace("-", "/", $deal["discovered"]));

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
	$num_purchased_string = "<span style='color:#ffffff; font-size:28px; text-align:center; font-weight:700'>" . number_format($deal["num_purchased"]) . "</span><br>sold";
} else {
	$num_purchased_string = "";
}

$deadline_attr = strtotime(str_replace("-", "/", $deal["deadline"]));
if ($deadline_attr == "") $deadline_attr = 2000000000;

$time_left_arr = time_left($deal["deadline"]);
$time_left_string = "<span style='font-weight:700'>";
if ($time_left_arr["d"] != 0) $time_left_string .= $time_left_arr["d"] . " <span class='expires-in-units'>d</span> ";
if ($time_left_arr["hr"] != 0) $time_left_string .= $time_left_arr["hr"] . " <span class='expires-in-units'>hr</span> ";		
if ($time_left_arr["min"] != 0) $time_left_string .= $time_left_arr["min"] . " <span class='expires-in-units'>min</span> ";
$time_left_string .= "</span> to go";


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
	$time_left_string = "This deal is currently available";
} else {
	$opacity = 1;
	$expired_attr = 0;
}


		
if (isset($deal["website"])) {
	$name = "<a href='" . $deal['website'] . "' target='_blank'>" . $deal["name"] . "</a>";
} else {
	$name = $deal["name"];
}
		
if (isset($deal["Categories"])) {
	$category_id = $deal["Categories"][0]["category_id"];
} else {
	$category_id = 0;
}


$seen_cities_str = "";
if (isset($deal["Addresses"])) {
	$street = $deal["Addresses"][0]["street"];
	$city = $deal["Addresses"][0]["city"];
	$state = $deal["Addresses"][0]["state"];
	$latitude = $deal["Addresses"][0]["latitude"];
	$longitude = $deal["Addresses"][0]["longitude"];

	// Set $edition only if it has not been set yet (by the URL parameter)
	if ($edition == -1) {
		$edition = calculate_city_edition_from_lat_lng($latitude, $longitude);
	}

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
	
	$city = $city . ", ";

} else {
	$street = "";
	$city = "";
	$state = "";
	$latitude = "";
	$longitude = "";
}


if (isset($deal["Images"])) {
	$image_url = $deal["Images"][0]["image_url"];
} else {
	$image_url = "";
}

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

$cities_arr_str = "";
if (isset($deal["Cities"])) {
	$cities_arr_str = "[";
	for($k = 0; $k < count($deal["Cities"]); $k++) {
		$cities_arr_str .= "" . $deal["Cities"][$k]["city_id"] . ", ";
	}
	$cities_arr_str = rtrim($cities_arr_str, ", ");
	$cities_arr_str .= "]";
}		

$link_to_single_deal_page = "/" . $cities_url[$edition] . "/daily-deals/" . $deal["id"] . "-" . hyphenate_title($deal["title"]);




$image_url = "";
$image_arr_str = "";
if (isset($deal["Images"])) {
	$image_url = $deal["Images"][0]["image_url"];
	$image_arr_str = "[";
	for($k = 0; $k < count($deal["Images"]); $k++) {
		$image_arr_str .= "'" . $deal["Images"][$k]["image_url"] . "', ";
	}
	$image_arr_str = rtrim($image_arr_str, ", ");
	$image_arr_str .= "]";
	$image_url = $deal["Images"][0]["image_url"];
} else {
	$image_url = "";
}

$cities_arr_str = "";
if (isset($deal["Cities"])) {
	$cities_arr_str = "[";
	for($k = 0; $k < count($deal["Cities"]); $k++) {
		$cities_arr_str .= "" . $deal["Cities"][$k]["city_id"] . ", ";
	}
	$cities_arr_str = rtrim($cities_arr_str, ", ");
	$cities_arr_str .= "]";
}


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

// If the deal still has no city ID, set it to nationwide
if ($edition == -1) {
	$edition = 2;
}



$html = <<<HTML


<span id="single-deal-data" image_arr_str="{$image_arr_str}" deal_edition="{$edition}" cities_arr_str="{$cities_arr_str}" lats_arr_str="{$lats_arr_str}" lngs_arr_str="{$lngs_arr_str}" title="{$deal["title"]}" deal_id="{$deal["id"]}" category_id="{$category_id}" full_url="{$link_to_single_deal_page}"></span>

	<div style="width:100%; overflow:hidden;">
		<div style="float:left; width:600px;">
			<h1>{$deal["title"]}</h1>
			<h2>{$deal["subtitle"]}</h2>

			<div style="margin-top:10px;">
				<span class="category category-{$category_id}">{$categories[$category_id]}</span>
			</div>
			
			<div style="margin-top:10px;">
				<a id="single-yelp-link" href="{$deal['yelp_url']}" target="_blank">
					<img src="/images/yelp/yelp_{$yelp_rating}.png"> - {$deal["yelp_review_count"]} reviews
				</a>
			</div>
			
			<a href="">{$name},</a> {$street}, {$city} {$state}

		</div>

		<div id="single-deal-coupon">
			<table width=100%>
				<tr>
				<td width=50% style="text-align:center;"><span style="color:#ffffff; font-size:28px; text-align:center; font-weight:700">\${$price}</span><br>for \${$value} value</td>
				<td width=50% style="text-align:center;">{$num_purchased_string}</td>
				</tr>
			</table>

			<a href="{$deal_site_url}" target="_blank" class="big-button orange-gradient" style="margin:10px 0px;" onclick='

mpq.track("Clicked to external deal site", {
	"mp_note": "Clicked on {$deal["id"]} - " + outputURL(),
	"Company": "{$companies[$deal["company_id"]]}",
	"Category": "{$categories[$category_id]}",
	"Yelp": "{$deal["yelp_rating"]}",
	"City": cityEdition,
	"Price": {$price},
	"Value": {$value},
	"Discount": {$discount},
	"View": "LIST",
	"Sort": currentSortBy,
	"UserID": userID
});							



			'><span>Details at {$companies[$deal["company_id"]]}</span></a>
			<br>

			{$time_left_string} - {$discovered_string}
		</div>

	</div>
	
	<div style="margin-top:20px; clear:both; width:100%; overflow:hidden;">
		<div style="width:310px; float:left">
			<div style="width:310px; position:relative">
				<div id="single-image-prev" style="display:none; position:absolute; left:0px; top:160px;">
					<a href="javascript:void(0)" onclick="showPrevImageSingle();"><img src="/images/prev.png"></a>
				</div>
				<div id="single-image-next" style="display:none; position:absolute; right:0px; top:160px;">
					<a href="javascript:void(0)" onclick="showNextImageSingle();"><img src="/images/next.png"></a>
				</div>
				<img style="width:310px; height:auto;" src="{$image_url}">
			</div>
			
			
			
			<div id="single-map" style="height:310px; width:310px; background-color:#eeeeee; margin-top:14px;"></div>
		</div>
		
		<div style="width:634px; float:left; margin-left:14px; line-height:1.5">{$deal["text"]}</div>
		
	</div>

HTML;


echo($html);




