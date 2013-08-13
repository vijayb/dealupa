<?php

require("helpers.php");
require("db_user.php");

// put in code to safeguard against injection attacks

					
$user_id = mysql_real_escape_string($_GET["user_id"]);
$token = mysql_real_escape_string($_GET["token"]);
$new_password_hash = mysql_real_escape_string($_GET["new_password_hash"]);
$latitude = mysql_real_escape_string($_GET["latitude"]);
$longitude = mysql_real_escape_string($_GET["longitude"]);


if (check_valid_id_token_pair($user_id, $token)) {
	$query = "UPDATE Users SET password_hash='$new_password_hash' WHERE user_id='$user_id'";

	$result = mysql_query($query);
	
	if ($result) {
		echo("1");
		exit;
	}
}

echo ("-1");

?>