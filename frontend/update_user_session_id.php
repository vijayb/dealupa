<?php

// TODO AT SOME POINT: This file should be merged with update_user.php

require("db_user.php");
require("helpers.php");

// Update user session in the DB

if (isset($_GET["user_id"]) && isset($_GET["session_id"])) {

    $user_id = mysql_real_escape_string($_GET["user_id"]);
	$session_id = mysql_real_escape_string($_GET["session_id"]);
    $query = "UPDATE Users SET session_id='$session_id' WHERE user_id='$user_id'";

	$result = mysql_query($query, $users_con);
	if (!$result) {
	  die('Invalid query: ' . mysql_error());
	}	
	
} else {
  die("Incorrect arguments passed to update_user.php\n");
}

// If the user doesn't have an edition set (which will be common for legacy
// users), set an edition based on the gbLat/gbLng which are passed to this PHP 

if (isset($_GET["user_id"]) && isset($_GET["latitude"]) && isset($_GET["longitude"])) {
	set_edition_if_not_set($_GET["user_id"], $_GET["latitude"], $_GET["longitude"]);
}

// If the user has no categories, add categories

if (isset($_GET["user_id"])) {
	initialize_categories_if_needed($_GET["user_id"]);
}

?>