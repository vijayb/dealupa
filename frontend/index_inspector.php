<?php

set_time_limit(0);

require_once("array_constants.php");
require("db.php");
require("refresh_deals_indexes.php");

$cache_life = 86400;

$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

if (!$memcache) {
  die ("Error: Unable to connect to memcache\n");
}

$orders = array("SOLD", "NEW", "PRICE", "DEADLINE");

foreach ($cities as $city_id => $city_name) {
	foreach ($orders as $sort_order_id => $sort_order) {
		$deals_index = getDealsIndex($sort_order, $city_id, $deals_con, $memcache, $cache_life);
		echo("deals_index_" . $sort_order . "_" . $city_id . "<br><br>");
		print_r($deals_index);
		echo("<br><br><br><br>");
	}
}
