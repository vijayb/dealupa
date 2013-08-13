<?php

require("db_user.php");
require("helpers.php");
require("email_welcome.php");

// put in code to safeguard against injection attacks

$email = mysql_real_escape_string($_GET["email"]);
$latitude = mysql_real_escape_string($_GET["latitude"]);
$longitude = mysql_real_escape_string($_GET["longitude"]);
$session_id = mysql_real_escape_string($_GET["session_id"]);
$utm = mysql_real_escape_string($_GET["utm"]);

if (isset($_GET["referrer"])) {
	$referrer = mysql_real_escape_string($_GET["referrer"]);
} else {
	$referrer = 0;
}

$password_hash = sha1($email + time());


// CHECK IF USER EXISTS


// Use this query if you want to ALLOW a FB email to coexist with a Dealupa email
// $query = "SELECT user_id FROM Users WHERE email='$email' AND fb_id='0'";

// Use this query if you don't want to allow a FB email to coexist with a Dealupa email
$query = "SELECT user_id FROM Users WHERE email='$email'";

$result = mysql_query($query, $users_con);
$num_rows = mysql_num_rows($result);

if ($num_rows > 0) {
	echo "0";
	exit();
}


// Calculate edition from lat/lng
$edition = calculate_city_edition_from_lat_lng($latitude, $longitude);


// Check email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	echo "-1";
	exit();
}


// IF NOT, INSERT USER
$query = "INSERT INTO Users (email, password_hash, last_seen, visits, latitude, longitude, edition, session_id, session_created, utm, subscribed, referrer) VALUES ('$email', '$password_hash', UTC_TIMESTAMP(), 0, '$latitude', '$longitude', '$edition', '$session_id', UTC_TIMESTAMP(), '$utm', 1, '$referrer') on duplicate key update last_seen=UTC_TIMESTAMP()";

$result = mysql_query($query, $users_con);
if (!$result) {
  die('Invalid query: ' . $query . " - " . mysql_error());
} else {
}


// RETURN USER ID

$query = "SELECT user_id FROM Users WHERE email='$email'";

$result = mysql_query($query, $users_con);

$num_rows = mysql_num_rows($result);

if ($num_rows == 1) {

	$row = mysql_fetch_assoc($result);
	$user_id = $row['user_id'];
	
	
	$token = generate_user_token_from_user_id($user_id);
	
	echo("['" . $user_id . "','" . $token . "']");

	// Put in default values for category preferences
	initialize_categories_if_needed($user_id);
	
	$html = generate_welcome_email("", $email);
		
	sendEmail($email, "", "Welcome to Dealupa", $html);
	
} else {
	echo "-1";
}

?>