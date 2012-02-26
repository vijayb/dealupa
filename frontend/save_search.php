<?php

require("db_user.php");

$user = $_GET["user"];
$name = $_GET["name"];
$url = substr(strstr($_SERVER["QUERY_STRING"], "url="), 4);
$url = preg_replace("/&user=(.*)/", "", $url);

$query = "INSERT INTO Searches (user, name, search_url) VALUES (" . $user . ", \"" . $name . "\", \"" . $url . "\");";

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}


?>