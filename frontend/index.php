<!doctype html>

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">

<head>



<script>
// indexOf for IE
Array.indexOf||(Array.prototype.indexOf=function(b){for(var a=0;a<this.length;a++)if(this[a]==b)return a;return-1});Array.lastIndexOf||(Array.prototype.lastIndexOf=function(b){for(var a=this.length;0<=a;a--)if(this[a]==b)return a;return-1});
</script>

<!--[if gte IE 9]>

<style type="text/css">

.gradient {
	filter: none;
}

</style>

<![endif]-->



<?php


// if (!isset($params_arr['c'])) $params_arr['c'] = "0,1,1,1,1,1,1,1,1,0";
// if (!isset($params_arr['n'])) $params_arr['n'] = 0;
// if (!isset($params_arr['y'])) $params_arr['y'] = 0;
// if (!isset($params_arr['o'])) $params_arr['o'] = 0;
// if (!isset($params_arr['i'])) $params_arr['i'] = 3;
// if (!isset($params_arr['v'])) $params_arr['v'] = "MAP";
// if (!isset($params_arr['l'])) $params_arr['l'] = 0;
// if (!isset($params_arr['h'])) $params_arr['h'] = 1000;
// if (!isset($params_arr['x'])) $params_arr['x'] = 1;
// if (!isset($params_arr['s'])) $params_arr['s'] = "SOLD";




// Either, MAP, LIST, SINGLE, or EMAIL
$page_type = "";

require("db.php");
require_once("refresh_deals_indexes.php");
require_once("helpers.php");

$params_arr = array();


// If this is true, index.php is being called with parameters in the URL, so
// those parameters should determine what deals are shown in list view.
if (count($_GET) > 0) {
	$params_arr = $_GET;

	
	
// Otherwise, if there are no URL parameters set when index.php is loaded, check
// to see if a last_view cookie is set. If so, the last_view cookie will
// determine what deals are shown in list view.	
} else if (isset($_COOKIE['last_view'])) {

	$cookie_str = $_COOKIE['last_view'];
	
	// parse_str requires that there be no question mark
	if (substr($cookie_str, 0, 1) == "?") {
		$cookie_str = substr($cookie_str, 1);
	}

	parse_str($cookie_str, $params_arr);
	
}


echo("<!--\n");
echo("These are the parameters being used to render index.php.\n");
if (count($_GET) > 0) {
	echo("These parameters were gotten from the URL.\n");
} else {
	echo("These parameters were gotten from the cookie.\n");
}
print_r($params_arr);
echo("-->");


// In order to show the correct meta tags, we must determine if index.php is
// being called to show a list of deals or a single deal.

if (isset($params_arr["m"]) && ((isset($params_arr["v"]) && $params_arr["v"] == "SINGLE") || (!isset($params_arr["v"])) )) {

	// If the above if statement is true, we are displaying a single deal, so
	// the meta tags have to show the specifics of the deal we are showing

	$page_type = "SINGLE";

	$single_deal_to_display = $params_arr["m"];

	$memcache = new Memcache;
	$success = $memcache->connect('localhost', 11211);

	$deal = getDealById($single_deal_to_display, $deals_con, $memcache, $cache_life);

	if (isset($deal["Images"])) {
		$image_url = $deal["Images"][0]["image_url"];
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

	// If we're in this else, then we are not showing a single deal.

	if (isset($params_arr["v"]) && $params_arr["v"] == "MAP") {
		$page_type = "MAP";
	} else if (isset($params_arr["v"]) && $params_arr["v"] == "LIST") {
		$page_type = "LIST";
	} else if (isset($params_arr["v"]) && $params_arr["v"] == "STARRED") {
		$page_type = "STARRED";
	}

	$meta = <<<HTML
	<meta property="og:title" content="Dealupa: The the best daily deals in one place" />
	<meta property="og:image" content="http://dealupa.com/images/envelope.png" />
	<title>Dealupa: The the best daily deals in one place</title>
HTML;

	echo($meta);

}

echo("\n\n<!--\n");
echo("The page type is $page_type.\n");
echo("-->");

?>


<meta charset="utf-8" />
<link rel="icon" type="image/png" href="/images/favicon.png"/>
<link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico"/>



<link href='http://fonts.googleapis.com/css?family=Lato:300,400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="/map148.css" />
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

$ip = "";

// Because frontends are behind a load balancer, they don't get the IP of the client's request. Instead, that IP is
// embedded in the header in the X-Cluster-Client-Ip field, so we extract it from there.

foreach (getallheaders() as $name => $value) {
    if (strcmp($name, "X-Cluster-Client-Ip") == 0) {
         $ip = $value;
    }
}

// If extraction of IP doesn't work, just fall back to the REMOTE_ADDR field.

if ($ip == "") {
    $ip= $_SERVER['REMOTE_ADDR'];
}

echo "// Client's IP address: $ip\n";

$lat = 34.0522;
$lng = -118.243;

if (isset($_COOKIE['geo'])) {
	$geo_info = preg_split("/:/", $_COOKIE['geo']);
	if (count($geo_info) == 2) {
		echo "// Got client's location from cookie\n";
		$lat = $geo_info[0];
		$lng = $geo_info[1];
	}
} else {
	if (strlen($ip) > 5) {
		$request = "http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress=$ip&pt_email=sanjay@gmail.com&pt_password=b4134789";
		$tags = get_meta_tags($request);

		if (preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/', $tags['latitude']) &&
			preg_match('/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/', $tags['longitude'])) {
			echo "// Got client's location from GeoBytes\n";
			$lat = $tags['latitude'];
			$lng = $tags['longitude'];
			setcookie('geo', $lat.":".$lng, (time()+(3600*24*30)));
		}
	}
}
	
echo("var gbLat = " . $lat . ";\n");
echo("var gbLng = " . $lng . ";\n");
echo("var ipAddress = '" . $ip . "';\n");

// We now set the URL paramters as a JS variable because deelio.js needs to know the URL parameters to begin the
// application in the correct state.

echo("var urlParams = \"?" . $_SERVER["QUERY_STRING"]) . "\";\n";


// Set the welcome screen randomly
$welcome_screens = array(100, 200, 300, 400, 401);
$random_number = (time() * rand());
$random_screen = $welcome_screens[$random_number % count($welcome_screens)];

if (isset($params_arr["w"])) {
	$w = $params_arr["w"];
} else {
	$w = $random_screen;
}

$w = 0;

echo("var welcomeScreen = " . $w . ";\n");


if (!isset($params_arr['i'])) $params_arr['i'] = calculate_city_edition_from_lat_lng($lat, $lng);

?>
	
</script>



<!----------- MIXPANEL BEGIN ----------->

<script type="text/javascript">
var mpq=[];

// Certain IPs (our development IPs) will not record to the Dealupa project on Mixpanel
ipToIgnore = ["98.248.33.17", "67.161.121.46", "166.250.46.110"];
if (ipToIgnore.indexOf(ipAddress) > -1) {
	// mobdealio.com
	mpq.push(["init","f033ea4322d882a4432b0caafdf2f4a9"]);
	mpq.push(['set_config', {'test': true}]);
	mpq.push(['set_config', {'debug': true}]);
} else {
	// dealupa.com
	mpq.push(["init","144b5d3887b73cfbbaadbc251ef18d26"]);
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
		<h1>Deals in Seattle</h1>
		<div id="container">

		<?php

		echo("<!-- \$params_arr that will be used in deal_html_from_url_params: ");
		print_r($params_arr);
		echo("-->\n");
		echo("<!-- page type: " . $page_type . "-->\n");

		// If index.php is being loaded directly in the browser for the first
		// time and the URL params or the cookie params indicate that list view
		// is to be shown, then we require the PHP file that renders the list
		// view. The required PHP file outputs HTML based on $params_arr.
		if ($params_arr != "" && $page_type == "LIST") {
			echo("<!-- calling deal_html_from_url_params -->\n");
			require("deal_html_from_url_params.php");

		}

		?>		

		</div> <!-- container -->
	</div>
	
	
	
	
	
	



	<div id="top-bar">
		<div id="top-bar-content" style="height:39px; width:958px; margin:3px auto; position:relative">
			<table width=100% border=0 style="position:relative; top:7px;">
				<tr>
				<td>
					<a id="map-view-toggle" href='javascript:void(0);' onclick='changeView("MAP"); loadAndDisplay(); pushState();' class="small-button brown-gradient"><span>Map</span></a>
					<a id="list-view-toggle" href='javascript:void(0);' onclick='changeView("LIST"); loadAndDisplay(); pushState();' class="small-button brown-gradient"><span>List</span></a>
				</td>
				<td style="text-align:right">
					<span id="top-bar-links"></span>
					&#160;&#160;&#160;
					<a id="login-button" href='javascript:void(0);' onclick='showLogin(this);' class="small-button brown-gradient"><span>Login</span></a>
					<a id="logout-button" href='javascript:void(0);' onclick='logout();' class="small-button brown-gradient" style="display:none;"><span>Logout</span></a>
					<a id="signup-button" href='javascript:void(0);' onclick='showWelcome(0);' class="small-button orange-gradient"><span>Sign up</span></a>
				</td>
			</table>
		</div>

		<div style="position:relative; top:-38px; margin:0 auto; width:300px; text-align:center">
			<a id="dealupa-title" href="javascript:void(0);" onclick="showEditionSelector(this);">
				<img src="/images/logo.png">
				<span style="color:#ffca9d; font-size:28px; -webkit-transform:rotate(0.05deg); position:relative; top:-7px; text-shadow:0px -1px 1px rgba(0, 0, 0, .4);" id="city-name"></span>
			</a>
		</div>

		<div style="position:relative; top:-42px; margin:0 auto; width:300px; text-align:center;">
			<input type="text" class="top-bar-search-box" id="top-bar-search-box" placeholder="Search deals">
			<a href='javascript:void(0);' class="small-button brown-gradient" onclick='executeSearch();'>Go</a>
		</div>
	</div>	




















	<div id="bottom-bar">
		<div id="bottom-bar-content" style="height:39px; width:958px; margin:13px auto; position:relative">
			<span id="num-hidden-categories" style="color:#ffca9d; font-size:14px; text-shadow:0px -1px 1px rgba(0, 0, 0, .4);"></span>
			
			<a id="unhide-categories" href='javascript:void(0);' class="small-button orange-gradient" onclick='toggleHiddenCategories(this);' style="display:none"><span>Unhide categories</span></a>
			
			<!-- <a href='javascript:void(0);' class="small-button brown-gradient" onclick='clearSearch(); resetFiltersGlobals(); loadAndDisplay();'><span>Reset</span></a> -->

			&#160;&#160;&#160;

			<span style="color:#ffca9d; font-size:14px; text-shadow:0px -1px 1px rgba(0, 0, 0, .4);">Yelp rating</span>
			&#160;&#160;
			<a id="filter-yelp-0" href='javascript:void(0);' class="small-button brown-gradient" onclick='setYelp(0); loadAndDisplay(); replaceState();'><span>Show all</span></a>
			<a id="filter-yelp-3" href='javascript:void(0);' class="small-button brown-gradient" onclick='setYelp(3); loadAndDisplay(); replaceState();'><span>3+</span></a>
			<a id="filter-yelp-4" href='javascript:void(0);' class="small-button brown-gradient" onclick='setYelp(4); loadAndDisplay(); replaceState();'><span>4+</span></a>
			<a id="filter-yelp-5" href='javascript:void(0);' class="small-button brown-gradient" onclick='setYelp(5); loadAndDisplay(); replaceState();'><span>5</span></a>
			
			&#160;&#160;&#160;
			
			<a id="filter-show-only-new" href='javascript:void(0);' class="small-button brown-gradient" onclick='toggleShowOnlyNew(); loadAndDisplay(); replaceState();'><span>Only deals posted today</span></a>
		</div>
	</div>	

	
	
	














	














	<div id="map"></div>


















	<div id="email">
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






















	<div id="single-deal-view"> <!-- opening tag in index.php -->

	<div style="width:100%; overflow:hidden;">
		<div style="float:left; width:600px;">
			<h1>{$deal["title"]}</h1>
			<h2>{$deal["subtitle"]}</h2>

			<div style="margin-top:10px;">
				<span class="category category-{$category_id}">{$categories[$category_id]}</span>
			</div>
			
			<div style="margin-top:10px;">
				<a id="single-yelp-link" href="{$deal['yelp_url']}" target="_blank">
					<img src="/images/yelp/yelp_{$yelp_rating}.png"> - {$deal["yelp_review_count"]} reviews
				</a>
			</div>
			
			<a href="">{$name},</a> {$street}, {$city}, {$state}

		</div>

		<div id="single-deal-coupon">
			<table width=100%>
				<tr>
				<td style="text-align:center;"><span style="color:#3A1500; font-size:28px; text-align:center; font-weight:700">\${$price}</span><br>for \${$value} value</td>
				<td style="text-align:center;"><span style="color:#3A1500; font-size:28px; text-align:center; font-weight:700">{$num_purchased_string}</span></td>
				</tr>
			</table>



			<a href="{$deal_site_url}" target="_blank" class="big-button orange-gradient" onclick='

mpq.track("Clicked to external deal site", {
	"mp_note": "Clicked on {$deal["id"]} - " + outputURL(),
	"Company": "{$companies[$deal["company_id"]]}",
	"Category": "{$categories[$category_id]}",
	"Yelp": "{$deal["yelp_rating"]}",
	"City": cityEdition,
	"Price": {$price},
	"Value": {$value},
	"Discount": {$discount},
	"View": "LIST",
	"Sort": currentSortBy,
	"UserID": userID
});							



			'>Details at {$companies[$deal["company_id"]]}</a>


			<br>
			{$time_left_string} - {$discovered_string}
		</div>

	</div>
	
	<div style="margin-top:20px; clear:both; width:100%; overflow:hidden;">
		<div style="width:310px; float:left">
			<img src="http://s3.grouponcdn.com/images/site_images/2020/9078/232a5b1e10_grid_6.jpg" style="width:310px; height:auto;">
			<div id="single-map" style="height:310px; width:310px; background-color:#eeeeee; margin-top:14px;"></div>
		</div>
		
		<div style="width:634px; float:left; margin-left:14px; line-height:1.5">
			Dolor sit amet, consectetur adipiscing elit. Duis sodales neque lectus, sit amet aliquet purus. Etiam at erat ut nisl placerat varius. Proin faucibus lobortis augue id rhoncus. Mauris pharetra porttitor enim, sed placerat augue auctor non. Curabitur dignissim, elit ut bibendum placerat, quam arcu ultrices nulla, sed vulputate erat tortor eu justo. Ut pharetra condimentum dictum. Nulla luctus tempus lorem. Etiam tempus quam at dolor elementum tristique quis ut felis. Fusce aliquet rhoncus felis, at aliquam libero tincidunt at. Praesent iaculis adipiscing velit, in suscipit magna tincidunt sed. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Ut quis nibh sed neque cursus egestas id non est. Aenean volutpat velit id neque mollis eu pretium turpis placerat. Duis ornare libero in augue auctor bibendum.
			<br><br>
			Nulla luctus tempus lorem. Etiam tempus quam at dolor elementum tristique quis ut felis. Fusce aliquet rhoncus felis, at aliquam libero tincidunt at. Praesent iaculis adipiscing velit, in suscipit magna tincidunt sed. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Ut quis nibh sed neque cursus egestas id non est. Aenean volutpat velit id neque mollis eu pretium turpis placerat. Duis ornare libero in augue auctor bibendum.
			<br><br>
			Proin faucibus lobortis augue id rhoncus. Mauris pharetra porttitor enim, sed placerat augue auctor non. Curabitur dignissim, elit ut bibendum placerat, quam arcu ultrices nulla, sed vulputate erat tortor eu justo. Ut pharetra condimentum dictum. Nulla luctus tempus lorem. Etiam tempus quam at dolor elementum tristique quis ut felis. Fusce aliquet rhoncus felis, at aliquam libero tincidunt at. Praesent iaculis adipiscing velit, in suscipit magna tincidunt sed.
		</div>
		
	</div>
	
	
	
	
	
	
	</div> <!-- closing tag in index.php -->








	
	<div id="list-yelp-reviews"></div>


	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	<div id="right-bar">
		<div class="deal-image" id="div-left-image">
			<div id="left-image-prev" style="display:none; position:absolute; left:0px; top:80px;">
				<a href="javascript:void(0)" onclick="showPrevImage();"><img src="/images/prev.png"></a>
			</div>
			<div id="left-image-next" style="display:none; position:absolute; right:0px; top:80px;">
				<a href="javascript:void(0)" onclick="showNextImage();"><img src="/images/next.png"></a>
			</div>
			<img class="deal-image" src="{$image_url}" id="left-image">
		</div>

		<div style="background-color:black; color:#fff; padding:5px; overflow:auto">
			<span style="float:left"><span style="font-size:18px; font-weight:700">\${$price}</span> <span style="position:relative; top:-5px; color:#999999">for \${$value} value</span></span>
			<span style="float:right">{$num_purchased_string}</span>
		</div>

		<div style="background-color:rgba(0, 0, 0, 0.7); color:#fff; background-image:url('/images/grid.png'); padding:5px;">
			<a style="font-size:20px; font-weight:300" id="maintitle-link"></a>
			<span id="deal-subtitle"></span>
			<br>
			<span class="gray">
				<span id="deal-name"></span>
				<br>
				<span id="deal-street"></span>
				<br>
				<span id="deal-city"></span> <span id="deal-state"></span>
				<br>
				<a id="list-yelp-link-{$deal["id"]}" href="{$deal["yelp_url"]}" target="_blank" style="position:relative; top:4px;"><img src="/images/yelp/yelp_{$yelp_rating}.png" alt="arrow">&#160;<img src="/images/yelp.png" style="position:relative; top:-2px;">
				</a>
				&#160;- {$deal["yelp_review_count"]} reviews
			</span>
			<div style="margin-top:8px">
				<span class="category category1">The finer things</span>
				<span class="category category2">Date night</span>
			</div>
			<span style="color:#999999; font-size:12px;">Posted 2 days ago - 9h 3m to go</span>
		</div>
	</div>	
	














	<div id="loading-div"><img src="/images/loading-spinner.gif"></div>
	
















	<div id="black-background"></div>
	
	














	<!-- POSHY TIP DIVS -->

	<div style="display:none;">
	
		<div id="login-div">
			<div style="width:200px;">
				Email address<br>
				<input type="text" id="login-email" style="width:100%"><br>
				Password<br>
				<input type="password" id="login-password" style="width:100%"><br>
				<br>
				<div style="float:left;">
				<a href='javascript:void(0);' onclick='login();' class="small-button brown-gradient"><span>Login</span></a>
				</div>
				<div style="font-size:13px; float:right; text-align:right;">
					<a href="javascript:void(0);" onclick="showForgotPassword();" clickcode="107025">Forgot</a> - <a href="javascript:void(0);" onclick="showChangePassword();">Change</a>
				</div>
				<br><br>
				<div id="login-message"></div>
				<br>
				Facebook <fb:login-button autologoutlink=true size="small" scope="email"></fb:login-button>
			</div>
		</div>


		<div id="change-password-div">
			<div style="width:200px;">
				Email address<br>
				<input type="text" id="change-email" style="width:100%"><br>
				Current password<br>
				<input type="password" id="change-current-password" style="width:100%"><br>
				New Password<br>
				<input type="password" id="change-new-password" style="width:100%"><br>
				<br>
				<div style="float:left;">
				<a href='javascript:void(0);' onclick='changePassword();' class="small-button brown-gradient"><span>Change my password</span></a>
				</div>
				<br><br>
				<div id="change-message"></div>
			</div>
		</div>

		
		<div id="forgot-password-div">
			<div style="width:200px;">
				Email address<br>
				<input type="text" id="forgot-email" style="width:100%"><br>
				<br>
				<div style="float:left;">
				<a href='javascript:void(0);' onclick='sendNewPassword();' class="small-button brown-gradient"><span>Reset my password</span></a>
				</div>
				<br><br>
				<div id="forgot-message"></div>
			</div>
		</div>
			
		<div id="city-selector">
			<table cellpadding=0 cellspacing=0>
			<tr>
			<td>
				<a href='http://dealupa.com/atlanta/daily-deals#!' onclick='changeEdition(14);pushState();return false;'>	Atlanta	</a><br>
				<a href='http://dealupa.com/austin/daily-deals#!' onclick='changeEdition(22);pushState();return false;'>	Austin	</a><br>
				<a href='http://dealupa.com/baltimore/daily-deals#!' onclick='changeEdition(28);pushState();return false;'>	Baltimore	</a><br>
				<a href='http://dealupa.com/boston/daily-deals#!' onclick='changeEdition(13);pushState();return false;'>	Boston	</a><br>
				<a href='http://dealupa.com/chicago/daily-deals#!' onclick='changeEdition(12);pushState();return false;'>	Chicago	</a><br>
				<a href='http://dealupa.com/cleveland/daily-deals#!' onclick='changeEdition(24);pushState();return false;'>	Cleveland	</a><br>
				<a href='http://dealupa.com/dallas/daily-deals#!' onclick='changeEdition(19);pushState();return false;'>	Dallas	</a><br>
				<a href='http://dealupa.com/denver/daily-deals#!' onclick='changeEdition(20);pushState();return false;'>	Denver	</a><br>
				<a href='http://dealupa.com/detroit/daily-deals#!' onclick='changeEdition(30);pushState();return false;'>	Detroit	</a><br>
				<a href='http://dealupa.com/houston/daily-deals#!' onclick='changeEdition(16);pushState();return false;'>	Houston	</a><br>
				<a href='http://dealupa.com/kansas-city/daily-deals#!' onclick='changeEdition(29);pushState();return false;'>	Kansas&#160;City	</a><br>
				<a href='http://dealupa.com/las-vegas/daily-deals#!' onclick='changeEdition(21);pushState();return false;'>	Las&#160;Vegas	</a><br>
				<a href='http://dealupa.com/los-angeles/daily-deals#!' onclick='changeEdition(09);pushState();return false;'>	Los&#160;Angeles	</a><br>
				<a href='http://dealupa.com/miami/daily-deals#!' onclick='changeEdition(18);pushState();return false;'>	Miami	</a><br>
				<a href='http://dealupa.com/minneapolis/daily-deals#!' onclick='changeEdition(25);pushState();return false;'>	Minneapolis	</a><br>
				<a href='http://dealupa.com/new-orleans/daily-deals#!' onclick='changeEdition(34);pushState();return false;'>	New&#160;Orleans	</a><br>
			</td>                                            
			<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td>                                             
				<a href='http://dealupa.com/new-york/daily-deals#!' onclick='changeEdition(11);pushState();return false;'>	New&#160;York	</a><br>
				<a href='http://dealupa.com/orange-county/daily-deals#!' onclick='changeEdition(27);pushState();return false;'>	Orange&#160;County	</a><br>
				<a href='http://dealupa.com/orlando/daily-deals#!' onclick='changeEdition(15);pushState();return false;'>	Orlando	</a><br>
				<a href='http://dealupa.com/philadelphia/daily-deals#!' onclick='changeEdition(23);pushState();return false;'>	Philadelphia	</a><br>
				<a href='http://dealupa.com/phoenix/daily-deals#!' onclick='changeEdition(26);pushState();return false;'>	Phoenix	</a><br>
				<a href='http://dealupa.com/pittsburgh/daily-deals#!' onclick='changeEdition(32);pushState();return false;'>	Pittsburgh	</a><br>
				<a href='http://dealupa.com/portland/daily-deals#!' onclick='changeEdition(04);pushState();return false;'>	Portland	</a><br>
				<a href='http://dealupa.com/san-antonio/daily-deals#!' onclick='changeEdition(33);pushState();return false;'>	San&#160;Antonio	</a><br>
				<a href='http://dealupa.com/san-diego/daily-deals#!' onclick='changeEdition(07);pushState();return false;'>	San&#160;Diego	</a><br>
				<a href='http://dealupa.com/san-francisco/daily-deals#!' onclick='changeEdition(05);pushState();return false;'>	San&#160;Francisco	</a><br>
				<a href='http://dealupa.com/san-jose/daily-deals#!' onclick='changeEdition(06);pushState();return false;'>	San&#160;Jose	</a><br>
				<a href='http://dealupa.com/seattle/daily-deals#!' onclick='changeEdition(03);pushState();return false;'>	Seattle	</a><br>
				<a href='http://dealupa.com/silicon-valley/daily-deals#!' onclick='changeEdition(08);pushState();return false;'>	Silicon&#160;Valley	</a><br>
				<a href='http://dealupa.com/st-louis/daily-deals#!' onclick='changeEdition(31);pushState();return false;'>	St.&#160;Louis	</a><br>
				<a href='http://dealupa.com/tacoma/daily-deals#!' onclick='changeEdition(10);pushState();return false;'>	Tacoma	</a><br>
				<a href='http://dealupa.com/dc/daily-deals#!' onclick='changeEdition(17);pushState();return false;'>	Washington,&#160;D.C.</a><br>
			</td>
			</tr>
			</table>
		</div>
	</div>
		


	
	<?php
	
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	require("insert_welcome.php");
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	?>	


	
	<div id="hover-info" style="position:relative; display:none; min-width:250px;">
		<div style="max-width:225px; margin-right:5px; float:left">
		<span style="font-size:11px; color:#666666; font-weight:700;" id="hover-info-company">Company name</span>
		<br>
		<span id="hover-info-maintitle">This is the title of the deal. It is usually not long.</span>
		<span style="font-size:12px; color:#666666" id="hover-info-subtitle">This is the subtitle. It is usually a lot longer. But that's okay.</span>
		<span style="font-size:12px; color:#999999" id="hover-info-yelp"><br><img id="hover-info-yelp-stars"  alt="arrow"><span style="font-size:11px; color:#666666" id="hover-info-yelp-count">Yelp</span></span>
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
	
	<script type="text/javascript" src="/lib/sha2.js"></script>

	<script type="text/javascript" src="/lib/jquery.poshytip.min.js"></script>

	<script type="text/javascript" src="/helpers.js"></script>
	<script type="text/javascript" src="/deelio148.js"></script>
	

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
		
	FB.getLoginStatus(function(response) {
		debug("FB response:" + response.status);
		if (loggedIn == "NONE") {
			if (response.authResponse) {
				setUserID(FB.getUserID());
				debug("SETTING loggedIn TO FACEBOOK");
				loggedIn = "FACEBOOK";
			}
		}
		setFBCallbacks();
		
		if (!isLoggedIn()) {
			debug("NOT LOGGED IN");
			initLoggedOut();
			showWelcome(1);
		}	
		
	});	
  };


  (function(d){
     var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
     js = d.createElement('script'); js.id = id; js.async = true;
     js.src = "//connect.facebook.net/en_US/all.js";
     d.getElementsByTagName('head')[0].appendChild(js);
   }(document));
   
	$(function () {

		var $container = $('#container');
	
		$container.imagesLoaded(function(){
			$("img.deal-image").each(function () {
				var ar = this.width / this.height;
				if (ar > 1.75) {
					this.style.height = "250px";
					this.style.width = "auto";

				}
				if (this.height > 310) {
					var nudge = (this.height - 310) / -2;
					this.style.marginTop = nudge + "px";
				}
			});

			
			$container.masonry({
				itemSelector : '.box',
				gutterWidth : 14,
				isAnimated: true,
				isFitWidth: true
			});
			
			$container.css({ opacity: 1 });
			
			registerListViewCallBacks();		
			
		});
		
	});
   
   
	load();
	
</script>
	
<!-- FB END -->	



	
</body>

</html>
