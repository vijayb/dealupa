<?php

$username_users="frontend";
$password_users="cheapass";
$database_users="production_users";

$users_con=mysql_connect("50.57.136.168", $username_users,
			 $password_users, true);

if (!$users_con) { die('Not connected : ' . mysql_error()); }

$db_selected_users = mysql_select_db($database_users, $users_con);
if (!$db_selected_users) {
  die ('Can\'t use db : ' . mysql_error());
}

?>