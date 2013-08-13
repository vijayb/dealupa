<?php

require("db_user.php");


if (isset($_GET["user_id"])) {
    $user_id = mysql_real_escape_string($_GET["user_id"]);
    $query = "UPDATE Users SET visits=visits+1, last_seen=UTC_TIMESTAMP(), session_created=UTC_TIMESTAMP() WHERE user_id=" . $user_id;
} else {
  die("Incorrect arguments passed to update_user.php\n");
}

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

?>