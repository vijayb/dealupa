<?php

require("db_user.php");


$session_id = mysql_real_escape_string($_GET["session_id"]);
$user_id = mysql_real_escape_string($_GET["user_id"]);

$query = "SELECT * FROM Users WHERE user_id='$user_id' AND session_id='$session_id' AND TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), session_created)) < 3600";

$result = mysql_query($query);

$num_rows = mysql_num_rows($result);

if ($num_rows == 1) {
	echo "1";
} else {
	echo "0";
}

?>