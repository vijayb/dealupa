<?php
// Script to remove from the Users database (Users, CategoryPreferences,
// EmailedDeals tables) users whose emails are bouncing, according to mailgun.
// Also removes all entries from EmailedDeals that are older than a week.
// After deleting the users, a status email is sent to founders@dealupa.com
// This script should be put on the WorkQueue so it runs once a day(ish).
//


$MAX_USERS_TO_DELETE = 1000;

require("db_user.php");
require("db.php");
require("helpers.php");

if (!isset($_GET["pass"]) || $_GET["pass"] != "oq3i4rh0w") {
  exit();
}

$response = json_decode(get_bounces());
$items = $response->{"items"};
//print_r($items);

$deleted_users = array();
$error_messages = array();

foreach ($items as $index => $status) {
  if (count($deleted_users) >= $MAX_USERS_TO_DELETE) {
    break;
  }

  $sql = "select user_id from Users where email='".
    mysql_real_escape_string($status->{"address"})."' limit 1";
  $result = mysql_query($sql, $users_con);
  
  $user_id=0;
  if ($row = mysql_fetch_assoc($result)) {
    $user_id=$row["user_id"];
  }

  //echo "$index: (userID $user_id): ".
  //$status->{"address"}.":".$status->{"error"}."<BR>\n";

  if ($user_id > 0) {
    //echo "Deleting user:$user_id<BR>\n";
    deleteUser($user_id, $users_con);

    array_push($deleted_users, $status->{"address"});
    array_push($error_messages, $status->{"error"});
  }
}


if (count($deleted_users) > 0) {
  $html = "<html><body>\n";
  $html .= "<center><h2>Deleted ".count($deleted_users).
    " users from Users database whose email addresses ".
    "were bouncing</h2></center>\n";
  $html .= "<center><table width=70% cellpadding=10>\n";
  for ($i=0; $i < count($deleted_users); $i++) {
    $html .= "<tr><td><b>".$deleted_users[$i]."</b></td><td>".
      $error_messages[$i]."</td></tr>\n";
  }

  $html .= "</table></center></body></html>\n";
  
  sendEmail("founders@dealupa.com", "vijayb@gmail.com",
	    "Daily user bounce report", $html);
  //echo "$html";

  echo "Deleted ".count($deleted_users)." bouncing users.";
}

function get_bounces() {
  $request =
    new HttpRequest('https://api.mailgun.net/v2/dealupamail.com/bounces?limit=1000',
                    HttpRequest::METH_GET);
  $auth = base64_encode('api:key-68imhgvpoa-6uw3cl8728kcs9brvlmr9');
  $request->setHeaders(array('Authorization' => 'Basic '.$auth));
  $request->send();
  return $request->getResponseBody();
}



function deleteUser($user_id, $users_con) {
  if ($user_id > 0) {
    $sql = "delete from Users where user_id=$user_id limit 1";
    //echo "$sql<BR>\n";
    mysql_query($sql, $users_con);

    $sql = "delete from CategoryPreferences where user_id=$user_id";
    //echo "$sql<BR>\n";
    mysql_query($sql, $users_con);

    $sql = "delete from EmailedDeals where user_id=$user_id";
    //echo "$sql<BR>\n";
    mysql_query($sql, $users_con);
  }
}




?>