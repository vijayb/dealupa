<?php

require("db_user.php");


if (isset($_GET["user"]))
{
    $user = mysql_real_escape_string($_GET["user"]);
    $query = "UPDATE Users SET visits=visits+1, last_seen=UTC_TIMESTAMP(), session_created=UTC_TIMESTAMP() WHERE user_id=" . $user;
} else {
  die("Incorrect arguments passed to update_user.php\n");
}

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

?>