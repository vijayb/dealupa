<?php

require("db_user.php");
require("helpers.php");

if (!isset($_GET["user_id"]) || !isset($_GET["token"])) {
	print("We're sorry, but we're having difficulty unsubscribing you. Please email founders@dealupa.com and we'll unsubscribe you manually.");
	exit();
}

$user_id = $_GET["user_id"];
$token = $_GET["token"];

if (!check_token($user_id, $token)) {
	print("We're sorry, but we're having difficulty unsubscribing you. Please email founders@dealupa.com and we'll unsubscribe you manually.");
	exit();
}

$query = "UPDATE Users SET subscribed=0 WHERE user_id=" . $user_id;

$result = mysql_query($query, $users_con);
if (!$result) {
	print("We're sorry, but we're having difficulty unsubscribing you. Please email founders@dealupa.com and we'll unsubscribe you manually.");
}

print("We've unsubscribed you. Thank you for trying Dealupa. You can close this browser tab now.");

?>