<!doctype html>

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">

<head>



<script>
// indexOf for IE
Array.indexOf||(Array.prototype.indexOf=function(b){for(var a=0;a<this.length;a++)if(this[a]==b)return a;return-1});Array.lastIndexOf||(Array.prototype.lastIndexOf=function(b){for(var a=this.length;0<=a;a--)if(this[a]==b)return a;return-1});
</script>




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



<link href='http://fonts.googleapis.com/css?family=Lato:400,700,&v2' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="/map148.css" />
<link rel="stylesheet" type="text/css" href="/lib/jquery.qtip.css" />
<link rel="stylesheet" href="/lib/jquery-ui-1.8.16.custom.css">

<style>

.ui-slider-horizontal .ui-state-default {
	background: url(/images/handle.png) no-repeat scroll 50% 50%;
}

.ui-slider a {
	outline: none;
}

</style>



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

//$w = 0;

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

	<div id="save-form" style="display:none">
		Name this view:<br>
		<input type="text" id="search-name"><br>
		<input type="button" class="c-g save-search" value="Cancel" onclick="$('#save-search-button').qtip('api').hide();">
		<input type="button" class="c-g save-search" value="Save" onclick="saveSearch(); $('#save-search-button').qtip('api').hide();">
	</div>

	<div id="map"></div>

	
	
	
	
	<div id="list-view-area">
		<div id="list-view-title">
			<span id="list-view-num-deals">Loading...</span>
			<span id="starred-list-view-num-deals">Loading...</span>
			<span id="list-view-sorted-by"></span>
			<div id="sort-options">
				Sort by
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href='javascript:void(0);' onclick='resortListView("NEW")' id="sortNEW" clickcode="105064">New</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href='javascript:void(0);' onclick='resortListView("SOLD")'  id="sortSOLD" clickcode="105065">Number Sold</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href='javascript:void(0);' onclick='resortListView("PRICE")'  id="sortPRICE" clickcode="105066">Price</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href='javascript:void(0);' onclick='resortListView("DEADLINE")'  id="sortDEADLINE" clickcode="105067">Time Left</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<span id="sortDISTANCE">Distance to</span> <input type="text" style="width:60px;" id="sort-zip" maxlength=5 placeholder="Zip code">
				<a class="c-g save-search" onclick='resortListView("DISTANCE");' clickcode="105068">Go</a>
			</div>
		</div>
		<div id="starred-list-view"></div>
		<div id="search-list-view"></div>
		<div id="list-view">
		
		
		<?php
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////



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

		////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		?>		
		
		</div>
		<div id="list-view-footer"></div>
	</div>
	
	
	
	
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
	
	
	
	
	<div id="white-bar" style="height:100%; width:15px; background-color:white; position:fixed; right:0px;"></div>

	
	
	
	
	
	
	
	<div id="single-deal-view"> <!-- opening tag in index.php -->

	<?php
	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////
	
	echo("<!-- \$_SERVER['QUERY_STRING']: " . $_SERVER["QUERY_STRING"] . "-->");
	
	if (isset($params_arr["m"]) && isset($params_arr["i"]) && $page_type == "SINGLE") {
		$single_deal_to_display = $params_arr["m"];
		require("single_deal_html.php");
	}
	
	////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////
	?>
	</div> <!-- closing tag in index.php -->


	
	<div id="list-yelp-reviews"></div>



	<div id="left-panels">
		<div id="filters-bar">
			<div id="view-toggle">
				<a href='javascript:void(0);' onclick='changeView("MAP"); loadAndDisplay(); pushState();'  id="map-view-toggle" clickcode="101001">Map view</a>
				<a href='javascript:void(0);' onclick='changeView("LIST"); loadAndDisplay(); pushState();' id="list-view-toggle" clickcode="101002">List view</a>
			</div>
			<div id="filters-cities">
				<div id="filters">
					
					<a id="filter-1" class="unselected" style="" clickcode="101003"><img id="filter-image-1" src="/images/check_off.png">&nbsp;&nbsp;Food & Drink</a>
					<span id="filter-1-only" style="display:none;"> <a class="secondary" href='javascript:void(0);' onclick='setOnly(1)' clickcode="101011">only</a></span><br>
					
					<a id="filter-2" class="unselected" style="" clickcode="101004"><img id="filter-image-2" src="/images/check_off.png">&nbsp;&nbsp;Activities & Events</a>
					<span id="filter-2-only" style="display:none;"> <a class="secondary" href='javascript:void(0);' onclick='setOnly(2)' clickcode="101012">only</a></span><br>
					
					<a id="filter-3" class="unselected" style="" clickcode="101005"><img id="filter-image-3" src="/images/check_off.png">&nbsp;&nbsp;Spa & Beauty</a>
					<span id="filter-3-only" style="display:none;"> <a class="secondary" href='javascript:void(0);' onclick='setOnly(3)' clickcode="101013">only</a></span><br>
					
					<a id="filter-4" class="unselected" style="" clickcode="101006"><img id="filter-image-4" src="/images/check_off.png">&nbsp;&nbsp;Kids & Parents</a>
					<span id="filter-4-only" style="display:none;"> <a class="secondary" href='javascript:void(0);' onclick='setOnly(4)' clickcode="101014">only</a></span><br>
				
					<a id="filter-5" class="unselected" style="" clickcode="101007"><img id="filter-image-5" src="/images/check_off.png">&nbsp;&nbsp;Shopping & Services</a>
					<span id="filter-5-only" style="display:none;"> <a class="secondary" href='javascript:void(0);' onclick='setOnly(5)' clickcode="101015">only</a></span><br>
					
					<a id="filter-6" class="unselected" style="" clickcode="101008"><img id="filter-image-6" src="/images/check_off.png">&nbsp;&nbsp;Classes & Learning</a>
					<span id="filter-6-only" style="display:none;"> <a class="secondary" href='javascript:void(0);' onclick='setOnly(6)' clickcode="101016">only</a></span><br>
					
					<a id="filter-7" class="unselected" style="" clickcode="101009"><img id="filter-image-7" src="/images/check_off.png">&nbsp;&nbsp;Fitness & Health</a>
					<span id="filter-7-only" style="display:none;"> <a class="secondary" href='javascript:void(0);' onclick='setOnly(7)' clickcode="101017">only</a></span><br>
					
					<a id="filter-8" class="unselected" style="" clickcode="101010"><img id="filter-image-8" src="/images/check_off.png">&nbsp;&nbsp;Dental & Medical</a>
					<span id="filter-8-only" style="display:none;"> <a class="secondary" href='javascript:void(0);' onclick='setOnly(8)' clickcode="101018">only</a></span><br>
				</div>
				
				<div style="padding-top:4px;">
					<img src="/images/yelp.png" style="position:relative; top:4px;"> rating:
					<select id="filter-yelp">
					<option value="0">Show all</option>
					<option value="3">3+ stars</option>
					<option value="3.5">3.5+ stars</option>
					<option value="4">4+ stars</option>
					<option value="4.5">4.5+ stars</option>
					<option value="5">5 stars</option>
					</select>
					
				</div>
				
				<div style="padding-top:4px;display:none;" id="div-filter-company">
					Company:
					<select id="filter-company">
					<option value="0">Show all</option>
					<option value="1">Groupon</option>
					<option value="2">Living Social</option>
					<option value="3">BuyWithMe</option>
					<option value="4">Tippr</option>
					<option value="5">TravelZoo</option>
					<option value="6">Angie's List</option>
					<option value="7">Gilt City</option>
					<option value="8">Yollar</option>
					<option value="9">Zozi</option>
					<option value="10">Bloomspot</option>
					<option value="11">ScoutMob</option>
					<option value="12">Amazon Local</option>
					<option value="13">kgbdeals</option>
					<option value="14">Lifebooker</option>
					<option value="15">DealOn</option>
					<option value="16">Eversave</option>
					<option value="17">LS Escapes</option>
					<option value="18">Google Offers</option>
					<option value="19">Get My Perks</option>
					<option value="20">Voice</option>
					<option value="21">Munch On Me</option>
					<option value="22">Doodle Deals</option>
					<option value="23">Juice</option>
					<option value="24">Schwaggle</option>
					<option value="25">Home Run</option>
					<option value="26">Bargain Bee</option>
					<option value="27">Signpost</option>
					</select>
				</div>
			</div> <!-- END	filters-cities -->
			
			
			<div id="filters-vacations" style="display:none">
				Price: <input type="text" id="amount" readonly="readonly" style="font-family:'Lato', sans-serif; color:#DA6800; border:0; background:rgba(0, 0, 0, 0);">
				<div id="slider-range"></div>
			</div>

			<div style="padding-top:8px;">
				<input type="checkbox" id="filter-show-new">Posted today</input>
			</div>
			
			<div style="padding-top:8px;" id="div-filter-hide-expired">
				<input type="checkbox" id="filter-hide-expired">Hide expired deals</input>
			</div>
			
			<div style="padding-top:8px;">
				<a href="javascript:void(0);" clickcode="101019" onclick='
				
mpq.track("Reset all filters", {
	"mp_note": outputURL() + " | User ID: " + userID,
	"City": cityEdition,
	"View": view,
	"UserID": userID
});
clearSearch();
resetFiltersGlobals();
loadAndDisplay();
			
				'>Reset all filters</a>
			</div>
			
			<!--
			<div style="padding-top:8px;" id="save-search-div">
				<span id="save-search-button-span"><button id="save-search-button" class="c-g save-search">Save this view</button></span>
				<div id="saved-searches"></div>
			</div>
			-->	
				
				
		</div>
		
		
		<div id="list-view-map" style="display:none"></div>
		
	</div>







	<div id="right-bar">
		<div id="div-left-image">
			<div id="left-image-prev" style="display:none; position:absolute; left:0px; top:80px;">
				<a href="javascript:void(0)" onclick="showPrevImage();" clickcode="106020"><img src="/images/prev.png"></a>
			</div>
			<div id="left-image-next" style="display:none; position:absolute; right:0px; top:80px;">
				<a href="javascript:void(0)" onclick="showNextImage();" clickcode="106021"><img src="/images/next.png"></a>
			</div>
			<img id="left-image" >
		</div>

		<div class="deal-content">
			<div id="deal-star" style="float:right; margin-left:4px;"></div>
			<a id="maintitle-link" href=""></a>
			<div id="deal-subtitle"></div><div style="display:none;" id="deal-marker-id"></div>
			<div class="coupon-text">
				<div style="float:left;"><a id="category">TEXT</a></div><div id="deal-send" style="position:relative; left:10px; height:20px;"></div>
				<div id="rating" style="clear:both;">
					<a id="yelp-link" href="" target='_blank' clickcode="106022"><img id="yelp-stars"  alt="arrow"><img src='/images/yelp.png'></a><span style="position:relative; top:-4px;" id="yelp-review-count"></span>
					<div id="yelp-reviews"></div>
				</div>
				<div id="deal-address1">
					<span id="deal-name"></span>
					<br>
					<span id="deal-street"></span>, <span id="deal-city"></span>,&nbsp;<span id="deal-state"></span><span id="deal-zip"></span>
				</div>
				<div class="coupon">
					<div id="coupon-pvd">
						<table class="coupon-table">
							<thead>
								<tr id="coupon-pvd-tr1">
									<td>price</td><td>value</td><td>discount</td>
								</tr>
							</thead>
							<tbody>
								<tr id="coupon-pvd-tr2">
									<td><span id="deal-coupon-price"></span></td><td><span id="deal-value"></span></td><td><span id="deal-discount"></span></td>
								</tr>
							</tbody>
						</table>
						<hr class="dotted">
					</div>
					<div id="coupon-posted">
						<span id="purchased"><span id="deal-num_purchased"></span>&nbsp;sold<hr class="dotted"></span>
					</div>
					<div id="list-deal-expires">
						<span id="deal-expires"></span>
						<br>
						<span style="font-size:12px;color:#999999" id="deal-discovered"></span>
					</div>
				</div>
				<div class="price-and-button">
					<div class="button">
						<a href="" target=_blank class="c-g map-cupid-green" id="details-button" clickcode="106023">See deal details</a>
					</div>
				</div>
				<div id="company" style="float:right; padding:0 0 10px 5px;">&nbsp;</div>
			</div>

		</div>
	</div>
	
	
	
	
	<div id="loading-div"><img src="/images/loading-spinner.gif"></div>
	
	<div id="black-background"></div>
	
	
	<div id="login-div-container">
		<div id="login-div">
		Email address<br>
		<input type="text" id="login-email"><br>
		Password<br>
		<input type="password" id="login-password"><br>
		<div style="float:left;">
		<input type="button" class="c-g save-search" value="Login" onclick="login();">
		</div>
		<div style="font-size:13px; float:right; text-align:right;">
		<a href="javascript:void(0);" onclick="showForgotPassword();" clickcode="107025">Forgot</a> - <a href="javascript:void(0);" onclick="showChangePassword();" clickcode="107026">Change</a>
		</div>
		<br>
		<br>
		<div id="login-message"></div>
		<br>
		Facebook <fb:login-button autologoutlink=true size="small" scope="email"></fb:login-button>
		</div>

		<div id="change-password-div">
		Email address<br>
		<input type="text" id="change-email"><br>
		Current password<br>
		<input type="password" id="change-current-password"><br>
		New Password<br>
		<input type="password" id="change-new-password"><br>
		<div style="float:left;">
		<input type="button" class="c-g save-search" value="Change my password" onclick="changePassword();">
		</div>
		<br>
		<br>
		<div id="change-message"></div>
		</div>

		<div id="forgot-password-div">
		Email address<br>
		<input type="text" id="forgot-email"><br>
		<div style="float:left;">
		<input type="button" class="c-g save-search" value="Reset my password" onclick="sendNewPassword();">
		</div>
		<br>
		<br>
		<div id="forgot-message"></div>
		</div>
		
	</div>


	
	<div id="top-bar">
		<div id="top-bar-logo" style="float:left; position:relative; top:-3px;">
			<a href="/" clickcode="102027"><img style="position:relative; top:-1px;" src="/images/logo_dealupa.png"></a>
		</div>
		<div id="edition" style="float:left; left:10px; position:relative;">
			<span id="city-name"></span>
			<img src="/images/down_arrow.png">
			<div id="city-selector">
				<table cellpadding=0 cellspacing=0>
				<tr>
				<td colspan=3 style="vertical-align: top; position: relative;">
					<a href='javascript:void(0);' onclick='changeEdition(cityEdition);' clickcode="102028"><span id="city-selector-current-city"></span></a>
					<hr class="dotted">
					<!--<img src="/images/palm_tree.png">--><a href='javascript:void(0);' onclick='changeEdition(VACATIONS_CITY_ID);pushState();' style="font-weight:700"> Dealupa Vacations</a><br>
					<hr class="dotted">
					<a href='javascript:void(0);' onclick='changeEdition(NATIONWIDE_CITY_ID);pushState();' style="font-weight:700"> Dealupa Nation</a><br>
					<hr class="dotted">
				</td>
				</tr>
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
		
		<div id="top-bar-search" style="float:left; position:relative; top:-1px; left:25px;">
			<input type="text" id="top-bar-search-box" placeholder="Search deals">
			&nbsp;
			<input type="button" class="c-g save-search" style="height:27px; position:relative; top:-2px" value="Go" onclick="search('TOP BAR');">
			<input id="clear-search-button" type="button" class="c-g save-search" style="display:none; height:27px; position:relative; top:-3px" value="Clear search" onclick="clearSearch(); loadAndDisplay(); pushState();">
		</div>

		
		
		<?php require("top_links_div.php"); ?>
		
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
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
	
	<script type="text/javascript" src="/lib/jquery.qtip.min.js"></script>
	<script type="text/javascript" src="/lib/sha2.js"></script>
		
	<script type="text/javascript" src="/info_menu.js"></script>
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
   
   
   
	load();
	
</script>
	
<!-- FB END -->	



	
</body>

</html>
