<?php

require_once("array_constants.php");
require_once("db_user.php");
require_once("db.php");

function getCategoryInformation() {
	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	global $deals_con;

	$categoryInformationKey = "categoryInformation";
	$categoryInformation = $memcache->get($categoryInformationKey);

	if (!$categoryInformation) {
		$query = "SELECT * FROM CategoryInfo";

		$result = mysql_query($query, $deals_con);
		if (!$result) die('Invalid query: ' . mysql_error());

		$categoryInformation = array();
		while ($row = mysql_fetch_assoc($result)) {
			array_push($categoryInformation, $row);
		}

		$memcache->set($categoryInformationKey, $categoryInformation, false, 3600);
	}
	
	return $categoryInformation;
}



function get_simple_categories_array() {
	$get_categories = getCategoryInformation();
	
	$return_categories = array();
	$return_categories[0] = "Uncategorized";
	
	for ($j = 0; $j < count($get_categories); $j++) {
		$return_categories[$j + 1] = $get_categories[$j]["name"];
	}
	
	return $return_categories;
}






function initialize_categories_if_needed($user_id) {
	
	global $users_con;
	
	$query = "SELECT id FROM CategoryPreferences WHERE user_id=$user_id LIMIT 1";
	$result = mysql_query($query, $users_con);
	if (!$result) die('Invalid query: ' . mysql_error());

	if (mysql_num_rows($result) > 0) {
		return;
	}	
	
	
	if (isset($user_id) && $user_id != "") {
		$categoryInformation = getCategoryInformation();
		
		for ($i = 0; $i < count($categoryInformation); $i++) {
			$category_id = intval($categoryInformation[$i]["id"]);
		
			$insert_sql = "INSERT INTO CategoryPreferences (user_id, category_id, rank) VALUES ($user_id, $category_id, 1) ON DUPLICATE KEY UPDATE id=id";
			$result = mysql_query($insert_sql, $users_con);
		}
	}
}


function set_edition_if_not_set($user_id, $latitude, $longitude) {

	global $users_con;

	$edition = calculate_city_edition_from_lat_lng($latitude, $longitude);
	
	$query = "UPDATE Users SET edition=$edition WHERE user_id=$user_id AND edition IS NULL";
	
	$result = mysql_query($query, $users_con);
	if (!$result) {
	  die('Invalid query: ' . mysql_error());
	}
}



function generate_password_link($email) {
	return generate_action_link($email) . "&action=reset_password";
}


function generate_settings_link($email) {
	return generate_action_link($email) . "&action=show_settings";
}

function generate_logged_in_link($email) {
	return generate_action_link($email) . "&action=email_login";
}


function generate_action_link($email) {

	global $users_con;
	global $domain_ac;

	$query = "SELECT user_id, user_created FROM Users WHERE email='$email' LIMIT 1";
	
	$result = mysql_query($query, $users_con);

	if ($row = mysql_fetch_assoc($result)) {
		$user_id = $row['user_id'];
		$user_created = $row['user_created'];
		$token = generate_user_token($user_id, $user_created);
	} else {
		return "0";
	}

	return($domain_ac . "/?id=$user_id&token=$token");

}

function check_token($user_id, $token) {
	$correct_token = generate_user_token_from_user_id($user_id);
	if ($token == $correct_token) {
		return 1;
	}

	return 0;
}


function generate_user_token($user_id, $user_created) {
	return sha1($user_id . $user_created);
}


function generate_user_token_from_user_id($user_id) {

	global $users_con;
	
	$query = "SELECT user_created FROM Users WHERE user_id='$user_id' LIMIT 1";
	
	$result = mysql_query($query, $users_con);

	if ($row = mysql_fetch_assoc($result)) {
		$user_created = $row['user_created'];
		$token = generate_user_token($user_id, $user_created);
	} else {
		return "0";
	}

	return($token);

}




function days_ago($d) { 
	$ts = time() - $d; 
	if ($ts > 86400) {
		$val = floor($ts/86400);
	} else {
		$val = 0;
	}
	return $val; 
} 


function has_expired($d) {
	$remaining = $d - time();
	if ($remaining > 0) {
		return false;
	} else {
		return true;
	}
}

function time_left($d) {
	$remaining = $d - time();
	$days_remaining = floor($remaining / 86400);
	$hours_remaining = floor(($remaining % 86400) / 3600);
	$minutes_remaining = floor(($remaining % 3600) / 60);	
	$time_left_arr = array("d" => $days_remaining, "hr" => $hours_remaining, "min" => $minutes_remaining,);
	return $time_left_arr;
}


// IMPORTANT: ANY CHANGES MADE HERE MUST ALSO BE MADE IN hyphenateTitle IN deelio.js

function hyphenate_title($url_title) {


	$url_title = strtolower($url_title);

	$patterns = array();
	$replacements = array();
	
	
	$patterns[0] = '/\b[a-z]\b/';
	$replacements[0] = '';

	$patterns[1] = '/\b[a-z][a-z]\b/';
	$replacements[1] = '';

	$patterns[2] = '/[0-9] (value|regular)\b/';
	$replacements[2] = '';

	$patterns[3] = '/[0-9] (towards|spend)\b/';
	$replacements[3] = '';

	$patterns[4] = '/\b(for|the|and|are|but|you|reg|your|more)\b/i';
	$replacements[4] = '';

	$patterns[5] = '/&[0-9a-z]+;/';
	$replacements[5] = '-';

	$patterns[6] = '/[^a-z0-9\-]/';
	$replacements[6] = '-';

	$patterns[7] = '/\b[0-9]+\b/';
	$replacements[7] = '-';
	
	$patterns[8] = '/ /';
	$replacements[8] = '-';

	$patterns[9] = '/-+/';
	$replacements[9] = '-';

	$patterns[10] = '/deal-ends-soon/';
	$replacements[10] = '';

	$patterns[11] = '/^-/';
	$replacements[11] = '';

	$patterns[12] = '/-$/';
	$replacements[12] = '';
	
	$url_title = preg_replace($patterns, $replacements, $url_title);
	
	if ($url_title == "") {
		$url_title = "deal";
	}


	return $url_title;
}





function get_discount($price, $value) {
	if ($value == 0) {
		return 0;
	}
	$difference = $value - $price;
	$discount = 100 * ($difference / $value);
	return round($discount);
}

/*
// Old version before we had a 1 edition
function calculate_city_edition_from_lat_lng($lat, $lng) {

	global $cityLat;
	global $cityLng;
	
	$minDistance = 1000000;
	$currDistance;
	
	$edition = 3;

	for ($i = 0; $i < count($cityLat); $i++) {
		if ($cityLat[$i] != 0 && $i != 500) {
			$currDistance = distance($lat, $lng, $cityLat[$i], $cityLng[$i]);
			if ($currDistance < $minDistance) {
				$minDistance = $currDistance;
				$edition = $i;
			}
		}
	}
	
	return $edition;
}
*/



function calculate_city_edition_from_lat_lng($lat, $lng) {
	
	global $cities;
	global $swLat, $swLng, $neLat, $neLng, $center_lat, $center_lng;

	$edition = 1;
	$min_distance = 10000000;
	
	for ($i = 3; $i <= count($cities); $i++) {
		if ($swLng[$i] <= $lng && $swLat[$i] <= $lat && $neLng[$i] >= $lng && $neLat[$i] >= $lat) {

			$distance = distance($lat, $lng, $center_lat[$i], $center_lng[$i]);
			if ($distance < $min_distance) {
				$edition = $i;
				$min_distance = $distance;
			}
		}
	}
	
	return $edition;
}









function distance($latitude1, $longitude1, $latitude2, $longitude2) {
    $theta = $longitude1 - $longitude2;
    $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
    $miles = acos($miles);
    $miles = rad2deg($miles);
    return ($miles * 60 * 1.1515);
}


function sendEmail($to, $from, $subject, $html) {

	if ($from == "") {
		$from = "Dealupa <founders@dealupa.com>";
	}

  $request = new HttpRequest('https://api.mailgun.net/v2/dealupamail.com/messages', HttpRequest::METH_POST);
  $auth = base64_encode('api:key-68imhgvpoa-6uw3cl8728kcs9brvlmr9');
  $request->setHeaders(array('Authorization' => 'Basic '.$auth));
  $request->setPostFields(array('from' => $from,
								'to' => $to,
								'subject' => $subject,
								'html' => $html));
  $request->send();
  return $request;
}




function check_valid_id_token_pair($user_id, $token) {

	global $users_con;

	$query = "SELECT user_created FROM Users WHERE user_id='$user_id' LIMIT 1";
	
	$result = mysql_query($query, $users_con);
	if ($row = mysql_fetch_assoc($result)) {
		$created = $row["user_created"];

		$token_db = sha1($user_id . $created);
		
		if ($token_db == $token) {
			return 1;
		}
	}
	return 0;
}





?>
