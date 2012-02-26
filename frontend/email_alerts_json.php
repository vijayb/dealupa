<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


require("db_user.php");

$user = $_GET["user"];

$query = "SELECT * FROM EmailAlerts WHERE user_id=" . $user;

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

$arr = array();

while ($row = mysql_fetch_object($result)) {
	$arr[] = $row;
}


print_r($arr);

print("\n\n\n");

echo(json_encode($arr));





?>
