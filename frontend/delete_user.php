<?php

require("db_user.php");

if (isset($_GET["email"])) {
  $sql = "SELECT user_id from Users where strcmp(email, '".mysql_real_escape_string($_GET["email"])."')=0 limit 1";
  echo($sql);
  
  $result = mysql_query($sql, $users_con);
  if ($row = @mysql_fetch_assoc($result)) {
    $user_id = $row["user_id"];
  }
} else if (isset($_GET["user_id"]) && is_numeric($_GET["user_id"])) {
  $user_id = mysql_real_escape_string($_GET["user_id"]);
}



if (isset($user_id) && isset($_GET["password"]) && $_GET["password"] == "cheapass") {
	$sql = "DELETE FROM CategoryPreferences WHERE user_id = $user_id";
	echo($sql."<BR>\n");
	
	$result = mysql_query($sql, $users_con);

	$sql = "DELETE FROM EmailedDeals WHERE user_id = $user_id";
	echo($sql."<BR>\n");
	
	$result = mysql_query($sql, $users_con);
	
	$sql = "DELETE FROM Users WHERE user_id = $user_id";
	echo($sql);
	
	$result = mysql_query($sql, $users_con);
		
}

?>