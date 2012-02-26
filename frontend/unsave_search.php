<?php

require("db_user.php");

$user = $_GET["user"];
$name = $_GET["name"];
$url = substr(strstr($_SERVER["QUERY_STRING"], "url="), 4);
$url = preg_replace("/&user=(.*)/", "", $url);

$query = "DELETE FROM Searches WHERE user=" . $user . " AND search_url=\"" . $url . "\" AND name=\"" . $name . "\"";

echo($query);

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}


?>