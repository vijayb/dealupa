<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

$start_time = time();

///// GLOBALS ///////////////////
$MAX_SUBSCRIBERS_TO_EMAIL=10000;
///////////////////////////////


require_once("array_constants.php");
require_once("db_user.php");
require_once("db.php");
require_once("get_deal.php");
require_once("helpers_email.php");
require("email_welcome.php");

if (!isset($_GET["pass"]) || $_GET["pass"] != "cheapass") {
  exit();
}

if (!isset($_GET["user_id"])) {
  echo "Error, no user_id set as get parameter. Set to 0 to email everyone<BR>\n";
  exit;
}


// For each users who's subscribed, get all their email alerts.
// For each alert, find all the deals that match it and add
// them to the list of deals to email. Make sure not to add
// deals to the list if they've been sent to the user before.
$subscribed_users = getSubscribedUsers($_GET["user_id"],
				       $MAX_SUBSCRIBERS_TO_EMAIL,
				       $users_con);

$users_who_were_emailed = 0;
foreach ($subscribed_users as $user_id => $user_info) {
  echo "User_id: $user_id<BR>\n";

  $edition = $user_info["edition"];
  $zipcode = $user_info["zipcode"];

  $token = generate_user_token($user_id, $user_info["user_created"]);
  $html = generate_onboarding_email($user_info["first_name"],$user_info["email"], 
				    $edition, $zipcode);
  //echo $html;

  sendEmail($user_info["email"], "\"Dealupa\" <founders@dealupa.com>",
	    "Welcome to Dealupa", $html);
  $users_who_were_emailed++;
}

$time_taken = time() - $start_time;
echo "Time taken to send ".$users_who_were_emailed." emails: ".$time_taken." seconds<BR>\n";

////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////// HELPER FUNCTIONS ///////////////////////////////////////////



function getSubscribedUsers($user_id, $max_subscribers, $users_con) {
  $subscribed_users = array();

  $user_clause = "";
  if ($user_id > 0) {
    $user_clause = "user_id=$user_id and ";
  }

  $sql = "select user_id,first_name,email,edition,zipcode,max_deal_distance, user_created from Users where $user_clause".
    "email is not null and subscribed=0 and user_created < '2012-03-19 05:42:00'";

  //echo "$sql<BR><BR><BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die("Invalid query: $sql " . mysql_error());
  }


  while ($row = @mysql_fetch_assoc($result)) {
    $subscribed_users[$row['user_id']]["first_name"] = $row['first_name'];
    $subscribed_users[$row['user_id']]["email"] = $row['email'];
    $subscribed_users[$row['user_id']]["edition"] = $row['edition'];
    $subscribed_users[$row['user_id']]["user_created"] = $row['user_created'];
    $subscribed_users[$row['user_id']]["zipcode"] = $row['zipcode'];
    $subscribed_users[$row['user_id']]["max_deal_distance"] = $row['max_deal_distance'];
  }

  return $subscribed_users;
}




?>
