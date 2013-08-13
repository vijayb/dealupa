<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

$start_time = time();

///// GLOBALS ///////////////////
$MAX_DEALS_PER_EMAIL = 5;
$MAX_HEART_DEALS_PER_EMAIL = 5;
$MAX_SUBSCRIBERS_TO_EMAIL=30000;
///////////////////////////////


$cache_life = 86400;
$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

if (!$success) {
  die("Unable to connect to memcache!\n");
}

require_once("array_constants.php");
require_once("db_user.php");
require_once("db.php");
require_once("get_deal.php");
require_once("helpers_email.php");

if (!isset($_GET["pass"]) || $_GET["pass"] != "cheapass") {
  exit();
}

if (!isset($_GET["user_id"])) {
  echo "Error, no user_id set as get parameter. Set to 0 to email everyone<BR>\n";
  exit;
}

foreach ($cities as $city_id => $city_name) {
  $deals_indexes[$city_id] = 
    $memcache->get("deals_index_"."SOLD"."_".$city_id);
}


$zipcodes = getZipcodes($users_con);

// For each users who's subscribed, get all their email alerts.
// For each alert, find all the deals that match it and add
// them to the list of deals to email. Make sure not to add
// deals to the list if they've been sent to the user before.
$subscribed_users = getSubscribedUsers($_GET["user_id"],
				       $MAX_SUBSCRIBERS_TO_EMAIL,
				       $users_con);


$emailed_deals = getEmailedDeals($users_con);
$newly_sent_deals = array();
$users_who_were_emailed = array();

//echo count($subscribed_users)."<BR>\n";

// We pay a one-time cost to find all the dealupa recommends deals
// in all the editions.
foreach ($cities as $city_id => $city_name) {
  $city_index = $deals_indexes[$city_id];
  $recommend_deals[$city_id] = array();
  for ($i=0; $i < count($city_index); $i++) {
    $deal = getDealById($city_index[$i], $deals_con, $memcache, $cache_life);

    if (isset($deal["recommend"]) && $deal["recommend"] == 1) {
      array_push($recommend_deals[$city_id], $city_index[$i]);
    }
  }
}

foreach ($subscribed_users as $user_id => $user_info) {
  echo "User_id: $user_id<BR>\n";
  $deals_to_email = array();
  $recommend_deals_to_email = array();
  $heart_deals_to_email = array();
  //  print_r($user_info);
  $my_index = $deals_indexes[$user_info["edition"]];
  $edition = $user_info["edition"];
  echo "Edition: $edition<BR>\n";

  // Add all the national dealupa recommends deals
  for ($i=0; $i < count($recommend_deals[2]); $i++) {
    if (!userHasBeenEmailedDeal($user_id, $recommend_deals[2][$i], $emailed_deals)) {
      setUserHasBeenEmailedDeal($user_id, $recommend_deals[2][$i], $emailed_deals);
      setUserHasBeenEmailedDeal($user_id, $recommend_deals[2][$i], $newly_sent_deals);
      array_push($recommend_deals_to_email, $recommend_deals[2][$i]);
      //echo $recommend_deals[2][$i]."<BR>\n";
    }
  }
  
  if ($edition != 1) {
    for ($i=0; $i < count($recommend_deals[$edition]); $i++) {
      if (!userHasBeenEmailedDeal($user_id, $recommend_deals[$edition][$i], $emailed_deals)) {
	setUserHasBeenEmailedDeal($user_id, $recommend_deals[$edition][$i], $emailed_deals);
	setUserHasBeenEmailedDeal($user_id, $recommend_deals[$edition][$i], $newly_sent_deals);
	array_push($recommend_deals_to_email, $recommend_deals[$edition][$i]);
	//echo $recommend_deals[$edition][$i]."<BR>\n";
      }
    }
  }



  for ($i=0; $i < count($my_index); $i++) {
    $deal = getDealById($my_index[$i], $deals_con, $memcache, $cache_life);

    if (!userHasBeenEmailedDeal($user_id, $deal['id'], $emailed_deals)) {
      $preference_score = matchesCatPreferences($deal, $user_info["category_preferences"]);

      if ($preference_score > 0 && 
	  notTooFarAway($deal, $user_info["zipcode"], $user_info["edition"], $user_info["max_deal_distance"], $zipcodes)) {
	$sending_deal = 0;

	if (isset($deal["recommend"]) && $deal["recommend"] == 1) {
	  array_push($recommend_deals_to_email, $my_index[$i]);
	  $sending_deal = 1;
	} else if ($preference_score == 2 && count($heart_deals_to_email) < $MAX_HEART_DEALS_PER_EMAIL) {
	  array_push($heart_deals_to_email, $my_index[$i]);
	  $sending_deal = 1;
	} else if (count($deals_to_email) < $MAX_DEALS_PER_EMAIL) {
	  array_push($deals_to_email, $my_index[$i]);
	  $sending_deal = 1;
	}

	if ($sending_deal) {
	  	//echo "&nbsp;&nbsp;&nbsp;".$my_index[$i]."<BR>\n";
	  setUserHasBeenEmailedDeal($user_id, $deal['id'], $emailed_deals);
	  setUserHasBeenEmailedDeal($user_id, $deal['id'], $newly_sent_deals);
	}

      }
    }

    if (count($deals_to_email) + count($heart_deals_to_email) >= $MAX_DEALS_PER_EMAIL + $MAX_HEART_DEALS_PER_EMAIL) {
      break;
    }
  }

  if (count($deals_to_email) > 0 || count($heart_deals_to_email) > 0 || count($recommend_deals_to_email) > 0) {
    array_push($users_who_were_emailed, $user_id);

    echo "Sending ".count($recommend_deals_to_email)." recommend deals<BR>\n";
    print_r($recommend_deals_to_email)."<BR>\n";
    echo "Sending ".count($heart_deals_to_email)." heart deals<BR>\n";
    echo "Sending ".count($deals_to_email)." deals<BR>\n";
    $token = generate_user_token($user_id, $user_info["user_created"]);
    $html = get_email_html($user_id, $token, $user_info["email"], $user_info["first_name"],
			   $user_info["zipcode"], $user_info["edition"],
			   $recommend_deals_to_email, $heart_deals_to_email,
			   $deals_to_email, $deals_con, $memcache, $cache_life);
    

    // echo($html);
    if ($user_info["edition"] != 1) {
      $subject = "Today's deals in ".$cities[$user_info["edition"]];
    } else {
      if (isset($user_info["zipcode"])) {
	$subject = "Today's deals near ".$user_info["zipcode"];
      } else {
	// This case should not happen, but just in case:
	$subject = "Today's deals in your area";
      }
    }

    sendEmail($user_info["email"], "\"Dealupa\" <founders@dealupa.com>", $subject, $html);

    
	/*
    print_r($deals_to_email);
    echo "<BR>\n";
    print_r($emailed_deals);

    echo "<BR>\n";
    print_r($newly_sent_deals);
	*/
  }
}

if (count($newly_sent_deals) > 0) {
  updateEmailedDeals($newly_sent_deals, $users_con);
}
if (count($users_who_were_emailed) > 0) {
  incrementSentCountForUsers($users_who_were_emailed, $users_con);
}

$time_taken = time() - $start_time;
echo "Time taken to send ".count($users_who_were_emailed). " emails: ".$time_taken." seconds<BR>\n";

$report_html = "<html><body><h3>Time taken</h3>";
$report_html .= "Time taken to send ".count($users_who_were_emailed). " emails: ".
  $time_taken." seconds<BR>\n";
$report_html .= "<h3>User IDs who were emailed</h3>\n";
for ($i=0; $i < count($users_who_were_emailed); $i++) {
  $report_html .= $users_who_were_emailed[$i].",";
}
$report_html .= "</body></html>\n";
sendEmail("founders@dealupa.com", "<vijayb@gmail.com>", "Email status report", $report_html);

////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////// HELPER FUNCTIONS ///////////////////////////////////////////


function notTooFarAway($deal, $user_zipcode, $edition, $max_deal_distance, $zipcodes) {
  if (!isset($edition)) { return 0; }

  if ($edition > 1 && isset($deal["recommend"]) && $deal["recommend"] == 1) { return 1; }

  if ($edition == 1 && ($max_deal_distance <= 0 || !isset($user_zipcode))) { return 0; }

  if ($edition != 1 && ($max_deal_distance <= 0 || !isset($user_zipcode))) { return 1; }
  
  // If we get to here we know that we have an edition AND a $user_zipcode AND
  // $max_deal_distance > 0.

  $zipcode_as_integer = $user_zipcode + 0;
  if (isset($zipcodes[$zipcode_as_integer]["latitude"]) &&
      isset($zipcodes[$zipcode_as_integer]["longitude"])) {
    $lat = $zipcodes[$zipcode_as_integer]["latitude"];
    $lng = $zipcodes[$zipcode_as_integer]["longitude"];

    if (isset($deal["Addresses"])) {
      $addresses = $deal["Addresses"];
      for ($k=0; $k < count($addresses); $k++) {
	$distance = distance($lat, $lng, $addresses[$k]["latitude"], $addresses[$k]["longitude"]);
	//echo "Deal ".$deal['id']." is $distance miles from zipcode $user_zipcode<BR>\n";

	if ($distance < $max_deal_distance) {
	  return 1;
	}
      }

      return 0;
    } else if ($max_deal_distance < 10) {
      // Product decision: If a user has a max_deal_distance < 10, we're not going to allow
      // deals which have no addresses to be shown to them. The reason is that if e.g.,
      // their max_deal_distance is 1, then the majority of the deal we send them will be
      // things like "carpet cleaning" deals, which will look strange to them when they
      // specified they wanted deals within a mile.
      return 0;
    }


  }

  return 1;
}


function matchesCatPreferences($deal, $cat_preferences) {
  if (!isset($cat_preferences)) {
    return 0;
  }

  if (!isset($deal["Categories"])) {
    return 0;
  }

  $returnVal = 1;
  for ($i=0; $i < count($deal["Categories"]); $i++) {
    if (!isset($cat_preferences[$deal["Categories"][$i]["category_id"]])) {
      return 0;
    } else {
      $returnVal = max($returnVal, $cat_preferences[$deal["Categories"][$i]["category_id"]]);
    }

  }

  return $returnVal;
}



function getZipcodes($users_con) {
  $zipcodes = array();

  $sql = "SELECT zip, latitude, longitude from Zipcodes where 1";
  $result = mysql_query($sql, $users_con);

  while ($row = @mysql_fetch_assoc($result)) {
    $zipcodes[$row["zip"]]["latitude"] = $row["latitude"];
    $zipcodes[$row["zip"]]["longitude"] = $row["longitude"];
  }

  return $zipcodes;
}

function getSubscribedUsers($user_id, $max_subscribers, $users_con) {
  $subscribed_users = array();

  mysql_query("SET AUTOCOMMIT=0", $users_con);
  mysql_query("START TRANSACTION", $users_con);

  $user_clause = "";
  if ($user_id > 0) {
    $user_clause = "user_id=$user_id and ";
  }

  $sql = "select user_id,first_name,email,edition,zipcode,max_deal_distance, user_created from Users where $user_clause".
    "email is not null and edition is not null and subscribed=1 and email_frequency > 0 ".
    "and (last_emailed is null or ".
    "TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), last_emailed)) > (email_frequency-18000)) ".
    "limit $max_subscribers";

  //echo "$sql<BR><BR><BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    mysql_query('ROLLBACK', $users_con);
    mysql_query('SET AUTOCOMMIT=1', $users_con);
    die("Invalid query: $sql " . mysql_error());
  }


  while ($row = @mysql_fetch_assoc($result)) {
    $subscribed_users[$row['user_id']]["first_name"] = $row['first_name'];
    $subscribed_users[$row['user_id']]["email"] = $row['email'];
    $subscribed_users[$row['user_id']]["edition"] = $row['edition'];
    $subscribed_users[$row['user_id']]["user_created"] = $row['user_created'];
    $subscribed_users[$row['user_id']]["zipcode"] = $row['zipcode'];
    $subscribed_users[$row['user_id']]["max_deal_distance"] = $row['max_deal_distance'];
	
    $subscribed_users[$row['user_id']]["category_preferences"] = getCategoryPreferences($row['user_id'], 
											$users_con);

    $sql = "update Users set last_emailed=UTC_TIMESTAMP() where user_id=".$row['user_id'];
    //echo "$sql<BR>\n";
    $update_result = mysql_query($sql, $users_con);
    if (!$update_result) {
      mysql_query('ROLLBACK', $users_con);
      mysql_query('SET AUTOCOMMIT=1', $users_con);
      die("Invalid query: $sql " . mysql_error());
    }
  }
  

  mysql_query('COMMIT', $users_con);
  mysql_query('SET AUTOCOMMIT=1', $users_con);
  return $subscribed_users;
}



function getEmailedDeals($users_con) {
  $emailed_deals = array();

  $sql = "select user_id, deal_id from EmailedDeals where 1";

  //echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die("Invalid query: $sql" . mysql_error());
  }

  while ($row = @mysql_fetch_assoc($result)) {
    $emailed_deals[$row['user_id'].",".$row['deal_id']] = 1;
  }
  
  return $emailed_deals;
}

function updateEmailedDeals($newly_emailed_deals, $users_con) {
  $sql_arr = array();
  foreach ($newly_emailed_deals as $user_id_deal_id_pair => $value) {
    $sql_arr[] = '('.$user_id_deal_id_pair.', UTC_TIMESTAMP())';
  }

  $sql = "INSERT INTO EmailedDeals (user_id, deal_id, time_sent) VALUES ".
    implode(',', $sql_arr). " on duplicate key update id=id";

  //print_r($sql);
  //echo"<BR>\n";
  
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die("Invalid query: $sql" . mysql_error());
  }
}

function incrementSentCountForUsers($users_who_were_emailed, $users_con) {
  $sql_arr = array();
  for ($i=0; $i < count($users_who_were_emailed); $i++) {
    $sql_arr[] = 'user_id='.$users_who_were_emailed[$i];
  }

  $sql = "UPDATE Users set num_emails=num_emails+1 where ".
    implode(' or ', $sql_arr);
  
  //  echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die("Invalid query: $sql" . mysql_error());
  }

}


function setUserHasBeenEmailedDeal($user_id, $deal_id, &$emailed_deals) {
  $emailed_deals[$user_id.",".$deal_id] = 1;
}

function userHasBeenEmailedDeal($user_id, $deal_id, $emailed_deals) {
  return isset($emailed_deals[$user_id.",".$deal_id]);
}



function getCategoryPreferences($user_id, $users_con) {
  $category_preferences = array();

  $sql = "select category_id, rank from CategoryPreferences where user_id=$user_id";
  //echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die("Invalid query: $sql" . mysql_error());
  }

  while ($row = @mysql_fetch_assoc($result)) {
    $category_preferences[$row['category_id']] = $row['rank'];
  }
  
  return $category_preferences;
}



function insertSentDeals($user_id, $deals_to_email, $users_con) {
  foreach ($deals_to_email as $alert_id => $deals_index) {
    for ($i=0; $i < count($deals_index); $i++) {
      $sql = "insert into EmailedDeals (user_id, deal_id, time_sent) values ".
	"($user_id, ".$deals_index[$i].", UTC_TIMESTAMP())";
      //echo "$sql<BR>\n";
      $result = mysql_query($sql, $users_con);
      if (!$result) {
	die("Invalid query: $sql" . mysql_error());
      } 
    }
  }
}

function insertStatus($user_id, $request, $users_con) {
    $sql = "insert into EmailStatus (user_id, status_code, status, time_sent) values ".
      "($user_id, ".$request->getResponseCode().", '".
      mysql_real_escape_string($request->getResponseStatus(), $users_con)."', UTC_TIMESTAMP())";
    //echo "$sql<BR>\n";
    $result = mysql_query($sql, $users_con);
    if (!$result) {
      die("Invalid query: $sql" . mysql_error());
    } 
}



?>
