<?php

require("db_user.php");
require("helpers.php");

$session_id = mysql_real_escape_string($_GET["session_id"]);
$user_id = mysql_real_escape_string($_GET["user_id"]);

$query = "SELECT * FROM Users WHERE user_id='$user_id' AND session_id='$session_id'";

$result = mysql_query($query, $users_con);


if ($row = mysql_fetch_assoc($result)) {

	$token = generate_user_token_from_user_id($user_id);

	if ($row["first_name"] == "") {
		echo("['" . $row["email"] . "','" . $token . "']");
	} else {
		echo("['" . $row["first_name"] . " " . $row["last_name"] . "','" . $token . "']");
	}
} else {
	echo "[0]";
}

?>