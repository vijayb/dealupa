
<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("helpers.php");

function deal_html_from_deal_id($deal_id, $deals_con, $force_edition) {

	require("array_constants.php");

	$MAX_SEARCH_RESULTS = 300;
	
	$cache_life = 86400;
	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	$deal = getDealById($deal_id, $deals_con, $memcache, $cache_life);
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
		$num_purchased_string = "<span style='font-size:18px; font-weight:700'>" . number_format($deal["num_purchased"]) . "</span> <span style='position:relative; top:-5px; color:#999999'>sold</span>";
	} else {
		$num_purchased_string = "";
	}
	
	$deadline_attr = strtotime(str_replace("-", "/", $deal["deadline"]));
	if ($deadline_attr == "") $deadline_attr = 2000000000;
	
	$time_left_arr = time_left($deal["deadline"]);
	$time_left_string = "";
	if ($time_left_arr["d"] != 0) $time_left_string .= $time_left_arr["d"] . " <span class='expires-in-units'>d</span> ";
	if ($time_left_arr["hr"] != 0) $time_left_string .= $time_left_arr["hr"] . " <span class='expires-in-units'>hr</span> ";		
	if ($time_left_arr["min"] != 0) $time_left_string .= $time_left_arr["min"] . " <span class='expires-in-units'>min</span> ";
	$time_left_string .= "to go";

	
	$opacity = 1;
	if ($deal["upcoming"] == 1) {
		$time_left_string = "<span style='color:#659829;font-weight:700'>UPCOMING DEAL</span>";
		$expired_attr = 0;
		$opacity = 0.6;
	} else if (($deal["deadline"] != "" && has_expired($deal["deadline"])) || $deal["expired"] == 1) {
		$time_left_string = "<span style='color:#a20000;font-weight:700'>EXPIRED</span>";
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

	
	if ($force_edition == 0) {
		$edition = 0;
	} else {
		$edition = $force_edition;
	}
	
	$cities_arr_str = "";
	if (isset($deal["Cities"])) {
		$cities_arr_str = "[";
		for($k = 0; $k < count($deal["Cities"]); $k++) {
			$cities_arr_str .= "" . $deal["Cities"][$k]["city_id"] . ", ";
			
			if ($force_edition == 0 && $deal["Cities"][$k]["city_id"] != 1) {
				$edition = $deal["Cities"][$k]["city_id"];
			}
			
		}
		$cities_arr_str = rtrim($cities_arr_str, ", ");
		$cities_arr_str .= "]";
	}		
	
	$link_to_single_deal_page = "/" . $cities_url[$edition] . "/daily-deals/" . $deal["id"] . "-" . hyphenate_title($deal["title"]);

	$list_item = <<<HTML

		<div class="top-div box" 
			 id-at="{$deal["id"]}" 
			 lat-at="{$latitude}"
			 lng-at="{$longitude}"
			 cat-at="{$category_id}"
			 cmp-at="{$deal["company_id"]}"
			 y-at="{$deal["yelp_rating"]}"
			 p-at="{$price}"
			 v-at="{$value}"
			 url-at="{$link_to_single_deal_page}">

			 
				
				
				<!-- DIV 1: IMAGE -->
				
				<div class="deal-image">
					<a href="{$deal_site_url}" id="image-{$deal["id"]}" target=_blank>
						<img src="{$image_url}" class="deal-image" onerror="this.style.display='none';">
					</a>
				</div>

				
				<!-- DIV 2: PRICE -->
				
				<div style="background-color: black; color: #fff; padding:5px; overflow:auto; ">
					<span style="float: left"><span style="font-size:18px; font-weight:700">\${$price}</span> <span style="position:relative; top:-5px; color:#999999">for \${$value} value</span></span>
					<span style="float: right">{$num_purchased_string}</span>
				</div>

				
				<!-- DIV 3: INFO -->
				
				<div class="on-black" style="background-color: rgba(40, 40, 40, .9); color: #fff; padding:5px; background-image: url('/images/grid_35.png');">
					<a id="title" href="javascript:void(0);" style="font-size:18px; font-weight:700">{$deal["title"]}</a>
				<br>
HTML;

					
					
					
					
					
	if ($name != "") {
		
		$list_item .= <<<HTML
						{$name}
						<br>
HTML;
	}						
					
					
					





	$list_item .= <<<HTML
						{$city}
						{$state}
HTML;








	if ($yelp_rating != "") {

		$list_item .= <<<HTML
					<br>
					<a id="yelp-link-{$deal["id"]}" href="{$deal["yelp_url"]}" target=_blank style="position:relative; top:4px;">
						<img src="/images/yelp/yelp_{$yelp_rating}.png" alt="arrow">
						&#160;&#160;
						<img src="/images/yelp.png">
						&#160;- {$deal["yelp_review_count"]} reviews
					</a>
HTML;
	}
	
	
	$list_item .= <<<HTML
					<br> 
					<div class="expansion" style="display: none">
						<div style="margin-top: 8px">
							<!--
							<span class="category category1">The finer things</span>
							&#160;&#160;
							<span class="category category2">Date night</span>
							-->
							<span class="category category-{$category_id}">
								{$categories[$category_id]}
								&#160;&#160;
								<a href="javascript:void(0)" onclick="hideCategory({$category_id}); loadAndDisplay(); pushState();">&#160;<img src="/images/category_x.png" style="position:relative; top:1px;"></a>
							</span>
						</div>

HTML;
/*
	if ($deal["subtitle"] != "") {
		$list_item .= <<<HTML
				<div class="expansion list-subtitle">
					{$deal["subtitle"]}
					<br>
				</div>
HTML;
	}
*/




	$list_item .= <<<HTML
						<a target=_blank href="{$deal_site_url}" id="details-{$deal["id"]}" class="big-button orange-gradient" style="margin-top:10px; margin-bottom:5px; color: #130800">
							<span>Details at {$companies[$deal["company_id"]]}</span>
						</a>
					</div>
					<span class="gray" style="font-size:12px;">{$discovered_string} - {$time_left_string}</span>
				</div>
HTML;










	if ($yelp_user_url1 != "") {

		$list_item .= <<<HTML
				<!-- DIV 1: IMAGE -->
				<div id="yelp-reviews-{$deal["id"]}" style="display:none">
					<table>
						<tr>
						  <td class="y-td-1">
							<a href="{$yelp_user_url1}" target=_blank><img src="{$yelp_user_image_url1}"  class="y-u-p"></a>
						  </td>
						  <td class="y-td-2">
							<img src="/images/yelp/yelp_{$yelp_rating1}">&nbsp;<a href="{$yelp_user_url1}" target=_blank><span style="font-size:12px;">{$yelp_user1}</span></a>
							<br>
							{$yelp_excerpt1}<a href="{$yelp_review_url1}" target=_blank> More</a>
						  </td>
						</tr>
					</table>
HTML;

		if ($yelp_user_url2 != "") {
		
			$list_item .= <<<HTML
				
					<hr class="dotted">
					<table>
						<tr>
						  <td class="y-td-1">
							<a href="{$yelp_user_url2}" target=_blank><img src="{$yelp_user_image_url2}"  class="y-u-p"></a>
						  </td>
						  <td class="y-td-2">
							<img src="/images/yelp/yelp_{$yelp_rating2}">&nbsp;<a href="{$yelp_user_url2}" target=_blank><span style="font-size:12px;">{$yelp_user2}</span></a>
							<br>
							{$yelp_excerpt2}<a href="{$yelp_review_url2}" target=_blank> More</a>
						  </td>
						</tr>
					</table>
HTML;
		}
		
		if ($yelp_user_url3 != "") {
			
				$list_item .= <<<HTML
					<hr class="dotted">
					<table>
						<tr>
						  <td class="y-td-1">
							<a href="{$yelp_user_url3}" target=_blank><img src="{$yelp_user_image_url3}" class="y-u-p"></a>
						  </td>
						  <td class="y-td-2">
							<img src="/images/yelp/yelp_{$yelp_rating3}">&nbsp;<a href="{$yelp_user_url3}" target=_blank><span style="font-size:12px;">{$yelp_user3}</span></a>
							<br>
							{$yelp_excerpt3}<a href="{$yelp_review_url3}" target=_blank> More</a>
						  </td>
						</tr>
					</table>
HTML;
		}
		
		$list_item .= <<<HTML
				</div>
		
HTML;
	}

	
	$list_item .= <<<HTML

		</div>
	
HTML;






	return $list_item;




}



function deal_html_from_deals_index($deals_index, $deals_con, $force_edition) {

	$deal_count = 0;

	$html = "";

	for ($j = 0; $j < count($deals_index); $j++) {

		$html .= deal_html_from_deal_id($deals_index[$j], $deals_con, $force_edition);
		$deal_count++;

	}

	$html = str_replace("\t",'',$html);
	$html = str_replace("\n",'',$html);
	return $html;
}