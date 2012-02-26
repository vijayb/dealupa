<?php

require("db_user.php");

// put in code to safeguard against injection attacks

$email = mysql_real_escape_string($_GET["email"]);
$current_password_hash = mysql_real_escape_string($_GET["curr_password_hash"]);
$new_password_hash = mysql_real_escape_string($_GET["new_password_hash"]);
$session_id = mysql_real_escape_string($_GET["session_id"]);


// CHECK IF USER EXISTS

$query = "SELECT user_id FROM Users WHERE email='$email' AND password_hash='$current_password_hash'";

$result = mysql_query($query);
$num_rows = mysql_num_rows($result);

if ($num_rows != 1) {
	echo "0"; // wrong email/password combo
	exit();
}

if ($num_rows == 1) {

	$row = mysql_fetch_assoc($result);
	$user_id = $row['user_id'];
	
	// UPDATE SESSION INFO IN DB
	$query = "UPDATE Users SET session_id='$session_id', password_hash='$new_password_hash' WHERE user_id='$user_id'";
	
	$result = mysql_query($query);
	if (!$result) {
    	echo "0";
	}

	echo ($user_id);

	
} else {
	echo "-1";
}

?>