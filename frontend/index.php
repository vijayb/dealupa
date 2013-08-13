<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">

<head>









<script>
// indexOf for IE
Array.indexOf||(Array.prototype.indexOf=function(b){for(var a=0;a<this.length;a++)if(this[a]==b)return a;return-1});Array.lastIndexOf||(Array.prototype.lastIndexOf=function(b){for(var a=this.length;0<=a;a--)if(this[a]==b)return a;return-1});
</script>

<!--[if gte IE 9]>
<style type="text/css">
.gradient { filter: none;}
</style>
<![endif]-->









<?php

require("db.php");
require("db_user.php");
require_once("refresh_deals_indexes.php");
require_once("helpers.php");








// Check if there's a token/ID pair that would log the user in. There would only
// be a token/ID pair if there is an action parameter present.

if (isset($_GET["token"]) && isset($_GET["id"])) {

	if (check_valid_id_token_pair($_GET["id"], $_GET["token"])) {
	
		$d = time();		
		$query = "UPDATE Users SET session_id=$d WHERE user_id='" . $_GET["id"] . "'";

		$result = mysql_query($query, $users_con);
		if (!$result) die('Invalid query: ' . mysql_error());
		
		setcookie('session_cookie', $d, time() + 1000000000);
		setcookie('userid_cookie', $_GET["id"], time() + 1000000000);
		
	} else {
		setcookie('session_cookie', "", time() - 1000000000);
		setcookie('userid_cookie', "", time() - 1000000000);
		echo("Deleted session cookie!");
		exit();
	}

}





$ip = "";

// Because frontends are behind a load balancer, they don't get the IP of the
// client's request. Instead, that IP is embedded in the header in the
// X-Cluster-Client-Ip field, so we extract it from there.
foreach (getallheaders() as $name => $value) {
    if (strcmp($name, "X-Cluster-Client-Ip") == 0) {
         $ip = $value;
    }
}

// If extraction of IP doesn't work, just fall back to the REMOTE_ADDR field.
if ($ip == "") {
    $ip= $_SERVER['REMOTE_ADDR'];
}

echo "<!-- Client's IP address: $ip -->\n";

$lat = 34.1234;
$lng = -118.1234;



if (isset($_COOKIE['geo'])) {
	$geo_info = preg_split("/:/", $_COOKIE['geo']);
	if (count($geo_info) == 2) {
		$lat = $geo_info[0];
		$lng = $geo_info[1];
		
		echo "<!-- Got client's location from the 'geo' cookie. -->\n\n";
	}
} else {

	if (strpos($_SERVER["HTTP_USER_AGENT"], "Googlebot") === false) {
	
		if (strlen($ip) > 5) {
			$request = "http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress=$ip&pt_email=sanjay@gmail.com&pt_password=b4134789";
			$tags = get_meta_tags($request);
			if (preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/', $tags['latitude']) &&
				preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/', $tags['longitude'])) {
				$lat = $tags['latitude'];
				$lng = $tags['longitude'];
				setcookie('geo', $lat.":".$lng, (time()+(3600*24*30)));
				
				echo "<!-- Got client's location from GeoBytes because a 'geo' cookie could not be found. -->\n\n";
				echo "<!-- ..and according to GeoBytes, the edition ought to be " . $cities[calculate_city_edition_from_lat_lng($lat, $lng)] . " -->\n\n";
			}
		}
	
	} else {
		echo("<!-- Googlebot, so don't do the IP address to geo check -->\n");
	}
}











$params_arr = array();

if (count($_GET) > 0) {
	$params_arr = $_GET;
}
	
if (isset($params_arr["m"]) && isset($params_arr["i"]) && ((isset($params_arr["v"]) && $params_arr["v"] == "SINGLE-DEAL") || (!isset($params_arr["v"])))) {

	// If the above if statement is true, we are displaying a single deal, so
	// the meta tags have to show the specifics of the deal we are showing

	$page_type = "SINGLE-DEAL";
	
	$params_arr["v"] = "SINGLE-DEAL";

	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	$deal = getDealById($params_arr["m"], $deals_con, $memcache, $cache_life);
	
	if ($deal != false) {
		if (isset($deal["Images"])) {
			$image_url = "http://dealupa_images.s3.amazonaws.com/" . sha1($deal["Images"][0]["image_url"]);
		}

		$text = strip_tags($deal["text"]);
		
		$meta = <<<HTML
			<meta property="og:title" content="{$deal["title"]}" />
			<meta property="og:image" content="{$image_url}" />
			<meta property="og:description" content="{$text}" />
			<title>Dealupa: {$deal["title"]}</title>
HTML;

		echo($meta);
	} else {
		header("HTTP/1.0 410 Gone");
		$_SERVER["QUERY_STRING"] = "";
		$params_arr["v"] = "LIST";
	}
	
} else {

	$meta = <<<HTML
		<meta property="og:title" content="Dealupa: The best daily deals in one place" />
		<meta property="og:image" content="http://dealupa.com/images/d.png" />
		<title>Dealupa: The best daily deals in one place!</title>
HTML;

	echo($meta . "\n\n");

}


?>


<meta charset="utf-8" />
<link rel="icon" type="image/png" href="/images/favicon.png"/>
<link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico"/>





<link href='http://fonts.googleapis.com/css?family=Lato:300,400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="/map160version.css" />
<link rel="stylesheet" href="/lib/jquery-ui-1.8.16.custom.css">












<!----------- GOOGLE ANALYTICS BEGIN ----------->

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-25795044-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<!------------ GOOGLE ANALYTICS END ------------>






















































<script type="text/javascript">

<?php

$categoryInformation = getCategoryInformation();

$all_zeros_string = "";
$all_ones_string = "";

// Add 1 for the "Uncategorized" category which is not included in the DB
for ($i = 0; $i < count($categoryInformation) + 1; $i++) {
	$all_zeros_string .= "0,";
	$all_ones_string .= "1,";
}

$all_zeros_string = rtrim($all_zeros_string, ",");
$all_ones_string = rtrim($all_ones_string, ",");

$single_category_str = $all_zeros_string;
$single_category_str[0] = 1;

echo("var categories = [];\n");
echo("categories[0] = 'Uncategorized';\n");

$v = 1;

for ($j = 0; $j < count($categoryInformation); $j++) {

	$row = $categoryInformation[$j];
	
	echo("categories[" . $v . "] = '" . $row["name"] . "';\n");

	$single_category_str = $all_zeros_string;
	$single_category_str[$v * 2] = "1";

	$v++;
}

$cat_string = "";

for ($j = 0; $j < count($categoryInformation); $j++) {
	$cat_string .= ($j + 1) . ",";
}

$cat_string = rtrim($cat_string, ",");
echo("var categories_ALL = [" . $cat_string . "];\n");


echo("var gbLat = " . $lat . ";\n");
echo("var gbLng = " . $lng . ";\n");
echo("var ipAddress = '" . $ip . "';\n");

echo("var urlParams = \"?" . $_SERVER["QUERY_STRING"]) . "\";\n";

if (isset($_GET["token"])) {
	echo("var token = '" . $_GET["token"] . "';\n");
} else {
	echo("var token = '';\n");
}

if (isset($_GET["referrer"])) {
	echo("var referrer = '" . $_GET["referrer"] . "';\n");
} else {
	echo("var referrer = '';\n");
}

// Set the welcome screen randomly
$welcome_screens = array("A1");
$random_number = (time() * rand());
$random_screen = $welcome_screens[$random_number % count($welcome_screens)];

if (isset($params_arr["w"])) {
	$w = $params_arr["w"];
} else {
	// $w = $random_screen;
	$w = "A1";
}

echo("var welcomeScreen = '" . $w . "';\n");



?>
	
</script>





























































































<!----------- MIXPANEL BEGIN ----------->

<script type="text/javascript">
var mpq=[];

// Certain IPs (our development IPs) will not record to the Dealupa project on Mixpanel
ipToIgnore = ["98.248.33.17", "67.161.121.46", "166.250.46.110", "174.61.228.99"];
if (ipToIgnore.indexOf(ipAddress) > -1) {
	// mobdealio.com
	mpq.push(["init","f033ea4322d882a4432b0caafdf2f4a9"]);
	mpq.push(['set_config', {'test': true}]);
	mpq.push(['set_config', {'debug': true}]);
} else {
	// dealupa.com
	mpq.push(["init","b9512487a96af16ee91d836f4ad9ea22"]);
}
	
	
(function(){
var b,a,e,d,c;
b=document.createElement("script");
b.type="text/javascript";
b.async=true;
b.src=(document.location.protocol==="https:"?"https:":"http:")+"//api.mixpanel.com/site_media/js/api/mixpanel.js";
a=document.getElementsByTagName("script")[0];
a.parentNode.insertBefore(b,a);
e=function(f){return function(){mpq.push([f].concat(Array.prototype.slice.call(arguments,0)))}};
d=["init","track","track_links","track_forms","register","register_once","identify","name_tag","set_config"];
for(c=0;c<d.length;c++){mpq[d[c]]=e(d[c])}})();
</script>

<!----------- MIXPANEL END------------->














































</head>
<?php flush(); ?>
<body>









	
	
	
	
	<div id="list-view-area">
		<div id="list-view-title" style="overflow:hidden; width:100%">
			<div style="float:left">
				<div style="margin-bottom:5px">
					<a href="javascript:void(0);" onclick='
						changeView("LIST");
						loadAndDisplay(0);
						pushState();'					
					id="single-category-back" style="font-size:18px; font-weight:700"></a>
				</div>
				<h1>
					<span id="list-view-num-deals">Loading...</span>
					<span id="list-view-sorted-by"></span>
				</h1>
			</div>
			<div id="sort-options">
				Sort by
				&nbsp;&nbsp;<a href='javascript:void(0);' onclick='resortListView("SOLD")'  id="sortSOLD" clickcode="105065">Deal Quality</a>
				&nbsp;&nbsp;<a href='javascript:void(0);' onclick='resortListView("NEW")' id="sortNEW" clickcode="105064">New</a>
				&nbsp;&nbsp;<a href='javascript:void(0);' onclick='resortListView("PRICE")'  id="sortPRICE" clickcode="105066">Price</a>
				<!-- &nbsp;&nbsp;<a href='javascript:void(0);' onclick='resortListView("DEADLINE")'  id="sortDEADLINE" clickcode="105067">Time Left</a> -->
				&nbsp;&nbsp;<span id="sortDISTANCE">Distance to</span> <input type="text" style="width:60px;" id="sort-zip" maxlength=5 placeholder="Zip code">
				<a href="javascript:void(0)" class="small-button brown-gradient" onclick='resortListView("DISTANCE");' clickcode="105068">Go</a>
			</div>
		</div>
		<div id="container"></div>
	</div>
	

	
	
	

	
	<div id="top-bar-welcome">
		<div style="position:relative; top:6px; margin:0 auto; width:340px; text-align:center">
			<img src="/images/logo.png">
			<span class="beige" style="font-weight:300; font-size:31px; position:relative; top:-7px; text-shadow:0px -1px 1px rgba(0, 0, 0, .4);">daily deals</span>
		</div>
	</div>	
	
	
	
	
	
	
	
	



	<div id="top-bar">
		<div id="top-bar-content" style="height:39px; width:958px; margin:3px auto; position:relative">
			<table id="top-bar-content-table" width=100% border=0 style="position:relative; top:9px; display:none;">
				<tr>
				<td>
					<span id="map-list-toggle">
						<a id="map-view-toggle" href='javascript:void(0);' class="small-button brown-gradient" onclick='
						

mpq.track("Toggled view", {
	"mp_note": "Changed to map view",
	"View": "LIST TO MAP",
	"UserID": userID
});	


changeView("MAP");
loadAndDisplay();
pushState();
							
						'><span>Map</span></a>
						<a id="list-view-toggle" href='javascript:void(0);' class="small-button brown-gradient" onclick='

mpq.track("Toggled view", {
	"mp_note": "Changed to list view",
	"View": "MAP TO LIST",
	"UserID": userID
});							

changeView("LIST");
loadAndDisplay();
pushState();
						
						
						'><span>Gallery</span></a>
					</span>
					&#160;&#160;
					<span id="refer-a-friend-area">
						<!--<a href="javascript:void(0);" style="color:#ffffff" onclick="changeView('REFER');"><b>Refer a friend to Dealupa</b></a>-->
					</span>
				</td>
				<td style="text-align:right">
					<span id="top-bar-links"></span>
					&#160;&#160;
					<span id="top-bar-user-id" class="beige"></span>
					&#160;&#160;
					<a id="login-button" href='javascript:void(0);' onclick="showLogin(1);" class="small-button brown-gradient"><span>Login</span></a>
					<a id="logout-button" href='/' onclick='logout();' class="small-button brown-gradient" style="display:none;"><span>Logout</span></a>
					<a id="signup-button" href='javascript:void(0)' onclick='showWelcome(1);' class="small-button orange-gradient"><span>Sign up</span></a>
				</td>
			</table>
		</div>

		<div style="position:relative; top:-41px; margin:0 auto; width:340px; text-align:center">
			<a id="dealupa-title" href="javascript:void(0);">
				<img src="/images/logo.png">
				<span class="beige" style="font-weight:300; font-size:31px; position:relative; top:-7px; text-shadow:0px -1px 1px rgba(0, 0, 0, .4);" id="city-name">daily deals</span>
				<img style="position:relative; top:-11px; left:1px" src="/images/city_selector_arrow.png">
			</a>
		</div>

		<div style="position:relative; top:-48px; margin:0 auto; width:290px; text-align:center;">
			<input type="text" class="top-bar-search-box" id="top-bar-search-box" placeholder="Search deals" style="width:195px;">
			<a href='javascript:void(0);' class="small-button brown-gradient" onclick='executeSearch();'>Go</a>
			<a id="clear-search-button" href='javascript:void(0);' class="small-button orange-gradient" onclick='clearSearch(); loadAndDisplay(); pushState();' style="display:none">Clear</a>
		</div>
	</div>	





















	
	
	
	
	<div id="notification-bar">
		<div id="nb-close" style="position:absolute; top:12px; right:30px"><a href="javascript:void(0);" onclick='$("#notification-bar").fadeOut()'><img src="/images/nb_x"></a></div>
		<div id="nb-title" style="font-size:22px; font-weight:700">This is a test message!</div>
		<div id="nb-subtitle" style="font-size:18px; font-weight:300"></div>
		<!--<span id="nb-close"><a href='javascript:void(0);' class="small-button orange-gradient" onclick='$("#notification-bar").slideUp();'>Close</a></span>-->
	</div>





















	<div id="bottom-bar">

		<div id="gallery-map-panel" style="position:relative; top:-65px; margin:0 auto; width:404px; text-align:center">
			<img src="/images/bottom_wood_map_panel.png">
			<!--<div style="background-color:red; height:20px; width:100px; position:absolute; top:0px; left:0px;">asdf</div>-->
			<div id="gallery-map-container" style="display:none">
				<div id="gallery-map"></div>
				<div style="background-image:url(/images/map_cover_left.png); position:absolute; top:0px; left:0px; height:32px; width:41px;"></div>
				<div style="background-image:url(/images/map_cover_right.png); position:absolute; top:0px; right:0px; height:32px; width:41px;"></div>
			</div>

			
		</div>


		<div id="bottom-bar-content" style="height:39px; width:958px; margin:13px auto; position:absolute; left:50%; top:0px; display:none">
			<div style="float:left" id="hidden-categories">
				<span id="num-hidden-categories" class="beige" style="font-size:14px; text-shadow:0px -1px 1px rgba(0, 0, 0, .4);"></span>				
				<a id="unhide-categories" href='javascript:void(0);' class="small-button orange-gradient" style="display:none"><span>Unhide categories</span></a>
			</div>

			<div style="float:right">
				<span class="beige" style="font-size:14px; text-shadow:0px -1px 1px rgba(0, 0, 0, .4);">Filter by Yelp rating</span>
				&#160;&#160;
				<a id="filter-yelp-0" href='javascript:void(0);' class="small-button brown-gradient" onclick='setYelp(0); loadAndDisplay();'><span>Show all</span></a>
				<a id="filter-yelp-3" href='javascript:void(0);' class="small-button brown-gradient" onclick='setYelp(3); loadAndDisplay();'><span>3+</span></a>
				<a id="filter-yelp-4" href='javascript:void(0);' class="small-button brown-gradient" onclick='setYelp(4); loadAndDisplay();'><span>4+</span></a>
				<a id="filter-yelp-5" href='javascript:void(0);' class="small-button brown-gradient" onclick='setYelp(5); loadAndDisplay();'><span>5</span></a>
			</div>
		</div>










		</div>	


	
	
	<div id="footer-bar">
		<div style="position:relative; top:4px;">
			<a href="/about">About</a>
			&#160;&#160;&#160;&#160;&#160;&#160;
			<a href="/privacy">Privacy Policy</a>
			&#160;&#160;&#160;&#160;&#160;&#160;
			<a href="/terms">Terms of Service</a>
			&#160;&#160;&#160;&#160;&#160;&#160;
			<a href="javascript:void(0);" style="color:#3A1500" onclick='load(); removeWelcome();'>.</a>
		</div>
	</div>


























	


























	<div id="map"></div>



	
	
	
	
	
	
	
	
	
	
	
	
	
	
	<div id="refer-a-friend" style="display:none">
		
		<table style="width:100%">
			<tr>
				<td>
					<h1>Refer A Friend</h1>
				</td>
				<td style="text-align:right">
					<!--
					<a href="javascript:void(0)" class="brown-gradient small-button" onclick=""><span>Cancel</span></a>
					<a href="javascript:void(0)" class="orange-gradient small-button" onclick="referAFriend();"><span>Refer these friends</span></a>
					-->
				</td>
			</tr>
		</table>
		
		
		<div style="margin-top:40px; clear:both; overflow:hidden;">
		
			<span class="settings-subtitle">
				<b>Invite your friends to Dealupa!</b>
				<br><br>
				Enter your friends' email addresses below.
			</span>

			<br>
			
			<span id="refer-inputs">
				<input type="text" style="width:150px; margin:4px 4px 4px 0px;"></input>
				<input type="text" style="width:150px; margin:4px 4px 4px 0px;"></input>
				<input type="text" style="width:150px; margin:4px 4px 4px 0px;"></input>
				<br>
				<input type="text" style="width:150px; margin:4px 4px 4px 0px;"></input>
				<input type="text" style="width:150px; margin:4px 4px 4px 0px;"></input>
				<input type="text" style="width:150px; margin:4px 4px 4px 0px;"></input>
			</span>
			
			<br><br>

			<span class="settings-subtitle">
				And a brief message.
			</span>
			<br>
			<textarea id="email-text" style="width:475px; height:75px; line-height:1.5;">I've found a site I think you'll like. It's called Dealupa. You tell it what you're into and Dealupa brings you the best daily deals from around the web that you're bound to like.</textarea>
			
			<br><br>
			
			<a href="javascript:void(0)" class="brown-gradient small-button" onclick="hideMessage(); changeView('LIST'); loadAndDisplay();"><span>Cancel</span></a>
			<a href="javascript:void(0)" class="orange-gradient small-button" onclick="referAFriend();"><span>Refer these friends</span></a>
			
			<br><br>
			
			
		</div>

		
	</div>
	















	<div id="email" style="display:none">
		
		<table style="width:100%">
			<tr>
				<td>
					<h1>Settings</h1>
				</td>
				<td style="text-align:right">
					<a href="javascript:void(0)" class="brown-gradient small-button" onclick="hideMessage(); changeView('LIST'); loadAndDisplay();"><span>Cancel</span></a>
					<a href="javascript:void(0)" class="orange-gradient small-button" onclick="saveEmailSettings(); pushState();"><span>Save</span></a>
				</td>
			</tr>
		</table>
		
		
		<div style="margin-top:40px; clear:both; overflow:hidden;">
			<div style="float:left; width:350px;">
				<span class="settings-title">Email frequency</span>
				<br>
				<span class="settings-subtitle">How often do you want a deals email?</span>
			</div>
			<div style="float:left; margin-top:6px;" class="settings-subtitle">
				<input type="radio" name="account-frequency" value="86400" checked> Daily
				&#160;&#160;
				<input type="radio" name="account-frequency" value="259200"> Every 3 days
				&#160;&#160;
				<input type="radio" name="account-frequency" value="604800"> Once a week

				<!--
				<a id="email-frequency-86400" href='javascript:void(0);' onclick='selectFrequencyButton(this);' class="small-button brown-gradient frequency-button"><span>Daily</span></a>
				&#160;&#160;
				<a id="email-frequency-259200" href='javascript:void(0);' onclick='selectFrequencyButton(this);' class="small-button brown-gradient frequency-button"><span>Every 3 days</span></a>
				&#160;&#160;
				<a id="email-frequency-604800" href='javascript:void(0);' onclick='selectFrequencyButton(this);' class="small-button brown-gradient frequency-button"><span>Once a week</span></a>
				-->
			</div>
		</div>
			
		<div style="margin-top:30px; clear:both; overflow:hidden;">
			<div style="float:left; width:350px;">
				<span class="settings-title">Your zip code</span>
				<br>
				<span class="settings-subtitle">We'll send you deals in your area</span>
			</div>
			<div style="float:left;">
				<input type="text" id="account-zipcode" style="width:85px;" maxlength=5>
				<br>
				<span class="settings-subtitle">Send me deals that are</span>
					<select id="account-deal-distance">
						<option value="0">anywhere in my greater metro area</option>
						<option value="1">within 1 mile of my zipcode</option>
						<option value="3">within 3 miles of my zipcode</option>
						<option value="10">within 10 miles of my zipcode</option>
						<option value="25">within 25 miles of my zipcode</option>
						<option value="50">within 50 miles of my zipcode</option>
					</select>
			</div>
		</div>
				
		
		<div style="margin-top:30px;">
			<span class="settings-title">Your category preferences</span>
			<br>
			<span class="settings-subtitle">
				Tell us what kind of things you like and don't like so we can show you the deals you care about.
				Your selections here affect <b>both</b> what you see on the Dealupa website and what you see in your email.
				<br>
				<img style="position:relative; top:6px" src="http://mobdealio.com/images/cat_heart_on.png"> = <span style="font-weight:700">I love this stuff!</span> Make sure to send me deals from this category.
				<br>
				<img style="position:relative; top:6px" src="http://mobdealio.com/images/cat_check_on.png"> = <span style="font-weight:700">Depends.</span> I wouldn't mind seeing these deals ocassionally, if they're particularly good.
				<br>
				<img style="position:relative; top:6px" src="http://mobdealio.com/images/cat_x_on.png">  = <span style="font-weight:700">No way!</span> Don't send me any deals from this category...ever.
			</span>
				
		</div>
		
		
		
		<div style="margin-top:30px;">
		
		<?php
		
		$html = "";
		$counter = 0;
		
		for ($k = 0; $k < (ceil(count($categoryInformation) / 6)); $k++) {
		
			$start = $k * 6;
			$html .= <<<HTML

			<div id="settings-categories" style="margin-bottom:25px; overflow:hidden; clear:both;">
HTML;

			for ($j = $start; $j < $start + 6; $j++) {
			
				if ($counter == count($categoryInformation)) {
					break;
				}
			
				$row = $categoryInformation[$counter];		
				$name = $row["name"];
				$id = $row["id"];
				$description = $row["description"];
			
			
				$counter_plus_one = $counter + 1;
			
				$html .= <<<HTML
					
					<div class="category-preference" cat-id="{$id}" description="{$description}" style="width:159px; height:180px; text-align:center; float:left; position:relative; color:#3A1500; font-weight:300">
						<span style="font-size:17px; font-weight:700">{$name}</span>
						<br>
						<a href="javascript:void(0);" onclick="setTo2InCatPrefUI(this.parentNode);" class="heart-button">
							<img class="heart" src="/images/cat_heart_off.png">
						</a>
						&#160;&#160;
						<a href="javascript:void(0);" onclick="setTo1InCatPrefUI(this.parentNode);" class="check-button">
							<img class="check" src="/images/cat_check_on.png">
						</a>
						&#160;&#160;
						<a href="javascript:void(0);" onclick="setTo0InCatPrefUI(this.parentNode);" class="x-button">
							<img class="x" src="/images/cat_x_off.png">
						</a>
						<a href="javascript:void(0);" onclick="toggleInCatPrefUI(this.parentNode);">
							<img src="/images/category_images/category_{$counter_plus_one}.jpg" style="position:absolute; top:50px; left:20px">
							<img src="/images/category_x_off.png" style="position:absolute; top:50px; left:20px; display:none" class="x-image">
							<img src="/images/category_heart.png" style="position:absolute; top:50px; left:20px; display:none" class="heart-image">
						</a>
					</div>
			
HTML;
				$counter++;

			}
			
			$html .= <<<HTML

			</div>
HTML;
			
		}
		
		echo($html);
		
		?>
		
		</div>		
		

		<div style="text-align:center; margin-top:50px;">
			<a href="javascript:void(0)" class="brown-gradient big-button" onclick="saveEmailSettings();" style="padding:8px 20px;"><span>Cancel</span></a>
			&#160;&#160;
			<a href="javascript:void(0)" class="orange-gradient big-button" onclick="saveEmailSettings();" style="padding:8px 20px;"><span>Save</span></a>
		</div>


		<div id="settings-unsubscribe" style="text-align:center; margin-top:150px;">
			<a href="javascript:void(0)" class="brown-gradient small-button" onclick="unsubscribe();"><span>Unsubscribe</span></a>
		</div>

		
	</div>
	
	
	
	









	<!--
	<div id="email" style="display:none">
		<div id="email-top-panel" style="position:fixed; top:40px; left:0px; z-index:10; background-color:white; padding:15px; width:100%; border-bottom:1px solid #dddddd;">
			<div>
				<div>
					<span class="page-title">My Email Alerts</span>
					<br><br>
					Email alerts let you get notified when <b>exactly</b> the deal you want becomes available.
					<br><br>
				</div>
				<div id="email-alerts">
				</div>
			</div>
		</div>
		<div id="test-alerts" style="position:relative; left:15px;">
			<div class="page-title" id="test-alerts-title" style="padding-top:10px"></div>
			<div id="test-alerts-list"></div>
		</div>
	</div>
	-->











































	<div id="single-deal-view"> <!-- opening tag in index.php -->

	<?php
	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////
	
	echo("<!-- \$_SERVER['QUERY_STRING']: " . $_SERVER["QUERY_STRING"] . "-->");
	
	if (isset($params_arr["m"]) && isset($params_arr["i"]) && $page_type == "SINGLE-DEAL") {

		$single_deal_to_display = $params_arr["m"];
		
		$deal = getDealById($single_deal_to_display, $deals_con, $memcache, $cache_life);

		if ($deal["company_id"] == 2 || $deal["company_id"] == 12) {
			
			require("single_deal_html_portrait.php");
		
		} else {
		
			require("single_deal_html_landscape.php");
		
		}
		
	}
	
	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////
	?>
	</div> <!-- closing tag in index.php -->



	
	
	
	
	<div id="single-category-view"></div>


















	
	<div id="list-yelp-reviews"></div>



	
	
	
	
	
	
	
	
	
	
	
	
	
	<div id="right-bar"></div>
	
	

	<div id="loading-div"><img src="/images/loadinfo.gif"></div>
	





	<div id="photo-background">
		<img class="photo-background" src="/images/backgrounds/background_4.jpg">
	</div>
	
	






















	<!-- POSHY TIP DIVS -->

	<div style="display:none">
	

				
	
			
		
		<div id="city-selector">
			<table cellpadding=0 cellspacing=0>
			<tr><td colspan=5><a href="javascript:void(0);" onclick='changeEdition(2);pushState();return false;'><b>National Deals</b></a><br></td></tr>
			<tr>
			<td width=130>
			
<?php

$cities_sorted = $citiesReverse;
ksort($cities_sorted);

$i = 0;
foreach ($cities_sorted as $key => $value) {
	if ($key == "nation" || $key == "other") continue;

	if ($i >= 0 && $i <= 15) {
		echo("<a href='http://dealupa.com/" . $key . "/daily-deals#!' onclick='changeEdition(" . $value . ");pushState();return false;'>" . $cities[$value] . "</a><br>");	
	}
	$i++;
}

?>
			</td>                                            
			<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td width=130>                                             


<?php

$i = 0;
foreach ($cities_sorted as $key => $value) {
	if ($key == "nation" || $key == "other") continue;

	if ($i >= 16 && $i <= 31) {
		echo("<a href='http://dealupa.com/" . $key . "/daily-deals#!' onclick='changeEdition(" . $value . ");pushState();return false;'>" . $cities[$value] . "</a><br>");	
	}
	$i++;
}

?>

			</td>
			<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td width=130>                                             

			
<?php

$i = 0;
foreach ($cities_sorted as $key => $value) {
	if ($key == "nation" || $key == "other") continue;

	if ($i >= 32 && $i <= 47) {
		echo("<a href='http://dealupa.com/" . $key . "/daily-deals#!' onclick='changeEdition(" . $value . ");pushState();return false;'>" . $cities[$value] . "</a><br>");	
	}
	$i++;
}

?>			


			</td>
			
			</tr>
			</table>
		</div>

	</div>
	
	
	
	
	<!-- END POSHY TIP DIVS -->



	
	
	
	
	
	
	
	
	
	
	
	
	
	
	


	

	
	<!-- SIGNUP FLOW DIVS -->
	


	<div id="welcome-A1" class="welcome signup-flow wooden">

		<!-- This X is shown via a parameter in showWelcome(showX) -->
		<span class="x">
			<a href="javascript:void(0);" onclick="if (!initialLoadCompleted) load(); removeWelcome();">
				<div style="position:absolute; top:20px; right:20px;"><img src="/images/welcome_x.png"></div>
			</a>
		</span>
		
		<div class="title">A fresh, new way to<br>shop daily deals.</div>
		<div class="subtitle">Enter your email address to begin.</div>

		<table style="margin:0 auto;">
			<tr>
				<td>
					<input type="text" id="signup-email" name="signup-email" class="big-input" placeholder="Enter your email">&#160;&#160;
				</td>
				<td>
					<a href="javascript:void(0)" class="brown-gradient big-button" onclick="signupUser();" style="width:80px;"><span>Sign up</span></a>	
				</td>
			</tr>
			<tr>
				<td style="text-align:left;" class="gray" colspan=2>
					Already signed up? <a href="javascript:void(0);" onclick="showLogin();">Login</a> or <a href="javascript:void(0);" onclick="showForgotPassword();">reset your password</a>.
				</td>
			</tr>
		</table>
		
		<div style="margin-top:25px; color:#7F543E;">
			<a href="javascript:void(0);" onclick="fbLogin();return false;" class="small-button blue-gradient"><span>Sign up with Facebook</span></a>
		</div>
		
	</div>




	<div id="login-A1" class="login signup-flow wooden">

		<span class="x">
			<a href="javascript:void(0);" onclick="if (!initialLoadCompleted) load(); removeWelcome();">
				<div style="position:absolute; top:20px; right:20px;"><img src="/images/welcome_x.png"></div>
			</a>
		</span>
	
		<div class="title" style="margin-top:30px;">Welcome back.</div>
		<div class="subtitle">Log in to your Dealupa account.</div>
		
		<table style="margin:0 auto;">
			<tr>
				<td>
					<input type="text" id="login-email" class="big-input" placeholder="Email address" style="width:220px;">&#160;&#160;
				</td>
				<td>
					<input type="password" id="login-password" class="big-input" placeholder="Password" style="width:220px;">&#160;&#160;
				</td>
				<td>
					<a href="javascript:void(0)" class="brown-gradient big-button" onclick="emailLogin();"  style="width:50px;"><span>Login</span></a>	
				</td>
			</tr>
			<tr>
				<td>	
				</td>
				<td style="text-align:left;">
					<a href="javascript:void(0);" onclick="showForgotPassword();">Don't know your password?</a>
				</td>
				<td>
				</td>
			</tr>
		</table>
		
		<div style="margin-top:40px;" class="gray">
			New to Dealupa? <a href="javascript:void(0);" onclick="showWelcome();">Sign up</a> or <a href="javascript:void(0);" onclick="fbLogin();return false;" class="small-button blue-gradient"><span>Login with Facebook</span></a>
		</div>		
	</div>

	
	
	
	<div id="forgot-password-A1" class="forgot-password signup-flow wooden">
		
		<div class="title" style="margin-top:30px;">Enter your email</div>
		<div class="subtitle">We'll send you a link to reset your password.</div>
		
		<input type="text" id="forgot-email" class="big-input" placeholder="Enter your email">
		&#160;
		<a href="javascript:void(0)" class="brown-gradient big-button" onclick="sendNewPassword();"  style="width:50px;"><span>Go</span></a>
		
		<div style="margin-top:40px;" class="gray">
			New to Dealupa? <a href="javascript:void(0);" onclick="showWelcome();">Sign up</a>
		</div>
		
	</div>



	<div id="reset-password-A1" class="reset-password signup-flow wooden">

		<div class="title" style="margin-top:30px;">Type in a new password.</div>
		<div class="subtitle">.</div>
	
		<input type="password" id="reset-password" class="big-input" placeholder="Create a new password">
		&#160;
		<a href="javascript:void(0)" class="brown-gradient big-button" onclick="changePassword();"  style="width:50px;"><span>Go</span></a>	

		<div style="margin-top:40px;" class="gray">
			Already have an account? <a href="javascript:void(0);" onclick="showLogin();">Login</a> or <a href="javascript:void(0);" onclick="fbLogin();return false;" class="small-button blue-gradient"><span>Login with Facebook</span></a>
		</div>

		
	</div>

	
	


	<div id="zipcode-A1" class="zip-code signup-flow wooden">
	
		<span id="x">
			<a href="javascript:void(0);" onclick="
					
					if (!initialLoadCompleted) {
						load();
					}
					removeWelcome();
					
					mpq.track('User dismissed zipcode screen', {
						'mp_note': 'User dismissed zipcode screen with userID ' + userID,
						'UTM': utm,
						'UserID': userID
					});

					">
				<div style="position:absolute; top:20px; right:20px;"><img src="/images/welcome_x.png"></div>
			</a>
		</span>
		
		<div class="title" style="margin-top:30px;">What's your zip code?</div>
		<div class="subtitle">So we can show you hand-picked deals in your area.</div>
		
		<input type="text" id="signup-zipcode" name="signup-zipcode" class="big-input" placeholder="Enter your zip code">
		&#160;
		<a href="javascript:void(0)" class="brown-gradient big-button" onclick="recordZipcode();"  style="width:50px;"><span>Go</span></a>	
	</div>



	<div id="category-preferences-A1" class="signup-flow full-page"  style="text-align:center;">
		<div class="category-preferences-title" style="margin-top:15px;">What kind of deals are you into?</div>
		<div class="category-preferences-subtitle">
			<img style="position:relative; top:6px" src="http://mobdealio.com/images/cat_heart_on.png"> = <span style="font-weight:700">I love this stuff!</span> Make sure I see deals from this category!
			<br>
			<img style="position:relative; top:6px" src="http://mobdealio.com/images/cat_check_on.png"> = <span style="font-weight:700">It depends.</span> Show me just the <i>really</i>&#160;&#160;good deals from this category.
			<br>
			<img style="position:relative; top:6px" src="http://mobdealio.com/images/cat_x_on.png">  = <span style="font-weight:700">No way!</span> Don't send me any deals from this category.
		</div>

		<a class="orange-gradient big-button" href="javascript:void(0);" onclick="recordCategoryPreferences();" style="padding:8px 30px;"><span>Done</span></a>

		<br><br><br>
		
		<?php
		
		$html = "";
		$counter = 0;
		
		for ($k = 0; $k < (ceil(count($categoryInformation) / 6)); $k++) {
		
			$start = $k * 6;
			$html .= <<<HTML

			<div id="welcome-categories" style="margin-bottom:25px; overflow:hidden; clear:both;">
HTML;

			for ($j = $start; $j < $start + 6; $j++) {
			
				if ($counter == count($categoryInformation)) {
					break;
				}
			
				$row = $categoryInformation[$counter];		
				$name = $row["name"];
				$id = $row["id"];
				$description = $row["description"];
			
			
				$counter_plus_one = $counter + 1;
			
				$html .= <<<HTML
					
					<div class="category-preference" cat-id="{$id}" description="{$description}" style="width:159px; height:180px; text-align:center; float:left; position:relative; color:#3A1500; font-weight:300">
						<span style="font-size:17px; font-weight:700">{$name}</span>
						<br>
						<a href="javascript:void(0);" onclick="setTo2InCatPrefUI(this.parentNode);">
							<img class="heart" src="/images/cat_heart_off.png">
						</a>
						&#160;&#160;
						<a href="javascript:void(0);" onclick="setTo1InCatPrefUI(this.parentNode);">
							<img class="check" src="/images/cat_check_on.png">
						</a>
						&#160;&#160;
						<a href="javascript:void(0);" onclick="setTo0InCatPrefUI(this.parentNode);">
							<img class="x" src="/images/cat_x_off.png">
						</a>
						<a href="javascript:void(0);" onclick="toggleInCatPrefUI(this.parentNode);">
							<img src="/images/category_images/category_{$counter_plus_one}.jpg" style="position:absolute; top:50px; left:20px">
							<img src="/images/category_x_off.png" style="position:absolute; top:50px; left:20px; display:none" class="x-image">
							<img src="/images/category_heart.png" style="position:absolute; top:50px; left:20px; display:none" class="heart-image">
						</a>
					</div>
			
HTML;
				$counter++;

			}
			
			$html .= <<<HTML

			</div>
HTML;
			
		}
		
		echo($html);
		
		?>		
		
		<a class="orange-gradient big-button" href="javascript:void(0);" onclick="recordCategoryPreferences();" style="padding:8px 30px;"><span>Done</span></a>
		
	</div>
	

	<!-- END FLOW DIVS -->
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	<div id="hover-info" style="position:relative; display:none; min-width:250px;">
		<div style="max-width:225px; margin-right:5px; float:left">
		<div style="color:#666666; font-weight:700; margin-bottom:2px;" id="hover-info-company">Company name</div>
		<div id="hover-info-maintitle"></div>
		<div style="font-size:12px; color:#666666; margin-bottom:4px;" id="hover-info-subtitle"></div>
		<div style="font-size:12px; color:#999999" id="hover-info-yelp"><img id="hover-info-yelp-stars"  alt="arrow"><span style="color:#666666" id="hover-info-yelp-count">Yelp</span></div>
		</div>
		<div style=" width:113px; height:85px; overflow:hidden; float:right;">
			<img id="hover-info-image" style="float:left;">
		</div>
	</div>

	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/masonry/jquery.masonry.min.js"></script>
	<script type="text/javascript" src="/lib/modernizr-transitions.js"></script>
	<script type="text/javascript" src="/lib/jquery.placeholder.min.js"></script>
	<script type="text/javascript" src="/lib/sha2.js"></script>
	<script type="text/javascript" src="/lib/jquery.poshytip.min.js"></script>
	<script type="text/javascript" src="/lib/jquery.hoverIntent.minified.js"></script>
	<script type="text/javascript" src="/lib/jquery.anythingslider.min.js"></script>
	<script type="text/javascript" src="/lib/animatedpng.js"></script>
	<script type="text/javascript" src="/helpers160version.js"></script>
	<script type="text/javascript" src="/deelio160version.js"></script>
	



<!-- FB BEGIN -->	

<div id="fb-root"></div>
<script type="text/javascript">

window.fbAsyncInit = function() {

	FB.init({

<?php if ($_SERVER["HTTP_HOST"] == "mobdealio.com") { ?>
		appId: '144572715637682', // MOBDEALIO
<?php } else if ($_SERVER["HTTP_HOST"] == "dealupa.com") { ?>
		appId: '201211216608489', // DEALUPA
<?php } else if ($_SERVER["HTTP_HOST"] == "50.57.136.83") { ?>
		appId: '118444051605081', // FE3
<?php } ?>
		status : true, // check login status
		cookie : true, // enable cookies to allow the server to access the session
		xfbml  : true, // parse XFBML
		channelURL : 'channel.html', // channel.html file
		oauth  : true // enable OAuth 2.0
	});

};



(function (d) {
  var js, id = 'facebook-jssdk';
  if (d.getElementById(id)) {
	  return;
  }
  js = d.createElement('script');
  js.id = id;
  js.async = true;
  js.src = "//connect.facebook.net/en_US/all.js";
  d.getElementsByTagName('head')[0].appendChild(js);
}(document));





$(function () {
	$('#container').masonry({
		itemSelector: '.box',
		gutterWidth: 12,
		isAnimated: false,
		isFitWidth: true
	});  
});































debug("THE BEGINNING OF CONTROL [[index.php]]");

cityEdition = calculateCityEditionFromLatLng(gbLat, gbLng);
userEdition = cityEdition;


// Initialize the last_view cookie if it doesn't exist or is too old.
if ($.cookie('last_view') == null || $.cookie('last_view') == "" || isTooOld($.cookie('timestamp'))) {
	debug("User has no last_view cookie OR it is too old, so create/initialize one [[index.php]].");
	setLastViewCookie("?c=" + categories_ALL.toString() + "&i=" + cityEdition);
} else {
	debug("User has a last_view cookie AND it is not too old, so there's no need to create/initialize one [[index.php]].");
}





loadRegistrations();




// Check the user's session/user ID cookie to see if he ought to be logged in
debug("Attempting to set userID [[index.php]].");
setUserIDFromSessionCookie();
debug("userID set to " + userID + " [[index.php]].");






// Check if there's a URL action (e.g., the user has clicked a link to reset
// his password. REMEMBER, WHEN THE USER HAS reset_password IN THE URL, THE
// PHP AT THE START OF INDEX.PHP CREATES A SESSION COOKIE THAT WILL THEN LOG
// THE USER IN WHEN setUserIDFromSessionCookie IS CALLED ABOVE.

// We check for isLoggedIn because in the case that the token/ID pair is NOT
// valid, a session cookie is NOT created at the top of index.php and the user
// is therefore NOT logged in. And in that case, we want to show the welcome screen.
if (urlParams.indexOf("reset_password") != -1 && token != "" && isLoggedIn()) {
	debug("The string 'reset_password' is in the URL, so show the reset password screen.");
	$("#reset-password").data("token", token);
	showResetPassword();


	
} else if (urlParams.indexOf("show_settings") != -1 && token != "" && isLoggedIn()) {
	debug("The string 'show_settings' is in the URL, so show the settings screen.");
	load();




} else if (urlParams.indexOf("email_login") != -1 && token != "" && isLoggedIn()) {
	debug("The string 'email_login' is in the URL, so show the gallery view.");
	load();
	
	
// Check if we're displaying a single-deal view. If we are, show it right away.	
} else if (urlParams.indexOf("m=") != -1 && urlParams.indexOf("i=") != -1) {
	debug("The parameters m and i are in the URL (" + urlParams + "), so call load. The application will display the single-deal view.");
	load();
	


// Check if we're displaying a /seattle (or similar) URL view
} else if (urlParams.indexOf("i=") != -1 && urlParams.indexOf("v=LIST") != -1) {
	debug("The parameters i and v are in the URL (" + urlParams + "), so call load. The application will display the gallery for this edition.");
	logout();
	load();
	
} else {

	showWelcome(0);
	
}
	
</script>
	
<!-- FB END -->	






	
</body>

</html>

