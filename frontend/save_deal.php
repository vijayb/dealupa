<?php

require("db_user.php");

$user = $_GET["user"];
$deal = $_GET["deal"];

$query = "INSERT INTO Saved777 (user, deal_id) VALUES (" . $user . ", \"" . $deal . "\");";

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

echo($result);

?>