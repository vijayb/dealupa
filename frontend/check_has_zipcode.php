<?php

require("db_user.php");

$user_id = $_GET["user_id"];

$query = "SELECT zipcode FROM Users WHERE zipcode IS NOT NULL AND user_id=" . $user_id;
$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}


if (mysql_num_rows($result) == 1) {
	echo(1);
	exit;
}

echo(0)


?>