<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require("db.php");
require("db_user.php");
require("get_deal.php");
require("array_constants.php");
require("helpers.php");

class MetricsTracker {
    public $token;
    public $host = 'http://api.mixpanel.com/';
    public function __construct($token_string) {
        $this->token = $token_string;
    }
    function track($event, $properties=array()) {
        $params = array(
            'event' => $event,
            'properties' => $properties
            );

        if (!isset($params['properties']['token'])){
            $params['properties']['token'] = $this->token;
        }
        $url = $this->host . 'track/?data=' . base64_encode(json_encode($params));
        //you still need to run as a background process
        exec("curl '" . $url . "' >/dev/null 2>&1 &"); 
    }
}

$metrics = new MetricsTracker("b9512487a96af16ee91d836f4ad9ea22");


$user_id = mysql_real_escape_string($_GET["user_id"]);
$deal_id = mysql_real_escape_string($_GET["deal_id"]);
$token = mysql_real_escape_string($_GET["token"]);

$query = "SELECT * FROM Users WHERE user_id='$user_id'";

$result = mysql_query($query, $users_con);

$fake_click = 0;


if ($row = mysql_fetch_assoc($result)) {
	$user_created = $row["user_created"];
	$token_from_db = generate_user_token($user_id, $user_created);
	
	if ($token_from_db != $token) {
		$fake_click = 1;
	}
} else {
	$fake_click == 1;
}

if ($fake_click) {

	$metrics->track('Fake email click',
						array('mp_note'=> 'User: ' . $user_id,
							  'User'=> $user_id,
							  'ip'=>$_SERVER['REMOTE_ADDR'])
	);
	
	echo("Sorry, something went wrong. Try clicking the link from your email again.");

	exit();
}	


$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);
$cache_life = 86400;

$deal = getDealById($deal_id, $deals_con, $memcache, $cache_life);


if (isset($deal["Categories"][0]["category_id"])) {
	$deal_category = $deal["Categories"][$k]["category_id"];
} else {
	$deal_category = 0;
}

$metrics->track('Email click to external deal site',
					array('mp_note'=> 'User: ' . $user_id . ' company: ' . $deal["company_id"] . ' deal: ' . $deal["id"],
						  'User'=> $user_id,
						  'Company'=> $deal["company_id"],
					  	  'Deal ID'=> $deal["id"],
						  'ip'=>$_SERVER['REMOTE_ADDR'])
);

if (isset($deal["affiliate_url"]) && $deal["affiliate_url"] != "") {
	$deal_site_url = $deal["affiliate_url"];
} else {
	$deal_site_url = $deal["url"];
}

header( "Location: $deal_site_url" );


?>