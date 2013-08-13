<?php

require("db_user.php");
require("helpers.php");

// put in code to safeguard against injection attacks

$email = mysql_real_escape_string($_POST["email"]);
$password_hash = mysql_real_escape_string($_POST["password_hash"]);

// CHECK IF USER EXISTS

$query = "SELECT user_id FROM Users WHERE email='$email' AND password_hash='$password_hash' LIMIT 1";

$result = mysql_query($query, $users_con);
$num_rows = mysql_num_rows($result);

if ($num_rows != 1) {
	echo "[0]";
	exit();
}

if ($num_rows == 1) {

	$row = mysql_fetch_assoc($result);
	$user_id = $row['user_id'];
	
	$token = generate_user_token_from_user_id($user_id);
	
	echo ("[" . $user_id . ", '" . $token . "']");
}

?>