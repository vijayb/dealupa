<?php

require("db_user.php");

$user = $_GET["user"];
$params = $_GET["url"];

$query = "INSERT INTO EmailAlerts (user_id, params, name) VALUES (" . $user . ", \"" . $params . "\", \"\");";

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

$id = mysql_insert_id($users_con);
echo($id);

exit;

?>
