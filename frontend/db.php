<?php

$username_deals="frontend";
$password_deals="cheapass";
$database_deals="Deals";

$deals_con=mysql_connect ("10.182.139.133", $username_deals, $password_deals, true);

if (!$deals_con) { die('Not connected : ' . mysql_error()); }

$db_selected_deals = mysql_select_db($database_deals, $deals_con);
if (!$db_selected_deals) {
  die ('Can\'t use db : ' . mysql_error());
}

?>