<?php

require("db_user.php");
require("helpers.php");
require("email_welcome.php");

if (isset($_GET["user_id"]) && isset($_GET["first"]) &&
    isset($_GET["last"]) && isset($_GET["email"]) &&
    isset($_GET["latitude"]) && isset($_GET["longitude"]))
{
    $user_id = mysql_real_escape_string($_GET["user_id"]);
    $first = mysql_real_escape_string($_GET["first"]);
    $last = mysql_real_escape_string($_GET["last"]);
    $email = mysql_real_escape_string($_GET["email"]);
    $latitude = mysql_real_escape_string($_GET["latitude"]);
    $longitude = mysql_real_escape_string($_GET["longitude"]);

	$edition = calculate_city_edition_from_lat_lng($latitude, $longitude);

	
	// First, check if the user with the incoming fb id exists already
	$query = "SELECT user_id FROM Users WHERE fb_id=$user_id";
	
	$result = mysql_query($query, $users_con);

	if (!$result) {
	  die('Invalid query: ' . mysql_error());	
	}
	
	$user_is_new = 0;
	if (!mysql_fetch_assoc($result)) {
		$user_is_new = 1;
	}
	
	
    if (isset($user_id) && strlen($user_id) > 0 && isset($first) && strlen($first) > 0 && isset($last) && strlen($last) > 0) {
      $query = "INSERT INTO Users (fb_id, first_name, last_name, email, latitude, longitude, last_seen, visits, edition) VALUES ('$user_id', '$first', '$last', '$email', '$latitude', '$longitude', UTC_TIMESTAMP(), 0, $edition) on duplicate key update fb_id='$user_id', first_name='$first', last_name='$last', last_seen=UTC_TIMESTAMP()";
    } else {
      $query = "INSERT INTO Users (email, latitude, longitude, last_seen, visits, edition) VALUES ('$email', '$latitude', '$longitude', UTC_TIMESTAMP(), 0, $edition) on duplicate key update last_seen=UTC_TIMESTAMP()";
    }
	
	$result = mysql_query($query, $users_con);

	if (!$result) {
	  die('Invalid query: ' . mysql_error());	
	}

	if ($user_is_new) {
		$html = generate_welcome_email($first, $email);
		sendEmail($email, "", "Welcome to Dealupa", $html);
	}


	
} else {
  die("Incorrect arguments passed to save_user.php\n");
}	
	






// Get the Dealupa user_id of the user required for 2 things:
//	- so we can return it to the JS so the userID can be set there
//	- so category prefs can be added to the DB *IF* this is a new user

$query = "SELECT user_id FROM Users WHERE fb_id='$user_id'";
$result = mysql_query($query, $users_con);

if ($row = mysql_fetch_assoc($result)) {
	$user_id = $row['user_id'];
} else {
	echo "Big error in save_user.php";
}




$token = generate_user_token_from_user_id($user_id);

echo ("[" . $user_id . ", '" . $token . "']");







initialize_categories_if_needed($user_id);


?>