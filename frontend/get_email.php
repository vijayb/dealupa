<?php

require("db_user.php");
require("helpers.php");

if (!isset($_GET["user_id"]) || !isset($_GET["token"])) {
	exit();
}

$user_id = $_GET["user_id"];
$token = $_GET["token"];

if (!check_token($user_id, $token)) {
	exit();
}

$query = "SELECT first_name, last_name, fb_id, email FROM Users WHERE user_id=" . $user_id;
$result = mysql_query($query, $users_con);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}


if (mysql_num_rows($result) == 1) {
	$first_name = mysql_result($result, 0, "first_name");
	$last_name = mysql_result($result, 0, "last_name");
	$fb_id = mysql_result($result, 0, "fb_id");
	$email = mysql_result($result, 0, "email");
	
	if ($first_name == "") {
		echo("$email");
	} else {
		echo("$first_name $last_name");
	}
}

?>