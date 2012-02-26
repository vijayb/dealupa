<?php

require("db_user.php");

// put in code to safeguard against injection attacks

// lookup_userid assumes that there is a user in the DB with a fb_id of fb_id

$fb_id = mysql_real_escape_string($_GET["fb_id"]);
$query = "SELECT user_id FROM Users WHERE fb_id='$fb_id'";

$result = mysql_query($query);

$num_rows = mysql_num_rows($result);

if ($num_rows == 1) {
	$row = mysql_fetch_assoc($result);
	echo $row['user_id'];
} else {
	echo "0";
}

?>