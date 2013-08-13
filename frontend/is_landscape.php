<?php

require("db.php");
require("get_deal.php");




// When this file is called via ajax from deelio.js...
if (isset($_GET["ajax"]) && isset($_GET["deal_id"])) {
	if (is_landscape($_GET["deal_id"])) {
		echo 1;
	} else {
		echo 0;
	}
}




function is_landscape($deal_id) {

	global $deals_con;

	$cache_life = 86400;
	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	$deal = getDealById($deal_id, $deals_con, $memcache, $cache_life);

	// For now, the company ID will tell us what the AR is
	if ($deal["company_id"] == 2 || $deal["company_id"] == 12) {
		return false;
	} else {
		return true;
	}
}

?>