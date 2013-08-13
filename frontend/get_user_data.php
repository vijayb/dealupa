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

$query = "SELECT zipcode, email_frequency, edition, max_deal_distance FROM Users WHERE user_id=" . $user_id;
$result = mysql_query($query, $users_con);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}


if (mysql_num_rows($result) == 1) {
	$row = mysql_fetch_assoc($result);
	echo(json_encode($row));
}

?>
