<?php

require("db_user.php");
require("helpers.php");


if (!isset($_GET["user_id"]) || !isset($_GET["token"])|| !isset($_GET["rank"])) {
	exit();
}

$user_id = $_GET["user_id"];
$token = $_GET["token"];
$rank = $_GET["rank"];



if (!check_token($user_id, $token)) {
	exit();
}



if ($rank == "1") {
	$query = "SELECT category_id FROM CategoryPreferences WHERE rank=1 AND user_id=" . $user_id;
} else if ($rank == "2") {
	$query = "SELECT category_id FROM CategoryPreferences WHERE rank=2 AND user_id=" . $user_id;
}


$result = mysql_query($query, $users_con);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

$categories = array();

while ($row = mysql_fetch_assoc($result)) {
	array_push($categories, intval($row["category_id"]));
}

echo(json_encode($categories));

?>