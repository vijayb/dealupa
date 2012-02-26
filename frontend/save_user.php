<?php

require("db_user.php");

if (isset($_GET["user"]) && isset($_GET["first"]) &&
    isset($_GET["last"]) && isset($_GET["email"]) &&
    isset($_GET["latitude"]) && isset($_GET["longitude"]))
{
    $user = mysql_real_escape_string($_GET["user"]);
    $first = mysql_real_escape_string($_GET["first"]);
    $last = mysql_real_escape_string($_GET["last"]);
    $email = mysql_real_escape_string($_GET["email"]);
    $latitude = mysql_real_escape_string($_GET["latitude"]);
    $longitude = mysql_real_escape_string($_GET["longitude"]);

    if (isset($user) && length($user) > 0 && isset($first) && length($first) > 0 && isset($last) && length($last) > 0) {
      $query = "INSERT INTO Users (fb_id, first_name, last_name, email, latitude, longitude, last_seen, visits) VALUES ('$user', '$first', '$last', '$email', '$latitude', '$longitude', UTC_TIMESTAMP(), 0) on duplicate key update fb_id='$user', first_name='$first', last_name='$last', last_seen=UTC_TIMESTAMP()";
    } else {
      $query = "INSERT INTO Users (email, latitude, longitude, last_seen, visits) VALUES ('$email', '$latitude', '$longitude', UTC_TIMESTAMP(), 0) on duplicate key update last_seen=UTC_TIMESTAMP()";
    }
} else {
  die("Incorrect arguments passed to save_user.php\n");
}

echo $query;

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

?>