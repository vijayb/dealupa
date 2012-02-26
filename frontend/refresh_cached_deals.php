<?php
// The purpose of this script is to keep recently updated deals fresh in
// memcache. So e.g., when a deal is recrawled, its last_updated field
// is set to the current time. We want to make sure that the cache
// has the latest version of any deals that have been recently updated.
// Thus this script should be periodically called to keep deals fresh in the
// cache. The downside of not periodically calling this script is that
// marker_xml.php and deal_html.php will not have the latest version of deals
// as they are in the Deals database. It will also mean that deals may
// fall out of the cache ($cache_life seconds after the last time this
// script was called), which means that deal_html.php and marker_xml.php
// will run more slowly.


// If we haven't previously updated the cache, we default to refreshing
// all deals which have been updated within the last $default_update_age
// seconds in the database.
$default_update_age = 14*24*3600; // 2 weeks

$cache_life = $default_update_age + 86400; // + one day



////////////////////////////////////////////////////////////////
///////////////////// MEMCACHE SECTION /////////////////////////
require 'db.php';
require 'get_deal.php';

$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);
if (!$memcache) {
  die ("Error: Unable to connect to memcache\n");
}


// See if last_updated (the last time we refreshed the cache
// with all the latest deals) is in memcache.
$last_updated = $memcache->get("last_updated");

// reload_cache can be used as a parameter to force a load
// on all deals updated since $default_update_age
if (!$last_updated || isset($_GET["reload_cache"])) {
  $last_updated = time() - $default_update_age;
}

$seconds_since_last_updated = (time() - $last_updated) + 60;

$query = 
  "SELECT id from Deals777
   WHERE TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), last_updated)) < 
   $seconds_since_last_updated";

$result = mysql_query($query, $deals_con);
if (!$result) {
  die('Error: failed query ' . mysql_error());
}

$count = 0;
while ($row = @mysql_fetch_assoc($result)) {
  //echo "Refreshing article with id: ".$row['id']." in cache<BR>\n";
  refreshDealById($row['id'], $deals_con, $memcache, $cache_life);
  $count++;
}
echo "Successfully refreshed $count deals since the last update [$seconds_since_last_updated seconds ago]";


// If we've gotten this far then we successfully updated the cache
// with deals that have recently been updated. So we can
// set last_updated to now.
$memcache->set("last_updated", time(), false, $default_update_age);

/////////////////// END MEMCACHE SECTION ///////////////////////
////////////////////////////////////////////////////////////////
?>