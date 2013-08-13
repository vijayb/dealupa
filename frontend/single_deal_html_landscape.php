<?php
$time1 = microtime(true);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("refresh_deals_indexes.php");
require_once("array_constants.php");
require_once("helpers.php");

require_once("deal_html_from_deals_index.php");




$cache_life = 86400;
$MAX_SEARCH_RESULTS = 300;
$MAX_SIMILAR_DEALS = 3;

$prefix = "";


// Remember, $m is set in index.php if index.php is fetching a single deal view

if (isset($_GET["m"])) {
	$single_deal_to_display = $_GET["m"];
} else {
	return;
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


$categories = get_simple_categories_array();


$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

require_once("refresh_deals_indexes.php");

$deal = getDealById($single_deal_to_display, $deals_con, $memcache, $cache_life);

if ($edition != -1) {
	$index = getDealsIndex("SOLD", $edition, $deals_con, $memcache, $cache_life);

	$similar_deals = array();
	if (isset($deal["Categories"])) {
		$deal_cat = $deal["Categories"][0]["category_id"];
		
		//echo "*********** CATEGORY: [$deal_cat]\n";
		for ($q = 0; $q < count($index); $q++) {
			if ($index[$q] == $single_deal_to_display) { continue; }
			if (count($similar_deals) >= $MAX_SIMILAR_DEALS) { break; }

			$tmpdeal = getDealById($index[$q], $deals_con, $memcache, $cache_life);
			if (hasCat($tmpdeal, $deal_cat)) {
				array_push($similar_deals, $index[$q]);
			}
		}

		if (count($similar_deals) <$MAX_SIMILAR_DEALS) {
			for ($q =0; $q <count($index); $q++) {
				if ($index[$q] == $single_deal_to_display) { continue; }
				if (count($similar_deals) >= $MAX_SIMILAR_DEALS) { break; }

				if (!in_array($index[$q], $similar_deals)) {
					array_push($similar_deals, $index[$q]);
				}
			}
		}
		
	}
}




function hasCat($deal, $category) {
	if (!isset($deal["Categories"])) {
		return 0;
	}

	for ($j = 0; $j < count($deal["Categories"]); $j++) {
		if ($deal["Categories"][$j]["category_id"] == $category) {
			return 1;
		}
	}

	return 0;
}


require("deal_data_extraction.php");


$html = <<<HTML


<span id="single-deal-data" image_arr_str="{$image_arr_str}" deal_edition="{$edition}" cities_arr_str="{$cities_arr_str}" lats_arr_str="{$lats_arr_str}" lngs_arr_str="{$lngs_arr_str}" title="{$deal["title"]}" deal_id="{$deal["id"]}" category_id="{$catgegory_arr_str}" full_url="{$link_to_single_deal_page}"></span>

	<div style="width:100%; overflow:hidden;">
		<div style="float:left; width:600px;">
HTML;

// Only show the "See all deals in X" link if this deal belongs to a metro edition
if ($edition != 1 || (isset($_GET["logged_in"]) && $_GET["logged_in"] == 1)) {

	if ($edition != 1) {
		$back_link_string = "See all deals in " . $cities[$edition];
	} else {
		$back_link_string = "See all deals";
	}
	
	$html .= <<<HTML
			<div style="margin-bottom:5px;"><a href="javascript:void(0);" style="font-size:18px; font-weight:700" onclick='
				
				changeView("LIST");
				if ($("#container").html().length > 1000 && parseInt($("#single-deal-data").attr("deal_edition")) == cityEdition) {
					loadAndDisplay(1);
				} else {
					loadAndDisplay(0);
				}
				setTimeout("window.scrollTo(0, listScrollPosition);", 15);
				pushState();'

				>&#171; {$back_link_string}</a>
			</div>
HTML;

}

$html .= <<<HTML
			<h1>{$deal["title"]}</h1>
			<h2>{$deal["subtitle"]}</h2>
			<div style="margin:10px 0px;">
HTML;
			
	for ($x = 0; $x < count($category_arr); $x++) {

		$html .= <<<HTML
				<span class="category category-set-{$categoryIDToSetID[$category_arr[$x]]}">{$categories[$category_arr[$x]]}</span>&#160;&#160;
HTML;

	}					
			
			
			
	$html .= <<<HTML
			</div>
HTML;

if ($yelp_rating != "") {
	
	$html .= <<<HTML
			
			<div>
				<a id="single-yelp-link" href="{$deal['yelp_url']}" target="_blank">
					<img src="/images/yelp/yelp_{$yelp_rating}.png"> - {$deal["yelp_review_count"]} reviews
				</a>
			</div>
HTML;

}
	$html .= <<<HTML
			<div>
				{$name_linked}
				<br>
HTML;


	if ($yelp_rating != "") {
		$html .= <<<HTML
				{$street}
				<br>
HTML;
	}
	


$html .= <<<HTML

				{$city} {$state}
			</div>
			
		</div>

		<div id="single-deal-coupon">
HTML;
		
	
	if ($value > 0) {
		$value_html = "<br>for \$" . $value . " value";
	} else {
		$value_html = "";
	}
		
	if ($num_purchased_string != "" && $num_purchased_string > 0) {
		$html .= <<<HTML
			<table width=100%>
				<tr>
				<td width=50% style="text-align:center;"><span style="color:#ffffff; font-size:28px; text-align:center; font-weight:700">\${$price}</span>{$value_html}</td>
				<td width=50% style="text-align:center;"><span style="color:#ffffff; font-size:28px; text-align:center; font-weight:700">{$num_purchased_string}</span><br>sold</td>
				</tr>
			</table>
HTML;
	} else {
		$html .= <<<HTML
			<table width=100%>
				<tr>
				<td width=100% style="text-align:center;"><span style="color:#ffffff; font-size:28px; text-align:center; font-weight:700">\${$price}</span>{$value_html}</td>
				</tr>
			</table>
HTML;
	}
	
	$html .= <<<HTML
			<a href="{$deal_site_url}" target="_blank" class="big-button orange-gradient" style="margin:10px 0px; width:265px;" onclick='


mpq.track("Clicked to external deal site", {
	"mp_note": "Clicked on {$deal["id"]}",
	"Company": "{$companies[$deal["company_id"]]}",
	"Category": "{$categories[$category_arr[0]]}",
	"Yelp": {$deal["yelp_rating"]},
	"City": cities[cityEdition],
	"Price": {$price},
	"Value": {$value},
	"Discount": {$discount},
	"View": "SINGLE",
	"UserID": userID
});



			'><span>Details at {$companies[$deal["company_id"]]}</span></a>
			<br>
			<span id="single-deal-time-left">{$time_left_string}</span> - {$discovered_string}
		</div>

	</div>







	
	<div style="margin-top:15px; clear:both; width:100%; overflow:hidden;">
	
		<div style="width:688px; float:left; opacity:0.0" id="single-deal-left">
		
		
			<div>
				<ul id="slider">
HTML;
	
	if (isset($deal["Images"])) {
		for ($i = 0; $i < count($deal["Images"]); $i++) {
			$current_image = "http://dealupa_images.s3.amazonaws.com/" . sha1($deal["Images"][$i]["image_url"]);
			$html .= <<<HTML
				
					<li>
						<img src="{$current_image}" style="width:688px;">
					</li>
HTML;
		}
	}

	$html .= <<<HTML
				
				</ul>
			</div>
			
			<div style="margin-top:15px">
HTML;


















	$deal_text_div_width = "style='float:right; line-height:1.5'";

	if ($yelp_attr > -1) {
	
		$deal_text_div_width = "style='width:358px; float:right; line-height:1.5'";

$html .= <<<HTML

	<div style="width:280px; float:left; padding:15px; margin:0px 20px 0px 0px; background-image:url(/images/tile_leather.jpg)">

		<div>
		  <table>
			<tr>
			  <td style="width:45px; vertical-align:top;">
				<a href="{$yelp_user_url1}" target=_blank><img src="{$yelp_user_image_url1}" style="width:40px; height:40px"></a>
			  </td>
			  <td style="vertical-align:top; padding:0px 0 0 5px;">
				<img src="/images/yelp/yelp_{$yelp_rating1}">&nbsp;<a href="{$yelp_user_url1}" target=_blank>{$yelp_user1}</a>
				<br>
				<span>{$yelp_excerpt1}</span> - <a href="{$yelp_review_url1}" target=_blank>Read more</a>
			  </td>
			</tr>
		  </table>
		</div>
HTML;

	}


	if ($yelp_excerpt2 != "") {
		
$html .= <<<HTML
		<div style="padding:15px 0px 0px 0px;">
		  <table>
			<tr>
			  <td style="width:45px; vertical-align:top;">
				<a href="{$yelp_user_url2}" target=_blank><img src="{$yelp_user_image_url2}" style="width:40px; height:40px"></a>
			  </td>
			  <td style="vertical-align:top; padding:0px 0 0 5px;">
				<img src="/images/yelp/yelp_{$yelp_rating2}">&nbsp;<a href="{$yelp_user_url2}" target=_blank>{$yelp_user2}</a>
				<br>
				<span>{$yelp_excerpt2}</span> - <a href="{$yelp_review_url2}" target=_blank>Read more</a>
			  </td>
			</tr>
		  </table>
		</div>
HTML;

	}




	if ($yelp_excerpt2 != "") {
		
$html .= <<<HTML
		<div style="padding:15px 0px 0px 0px;">
		  <table>
			<tr>
			  <td style="width:45px; vertical-align:top;">
				<a href="{$yelp_user_url3}" target=_blank><img src="{$yelp_user_image_url3}" style="width:40px; height:40px"></a>
			  </td>
			  <td style="vertical-align:top; padding:0px 0 0 5px;">
				<img src="/images/yelp/yelp_{$yelp_rating3}">&nbsp;<a href="{$yelp_user_url3}" target=_blank>{$yelp_user3}</a>
				<br>
				<span>{$yelp_excerpt3}</span> - <a href="{$yelp_review_url3}" target=_blank>Read more</a>
			  </td>
			</tr>
		  </table>
		</div>
HTML;
	}




	if ($yelp_attr > -1) {

$html .= <<<HTML

	</div>

HTML;

	}










	$html .= <<<HTML
				

				<div {$deal_text_div_width}>
					{$deal["text"]}	
				</div>
			</div>
		</div>

		
		<div style="float:right; width:250px; overflow:hidden">

		<h1>You might also like...</h1>
		<br>		
HTML;

if (isset($similar_deals)) {
	foreach ($similar_deals as $num => $deal_id) {
		$related_html = related_deal_html_from_deal_id($deal_id, $deals_con, $edition);
		$html .= $related_html;
	}
}
		
		
	$html .= <<<HTML
			<a href="javascript:void(0);" onclick='changeView("LIST");loadAndDisplay();setTimeout("window.scrollTo(0, listScrollPosition);", 15);pushState();' style="font-size:16px;">See all deals in {$cities[$edition]}</a>
		</div>
			
	</div>

HTML;


echo($html);
