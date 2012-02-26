<?php

require("db_user.php");

$user = $_GET["user"];
$deal = $_GET["deal"];

$query = "SELECT * FROM Saved777 WHERE user=" . $user . " AND deal_id=\"" . $deal . "\"";

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

$num_rows = mysql_num_rows($result);

echo($num_rows);


?>