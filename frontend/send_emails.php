<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

///// GLOBALS ///////////////////
$MIN_EMAIL_TIME = 1;
$MAX_DEALS_PER_ALERT = 3;
///////////////////////////////


$cache_life = 86400;
$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

require_once("deals_index_from_url_params.php");
require_once("db_user.php");
require_once("db.php");


// For each users who's subscribed, get all their email alerts.
// For each alert, find all the deals that match it and add
// them to the list of deals to email. Make sure not to add
// deals to the list if they've been sent to the user before.
$subscribed_users = getSubscribedUsers($users_con);

for ($i=0; $i < count($subscribed_users); $i++) {
  //echo $subscribed_users[$i]."<BR>\n";
  $alerts = getAlerts($subscribed_users[$i], $users_con);
  $seen_deals = getEmailedDeals($subscribed_users[$i], $users_con);

  $deals_to_email = array();
  $num_deals_sent_to_user = 0;
  foreach ($alerts as $alert_id => $alert_params) {
    //echo $alert_params."<BR>\n";

    $deals_index = deals_index_from_url_params($alert_params, $deals_con, $users_con, $memcache, $cache_life);
    $alert_deals = array();

    $added_count = 0;
    for ($k=0; $k < count($deals_index); $k++) {
      if ($added_count >= $GLOBALS['MAX_DEALS_PER_ALERT']) {
	break;
      }
      if (!isset($seen_deals[$deals_index[$k]])) {
	array_push($alert_deals, $deals_index[$k]);
	$added_count++;
	$seen_deals[$deals_index[$k]] = 1;
      }
    }
    if ($added_count > 0) {
      $deals_to_email[$alert_id] = $alert_deals;
    }

    $num_deals_sent_to_user += $added_count;
  }

  if (count($deals_to_email) > 0) {
    $html = buildHtml($deals_to_email, $deals_con, $memcache, $cache_life);
    insertSentDeals($subscribed_users[$i], $deals_to_email, $users_con);
    
    $email = getEmail($subscribed_users[$i], $users_con);

    $request = sendEmail($email, $html);
    insertStatus($subscribed_users[$i], $request, $users_con);
    updateLastEmailed($subscribed_users[$i], $users_con);


    if (isset($_GET["debug"])) {
      echo $email."<BR>\n";
      echo $html;
    }
    echo "[".$subscribed_users[$i]."]:[".$num_deals_sent_to_user."],";
  }
}




////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////// HELPER FUNCTIONS ///////////////////////////////////////////
function sendEmail($email, $html) {
  $request =
    new HttpRequest('https://api.mailgun.net/v2/dealupamail.com/messages',
                    HttpRequest::METH_POST);
  $auth = base64_encode('api:key-68imhgvpoa-6uw3cl8728kcs9brvlmr9');
  $request->setHeaders(array('Authorization' => 'Basic '.$auth));
  $request
    ->setPostFields(array('from' => 'Emily J. Smith <emily@dealupa.com>',
                          'to' => $email,
                          'subject' => 'Hello '.time(),
                          'html' => $html
                          ));
  $request->send();
  return $request;
}


function buildHtml($deals_to_email, $deals_con, $memcache, $cache_life) {
	//print_r($deals_to_email);
	//return;
	
	require("array_constants.php");
	
	$html = "<html><head><meta charset='utf-8' /></head><body style='font-family:sans-serif; font-size:13px'><img src='http://dealupa.com/email_images/email_logo.jpg'><br>\n";
	foreach ($deals_to_email as $alert_id => $deals_index) {
		$html = $html."<br><span style='font-size:23px; font-weight:bold'>Alert $alert_id</span><br><br>\n";
		
		
		for ($i=0; $i < count($deals_index); $i++) {
			$deal = getDealById($deals_index[$i], $deals_con, $memcache, $cache_life);
			//$html = $html."\tnbsp;nbsp;nbsp;<span>".$deal['title']."</span>\n";

			
			
			if (isset($deal["Images"])) {
				$image_url = $deal["Images"][0]["image_url"];
			} else {
				$image_url = "";
			}
			
			$yelp_rating = str_replace(".", "", $deal["yelp_rating"]);

			if (isset($deal["website"])) {
				$name = "<a style='color:#e87100; text-decoration:none' href='" . $deal['website'] . "' target='_blank'>" . $deal["name"] . "</a>";
			} else {
				$name = "<span style='color:#000'>" . $deal["name"] . "</span>";
			}			

			
	
			$show_map = 0;
						
			$seen_cities_str = "";
			if (isset($deal["Addresses"])) {
				$street = $deal["Addresses"][0]["street"];
				$city = $deal["Addresses"][0]["city"];
				$state = $deal["Addresses"][0]["state"];
				$latitude = $deal["Addresses"][0]["latitude"];
				$longitude = $deal["Addresses"][0]["longitude"];
			
				// An array of cities we've already seen for this deal
				$seen_cities = array();
				
				// Push the 0th address since we've seen it...
				array_push($seen_cities, $city);
				
				
				
				$map_url = "http://maps.googleapis.com/maps/api/staticmap?sensor=false&zoom=12&size=100x100";
				
				// Iterate through remaining addresses and add any city we haven't already seen
				for($k = 0; $k < count($deal["Addresses"]); $k++) {

					if (isset($deal["Addresses"][$k]["latitude"]) && isset($deal["Addresses"][$k]["longitude"])) {
						if ($show_map == 0) {
							$show_map = 1;
							$map_url .= "&center=" . $deal["Addresses"][$k]["latitude"] . "," . $deal["Addresses"][$k]["longitude"];
						}
						$map_url .= "&markers=color:red%7Clabel:%7C" . $deal["Addresses"][$k]["latitude"] . "," . $deal["Addresses"][$k]["longitude"];					
					}
				
				
				
					$current_city = $deal["Addresses"][$k]["city"];
					if (!in_array($current_city, $seen_cities)) {
						array_push($seen_cities, $current_city);
						$seen_cities_str .= $current_city . ", ";
					}
				}
				$seen_cities_str = rtrim($seen_cities_str, ", ");
				if ($seen_cities_str != "") {
					$seen_cities_str = "Also available in " . $seen_cities_str . "<br>";
				}
				
				$city = $city . ", ";
			} else {
				$street = "";
				$city = "";
				$state = "";
				$latitude = "";
				$longitude = "";
			}
			
				
			if (isset($deal["affiliate_url"]) && $deal["affiliate_url"] != "") {
				$deal_site_url = $deal["affiliate_url"];
			} else {
				$deal_site_url = $deal["url"];
			}
				
				
				
				
			
			
			
			
			
			
			$list_item = <<<HTML
			
<table border=0 width=650 cellpadding=2 cellspacing=0>
	<tr>
		<td colspan=2>
			<span style="font-size:19px;">
				{$deal["title"]}
			</span>
		</td>
		<td rowspan=4 valign="top">
			<img src="{$image_url}" width=170>
		</td>
	</tr>
	<tr>
		<td colspan=2 width=450>
			<span style="font-size:12px; color:#888888">
				{$deal["subtitle"]}
			</span>
		</td>
	</tr>
HTML;


			if ($yelp_rating != "") {
			
				$list_item .= <<<HTML
			

	<tr>
		<td valign="top" style="font-size:12px;" colspan=2>
			<a href="{$deal["yelp_url"]}" target="_blank"><img src="http://dealupa.com/images/yelp/yelp_{$yelp_rating}.png"><img src="http://dealupa.com/images/yelp.png"></a>- {$deal["yelp_review_count"]} reviews
		</td>
	</tr>
HTML;
			}
		
			
			
			$list_item .= <<<HTML

	<tr>
		<td colspan=2 valign="top">
			<table border=0 cellpadding=0 cellspacing=0>
				<tr>
HTML;
				
			if ($show_map) {
			
				$list_item .= <<<HTML

					<td>
						<img src="{$map_url}">
					</td>
					<td width=10></td>
HTML;
			}
					
			$list_item .= <<<HTML
					<td style="font-size:12px; color:#999999" valign="top">
						<b>{$name}</b>
						<br>
						{$street}
						<br>
						{$city} {$state}
						<br><br>
						<a href="{$deal_site_url}" style="background-color:#e87100; color:white; text-decoration:none; padding:8px; font-size:16px; font-weight:bold">Details at {$companies[$deal["company_id"]]}</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>			
			
			
<hr style="border-width:1px; border-color:#cccccc; border-style:none none dotted; margin: 20px 0px 15px; width:650px">			
			
			
			
HTML;
			
			$html .= $list_item;	
			
		}
		
		$html = $html."</div>\n";
	}

	$html = $html."</body></html>\n";

	return $html;
}

function getSubscribedUsers($users_con) {
  $subscribed_users = array();

  $sql = "select user_id from Users where subscribed=1 and email is not null and ".
    "(last_emailed is null or ".
    "TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), last_emailed)) > ".
    $GLOBALS['MIN_EMAIL_TIME'].")";

  //echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die('Invalid query: ' . mysql_error());
  }

  while ($row = @mysql_fetch_assoc($result)) {
    array_push($subscribed_users, $row['user_id']);
  }
  
  return $subscribed_users;
}


function getEmail($user_id, $users_con) {
  $sql = "select email from Users where user_id=$user_id";

  //echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die('Invalid query: ' . mysql_error());
  }
  
  if ($row = mysql_fetch_row($result)) {
    return $row[0];
  }
}

function getAlerts($user_id, $users_con) {
  $alerts = array();

  $sql = "select id, params from EmailAlerts where user_id=$user_id";

  //echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die('Invalid query: ' . mysql_error());
  }

  while ($row = @mysql_fetch_assoc($result)) {
    $alerts[$row['id']] = $row['params'];
  }
  
  return $alerts;
}

function getEmailedDeals($user_id, $users_con) {
  $emailed_deals = array();

  $sql = "select deal_id from EmailedDeals where user_id=$user_id";

  //echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die('Invalid query: ' . mysql_error());
  }

  while ($row = @mysql_fetch_assoc($result)) {
    $emailed_deals[$row['deal_id']] = 1;
  }
  
  return $emailed_deals;
}

function updateLastEmailed($user_id, $users_con) {
  $sql = "update Users set last_emailed=UTC_TIMESTAMP(), num_emails=(num_emails+1) where user_id=$user_id";
  //echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die('Invalid query: ' . mysql_error());
  } 
}

function insertSentDeals($user_id, $deals_to_email, $users_con) {
  foreach ($deals_to_email as $alert_id => $deals_index) {
    for ($i=0; $i < count($deals_index); $i++) {
      $sql = "insert into EmailedDeals (user_id, deal_id, time_sent) values ".
	"($user_id, ".$deals_index[$i].", UTC_TIMESTAMP())";
      //echo "$sql<BR>\n";
      $result = mysql_query($sql, $users_con);
      if (!$result) {
	die('Invalid query: ' . mysql_error());
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
      die('Invalid query: ' . mysql_error());
    } 
}

?>
