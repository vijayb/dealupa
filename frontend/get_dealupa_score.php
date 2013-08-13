<?php

require("dealupa_score.php");
require("db.php");
require("get_deal.php");

$cache_life = 86400;

$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

if (!$memcache) {
  die ("Error: Unable to connect to memcache\n");
}

if (isset($_GET["deal_id"])) {
  $deal = getDealById($_GET["deal_id"], $deals_con, $memcache, $cache_life);
  $dealupa_rank = dealupaScore($deal);
  echo $dealupa_rank;
} else {
  echo "No deal ID specified!";
}

?>