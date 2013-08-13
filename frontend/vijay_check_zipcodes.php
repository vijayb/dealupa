<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("db_user.php");
require_once("array_constants.php");
require_once("helpers.php");

if (!isset($_GET["pass"]) || $_GET["pass"] != "cheapass") {
  exit();
}


$zipcodes = getZipcodes($users_con);

$sql  = "SELECT user_id, edition, zipcode from Users where edition is not null and zipcode is not null";

$result = mysql_query($sql, $users_con);


$editions_arr[1] = 0;
for ($i=3; $i <=35; $i++) {
  $editions_arr[$i] = 0;
}

$sql_arr = array();
$total_count = 0;
$wrong_edition_count = 0;
while ($row = mysql_fetch_assoc($result)) {
  $total_count++;

  $zipcode_as_integer = $row["zipcode"] + 0;
  if (isset($zipcodes[$zipcode_as_integer]["latitude"]) &&
      isset($zipcodes[$zipcode_as_integer]["longitude"])) {
    $lat = $zipcodes[$zipcode_as_integer]["latitude"];
    $lng = $zipcodes[$zipcode_as_integer]["longitude"];
  } else {
    echo "Couldn't find lat long for ".$row["zipcode"]."<BR>\n";
    exit;
  }
  //$lat = $row['latitude'];
  //$lng = $row['longitude'];
  $user_id = $row['user_id'];
    
  $edition = 1;
  $min_distance = 10000000;
  for ($i=3; $i <=35; $i++) {
    if ($swLng[$i] <= $lng && $swLat[$i] <= $lat &&
	$neLng[$i] >= $lng && $neLat[$i] >= $lat) {

      $distance = distance($lat, $lng, $center_lat[$i], $center_lng[$i]);
      if ($distance < $min_distance) {
		if ($edition != 1) {
		  //echo "Changing edition from $edition to $i<BR>\n";
		}

		$edition = $i;
		$min_distance = $distance;
      }
    }
  }
  $editions_arr[$edition] +=1;
  if ($edition == 1) {
    echo $lat.",".$lng."<BR>\n";
  }

  //  echo "$user_id: [$lat,$lng]  $edition " . $row["edition"] ."<BR>\n";
  if ($row["edition"] != $edition) {
    //if ($edition != 1) {
      echo "Predicted $edition, see ".$row["edition"]." for zipcode: ".$row["zipcode"]."<BR>\n";

    //}

      /*
      $insertsql = "update Users set edition=$edition where user_id=$user_id";
      echo "$insertsql<BR>\n";
      mysql_query($insertsql, $users_con);

      if ($edition == 1) {
	$insertsql = "update Users set max_deal_distance=50 where user_id=$user_id";
	echo "$insertsql<BR>\n";
	mysql_query($insertsql, $users_con);
      }
      */
    $wrong_edition_count++;
  }



}


echo "Total: $total_count<BR>\n";
echo "Incorrect: $wrong_edition_count<BR>\n";
foreach ($editions_arr as $edition => $count) {
  echo "Edition $edition observed $count times<BR>\n";
}



function getZipcodes($users_con) {
  $zipcodes = array();

  $sql = "SELECT zip, latitude, longitude from Zipcodes where 1";
  $result = mysql_query($sql, $users_con);

  while ($row = @mysql_fetch_assoc($result)) {
    $zipcodes[$row["zip"]]["latitude"] = $row["latitude"];
    $zipcodes[$row["zip"]]["longitude"] = $row["longitude"];
  }

  return $zipcodes;
}


?>