<?php

require("db_user.php");

// put in code to safeguard against injection attacks

$email = mysql_real_escape_string($_GET["email"]);
$password_hash = mysql_real_escape_string($_GET["password_hash"]);
$latitude = mysql_real_escape_string($_GET["latitude"]);
$longitude = mysql_real_escape_string($_GET["longitude"]);
$session_id = mysql_real_escape_string($_GET["session_id"]);
$utm = mysql_real_escape_string($_GET["utm"]);

// CHECK IF USER EXISTS


// Use this query if you want to ALLOW a FB email to coexist with a Dealupa email
// $query = "SELECT user_id FROM Users WHERE email='$email' AND fb_id='0'";

// Use this query if you don't want to allow a FB email to coexist with a Dealupa email
$query = "SELECT user_id FROM Users WHERE email='$email'";

$result = mysql_query($query);
$num_rows = mysql_num_rows($result);

if ($num_rows > 0) {
	echo "0";
	exit();
}


// IF NOT, INSERT USER

$query = "INSERT INTO Users (email, password_hash, last_seen, visits, latitude, longitude, session_id, session_created, utm) VALUES ('$email', '$password_hash', UTC_TIMESTAMP(), 0, '$latitude', '$longitude', '$session_id', UTC_TIMESTAMP(), '$utm') on duplicate key update last_seen=UTC_TIMESTAMP()";

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
} else {
}


// RETURN USER ID

$query = "SELECT user_id FROM Users WHERE email='$email'";

$result = mysql_query($query);

$num_rows = mysql_num_rows($result);

if ($num_rows == 1) {
	$row = mysql_fetch_assoc($result);
	echo $row['user_id'];
} else {
	echo "-1";
}

?>