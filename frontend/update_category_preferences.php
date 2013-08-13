<?php

require("db_user.php");
require("helpers.php");


if (!isset($_GET["user_id"]) || !isset($_GET["token"])) {
	exit();
}

$user_id = $_GET["user_id"];
$token = $_GET["token"];

if (!check_token($user_id, $token)) {
	exit();
}

$categories_to_delete = array();
if (isset($_GET["categories_to_delete"])) {
	$categories_to_delete = json_decode($_GET["categories_to_delete"]);	

	for ($i = 0; $i < count($categories_to_delete); $i++) {
		$category_id = $categories_to_delete[$i];
		$sql = "DELETE FROM CategoryPreferences WHERE category_id = $category_id AND user_id = $user_id";
		echo($sql . "\n");
		$result = mysql_query($sql, $users_con);
	}	
}

$categories_to_add = array();
if (isset($_GET["categories_to_set_to_1"])) {
	$categories_to_add = json_decode($_GET["categories_to_set_to_1"]);
	
	for ($i = 0; $i < count($categories_to_add); $i++) {
		$category_id = $categories_to_add[$i];
	
		$sql = "INSERT INTO CategoryPreferences (user_id, category_id, rank) VALUES ($user_id, $category_id, 1) ON DUPLICATE KEY UPDATE rank=1";
		echo($sql . "\n");
		$result = mysql_query($sql, $users_con);
	}
}

$categories_to_heart = array();
if (isset($_GET["categories_to_set_to_2"])) {
	$categories_to_heart = json_decode($_GET["categories_to_set_to_2"]);
	
	for ($i = 0; $i < count($categories_to_heart); $i++) {
		$category_id = $categories_to_heart[$i];
	
		$sql = "INSERT INTO CategoryPreferences (user_id, category_id, rank) VALUES ($user_id, $category_id, 2) ON DUPLICATE KEY UPDATE rank=2";
		echo($sql . "\n");
		$result = mysql_query($sql, $users_con);
	}
}



?>