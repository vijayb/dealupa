<?php

require("db_user.php");

$id = $_GET["id"];


$query = "DELETE FROM EmailAlerts WHERE id=" . $id;

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

echo($result);

?>
