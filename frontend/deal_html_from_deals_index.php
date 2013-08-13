<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("helpers.php");
require_once("dealupa_score.php");
require_once("array_constants.php");


if (isset($_GET["map_view"])) {
	
	require("db.php");
	require("get_deal.php");

	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);
	
	$html = deal_html_from_deal_id($_GET["deal_id"], $deals_con, $_GET["edition"], 0, 0);
	
	echo($html);


}


function deal_html_from_deal_id($deal_id, $deals_con, $force_edition, $single_category, $seo) {

	require("array_constants.php");

	$MAX_SEARCH_RESULTS = 300;
	
	$cache_life = 86400;
	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	$deal = getDealById($deal_id, $deals_con, $memcache, $cache_life);
	
	require("deal_data_extraction.php");
	
	$score = round(dealupaScore($deal), 2);
	
	$categories = get_simple_categories_array();
	
	if ($price == 0) {
		$price_html = "Free";
	} else {
		$price_html = "\$" . $price;
	}	
	
	
	$list_item = <<<HTML

		<div class="top-div box" 
			 id-at="{$deal["id"]}" 
			 lat-at="{$latitude}"
			 lng-at="{$longitude}"
			 cat-at="{$catgegory_arr_str}"
			 cmp-at="{$deal["company_id"]}"
			 y-at="{$deal["yelp_rating"]}"
			 p-at="{$price}"
			 v-at="{$value}"
			 url-at="{$link_to_single_deal_page}"
			 d-score="{$score}"
			 
			 id="box-{$deal["id"]}"
			 >

			 
				
				
				<!-- DIV 1: IMAGE -->
				<div style="position:relative;">
					<div class="deal-image">	
						<a href="{$deal_site_url}" id="image-link-{$deal["id"]}" target=_blank>
							<img id="image-{$deal["id"]}" src="{$image_url}" class="deal-image" onerror="this.style.display='none';">
						</a>
						
						<div class="deal-panel-name">
							{$name}
						</div>						
						
					</div>

					<div class="expansion" id="fb-buttons-{$deal["id"]}" style="margin-top:5px; float:right; position:absolute; top:5px; left:10px; display:none;"></div>
				</div>
				<!-- CLOSE DIV 1: IMAGE -->

				
		
				<!-- DIV 2: INFO -->
				
				<div class="deal-panel-info" style="">
					
HTML;




	if ($seo) {
		$list_item .= <<<HTML
						<a id="title" href="{$link_to_single_deal_page}" style="font-weight:400">
							{$deal["title"]}
								<br>
							{$deal["subtitle"]}
						</a>
HTML;

	}

	$list_item .= <<<HTML
					<div style="margin-bottom:4px;">
						<span style="font-weight:700">{$price_html}</span>
HTML;

	if ($value > 0) {
		$list_item .= <<<HTML
						&#160;&#160;<span style="position:relative; color:#999999; text-decoration:line-through">\${$value}</span>
HTML;
	}

	if ($num_purchased_string != "" && $num_purchased_string > 0) {
		$list_item .= <<<HTML
						&#160;&#160;-&#160;&#160;{$num_purchased_string} sold
HTML;
	}
	$list_item .= <<<HTML
						<span style="float:right; color:#555555">{$companies[$deal["company_id"]]}<!-- - {$score} --></span>
					</div>
HTML;




	if ($city != "") {
		$list_item .= <<<HTML
					<div class="gray">
						{$street}
						{$city}
						{$state} <span class="expansion" style="display:none"> - <a href="http://maps.google.com/maps?q={$street}{$city}{$state}" target="_blank">map</a></span>
					</div>
HTML;
	}




	if ($yelp_rating != "") {

		$list_item .= <<<HTML
					<div>
						<a id="yelp-link-{$deal["id"]}" href="{$deal["yelp_url"]}" target=_blank>
							<img src="/images/yelp/yelp_{$yelp_rating}.png" alt="arrow">
							&#160;
							<img src="/images/yelp.png?150">
							&#160;- {$deal["yelp_review_count"]} reviews
						</a>
					</div>
HTML;
	}



	$list_item .= <<<HTML
					<div style="margin:2px 0 0 0; overflow:hidden; clear:both;">
HTML;









	for ($x = 0; $x < count($category_arr); $x++) {

		if ($category_arr[$x] != 0) {
		
			if ($single_category) {
		
				$list_item .= <<<HTML
						<div id="category-pill-{$deal["id"]}-{$category_arr[$x]}" style="white-space:nowrap; float:left; margin-right:8px; margin-bottom:6px;">
							<a cat-name="{$categories[$category_arr[$x]]}" id="category-pill-name-{$deal["id"]}-{$category_arr[$x]}" class="category category-set-{$categoryIDToSetID[$category_arr[$x]]} category-pill">
								{$categories[$category_arr[$x]]}
							</a>
						</div>
HTML;
			} else {
		
				$list_item .= <<<HTML
						<div id="category-pill-{$deal["id"]}-{$category_arr[$x]}" style="white-space:nowrap; float:left; margin-right:8px; margin-bottom:6px;">
							<a onclick="showSingleCategory({$category_arr[$x]})" href="javascript:void(0)" cat-name="{$categories[$category_arr[$x]]}" id="category-pill-name-{$deal["id"]}-{$category_arr[$x]}" class="category category-set-{$categoryIDToSetID[$category_arr[$x]]} category-pill">
								{$categories[$category_arr[$x]]}
							</a><a class="category-x category" href="javascript:void(0)" onclick="deleteCategory({$category_arr[$x]}); loadAndDisplay(); pushState();">
								<img src="/images/category_x.png" style="position:relative; top:1px;">
							</a>
						</div>
HTML;
			}
			
		}
	}					
						
	$list_item .= <<<HTML
					</div>

					
HTML;
	
	
	


	if ($deal["title"] == $name) {
		$text_for_expansion_box = $deal["subtitle"];
	} else {
		$text_for_expansion_box = $deal["title"];
	}
	$list_item .= <<<HTML


					<div class="expansion" style="display:none; margin-bottom:4px;">
						<div class="deal-panel-title">
							<a id="title" href="{$link_to_single_deal_page}" style="font-weight:400" onclick="openDealInSingleDealView({$deal["id"]}, {$force_edition}); pushState(); return false;">
							{$text_for_expansion_box}
						</a>
						</div>
						
						
						<a target=_blank href="{$deal_site_url}" id="details-{$deal["id"]}" class="big-button orange-gradient" style="color: #130800; width:206px; font-size:14px">
							<span>See deal at {$companies[$deal["company_id"]]}</span>
						</a>
					</div>
HTML;




	if (strpos($time_left_string, "Expired") === false) {
		$list_item .= <<<HTML
					<span class="expansion">
						<span class="gray">
							{$discovered_string} - {$time_left_string}
						</span>
					</span>
HTML;
	} else {
		$list_item .= <<<HTML
					<span class="gray">
						{$discovered_string} - {$time_left_string}
					</span>
HTML;
	}
	
	$list_item .= <<<HTML
				</div>
				<!-- CLOSE DIV 2: INFO -->
HTML;







/*
	if ($yelp_user_url1 != "") {

		$list_item .= <<<HTML

				<div id="yelp-reviews-{$deal["id"]}" style="display:none">
					<table>
						<tr>
						  <td class="y-td-1">
							<a href="{$yelp_user_url1}" target=_blank><img src="{$yelp_user_image_url1}"  class="y-u-p"></a>
						  </td>
						  <td class="y-td-2">
							<img src="/images/yelp/yelp_{$yelp_rating1}">&nbsp;<a href="{$yelp_user_url1}" target=_blank>{$yelp_user1}</a>
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
							<img src="/images/yelp/yelp_{$yelp_rating2}">&nbsp;<a href="{$yelp_user_url2}" target=_blank>{$yelp_user2}</a>
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
							<img src="/images/yelp/yelp_{$yelp_rating3}">&nbsp;<a href="{$yelp_user_url3}" target=_blank>{$yelp_user3}</a>
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

*/

	
	$list_item .= <<<HTML

		</div>
	
HTML;

	return $list_item;

}


function related_deal_html_from_deal_id($deal_id, $deals_con, $force_edition) {

	require("array_constants.php");

	$MAX_SEARCH_RESULTS = 300;
	
	$cache_life = 86400;
	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	$deal = getDealById($deal_id, $deals_con, $memcache, $cache_life);

	require("deal_data_extraction.php");
	
	$score = round(dealupaScore($deal), 2);
	
	$categories = get_simple_categories_array();
	
	$list_item = <<<HTML

		<div style="width:250px; margin-bottom:20px;">

			 
				
				
			<!-- DIV 1: IMAGE -->
			<div style="position:relative;">
				<div style="max-height:180px; overflow:hidden;">
					<a href="{$deal_site_url}" id="image-link-{$deal["id"]}" target=_blank onclick='
					
mpq.track("Clicked to external deal site", {
	"mp_note": "Clicked on {$deal["id"]}",
	"Company": "{$companies[$deal["company_id"]]}",
	"Category": "{$categories[$category_arr[0]]}",
	"Yelp": {$deal["yelp_rating"]},
	"City": cities[cityEdition],
	"Price": {$price},
	"Value": {$value},
	"Discount": {$discount},
	"View": "RELATED-image",
	"Sort": currentSortBy,
	"UserID": userID
});						
					
					
					
					
					
					'>
						<img id="image-{$deal["id"]}" src="{$image_url}" class="deal-image" onerror="this.style.display='none';" style="width:100% !important">
					</a>
				</div>

				<div class="expansion" id="fb-buttons-{$deal["id"]}" style="margin-top:5px; float:right; position:absolute; top:5px; left:10px; display:none;"></div>
			</div>

			
			<!-- DIV 2: PRICE -->
			<div class="deal-panel-price">
			
				<span style="float: left">
HTML;




	if ($price == 0) {
		$price_html = "Free";
	} else {
		$price_html = "\$" . $price;
	}
	
	$list_item .= <<<HTML
					<span style="font-size:18px; font-weight:400">{$price_html}</span>
HTML;



						
	if ($value > 0) {
		$list_item .= <<<HTML
						&#160;<span style="position:relative; top:-3px; color:#999999;">for \${$value} value</span>
HTML;
	}


	$list_item .= <<<HTML
					</span>
HTML;








	if ($num_purchased_string != "" && $num_purchased_string > 0) {
		
		$list_item .= <<<HTML
					<span style="float: right"><span style="font-size:18px; font-weight:400">{$num_purchased_string}</span> <span style="position:relative; top:-3px; color:#999999;">sold</span></span>

HTML;

	}					






	
	$list_item .= <<<HTML
			</div>				




		
				<!-- DIV 3: INFO -->
				
			<div class="deal-panel-info" style="">
					
HTML;

					
					
					
					
					
	if ($name != "") {
		
		$list_item .= <<<HTML
					{$name}
					<br>
HTML;
	}						
					
					
					






	if ($city != "") {


		$list_item .= <<<HTML
					<span class="gray">
						{$street}
						{$city}
						{$state}
					</span>
					<br>
HTML;
	}




	if ($yelp_rating != "") {

		$list_item .= <<<HTML
				<div style="margin-top:5px;">
					<a id="yelp-link-{$deal["id"]}" href="{$deal["yelp_url"]}" target=_blank>
						<img src="/images/yelp/yelp_{$yelp_rating}.png" alt="arrow">
						&#160;&#160;
						<img src="/images/yelp.png?150">
						&#160;- {$deal["yelp_review_count"]} reviews
					</a>
				</div>
HTML;
	}

	$list_item .= <<<HTML



				<div style="margin-bottom:10px;">
					<div class="deal-panel-title">
						<a id="title" href="{$link_to_single_deal_page}" style="font-weight:400" onclick="openDealInSingleDealView({$deal["id"]}, {$force_edition}); pushState(); return false;">{$deal["title"]}</a>
					</div>
					
					<a target=_blank href="{$deal_site_url}" id="details-{$deal["id"]}" class="small-button brown-gradient" style="color:#130800; width:210px; display:inline-block; text-align:center; font-size:14px" onclick='
					
					
mpq.track("Clicked to external deal site", {
	"mp_note": "Clicked on {$deal["id"]}",
	"Company": "{$companies[$deal["company_id"]]}",
	"Category": "{$categories[$category_arr[0]]}",
	"Yelp": {$deal["yelp_rating"]},
	"City": cities[cityEdition],
	"Price": {$price},
	"Value": {$value},
	"Discount": {$discount},
	"View": "RELATED-button",
	"UserID": userID
});							
					
					
					'>
						<span>See deal at {$companies[$deal["company_id"]]}</span>
					</a>
				</div>


				
				<span class="gray">
					{$discovered_string} - {$time_left_string}
				</span>
				<br>
				<b>{$companies[$deal["company_id"]]}</b>
			</div>
			
		</div>
	
HTML;

	return $list_item;

}

function deal_html_from_deals_index($deals_index, $deals_con, $force_edition, $single_category, $seo) {

	$deal_count = 0;

	$html = "";

	for ($j = 0; $j < count($deals_index); $j++) {

		$html .= deal_html_from_deal_id($deals_index[$j], $deals_con, $force_edition, $single_category, $seo);
		$deal_count++;

	}

	$html = str_replace("\t",'',$html);
	$html = str_replace("\n",'',$html);
	return $html;
}