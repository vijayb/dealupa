<?php

require("db_user.php");

$params_arr = $_GET;


echo("<!-- \$params_arr in deal_html_from_url_params:\n");
print_r($params_arr);
echo("-->");

require_once("deals_index_from_url_params.php");

$deals_index = deals_index_from_url_params($params_arr, $deals_con, $users_con, $memcache, $cache_life);

$num_deals = count($deals_index);

require_once("deal_html_from_deals_index.php");

$p_param = isset($params_arr['p']) ? $params_arr['p'] : 1;


// $params_arr['i'] should be set by here. If it's not, we purposely set i_param
// to -1 so as to create an error downstream such that we notice it.
$i_param = isset($params_arr['i']) ? $params_arr['i'] : -1;

if (!preg_match("/^[0-9]+$/", $i_param)) {
	$i_param = $citiesReverse[$i_param];
}

$items_per_page = 25;

if ($p_param > 0) {
	$deals_index = array_slice($deals_index, (($p_param - 1) * $items_per_page), ($items_per_page));
}


if (isset($params_arr['seo'])) {
	// This file is being called from mod_rewrite to generate seo output
	$html = deal_html_from_deals_index($deals_index, $deals_con, $i_param, 0, 1);
} else {

	if (isset($params_arr['v']) && $params_arr['v'] == "SINGLE-CATEGORY") {
		$html = deal_html_from_deals_index($deals_index, $deals_con, $i_param, 1, 0);
	} else {
		$html = deal_html_from_deals_index($deals_index, $deals_con, $i_param, 0, 0);
	}
}


/*
if ($p_param > 0 && count($deals_index) >= $items_per_page) {
	$html .= <<<HTML
		<a class="c-g" id="more-deals" href="javascript:void(0);" onclick="pages++; loadAndDisplayDeals(pages);">Load more deals</a>
HTML;

}
*/




if (isset($params_arr['seo'])) {
	$html .= <<<HTML
	<div style="clear:both">
		<h1>Dealupa in other cities</h1>
		<a href='http://dealupa.com/atlanta/daily-deals#!'>Daily Deals in Atlanta</a><br>
		<a href='http://dealupa.com/austin/daily-deals#!'>Daily Deals in Austin</a><br>
		<a href='http://dealupa.com/baltimore/daily-deals#!'>Daily Deals in Baltimore</a><br>
		<a href='http://dealupa.com/boston/daily-deals#!'>Daily Deals in Boston</a><br>
		<a href='http://dealupa.com/chicago/daily-deals#!'>Daily Deals in Chicago</a><br>
		<a href='http://dealupa.com/cleveland/daily-deals#!'>Daily Deals in Cleveland</a><br>
		<a href='http://dealupa.com/dallas/daily-deals#!'>Daily Deals in Dallas</a><br>
		<a href='http://dealupa.com/denver/daily-deals#!'>Daily Deals in Denver</a><br>
		<a href='http://dealupa.com/detroit/daily-deals#!'>Daily Deals in Detroit</a><br>
		<a href='http://dealupa.com/honolulu/daily-deals#!'>Daily Deals in Honolulu</a><br>
		<a href='http://dealupa.com/houston/daily-deals#!'>Daily Deals in Houston</a><br>
		<a href='http://dealupa.com/kansas-city/daily-deals#!'>Daily Deals in Kansas&#160;City</a><br>
		<a href='http://dealupa.com/las-vegas/daily-deals#!'>Daily Deals in Las&#160;Vegas</a><br>
		<a href='http://dealupa.com/los-angeles/daily-deals#!'>Daily Deals in Los&#160;Angeles</a><br>
		<a href='http://dealupa.com/miami/daily-deals#!'>Daily Deals in Miami</a><br>
		<a href='http://dealupa.com/minneapolis/daily-deals#!'>Daily Deals in Minneapolis</a><br>
		<a href='http://dealupa.com/new-orleans/daily-deals#!'>Daily Deals in New&#160;Orleans</a><br>
		<a href='http://dealupa.com/new-york/daily-deals#!'>Daily Deals in New&#160;York</a><br>
		<a href='http://dealupa.com/orange-county/daily-deals#!'>Daily Deals in Orange&#160;County</a><br>
		<a href='http://dealupa.com/orlando/daily-deals#!'>Daily Deals in Orlando</a><br>
		<a href='http://dealupa.com/philadelphia/daily-deals#!'>Daily Deals in Philadelphia</a><br>
		<a href='http://dealupa.com/phoenix/daily-deals#!'>Daily Deals in Phoenix</a><br>
		<a href='http://dealupa.com/pittsburgh/daily-deals#!'>Daily Deals in Pittsburgh</a><br>
		<a href='http://dealupa.com/portland/daily-deals#!'>Daily Deals in Portland</a><br>
		<a href='http://dealupa.com/san-antonio/daily-deals#!'>Daily Deals in San&#160;Antonio</a><br>
		<a href='http://dealupa.com/san-diego/daily-deals#!'>Daily Deals in San&#160;Diego</a><br>
		<a href='http://dealupa.com/san-francisco/daily-deals#!'>Daily Deals in San&#160;Francisco</a><br>
		<a href='http://dealupa.com/san-jose/daily-deals#!'>Daily Deals in San&#160;Jose</a><br>
		<a href='http://dealupa.com/seattle/daily-deals#!'>Daily Deals in Seattle</a><br>
		<a href='http://dealupa.com/silicon-valley/daily-deals#!'>Daily Deals in Silicon&#160;Valley</a><br>
		<a href='http://dealupa.com/st-louis/daily-deals#!'>Daily Deals in St.&#160;Louis</a><br>
		<a href='http://dealupa.com/tacoma/daily-deals#!'>Daily Deals in Tacoma</a><br>
		<a href='http://dealupa.com/dc/daily-deals#!'>Daily Deals in Washington,&#160;D.C.</a><br>
	</div>
HTML;


	$edition = $cities[$i_param];

	$full_html = <<<HTML
	
	<html>
		<head>
			<title>Daily Deals in {$edition}</title>
			<meta charset='utf-8' /><link rel='stylesheet' type='text/css' href='/map152.css' />
			<meta name="keywords" content="daily deals, coupons, discounts, groupon, living social, gilt city, bloomspot, scoutmob, goldstar, spa, restaurant, hair removal, food" />
			<meta name="description" content="All the best daily deals in {$edition} in one email." />
		</head>
		
		<body>
			{$html}
		</body>
	</html>
HTML;

	echo($full_html);
	exit;
		
}

echo("<span id='list-view-data' num-deals=" . $num_deals . "></span>" . $html);

?>