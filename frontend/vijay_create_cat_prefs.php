<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);

if (!isset($_GET["pass"]) || $_GET["pass"] != "cheapass") {
  exit();
}

require("db_user.php");

$sql = "select user_id from Users where 1";

$result = mysql_query($sql, $users_con);

$count = 0;
while ($row = @mysql_fetch_assoc($result)) {
  $user_id = $row["user_id"];
  for ($i=46; $i <= 59; $i++) {
    $insert_sql = "insert into CategoryPreferences (user_id, category_id, rank) values ($user_id, $i, 1) on duplicate key update id=id";
    mysql_query($insert_sql, $users_con);
    //echo $insert_sql."<BR>\n";
  }

  echo $row["user_id"]."<BR>\n";


  $count++;
  //if ($count > 10) { break; }

}




?>