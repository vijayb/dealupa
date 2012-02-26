<?php

require("db_user.php");

// put in code to safeguard against injection attacks

$email = mysql_real_escape_string($_POST["email"]);
$password_hash = mysql_real_escape_string($_POST["password_hash"]);
$session_id = mysql_real_escape_string($_POST["session_id"]);


// CHECK IF USER EXISTS

$query = "SELECT user_id FROM Users WHERE email='$email' AND password_hash='$password_hash'";

$result = mysql_query($query);
$num_rows = mysql_num_rows($result);

if ($num_rows != 1) {
	echo "0";
	exit();
}

if ($num_rows == 1) {

	$row = mysql_fetch_assoc($result);
	$user_id = $row['user_id'];
	echo ($user_id);
	
	// UPDATE SESSION INFO IN DB
	$query = "UPDATE Users SET session_id='$session_id' WHERE user_id='$user_id'";

	$result = mysql_query($query);
	if (!$result) {
	  die('Invalid query: ' . mysql_error());
	} else {
	}		
	
} else {
	echo "-1";
}

?>