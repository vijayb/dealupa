<?php

require("db_user.php");

$user = $_GET["user"];

$query = "SELECT * FROM Saved777 WHERE user=" . $user;

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

while ($row = mysql_fetch_assoc($result)) {
	echo($row["deal_id"] . ",");
}


?>
