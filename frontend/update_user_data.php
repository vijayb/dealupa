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

if (isset($_GET["edition"])) {
	$edition = mysql_real_escape_string($_GET["edition"]);

	$query = "UPDATE Users SET edition=$edition WHERE user_id = " . $user_id;
	
	$result = mysql_query($query, $users_con);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}
	
}







if (isset($_GET["email_frequency"])) {
	$email_frequency = mysql_real_escape_string($_GET["email_frequency"]);

	$query = "UPDATE Users SET email_frequency='$email_frequency' WHERE user_id = " . $user_id;

	$result = mysql_query($query, $users_con);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

}






if (isset($_GET["max_deal_distance"])) {
	$max_deal_distance = mysql_real_escape_string($_GET["max_deal_distance"]);
	
	if ($max_deal_distance != 0 || (isset($_GET["user_edition"]) && $_GET["user_edition"] != 1)) {
		$query = "UPDATE Users SET max_deal_distance='$max_deal_distance' WHERE user_id = " . $user_id;
		$result = mysql_query($query, $users_con);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
	}
}






if (isset($_GET["zipcode"])) {
	$zipcode = mysql_real_escape_string($_GET["zipcode"]);

	$zipcode_as_integer = $zipcode + 0;
	$query = "SELECT latitude, longitude from Zipcodes where zip = " . $zipcode_as_integer;

	$result = mysql_query($query);

	if ($row = mysql_fetch_assoc($result)) {
		$latitude = $row["latitude"];
		$longitude = $row["longitude"];
	} else {
		echo(0);
		exit;
	}

	$edition = calculate_city_edition_from_lat_lng($latitude, $longitude);


	$query = "UPDATE Users SET zipcode='$zipcode', edition=$edition WHERE user_id = " . $user_id;

	$result = mysql_query($query, $users_con);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	// We echo the edition so that the JS has it and can set the edition in the deelio
	// application based on the user's zip code

	echo($edition);
}






?>
