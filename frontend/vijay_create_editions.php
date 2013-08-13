<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("db_user.php");
require_once("array_constants.php");
require_once("helpers.php");

if (!isset($_GET["pass"]) || $_GET["pass"] != "cheapass") {
  exit();
}


$sql  = "SELECT user_id, latitude, longitude, edition from Users where zipcode is null and latitude is not null and longitude is not null and user_created < '2012-03-19 05:42:00'";

$result = mysql_query($sql, $users_con);


$editions_arr[1] = 0;
for ($i=3; $i <=50; $i++) {
  $editions_arr[$i] = 0;
}

$total_count = 0;
$sql_arr = array();
while ($row = mysql_fetch_assoc($result)) {
  $total_count++;
  $lat = $row['latitude'];
  $lng = $row['longitude'];
  $user_id = $row['user_id'];
    
  $edition = 1;
  $min_distance = 10000000;
  for ($i=3; $i <=50; $i++) {
    if ($swLng[$i] <= $lng && $swLat[$i] <= $lat &&
	$neLng[$i] >= $lng && $neLat[$i] >= $lat) {

      $distance = distance($lat, $lng, $center_lat[$i], $center_lng[$i]);
      if ($distance < $min_distance) {
	if ($edition != 1) {
	  echo "Changing edition from $edition to $i<BR>\n";
	}

	$edition = $i;
	$min_distance = $distance;
      }
    }
  }
  $editions_arr[$edition] +=1;

  if (isset($row["edition"]) && $edition != $row["edition"]) {
    echo "New $edition, old ".$row["edition"]."<BR>\n";
    echo "$user_id: [$lat,$lng]  $edition<BR>\n";

    $insertsql = "update Users set edition=$edition where user_id=$user_id";
    echo "$insertsql<BR>\n";
    
    //mysql_query($insertsql, $users_con);
  }

}


foreach ($editions_arr as $edition => $count) {
  echo "Edition $edition observed $count times<BR>\n";
}
echo "Total:$total_count<BR>\n";

?>