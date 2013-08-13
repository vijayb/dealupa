<?php

require("db_user.php");

$params_arr = $_GET;


echo("<!-- \$params_arr in deal_html_from_url_params:\n");
print_r($params_arr);
echo("-->");

require_once("deals_index_from_url_params.php");

$deals_index = deals_index_from_url_params($params_arr, $deals_con, $users_con, $memcache, $cache_life);

$num_deals = count($deals_index);



$deals = array();
for ($z = 0; $z < $num_deals; $z++) {
	array_push($deals, getDealById($deals_index[$z], $deals_con, $memcache, $cache_life));
}

print(json_encode($deals));

exit();
