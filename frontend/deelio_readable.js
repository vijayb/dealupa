/*

BEFORE PUSHING:

- Change deelioX.js and mapX.css to current version number in index.php, static_top.php
- Set debugging to 0 in this file
- Compile deelioX.js and put the readable source code in deelio_readable.js
- Minify CSS with http://www.ventio.se/tools/minify-css/
- Change $domain_ac in array_constants.php

*/

var columnWidth = 240;
var columnSpacing = 10;
var numPerColumnSmall = 4;
var numPerColumnBig = 5;

var debugging = 0;

// Set to 1 at the very end of the load() function. Set to 0 only here.
var initialLoadCompleted = 0;

var userID = 0;
var userName = "";
var userToken = "";

var markersArray = [];
var singleDealMarkersArray = [];
var singleDeal = {};		// The deal which the single-page display is showing. Analogous to markersArray , but for the SINGLE-DEAL view
var singleCategory = 0; 	// The single category that SINGLE-CATEGORY view is showing


var listScrollPosition;
var listMarker;

// The search query. If this is blank, there was no search.
var query = "";

var utm = "direct";
// welcomeScreen is set in index.php




var currentSortBy = "SOLD";

var currSWLat = -1;
var currSWLng = -1;
var currNELat = -1;
var currNELng = -1;
var currBoundingBoxSizeMultiplier = 1;



// The view the user is currently seeing. MAP, LIST, or IMAGE
var view = "LIST";
var pages = 1;

// In map view, the deal ID of the current deal displayed in the right panel
var mapViewDealID = "";

var cityEdition = -1;	// The current edition shown in the FE
var changedEditionWhileInListView = 0;

var userEdition = -1;	// The user's edition as stored in the database
var userZipCode = -1;	// The user's zip code as stored in the database
var maxDealDistance = 0;


var cityString;

// The zip code that the distance sort uses
var zip;
var zipLat;
var zipLng;


var historyObject = [];


// This array holds the CENTER of each metro
var cityLat = [
	0,			// 0
	0,
	0,
	47.614495,
	45.5235,
	37.775,		// 5
	37.342867,
	32.7153,
	37.448424,
	34.0522,
	47.268048,	// 10
	40.759306,
	41.888988,
	42.358433,
	33.781145,
	28.538537,	// 15
	29.760205,
	38.894774,
	25.78969,
	32.802282,
	39.740986,	// 20
	36.114858,
	30.267518,
	39.958175,
	41.51012,
	44.963826,	// 25
	33.452068,
	33.73005,
	39.287811,
	39.10662,
	42.33355,	// 30
	38.628404,
	40.440415,
	29.424124,
	29.951067
];




var cityLng = [
	0,			// 0
	0,
	0,
	-122.25386,
	-122.676,
	-122.418,	// 5
	-121.894684,
	-117.156,
	-122.159042,
	-118.243,
	-122.444,	// 10
	-73.984884,
	-87.6244,
	-71.059957,
	-84.357147,
	-81.379681,	// 15
	-95.36931,
	-77.036476,
	-80.225601,
	-96.768951,
	-104.98741,	// 20
	-115.173111,
	-97.74313,
	-75.125885,
	-81.672363,
	-93.178482,	// 25
	-112.05986,
	-117.865448,
	-76.605263,
	-94.561386,
	-83.029861,	// 30
	-90.182648,
	-79.997635,
	-98.493719,
	-90.071583
];



// High numbers are CLOSER IN; low numbers are FARTHER OUT
var cityZoom = [
	12,		// 0
	12,
	12,
	12, 
	12, 
	13,		// 5
	12,
	12, 
	11,
	10,
	12,		// 10
	14, 
	12, 
	12, 
	12, 
	12, 	// 15
	12, 
	12, 
	12, 
	10, 
	12, 	// 20
	12,
	12,
	12,
	12,
	10,		// 25
	10,
	10,
	12,
	12,
	12,		// 30
	12,
	12,
	11,
	11
];





var swLat;
var swLng;
var neLat;
var neLng;

var map;

var selectedMarkerIndex = -1;
var selectedMarkerImageIndex = 0;	// Each marker can have multiple images. This keeps track of which image is currently being shown

var companies = [];

var cities = [];
var citiesReverse = [];
var cities_url = [];

var showYelp = 0;
var showCompany = 0;






companies[0] = "No company at this index";
companies[1] = "Groupon";
companies[2] = "Living Social";
companies[3] = "BuyWithMe";
companies[4] = "Tippr";
companies[5] = "TravelZoo";
companies[6] = "Angies List";
companies[7] = "Gilt City";
companies[8] = "Yollar";
companies[9] = "Zozi";
companies[10] = "Bloomspot";
companies[11] = "ScoutMob";
companies[12] = "Amazon Local";	
companies[13] = "kgbdeals";
companies[14] = "Lifebooker";
companies[15] = "DealOn";
companies[16] = "Eversave";
companies[17] = "LS Escapes";
companies[18] = "Google Offers";
companies[19] = "Get My Perks";
companies[20] = "Voice Deals";
companies[21] = "Munch On Me";
companies[22] = "Doodle Deals";
companies[23] = "Juice in the City";
companies[24] = "Schwaggle";
companies[25] = "Home Run";
companies[26] = "Bargain Bee";
companies[27] = "Signpost";
companies[28] = "Crowd Seats";
companies[29] = "Landmarks Great Deals";
companies[30] = "DealFind";
companies[31] = "Restaurant.com";
companies[32] = "Pinchit";
companies[33] = "Goldstar";
companies[34] = "OnSale";
companies[35] = "Living Social";
companies[36] = "Entertainment.com";
companies[37] = "Thrillist";
companies[38] = "Savored";
companies[39] = "MSN Offers";
companies[40] = "CBS Local Offers";
companies[41] = "Crowd Savings";

cities[1] = "";
cities[2] = "Nation";
cities[3] = "Seattle";
cities[4] = "Portland";
cities[5] = "San Francisco";
cities[6] = "San Jose";
cities[7] = "San Diego";
cities[8] = "Silicon Valley";
cities[9] = "Los Angeles";
cities[10] = "Tacoma";
cities[11] = "New York";
cities[12] = "Chicago";
cities[13] = "Boston";
cities[14] = "Atlanta";
cities[15] = "Orlando";
cities[16] = "Houston";
cities[17] = "Washington, D.C.";
cities[18] = "Miami";
cities[19] = "Dallas";
cities[20] = "Denver";
cities[21] = "Las Vegas";
cities[22] = "Austin";
cities[23] = "Philadelphia";
cities[24] = "Cleveland";
cities[25] = "Minneapolis/St.Paul";
cities[26] = "Phoenix";
cities[27] = "Orange County";
cities[28] = "Baltimore";
cities[29] = "Kansas City";
cities[30] = "Detroit";
cities[31] = "St. Louis";
cities[32] = "Pittsburgh";
cities[33] = "San Antonio";
cities[34] = "New Orleans";
cities[35] = "Honolulu";
cities[36] = "Sacramento";
cities[37] = "Salt Lake City";
cities[38] = "Tampa";
cities[39] = "Cincinnati";
cities[40] = "Indianapolis";
cities[41] = "Madison";
cities[42] = "Milwaukee";
cities[43] = "Albany";
cities[44] = "Palm Beach";
cities[45] = "Birmingham";
cities[46] = "Columbus";
cities[47] = "Oklahoma City";
cities[48] = "Raleigh";
cities[49] = "Charlotte";
cities[50] = "Charleston";

citiesReverse["nation"] = 2;
citiesReverse["seattle"] = 3;
citiesReverse["portland"] = 4;
citiesReverse["san-francisco"] = 5;
citiesReverse["san-jose"] = 6;
citiesReverse["san-diego"] = 7;
citiesReverse["silicon-valley"] = 8;
citiesReverse["los-angeles"] = 9;
citiesReverse["tacoma"] = 10;
citiesReverse["new-york"] = 11;
citiesReverse["chicago"] = 12;
citiesReverse["boston"] = 13;
citiesReverse["atlanta"] = 14;
citiesReverse["orlando"] = 15;
citiesReverse["houston"] = 16;
citiesReverse["washington"] = 17;
citiesReverse["miami"] = 18;
citiesReverse["dallas"] = 19;
citiesReverse["denver"] = 20;
citiesReverse["las-vegas"] = 21;
citiesReverse["austin"] = 22;
citiesReverse["philadelphia"] = 23;
citiesReverse["cleveland"] = 24;
citiesReverse["minneapolis"] = 25;
citiesReverse["phoenix"] = 26;
citiesReverse["orange-county"] = 27;
citiesReverse["baltimore"] = 28;
citiesReverse["kansas-city"] = 29;
citiesReverse["detroit"] = 30;
citiesReverse["st-louis"] = 31;
citiesReverse["pittsburgh"] = 32;
citiesReverse["san-antonio"] = 33;
citiesReverse["new-orleans"] = 34;
citiesReverse["honolulu"] = 35;
citiesReverse["sacramento"] = 36;
citiesReverse["salt-lake-city"] = 37;
citiesReverse["tampa"] = 38;
citiesReverse["cincinnati"] = 39;
citiesReverse["indianapolis"] = 40;
citiesReverse["madison"] = 41;
citiesReverse["milwaukee"] = 42;
citiesReverse["albany"] = 43;
citiesReverse["palm-beach"] = 44;
citiesReverse["birmingham"] = 45;
citiesReverse["columbus"] = 46;
citiesReverse["oklahoma-city"] = 47;
citiesReverse["raleigh"] = 48;
citiesReverse["charlotte"] = 49;
citiesReverse["charleston"] = 50;



cities_url[1] = "other";
cities_url[2] = "nation";
cities_url[3] = "seattle";
cities_url[4] = "portland";
cities_url[5] = "san-francisco";
cities_url[6] = "san-jose";
cities_url[7] = "san-diego";
cities_url[8] = "silicon-valley";
cities_url[9] = "los-angeles";
cities_url[10] = "tacoma";
cities_url[11] = "new-york";
cities_url[12] = "chicago";
cities_url[13] = "boston";
cities_url[14] = "atlanta";
cities_url[15] = "orlando";
cities_url[16] = "houston";
cities_url[17] = "washington";
cities_url[18] = "miami";
cities_url[19] = "dallas";
cities_url[20] = "denver";
cities_url[21] = "las-vegas";
cities_url[22] = "austin";
cities_url[23] = "philadelphia";
cities_url[24] = "cleveland";
cities_url[25] = "minneapolis";
cities_url[26] = "phoenix";
cities_url[27] = "orange-county";
cities_url[28] = "baltimore";
cities_url[29] = "kansas-city";
cities_url[30] = "detroit";
cities_url[31] = "st-louis";
cities_url[32] = "pittsburgh";
cities_url[33] = "san-antonio";
cities_url[34] = "new-orleans";
cities_url[35] = "honolulu";
cities_url[36] = "sacramento";
cities_url[37] = "salt-lake-city";
cities_url[38] = "tampa";
cities_url[39] = "cincinatti";
cities_url[40] = "indianapolis";
cities_url[41] = "madison";
cities_url[42] = "milwaukee";
cities_url[43] = "albany";
cities_url[44] = "palm-beach";
cities_url[45] = "birmingham";
cities_url[46] = "columbus";
cities_url[47] = "oklahoma-city";
cities_url[48] = "raleigh";
cities_url[49] = "charlotte";
cities_url[50] = "charleston";

categoryIDToSetID = new Array();

// FOOD
categoryIDToSetID[1] = 1;
categoryIDToSetID[2] = 1;
categoryIDToSetID[3] = 1;
categoryIDToSetID[4] = 1;
categoryIDToSetID[5] = 1;
categoryIDToSetID[52] = 1;
categoryIDToSetID[54] = 1;
categoryIDToSetID[55] = 1;

// KIDS
categoryIDToSetID[6] = 2;
categoryIDToSetID[7] = 2;

// LEARNING
categoryIDToSetID[8] = 3;

// CLOTHES
categoryIDToSetID[9] = 4;

// SPA
categoryIDToSetID[10] = 5;
categoryIDToSetID[11] = 5;
categoryIDToSetID[12] = 5;
categoryIDToSetID[13] = 5;
categoryIDToSetID[14] = 5;

// HEALTH AND MEDICAL
categoryIDToSetID[15] = 6;
categoryIDToSetID[16] = 6;
categoryIDToSetID[17] = 6;
categoryIDToSetID[18] = 6;
categoryIDToSetID[46] = 6;

// ACTIVITIES
categoryIDToSetID[19] = 7;
categoryIDToSetID[20] = 7;
categoryIDToSetID[21] = 7;
categoryIDToSetID[22] = 7;
categoryIDToSetID[23] = 7;
categoryIDToSetID[24] = 7;
categoryIDToSetID[25] = 7;
categoryIDToSetID[26] = 7;
categoryIDToSetID[27] = 7;
categoryIDToSetID[28] = 7;
categoryIDToSetID[29] = 7;
categoryIDToSetID[47] = 7;
categoryIDToSetID[48] = 7;
categoryIDToSetID[50] = 7;
categoryIDToSetID[53] = 7;
categoryIDToSetID[56] = 7;
categoryIDToSetID[57] = 7;
categoryIDToSetID[58] = 7;
categoryIDToSetID[59] = 7;




// SERVICES
categoryIDToSetID[30] = 8;
categoryIDToSetID[31] = 8;
categoryIDToSetID[32] = 8;
categoryIDToSetID[33] = 8;
categoryIDToSetID[34] = 8;
categoryIDToSetID[51] = 8;



// SPECIAL INTEREST
categoryIDToSetID[35] = 9;
categoryIDToSetID[36] = 9;
categoryIDToSetID[37] = 9;
categoryIDToSetID[38] = 9;
categoryIDToSetID[39] = 9;
categoryIDToSetID[40] = 9;
categoryIDToSetID[41] = 9;
categoryIDToSetID[49] = 9;


// VACATION
categoryIDToSetID[42] = 10;
categoryIDToSetID[43] = 10;

// GIRLS
categoryIDToSetID[44] = 11;

// GUYS
categoryIDToSetID[45] = 12;


// The max price of any vacation in markersArray
var vacationsMax = 0;

var yelpTimeout = 0;
var yelpTimeoutList = 0;

var galleryMapTimeout = 0;
var galleryMapBlocker = 0;

var messageTimeout = 0;

var hoverImageTimer = 0;
var hoverImageIndex = 0;

// These need to be declared globally so closure doesn't mess with the names
var marker0 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(0, 0), new google.maps.Point(7, 7));
var marker1 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(13, 0), new google.maps.Point(7, 7));
var marker2 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(26, 0), new google.maps.Point(7, 7));
var marker3 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(39, 0), new google.maps.Point(7, 7));
var marker4 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(52, 0), new google.maps.Point(7, 7));
var marker5 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(65, 0), new google.maps.Point(7, 7));
var marker6 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(78, 0), new google.maps.Point(7, 7));
var marker7 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(91, 0), new google.maps.Point(7, 7));
var marker8 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(104, 0), new google.maps.Point(7, 7));
var marker9 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(117, 0), new google.maps.Point(7, 7));
var marker10 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(130, 0), new google.maps.Point(7, 7));
var marker11 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(143, 0), new google.maps.Point(7, 7));
var marker12 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point(156, 0), new google.maps.Point(7, 7));

//var markerShadow = new google.maps.MarkerImage('/images/marker_shadow.png', new google.maps.Size(25, 14), new google.maps.Point(0, 0), new google.maps.Point(13, 6));
//var markerShadowe = new google.maps.MarkerImage('/images/marker_shadow.png', new google.maps.Size(25, 14), new google.maps.Point(0, 0), new google.maps.Point(13, 6));

categoriesRank1_DB_array = [];
categoriesRank1DBDirtyBit = 1;

categoriesRank2_DB_array = [];
categoriesRank2DBDirtyBit = 1;


var infiniteScrollTimeout = 1;


var login_div = $("#login-div").detach();
var change_password_div = $("#change-password-div").detach();
var forgot_password_div = $("#forgot-password-div").detach();
var city_selector = $("#city-selector").detach();



var brownMapStyle;


//var popped = ('state' in window.history), initialURL = location.href

function load() {
		
	debug("***********************************");
	debug("********** START OF LOAD() ********");
	debug("***********************************");
	
	debug("At the start of load(), urlParams is: " + urlParams);
	debug("At the start of load(), the last_view cookie is:: " + $.cookie('last_view'));
	
	$(window).bind('popstate', function(event) {
		//var initialPop = !popped && location.href == initialURL;
		//popped = true;

		//if (initialPop) return;
		
		debug("||||||||||| POP |||||||||||");
		var state = event.originalEvent.state;

		if (state) {
			debug(state.stateParams);
			
			historyObject.pop();
			
			var viewBeforePop = view;
			parseStateString(state.stateParams);

			changeView(view);

			// If the user was just looking at single-deal view, don't reload when he presses back
			if (viewBeforePop == "SINGLE-DEAL" && $("#container").html().length > 100) {
				loadAndDisplay(1);
			} else {
				loadAndDisplay();
			}
			
			// clearSearch();
		}
		setTimeout('window.scrollTo(0, listScrollPosition);', 15);
	});
	
	// Set the userID if the user is logged in.
	userID = isLoggedIn();

	// cityEdition is calculated in index.php because it is needed to set the
	// last_view cookie in the case that the last_view cookie is null
	
	if (isLoggedIn()) {
		initLoggedIn();
	}
	
	$("#top-bar-welcome").hide();
	$("#top-bar").show();
	
	var initialOptions = {
		styles: brownMapStyle,
		zoom: cityZoom[cityEdition],
		center: new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]),
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		mapTypeControl: false,
		zoomControl: true,
		zoomControlOptions: {
			style: google.maps.ZoomControlStyle.LARGE,
			position: google.maps.ControlPosition.TOP_RIGHT
		}
	}
	map = new google.maps.Map(document.getElementById("map"), initialOptions);
	
	var listMapOptions = {
		styles: brownMapStyle,
		zoom: 12,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		streetViewControl: false,
		mapTypeControl: false,
		zoomControlOptions: {
			position: google.maps.ControlPosition.RIGHT_CENTER
		}
	}        
	listMap = new google.maps.Map(document.getElementById("gallery-map"), listMapOptions);	
	
	listMarker = new google.maps.Marker({
		map: listMap
	});
	
	google.maps.event.addListener(map, 'idle', function mapIdle() {
		if (view == "LIST" || view == "SINGLE-DEAL") return;
		cityEdition = calculateCityEditionFromLatLng(map.getCenter().lat(), map.getCenter().lng());
		$("#city-name").html(cities[cityEdition].toLowerCase());
		loadAndDisplay();
		
		
		if (BrowserDetect.browser == "Explorer") {
			// Hack so that all parts of the map are grabbable to drag
			$("div").css("background-color", "");
		}
		
	});
	
	resizeMap();
	
	// If the user doesn't have a welcome_cookie, set it to 1.
	if ($.cookie('welcome_cookie') == null) {
		$.cookie('welcome_cookie', '1', {
			expires: 1000,
			path:'/'
		});
		
	// Otherwise, increment the welcome_cookie counter.
	} else {
		var currCookieVal = parseInt($.cookie('welcome_cookie'));
		$.cookie('welcome_cookie', (currCookieVal + 1), {
			expires: 1000,
			path:'/'
		});	
	}	
	
	
	
	

	
	
	debug("The welcome count is " + $.cookie('welcome_cookie'));
	debug("UserID: " + isLoggedIn());

	



	// CASE 1: If there are URL params, use those to figure out what to display.
	if (urlParams != "?" && urlParams != "" && urlParams.indexOf("action") == -1) {
		parseStateString(urlParams);
		debug("calling changeview with arg " + view + " and edition " + cityEdition + " in load() - urlparams present: " + urlParams);
		
		
		// Log the user out if he's looking at a list view. This makes sure that
		// when a LOGGED IN user is looking at his view and then loads up a URL
		// like /seattle, he will be logged out so there is no confusion as to
		// which category set to write to if he then Xs-out categories
		if (view != "SINGLE-DEAL") {
			logout();
		}
		
		changeView(view);
		loadAndDisplay();
	
	
	
	
	
	// CASE 2: Otherwise, if the user is logged in, use DB prefs to figure out what to display.
	} else if (isLoggedIn()) {
	
		// If there is a show_settings action...
		if (urlParams.indexOf("action") != -1 && urlParams.indexOf("show_settings") != -1) {
			changeView("EMAIL");
			loadAndDisplayEmail();
			
		} else {


			if (urlParams.indexOf("action") != -1 && urlParams.indexOf("email_login") != -1) {
				mpq.track("User loaded site from email 'see all deals' link", {
					"mp_note": "User loaded site from email 'see all deals' link",
					"City": cities[cityEdition],
					"UserID": userID
				});
			}
		
		
			// First get the edition
			var urlString = "/get_user_data.php?user_id=" + userID + "&token=" + userToken;
			var userInfo;
			jQuery.ajax({
				type: "GET",
				url: urlString,
				success: function (data) {
					userInfo = $.parseJSON(data);
				},
				async: false
			});
			cityEdition = parseInt(userInfo["edition"]);
			userEdition = parseInt(userInfo["edition"]);
			userZipCode = parseInt(userInfo["zipcode"]);
			maxDealDistance = parseInt(userInfo["max_deal_distance"]);
			
			debug("calling changeview with arg " + view + " and edition " + cityEdition + " in load() - from DB");
			utm += "_" + welcomeScreen;
			changeView(view);
			changeEdition(cityEdition);
		}
		
		
		
		
	// CASE 3: Otherwise, if there is a last_view cookie, use that to figure out what to display.
	} else if ($.cookie('last_view') != null) {
	
		if (isTooOld($.cookie('timestamp'))) {
			$.cookie('timestamp', (new Date()).getTime(), {
				expires: 1000,
				path:'/'
			});
			debug("calling changeview with arg " + view + " and edition " + cityEdition + " in load() because last_view' cookie was present but was expired, so showing default view and reseting cookie 'timestamp'");
			changeView(view);
			changeEdition(cityEdition);
		
		} else {

			// last_view cookie exists and is NOT too old (presumed to be in a valid format)
			parseStateString($.cookie('last_view'));
			debug("calling changeview with arg " + view + " and edition " + cityEdition + " in load() - going off cookie: " + $.cookie('last_view'));
			changeView(view);
			loadAndDisplay();
		}

		
		
		
		
	// Otherwise, do a default display
	} else {
		debug("XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX ERROR XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX: There should ALWAYS be a last_view cookie");
		debug("calling changeview with arg " + view + " and edition " + cityEdition + " in load() - fresh load, neither params nor cookie to guide");
		utm += "_" + welcomeScreen;
		changeView(view);
		changeEdition(cityEdition);
	}
	
	pushState();

	debug("initialLoadCompleted!");
	initialLoadCompleted = 1;
	
	debug("===================================");
	debug("=========== END OF LOAD() =========");
	debug("===================================");
	debug("");
	
} // END OF LOAD FUNCTION







// Does initialization and callback registration. THIS FUNCTION MAY BE CALLED
// BEFORE LOAD, SO MAKE SURE NOT TO REFER TO VARIABLES THAT ARE DEFINED IN LOAD.
// This function is called in index.php.

function loadRegistrations() {

	debug("> loadRegistrations()");
	
	$('input[placeholder], textarea[placeholder]').placeholder();

	
	$(window).resize(resizeMap);
	
	$("#unhide-categories").poshytip({
		className: 'tip-black',
		showOn: 'none',
		alignTo: 'target',
		alignX: 'center',
		offsetX: 0,
		offsetY: 15
	});
	
	$("#dealupa-title").poshytip({
		className: 'tip-city-selector',
		content: city_selector.html(),
		showOn: 'none',
		alignTo: 'target',
		alignX: 'center',
		offsetX: 0,
		offsetY: 2
	});
	$("#city-selector").remove();
	
	$("#gallery-map-panel").mouseenter(function(e) {
		debug("on map");
		clearTimeout(galleryMapTimeout);
		galleryMapBlocker = 1;
	});
	
	$("#gallery-map-panel").mouseleave(function(e) {
		debug("off map");
		galleryMapBlocker = 0;
	});	
	
	$('#yelp-link').mouseover(function() {
		$('#yelp-reviews').show();
		clearTimeout(yelpTimeout);
	});

	$('#yelp-link').mouseout(function() {
		yelpTimeout = setTimeout("clearYelp()", 200);
	});

	$('#yelp-reviews').mouseover(function() {
		clearTimeout(yelpTimeout);
	});

	$('#yelp-reviews').mouseout(function() {
		yelpTimeout = setTimeout("clearYelp()", 200);
	});

	$('#list-yelp-reviews').mouseover(function() {
		clearTimeout(yelpTimeoutList);
	});

	$('#list-yelp-reviews').mouseout(function() {
		yelpTimeoutList = setTimeout("clearYelpList()", 200);
	});
	
	
	$("#top-bar-search-box").keypress(function(event) {
		var key = event.which;
		var keychar = String.fromCharCode(key).toLowerCase();
		
		if ((key==null) || (key==0) || (key==8) || (key==9) || (key==13) || (key==27)) {
			return true;
		}

		if (((" abcdefghijklmnopqrstuvwxyz0123456789").indexOf(keychar) == -1)) {
			event.preventDefault();
			return false;
		}
	});

	$("#sort-zip").keypress(function(event) {
		var key = event.which;
		var keychar = String.fromCharCode(key).toLowerCase();
		
		if ((key==null) || (key==0) || (key==8) || (key==9) || (key==13) || (key==27)) {
			return true;
		}

		if (((" 0123456789").indexOf(keychar) == -1)) {
			event.preventDefault();
			return false;
		}
	});
	
	$(window).scroll(function () {
		if (view == "LIST" && initialLoadCompleted) {
			if ($(window).scrollTop() >= $(document).height() - $(window).height() - 3000 && infiniteScrollTimeout) {

				mpq.track("Infinite scroll", {
					"mp_note": "Infinite scroll triggered",
					"City": cities[cityEdition],
					"UserID": userID
				});
				infiniteScrollTimeout = 0;
				debug("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! LOADING MORE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
				pages++;
				loadAndDisplayDeals(true);
			}
			
		}
	});

	$("#top-bar-search-box").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { executeSearch(); e.preventDefault(); }
	});
	
	$("#sort-zip").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { resortListView("DISTANCE"); e.preventDefault(); }
	});
	
	$("#move-map-input").keyup(function(event) {
		if (event.keyCode == 13) { $("#move-map-button").click(); }
	});
	
	$("#login-password").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { emailLogin(); e.preventDefault(); }
		if(code == 27) { removeLogin(); e.preventDefault(); }
	});	
	
	$("#reset-password").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { changePassword(); e.preventDefault(); }
	});	

	
	$("#forgot-email").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { sendNewPassword(); e.preventDefault(); }
		if(code == 27) { removeLogin(); e.preventDefault(); }
	});		
	
	$("#signup-email, #signup-password").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { signupUser(); e.preventDefault(); }
		// if(code == 27) { if (!initialLoadCompleted) load(); removeWelcome(); e.preventDefault(); }
	});
	
	$("#signup-zipcode").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { recordZipcode(); e.preventDefault(); }
		if(code == 27) { if (!initialLoadCompleted) load(); removeWelcome(); e.preventDefault(); }
	});
	
	
	$("a").click(function(e){
		if (e.metaKey || e.ctrlKey){
			debug("YOU ARE HOLDING DOWN CTRL!");
			return false;
		}
	});

	$("#dealupa-title").click(function(event) {
		showEditionSelector(event, this);
	});
	
	$("#unhide-categories").click(function(event) {
		toggleHiddenCategories(event, this);
	});
	
	brownMapStyle = 
		[
		  {
			stylers: [
			  { saturation: -90 },
			  { lightness: 16 },
			  { gamma: 0.8 }
			]
		  },{
			featureType: "poi.park",
			stylers: [
			  { hue: "#a1ff00" },
			  { saturation: 45 },
			  { gamma: 0.93 },
			  { lightness: -11 }
			]
		  },{
			featureType: "landscape",
			stylers: [
			  { saturation: 27 },
			  { gamma: 0.49 },
			  { hue: "#ffc300" },
			  { lightness: 10 }
			]
		  },{
			featureType: "water",
			stylers: [
			  { hue: "#00b2ff" },
			  { lightness: -8 },
			  { saturation: 20 }
			]
		  },{
			featureType: "road.arterial",
			elementType: "geometry",
			stylers: [
			  { hue: "#ff9900" },
			  { saturation: 10 },
			  { lightness: -3 },
			  { gamma: 0.94 }
			]
		  },{
			featureType: "road.highway",
			elementType: "geometry",
			stylers: [
			  { hue: "#ff6e00" },
			  { lightness: -24 },
			  { gamma: 1.62 },
			  { saturation: 24 }
			]
		  },{
			featureType: "road.arterial",
			elementType: "labels",
			stylers: [
			  { lightness: 45 },
			  { gamma: 0.52 },
			  { saturation: 5 },
			  { hue: "#ff9900" },
			  { visibility: "on" }
			]
		  },{
			featureType: "water",
			stylers: [
			  { visibility: "on" }
			]
		  }
		];
	
	
}









function isTooOld(cookie_timestamp) {
	// cookies older than march 7, 2012 are expired because they are in the old
	// format
	var minTS = 1331884904000;

	debug("cookie ts: " + cookie_timestamp);
	debug("min ts allowed: " + minTS);

	if (cookie_timestamp == null || cookie_timestamp == "" || cookie_timestamp < minTS) {
		debug("COOKIE IS TOO OLD");
		return true;
	}	
	debug("COOKIE IS GOOD (i.e., not too old)");
	return false;
	
}

function showEditionSelector(event, elem) {
	debug("> showEditionSelector(event, elem)");

	$("#dealupa-title").poshytip('show');
	
	event.stopPropagation();
	$('html').one("click", function(event) { $("#" + elem.getAttribute("id")).poshytip('hide'); });
	$('#' + elem.getAttribute("id") + '-tip').unbind("click");
	$('#' + elem.getAttribute("id") + '-tip').click(function(event) { event.stopPropagation(); });
}


function deleteCategory(category) {
	debug("> deleteCategory(category)");

	updateCategories("DELETE", [category]);
	setNumberHidden();

	var subtitle = "";
	if (!isLoggedIn()) {
		subtitle = "<a href=\'/\'>Sign up</a> to get a daily email of just the kind of deals you're interested in."
	}

	showMessage("You've hidden all &#160;<b>" + categories[category] + "</b>&#160; deals.",
				subtitle,
				5000);
}

function setCategoryTo1(category) {
	debug("> setCategoryTo1(category)");

	updateCategories("SET_TO_1", [category]);
	setNumberHidden();
	$("#unhide-categories").poshytip('hide');
	showMessage("You've turned on &#160;<b>" + categories[category] + "</b>&#160; deals.",
				"",
				5000);

}

function setNumberHidden() {
	debug("> setNumberHidden()");

	// Subtracting 1 because the categories array has an element for "uncategorized"
	var total = categories.length - getVisibleCategories().length - 1;
	
	
	if (total == 0) {
		$("#num-hidden-categories").fadeOut();
		$("#unhide-categories").fadeOut();
	} else if (total == 1) {
		$("#num-hidden-categories").html("Hiding <span style='font-weight:700'>" + total + "</span> category&#160;&#160;");
		$("#num-hidden-categories").fadeIn();
		$("#unhide-categories").fadeIn();
	} else {
		$("#num-hidden-categories").html("Hiding <span style='font-weight:700'>" + total + "</span> categories&#160;&#160;");
		$("#num-hidden-categories").fadeIn();
		$("#unhide-categories").fadeIn();
	}
}

function setYelp(newYelp) {
	$("#filter-yelp-0").removeClass("down");
	for (var i = 3; i <= 5; i++) {
		$("#filter-yelp-" + i).removeClass("down");
	}
	$("#filter-yelp-" + newYelp).addClass("down");
	showYelp = newYelp;
}






function toggleHiddenCategories(event, elem) {
	debug("> toggleHiddenCategories(event, elem)");

	
	var vc = getVisibleCategories();
	
	// Subtract 1 because "Uncategorized" is a category
	var totalNumHidden = categories.length - vc.length - 1;
	var numColumns = Math.ceil(totalNumHidden / 10);
	var perColumn = Math.ceil(totalNumHidden / numColumns);

	var pills = [];
	
	var counter = 0;
	
	// Start from 1 because "Uncategorized" is a category
	for (var i = 1; i < categories.length; i++) {
		if (vc.indexOf(i) == -1) {
		
			if (counter % perColumn == 0) {
				pills[counter] = "<div>";
			} else {
				pills[counter] = "<div style='margin-top:20px;'>";
			}
			
			pills[counter] += "<span class='category category-set-" + categoryIDToSetID[i] + "'>" + categories[i] + "</span> - <a href='javascript:void(0);' onclick='setCategoryTo1(" + i + "); loadAndDisplay(); pushState();'>Show</a></div>";
			counter++;
		}
	}

	var html = "<div><table><tbody><tr>";
					
	for (var i = 0; i < numColumns; i++) {
		html += "<td style='vertical-align:top'>" + pills.slice((i * perColumn), Math.min(((i * perColumn) + perColumn), vc.length)).join("") + "</td>";
		
		if (i + 1 != numColumns) {
			html += "<td>&nbsp;&nbsp;</td>";
		}
	}

	html += "</tr></tbody></table></div>";
	
	$("#unhide-categories").poshytip('update', html);
	$("#unhide-categories").poshytip('show');
	
	event.stopPropagation();
	$('html').one("click", function(event) { $("#" + elem.getAttribute("id")).poshytip('hide'); });
	$('#' + elem.getAttribute("id") + '-tip').unbind("click");
	$('#' + elem.getAttribute("id") + '-tip').click(function(event) { event.stopPropagation(); });
	
}





function showMessage(title, subtitle, timeout) {

	clearTimeout(messageTimeout);

	$("#notification-bar").css("bottom", "-100px");
	$("#notification-bar").show();
	
	
	if ($("#gallery-map-panel").is(":visible")) {
		var moveDistance = "+=250px";
	} else {
		var moveDistance = "+=200px";
	}
	
	
	$("#notification-bar").animate({ 
			bottom: moveDistance
		},
		300,
		function() {
		}
	);
	
	$("#nb-title").html(title);
	
	$("#nb-subtitle").html("");
	if (subtitle != "") {
		$("#nb-subtitle").html(subtitle);
	}
	
	if (timeout == -1) {
		// A -1 value for timeout means the message must NOT auto-disappear
	} else {
		messageTimeout = setTimeout('hideMessage();', timeout);
	}
	
}



function hideMessage() {
	$("#notification-bar").fadeOut();
}


function isLoggedIn() {
	return userID;
}



function setUserIDFromSessionCookie() {
	debug("> setUserIDFromSessionCookie()");
	
	// Check the session/userid cookie combo to see user is logged in. If he is,
	// userID is set to the user's userID
	
	debug("Checking to see if the user is logged in via his cookie.")
	
	if ($.cookie('session_cookie') != null && $.cookie('userid_cookie') != null) {
		var cookieSessionID = $.cookie('session_cookie');
		var cookieUserID = $.cookie('userid_cookie');
		jQuery.ajax({
			url: "/check_session.php?user_id=" + cookieUserID + "&session_id=" + cookieSessionID,
			success: function (data) {
			
				var userInfo = eval(data);
			
				if (userInfo.length == 2) {
					userID = parseInt(cookieUserID);
					
					userToken = userInfo[1];
					userName = userInfo[0];
					
					debug("Yes, the user (" + userName + ") is logged in via his cookie and userID has been set to " + userID);
				} else {
					userID = 0;
				}					
			},
			async: false
		});		
	}
}


function removeLogin() {
	debug("> removeLogin()");

	$("#login-email").val("");
	$("#login-password").val("");
	$("#change-email").val("");
	$("#change-current-password").val("");
	$("#change-new-password").val("");	
	$("#login-button").poshytip('hide');
}

function showResetPassword() {
	debug("> showResetPassword()");

	$(".signup-flow").hide();
	
	$("#reset-password-A1").find(".subtitle").html(userName);

	$("#photo-background").show();
	$("#top-bar").hide();
	$("#top-bar-welcome").show();
	showNoBar();
	
	$("#reset-password-A1").center().fadeIn();
	
	$("#reset-password").val("");
	$("#reset-password").focus();
	
}

function showForgotPassword() {
	debug("> showForgotPassword()");

	$(".signup-flow").hide();
	
	$("#forgot-password-A1").center().fadeIn();
	
	if ($("#login-email").val() != "") {
		$("#forgot-email").val($("#login-email").val());
		$("#forgot-password").focus();
	} else {
		$("#forgot-email").focus();
	}
}


function changePassword() {
	debug("> changePassword()");

	
	if ($("#reset-password").val() == "") {
		showMessage("Ummm...you didn't type in a new password.",
					"Please type in a new password.",
					5000);
		return;
	}
	
	var token = $("#reset-password").data("token");
	
	var newPasswordHash = hex_sha256($("#reset-password").val());
	//var newPasswordHash = ($("#reset-password").val());
	
	$("#reset-password").val("");

	var urlString = "/change_password.php?user_id=" + userID + 
	                "&token=" + token +
					"&new_password_hash=" + newPasswordHash + 
					"&latitude=" + gbLat + 
					"&longtitude=" + gbLng;
					
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			
			if (parseInt(data) == -1) {
				showMessage("Something went wrong. Please contact founders@dealupa.com.",
							"Sorry about that.",
							5000);
			} else {
				$("#photo-background").hide();
				window.location.replace("/");
			}
		},
		async: true
	});
}	




function logout() {
	debug("> logout()");

	userID = 0;
	userName = "";
	
	if (fbIsValid()) {
		FB.logout(function(response) {
		});
	}

	debug("XXXXXXXXXXXX DELETING SESSION COOKIE XXXXXXXXXXXX");
	debug("XXXXXXXXXXXX DELETING SESSION COOKIE XXXXXXXXXXXX");
	debug("XXXXXXXXXXXX DELETING SESSION COOKIE XXXXXXXXXXXX");
	$.cookie('session_cookie', null, {
		expires: 1000,
		path:'/'
	});

	$.cookie('userid_cookie', null, {
		expires: 1000,
		path:'/'
	});

	$("#saved-searches").empty();
	$("#top-bar-links").html("");
	$("#top-bar-user-id").html("");
	
	$("#login-button").show();
	$("#signup-button").show();
	$("#logout-button").hide();
	
	if (view == "EMAIL") {
		changeView("LIST");
	}
	
	// When a user logs out, we need to make sure the NEXT user doesn't see the
	// previous user's categories! Setting this dirty bit forces the categories
	// to be loade from the DB when getVisibleCategories is called next
	categoriesRank1DBDirtyBit = 1;
	categoriesRank2DBDirtyBit = 1;
}



function removeWelcome(recordAsSignupDismissal) {
	debug("> removeWelcome(recordAsSignupDismissal)");


	// removeWelcome is called when the user dismisses the signup screen AND at other times.
	// The argument determined whether this call was as a result of the user's dismissal of
	// the signup screen.
	

	$("#top-bar-welcome").hide();
	$("#top-bar").show();
	$("#photo-background").hide();
	$(".signup-flow").fadeOut();
	if (view == "LIST") {
		showMapBar();
	}
}

function executeSearch() {
	debug("> executeSearch()");

	if (view != "MAP") {
		view = "LIST";
		changeView(view);
	}
	
	query = $("#top-bar-search-box").val();
	$("#top-bar-search-box").select().focus();
	
	if (query == "") {
		return;
	}

	
	
	
	debug("query! [" + query + "]");

	$("#clear-search-button").show();
	$("#sort-options").hide();
	
	
	loadAndDisplay();
	
	$("#top-bar-search-box").css("width", "115px");
	$("#clear-search-button").fadeIn();
	
	
	
	pushState();
	
}


function clearSearch() {
	debug("> clearSearch()");

	$("#search-box").val("");
	$("#top-bar-search-box").val("");
	
	$("#clear-search-button").hide();
	$("#sort-options").show();
	
	query = "";
	
	debug("SETTING currNELng to -1 (2)");
	currNELng = -1;

	$("#top-bar-search-box").css("width", "195px");
	$("#clear-search-button").hide();
	
}



function showWelcome(showX) {
	debug("> showWelcome()");
		
	$("#top-bar").hide();
	$("#top-bar-welcome").show();
	
	$(".signup-flow").hide();
		
	showNoBar();
	
	if (showX) {
		$("#welcome-A1").find(".x").show();
	} else {
		$("#welcome-A1").find(".x").hide();
	}
		
	if (isLoggedIn()) {
		jQuery.ajax({
			url: "/check_has_zipcode.php?user_id=" + userID,
			success: function (data) {
			
				if (!parseInt(data)) {
					// User has NOT previously entered a zipcode
					debug("We don't have a zipcode for this user. Show the zipcode screen.");
					showZipcodeScreen();
				} else {
					// User HAS previously entered a zipcode
					debug("We have a zipcode for this user. Hide the welcome screens and call load.");
					$("#photo-background").hide();
					$(".signup-flow").hide();
					load();
				}
			},
			async: false
		});
	} else {
		debug("User is not logged [[showWelcome]].");

		mpq.track("Welcome screen shown", {
			"mp_note": "Direct load of site",
			"Origin": "Direct load of site",
			"UTM": utm
		});	

		if (welcomeScreen == "A1") {
			
			$("#welcome-A1").center();
			$("#photo-background").show();
			$("#welcome-A1").fadeIn();
			
			if (BrowserDetect.browser != "Explorer") {
				$("#signup-email").focus();
			}

		}
	}
}




function slideUpWelcome() {
	debug("> slideUpWelcome()");



	if (welcomeScreen == 100) {
		debug("");
	} else if (welcomeScreen == 200) {
		$("#welcome-200").slideDown();
	} else if (welcomeScreen == 300) {
		$("#welcome-300").slideDown();
	} else if (welcomeScreen >= 400 && welcomeScreen < 500) {
		debug("");
	}
}





function setUserID(fbID) {
	debug("> setUserID(fbID)");

	var urlString = "/lookup_userid.php?fb_id=" + fbID;
	
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			userID = parseInt(data);
			initLoggedIn();

		},
		async: false
	});	
	
}



function sendNewPassword() {
	debug("> sendNewPassword()");

	var email = $("#forgot-email").val();
	var urlString = "/mail_password.php?email=" + email;
	
	if (email == "") {
		showMessage("We've emailed you a link to reset your password.",
					"",
					10000);		
		return;
	}
	
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			var result = parseInt(data);
			
			if (result == -1) {
				showMessage("Looks like that email isn't registered.",
							"<a href='javascript:void(0)' onclick='showWelcome();'>Sign up</a> for an account.",
							10000);
				$("#forgot-email").val("").focus();
			} else if (result == 1) {
				showMessage("We've emailed you a link to reset your password.",
							"",
							10000);				
				showLogin();
			} else {
			
			}
			
			debug(data);

		},
		async: true
	});	
	
}





function recordZipcode() {
	debug("> recordZipcode()");


	var zipcode = $("#signup-zipcode").val();

	var isValidZip = /(^\d{5}$)/.test(zipcode);
	
	if (!isValidZip) {
		showMessage("Please enter a valid U.S. zip code.",
					"",
					5000);
		$("#signup-zipcode").val("").focus();
		return;
	}
	
	var urlString = "/update_user_data.php?user_id=" + userID + "&zipcode=" + zipcode + "&token=" + userToken;


	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			
			if (parseInt(data) == 0) {
				showMessage("Please enter a valid U.S. zip code.",
							"",
							5000);
				$("#signup-zipcode").val("").focus();			
			} else {
				debug("Zip code was recorded in the DB as " + zipcode);
				
				var gbCorrect = 1;
				if (cityEdition != parseInt(data)) {
					cityEdition = parseInt(data);
					userEdition = cityEdition;
					gbCorrect = 0;
				}
					
				mpq.track("Zip code successfully recorded", {
					"mp_note": "Zip code successfully recorded for userId " + userID + ", edition " + cityEdition + ", zipcode " + zipcode,
					"GB Correct": gbCorrect,
					"UTM": utm,
					"UserID": userID
				});				

				showCategoryPreferencesScreen();
			}
		},
		async: false
	});


	if (userEdition == 1) {
		var urlString = "/update_user_data.php?user_id=" + userID + "&max_deal_distance=50" + "&token=" + userToken;

		jQuery.ajax({
			type: "GET",
			url: urlString,
			success: function (data) {
			}
		});
	}


}

function showZipcodeScreen() {
	debug("> showZipcodeScreen()");

	$(".signup-flow").fadeOut();

	$("#photo-background").show();

	
	mpq.track("Zip code screen shown", {
		"mp_note": "Zip code screen shown",
		"UTM": utm
	});		

	$("#zipcode-A1").center();
	$("#zipcode-A1").fadeIn();
	
	$("#signup-zipcode").focus();
	
}



function showLogin(showX) {

	if (showX) {
		$("#login-A1").find(".x").show();
	} else {
		$("#login-A1").find(".x").hide();
	}

	debug("> showLogin()");

	$("#top-bar-welcome").show();
	$("#top-bar").hide();
	showNoBar();

	$(".signup-flow").hide();

	$("#photo-background").show();

	$("#login-A1").center();
	$("#login-A1").fadeIn();
	
	if ($("#login-email").val() != "") {
		$("#login-A1").find(".title").html("Have you signed up before?");
		$("#login-password").focus();
	}
	
}



function showCategoryPreferencesScreen() {
	debug("> showCategoryPreferencesScreen()");

	// The user might have been looking at a single-deal view when he decided
	// to sign up, so we have to hide the single-deal-view div which contains
	// the single-deal view.
	$("#single-deal-view").hide();

	// ONLY LOGGED IN USERS SHOULD EVER SEE THIS
	if (!isLoggedIn()) {
		debug("ERROR in showCategoryPreferencesScreen: a non-logged-user should never see the category preferences screen!");
		return;
	}


	// Check if each category is already hidden for this user per the DB. "Per
	// the DB" because EVERY USER WHO SEES THIS SHOULD BE LOGGED IN and
	// therefore has category preferences entries in the DB.
	
	// The category preferences screen should be initialized according to the
	// user's DB categories

	
	$("#category-preferences-A1 .category-preference").each(function() {
		
		var category = parseInt($(this).attr("cat-id"));
		$(this).data("status", "1");

		// If this is NOT visible, x-out the category in the UI
		if (getVisibleCategories().indexOf(category) == -1) {
			setTo0InCatPrefUI(this);
			
		// Otherwise, if this is HEARTED, heart the category in the UI
		} else if (categoriesRank2_DB().indexOf(category) != -1) {
			setTo2InCatPrefUI(this);
		}

		$(this).unbind("mouseenter").mouseenter(function() {
			$(this).poshytip({
				className: 'tip-twitter',
				content: $(this).attr("description"),
				showTimeout: 1,
				alignTo: 'target',
				alignX: 'center',
				offsetY: 5,
				allowTipHover: false,
				fade: false,
				slide: false
			});
			$(this).poshytip('show');
		});

		$(this).unbind("mouseleave").mouseleave(function() {
			$(this).poshytip('hide');
			$(this).poshytip('destroy');
		});
	});
	

	$(".signup-flow").hide();
	$("#photo-background").hide();
	

	$("#category-preferences-A1").show();
}








function saveEmailSettings() {
	debug("> saveEmailSettings()");

	catsToDelete = [];
	catsToSetTo1 = [];
	catsToSetTo2 = [];

	$("#email .category-preference").each(function() {
		if ($(this).data("status") == "0") {
			catsToDelete.push(parseInt($(this).attr("cat-id")));
		} else if ($(this).data("status") == "1") {
			catsToSetTo1.push(parseInt($(this).attr("cat-id")));
		} else if ($(this).data("status") == "2") {
			catsToSetTo2.push(parseInt($(this).attr("cat-id")));
		}
	});
	
	updateCategories("DELETE", catsToDelete);
	updateCategories("SET_TO_1", catsToSetTo1);
	updateCategories("SET_TO_2", catsToSetTo2);

	var zipcode = $("#account-zipcode").val();
	var frequency = parseInt($('input:radio[name=account-frequency]:checked').val());
	maxDealDistance = parseInt($("#account-deal-distance").val());
	
	var urlString = "/update_user_data.php?user_id=" + userID + "&user_edition=" + userEdition + "&zipcode=" + zipcode + "&email_frequency=" + frequency + "&max_deal_distance=" + maxDealDistance + "&token=" + userToken;

	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			if (parseInt(data) == 0) {
					showMessage("Please enter a valid U.S. zipcode.",
								"",
								5000);
				$("#account-zipcode").val("").focus();
			} else {
				debug("Zip code was recorded in the DB as " + zipcode);
				cityEdition = parseInt(data);
				userEdition = cityEdition;
				userZipCode = zipcode;
				setNumberHidden();
				changeView("LIST");
				loadAndDisplay();
			}
		},
		async: false
	});
	
	
}



function unsubscribe() {

	var urlString = "/unsubscribe.php?user_id=" + userID + "&token=" + userToken;

	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
		
			if ($("#reset-password").val() == "") {
				showMessage("You've unsubscribed.",
							"We're sorry to see you go.",
							5000);
			}
			
			$("#settings-unsubscribe").hide();
			
		},
		async: false
	});
}







function recordCategoryPreferences() {
	debug("> recordCategoryPreferences()");

	catsToDelete = [];
	catsToSetTo1 = [];
	catsToSetTo2 = [];

	$("#category-preferences-A1 .category-preference").each(function() {
		if ($(this).data("status") == "0") {
			catsToDelete.push(parseInt($(this).attr("cat-id")));
		} else if ($(this).data("status") == "1") {
			catsToSetTo1.push(parseInt($(this).attr("cat-id")));
		} else if ($(this).data("status") == "2") {
			catsToSetTo2.push(parseInt($(this).attr("cat-id")));
		}
	});
	
	updateCategories("DELETE", catsToDelete);
	updateCategories("SET_TO_1", catsToSetTo1);
	updateCategories("SET_TO_2", catsToSetTo2);	
	
	if (catsToDelete.length > 0 || catsToDelete.length > 0) {
		mpq.track("User submitted category preferences during signup", {
			"mp_note": "User submitted category preferences during signup, userId " + userID + ", number of categories removed " + catsToDelete.length + ", number of categories hearted " + catsToSetTo2.length,
			"Number deleted": catsToDelete.length,
			"Number hearted": catsToSetTo2.length,
			"UTM": utm,
			"UserID": userID
		});
	}
	
	
	if (!(typeof(singleDeal.dealID) === "undefined")) {
		// In this code path, the application is already loaded because the
		// user came directly to single-deal view, so we need not call load()
		$("#single-deal-view").show();
		removeWelcome();
	} else {			
		// In this code path, the user has only been seeing welcome screens,
		// so the application has not yet been loaded
		load();
		setNumberHidden();
		removeWelcome();
	}
	
	

}


function updateCategories(action, cats) {
	debug("> updateCategories(action, cats)");

	if (isLoggedIn()) {
		updateCategories_DB(action, cats);
	} else {
		updateCategories_COOKIE(action, cats);
	}
}

function saveEdition(newEdition) {
	debug("> saveEdition(newEdition)");

	if (isLoggedIn()) {
		saveEdition_DB(newEdition);
	} else {
		saveEdition_COOKIE(newEdition);
	}
}

function getVisibleCategories() {
	if (isLoggedIn()) {
		debug("from DB");
		return categoriesRank1_DB().concat(categoriesRank2_DB()).sort(function(a,b){return a - b});
	} else {
		debug("from COOKIE");
		return categoriesRank1_COOKIE().concat(categoriesRank2_COOKIE()).sort(function(a,b){return a - b});
	}
}

function isVisible(categoryID) {
	if (getVisibleCategories().indexOf(categoryID) != -1) {
		return true;
	} else {
		return false;
	}
}





// UPDATE CATEGORIES

function updateCategories_COOKIE(action, cats) {
	// Get cookie cats into array
	var cookieCats = categories_COOKIE();	
	
	// Add or remove from the array as needed
	$.each(cats, function(index, catID){
		if (action == "SET_TO_1") {
			if (cookieCats.indexOf(catID) == -1) {
				cookieCats.push(catID);
			}
		} else if (action == "DELETE") {
			var i = cookieCats.indexOf(catID);
			if (i != -1) cookieCats.splice(i, 1);
		}
	});

	// Write back to the cookie
	cookieCats.sort(function(a,b){return a - b});
	var cookieString = $.cookie("last_view");
	cookieString = cookieString.replace(/c=[^&]*/, "c=" + cookieCats.toString());
	setLastViewCookie(cookieString);
}

function updateCategories_DB(action, cats) {
	categoriesRank1DBDirtyBit = 1;
	categoriesRank2DBDirtyBit = 1;
	
	if (action == "SET_TO_1") {
		action = "categories_to_set_to_1";
	} else if (action == "DELETE") {
		action = "categories_to_delete";
	} else if (action == "SET_TO_2") {
		action = "categories_to_set_to_2";
	}
	
	var urlString = "/update_category_preferences.php?user_id=" + userID + "&" + action + "=" + JSON.stringify(cats) + "&token=" + userToken;
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
		},
		async: false
	});	
}






// UPDATE EDITION

function saveEdition_DB(newEdition) {
	var urlString = "/update_user_data.php?user_id=" + userID + "&edition=" + newEdition;
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
		},
		async: false
	});		
}

function saveEdition_COOKIE(newEdition) {
	var cookieString = $.cookie("last_view");
	cookieString = cookieString.replace(/i=[0-9]+/, "i=" + newEdition);
	setLastViewCookie(cookieString);
}







// TOGGLE CATEGORIES IN THE UI

function setTo0InCatPrefUI(elem) {
	debug("> setTo0InCatPrefUI(elem)");
	$(elem).data("status", "0");
	
	$(elem).find(".x").attr("src", "/images/cat_x_on.png");
	$(elem).find(".heart").attr("src", "/images/cat_heart_off.png");
	$(elem).find(".check").attr("src", "/images/cat_check_off.png");
	
	$(elem).find(".x-image").show();
	$(elem).find(".heart-image").hide();
}

function setTo1InCatPrefUI(elem) {
	debug("> setTo1InCatPrefUI(elem)");
	$(elem).data("status", "1");

	$(elem).find(".x").attr("src", "/images/cat_x_off.png");
	$(elem).find(".heart").attr("src", "/images/cat_heart_off.png");
	$(elem).find(".check").attr("src", "/images/cat_check_on.png");

	$(elem).find(".x-image").hide();
	$(elem).find(".heart-image").hide();
	
}

function setTo2InCatPrefUI(elem) {
	debug("> setTo2InCatPrefUI(elem)");
	$(elem).data("status", "2");

	$(elem).find(".x").attr("src", "/images/cat_x_off.png");
	$(elem).find(".heart").attr("src", "/images/cat_heart_on.png");
	$(elem).find(".check").attr("src", "/images/cat_check_off.png");

	$(elem).find(".x-image").hide();
	$(elem).find(".heart-image").show();
	
}

function toggleInCatPrefUI(elem) {
	var currentState = parseInt($(elem).data("status"));	
	var fn = "setTo" + ((currentState + 1) % 3) + "InCatPrefUI";
	debug(fn);
	window[fn](elem);
}


// RETURN CATEGORY ARRAYS

function categoriesRank1_DB() {
	var cats = [];

	if (!categoriesRank1DBDirtyBit) {
		cats = categoriesRank1_DB_array.slice();
	} else {
		var urlString = "/get_categories.php?user_id=" + userID + "&rank=1&token=" + userToken;
		jQuery.ajax({
			type: "GET",
			url: urlString,
			success: function (data) {
				cats = $.parseJSON(data);
			},
			async: false
		});
		
		categoriesRank1DBDirtyBit = 0;
		categoriesRank1_DB_array = cats.slice();
	}
	
	return cats;
}

function categoriesRank1_COOKIE() {
	cats = $.cookie("last_view").replace(/\?c=/, "").replace(/&.*/, "").split(",");
	$.each(cats, function(index, value) {
		cats[index] = parseInt(value);
	});
	return cats;
}






function categoriesRank2_DB() {
	var cats = [];

	if (!categoriesRank2DBDirtyBit) {
		cats = categoriesRank2_DB_array.slice();
	} else {
		var urlString = "/get_categories.php?user_id=" + userID + "&rank=2&token=" + userToken;
		jQuery.ajax({
			type: "GET",
			url: urlString,
			success: function (data) {
				cats = $.parseJSON(data);
			},
			async: false
		});
		
		categoriesRank2DBDirtyBit = 0;
		categoriesRank2_DB_array = cats.slice();
	}
	
	return cats;

}


function categoriesRank2_COOKIE() {
	// Cookie users don't have any categories of rank 2. Hearting happens during signup only.
	cats = [];
	return cats;
}

















function signupUser() {
	debug("> signupUser()");

	
	var email = $("#signup-email").val();

	if (!validateEmail(email)) {
		showMessage("Please enter a valid email address.",
					"",
					5000);
		$("#signup-email").val("").focus();
		$("#signup-password").val("");
		return;
	}
	
	var d = new Date();
	var sessionID = d.getTime();
	
	debug("UTM at time of signup: " + utm);
	
	var urlString = "/signup_user.php?email=" + email + "&latitude=" + gbLat + "&longitude=" + gbLng + "&session_id=" + sessionID + "&utm=" + utm + "&referrer=" + referrer;
	
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			debug("signupuser data: " + data);
			
			if (parseInt(data) == 0) {

				mpq.track("Signup attempt with existing email", {
					"mp_note": "Tried to sign up with " + email,
					"City": cityEdition,
					"UTM": utm
				});


				showMessage("Looks like that email is already signed up. Please log in.",
							"Forgot your password? <a onclick='showForgotPassword();' href='javascript:void(0);'>Reset it here</a>.",
							10000);
				$("#login-email").val(email);
				showLogin();
				
			} else if (parseInt(data) == -1) {
			
			
			} else {
			
				var userInfo = eval(data);
			
				if (userInfo.length == 2) {
				
					userToken = userInfo[1];
					userID = userInfo[0];
				
					debug("A new user record has been created. UserID is " + userID);

					mpq.track("User account created", {
						"mp_note": "User account created with userID " + userID,
						"City": cityEdition,
						"UTM": utm
					});

					
					$.cookie('session_cookie', sessionID, {
						expires: 1000,
						path:'/'
					});

					$.cookie('userid_cookie', userID, {
						expires: 1000,
						path:'/'
					});

					initLoggedIn();
					showZipcodeScreen();
				}
				
			}
		},
		async: true
	});
	

}




function fbLogin() {
	debug("> fbLogin()");
	
	FB.login(function(response) {
		debug("> FB.login callback");
		if (response.authResponse) {
			debug('Welcome!  Fetching your information.... ');
			debug("Calling FB.api to retrieve first, last, email, and Facebook ID.");
			FB.api('/me', function(response) {
			
				// We get here when the user is logged in to FB. It is called
				// both when the user ARRIVES to the site already logged in to
				// FB AND when the user comes to the site NOT logged in to FB
				// and then logs in to FB while on the site.
				
				
				// The following WILL NOT ALLOW a duplicate entry to be inserted
				// into the DB, so we don't have to check if this user already
				// exits. We just attempt the save blindly.
				urlString = "/save_user.php?user_id=" + response.id + "&first=" + response.first_name + "&last=" + response.last_name + "&email=" + response.email + "&latitude=" + gbLat + "&longitude=" + gbLng;
				// Save the user to the DB
				jQuery.ajax({
					url: urlString,
					success: function (data) {
						var userInfo = eval(data);
						
						userToken = userInfo[1];
						userID = userInfo[0];
						
						login();
						initLoggedIn();
						
						mpq.track("User manually logged in", {
							"mp_note": "User manually logged in with Facebook account and userID " + userID,
							"Type": "Facebook",
							"UTM": utm,
							"UserID": userID
						});

					},
					async: false
				});
			});
		} else {
			debug('User cancelled login or did not fully authorize.');
		}
	}, {scope:'email'});
}







function login() {
	debug("> login()");

	pushState();

	// Set session ID cookie so the next time the user returns, he's logged
	// in via the cookie. ALSO, send gbLat/gbLng over so the PHP can check if
	// this user has an edition set in the DB. If he doesn't, the PHP will set
	// an edition based on gbLat/gbLng.
	var d = new Date();
	var sessionID = d.getTime();
	var urlString = "/update_user_session_id.php?user_id=" + userID + "&session_id=" + sessionID + "&latitude=" + gbLat + "&longitude=" + gbLng;

	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			$.cookie('session_cookie', sessionID, {
				expires: 10000000,
				path:'/'
			});
			
			$.cookie('userid_cookie', userID, {
				expires: 10000000,
				path:'/'
			});								
		},
		async: false
	});


	$("#login-button").poshytip('hide');

	
	// Checks if we have a DB zipcode for this user and sends him off to the
	// next step accordingly.
	jQuery.ajax({
		url: "/check_has_zipcode.php?user_id=" + userID,
		success: function (data) {
		
			debug("check_has_zipcode says " + data);
		
			if (!parseInt(data)) {
				// User has NOT previously entered a zipcode
				showZipcodeScreen();
			} else {
				// User HAS previously entered a zipcode
				$("#photo-background").hide();
				$(".signup-flow").hide();
				load();
			}
		},
		async: false
	});

	
}





function emailLogin() {
	debug("> emailLogin()");

	
	if ($("#login-email").val() == "" || $("#login-password").val() == "") {
		return;
	}
	
	var password_hash = hex_sha256($("#login-password").val());
	
	var argString = "email=" + $("#login-email").val() + "&password_hash=" + password_hash;

	jQuery.ajax({
		type: "POST",
		url: "/login_user.php",
		data: argString,
		success: function (data) {
		
			debug(data);
		
			var userInfo = eval(data);
		
			if (userInfo.length != 2) {
				showMessage("Oops, wrong password.",
							"Forgot your password? <a onclick='showForgotPassword();' href='javascript:void(0);'>Reset it here</a>.",
							8000);
				$("#login-password").val("").focus();
			} else {			
				userToken = userInfo[1];
				userID = userInfo[0];
				
				debug("XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
				debug("XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
				debug("userToken: " + userToken);
				debug("userID: " + userID);
				debug("XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
				debug("XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
				
				$("#login-div-container").hide();
				
				login();
				initLoggedIn();


				mpq.track("User manually logged in", {
					"mp_note": "User manually logged in with Dealupa account and userID " + userID,
					"Type": "Dealupa",
					"UTM": utm,
					"UserID": userID
				});


			}
		},
		async: true
	});
}	


function fbIsValid() {
	return !(typeof FB === "undefined");
}

function createNewSendButton(element, url, size) {

	if (fbIsValid()) {
		var elem = $(document.createElement("fb:like"));
		elem.attr("href", url);
		elem.attr("send", "true");
		elem.attr("layout", size);
		elem.attr("show_faces", "true");
		$(element).empty().append(elem);
		FB.XFBML.parse($(element).get(0));
	}
}








function debug(string) {
	if (debugging) {
		if (this.console && typeof console.log != "undefined") {
			console.log(string);
		}
	}
}

function myConsoleTime(label) {
	if (BrowserDetect.browser == "Explorer") {
		return;
	} else {
		console.time(label);
	}
}

function myConsoleTimeEnd(label) {
	if (BrowserDetect.browser == "Explorer") {
		return;
	} else {
		console.timeEnd(label);
	}
}






function changeView(newView) {
	debug("> changeView(newView)");
		
	view = newView;
	
	$("#single-category-back").hide();
	hideMessage();
	
	if (newView == "MAP") {

		//////////// USER CHANGED VIEW TO MAP /////////////
		
		$("#refer-a-friend-area").hide();
		$("#refer-a-friend").hide();
		$("#map-list-toggle").show();
		$("#map-view-toggle").addClass("down");
		$("#list-view-toggle").removeClass("down");
		$("#map").show();
		$("#list-view-area").hide();
		$("#right-bar").hide();
		$("#filters-bar").show();
		$("#single-deal-view").hide();
		$("#email-settings").hide();
		$("#hidden-categories").show();
		
		google.maps.event.trigger(listMap, 'resize');
		
		// Every time we show the list view, it is refreshed from the server. So we might as well empty the contents when swithing OUT of list view
		// to increase performance of map view
		$("#list-view").empty();
		
		if (changedEditionWhileInListView) {
			map.setCenter(new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]));
			map.setZoom(cityZoom[cityEdition]);
			changedEditionWhileInListView = 0;
		}

		if (selectedMarkerIndex != -1) {
			markerClick(markersArray[selectedMarkerIndex])();
		}
		
		resizeMap();
		
		showNoMapBar();
		
		if (getVisibleCategories().length < 40) {
			setTimeout('showMessage("Want to see more deals?",\
									"Click \'Settings\' up top to adjust the kind of deals you see.",\
									8000);', 12000);
		}		
		
	} else if (newView == "LIST") {
	
		//////////// USER CHANGED VIEW TO LIST /////////////
		
		$("#refer-a-friend-area").show();
		$("#map-list-toggle").show();
		$("#map").hide();
		$("#email").hide();
		$("#right-bar").hide();
		$("#refer-a-friend").hide();
		$("#hidden-categories").show();
		$("#list-view").show();
		$("#list-view-num-deals").show();
		$("#filters-bar").show();
		$("#list-view-area").show();
		if (query == "") {
			$("#sort-options").show();
		}
		
		$("#map-view-toggle").removeClass("down");
		$("#list-view-toggle").addClass("down");
				
		$("#city-name").html(cities[cityEdition].toLowerCase());
		
		$("#single-deal-view").hide();
		
		showMapBar();
		
		// This will be needed when we reimplement dynamic width based on window
		// size. When you start in gallery view and go to SDV, then you change
		// the window size past the trigger, and then go back to gallery view,
		// the masonry looks messed up. So we call reload on it.
		
		if (initialLoadCompleted) {
			$("#container").masonry('reload');
		}


	} else if (newView == "SINGLE-CATEGORY") {
	
		$("#map-list-toggle").show();
		$("#map").hide();
		$("#email").hide();
		$("#right-bar").hide();
		$("#refer-a-friend-area").show();
		$("#hidden-categories").hide();
		$("#refer-a-friend").hide();
		$("#single-category-back").show();
		$("#list-view").show();
		$("#list-view-num-deals").show();
		$("#filters-bar").show();
		$("#list-view-area").show();
		if (query == "") {
			$("#sort-options").show();
		}
		
		$("#map-view-toggle").removeClass("down");
		$("#list-view-toggle").addClass("down");
				
		$("#city-name").html(cities[cityEdition].toLowerCase());
		
		$("#single-deal-view").hide();
		
		showMapBar();
		
		// This will be needed when we reimplement dynamic width based on window
		// size. When you start in gallery view and go to SDV, then you change
		// the window size past the trigger, and then go back to gallery view,
		// the masonry looks messed up. So we call reload on it.
		
		if (initialLoadCompleted) {
			$("#container").masonry('reload');
		}

	 



	} else if (newView == "SINGLE-DEAL") {

		$("#refer-a-friend-area").show();
		$("#refer-a-friend-area").hide();
		$("#refer-a-friend").hide();
		$("#map-list-toggle").hide();
		$("#single-deal-view").show();
		$("#email").hide();
		$("#map").hide();
		$("#right-bar").hide();
		$("#filters-bar").hide();
		$("#list-view-area").hide();
		$("#city-name").html(cities[cityEdition].toLowerCase());
		
		showNoBar();

	} else if (newView == "EMAIL") {
	
		$("#email").show();
		
		showNoBar();

		
		$("#refer-a-friend-area").hide();
		$("#map-list-toggle").hide();
		$("#refer-a-friend").hide();
		$("#single-deal-view").hide();
		$("#map").hide();
		$("#right-bar").hide();
		$("#filters-bar").hide();
		$("#list-view-area").hide();
		
		$("#city-name").html(cities[cityEdition].toLowerCase());
		
		if (cityEdition == 1) {
			 $("#account-deal-distance").val("25");
			 $("#account-deal-distance option[value=0]").hide();
		}
	
	} else if (newView == "REFER") {
	
		$("#refer-a-friend").show();
		
		showNoBar();
		
		$("#refer-a-friend-area").hide();
		$("#map-list-toggle").hide();
		$("#email").hide();
		$("#single-deal-view").hide();
		$("#map").hide();
		$("#right-bar").hide();
		$("#filters-bar").hide();
		$("#list-view-area").hide();
		
		$("#city-name").html(cities[cityEdition].toLowerCase());
		
	
	}

	
	
}
 


function loadAndDisplayEmail() {
	debug("> loadAndDisplayEmail()");

	$(document).scrollTop(0);


	// Get the data from the database: they are frequency and zip code.
	
	var urlString = "/get_user_data.php?user_id=" + userID + "&token=" + userToken;
	var userInfo;
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			userInfo = $.parseJSON(data);
		},
		async: false
	});

	$("#account-zipcode").val(userInfo["zipcode"]);
	
	$("#account-deal-distance").val(userInfo["max_deal_distance"]);
	
	debug("This user's frequency as recorded in DB: " + userInfo["email_frequency"]);
	debug("This user's zip code as recorded in DB: " + userInfo["zipcode"]);
	debug("This user's visible categories: " + getVisibleCategories());
	
	// Set up the category prefs icons
	
	$("#email .category-preference").each(function() {
	
		var category = parseInt($(this).attr("cat-id"));
		$(this).data("status", "1");
		
		// If this is NOT visible, x-out the category in the UI
		if (getVisibleCategories().indexOf(category) == -1) {
			setTo0InCatPrefUI(this);
			
		// Otherwise, if this is HEARTED, heart the category in the UI
		} else if (categoriesRank2_DB().indexOf(category) != -1) {
			setTo2InCatPrefUI(this);
		}
		
		$(this).unbind("mouseenter").mouseenter(function() {

			$(this).poshytip({
				className: 'tip-twitter',
				content: $(this).attr("description"),
				showTimeout: 1,
				alignTo: 'target',
				alignX: 'center',
				offsetY: 5,
				allowTipHover: false,
				fade: false,
				slide: false
			});
			$(this).poshytip('show');
		});
		
		$(this).unbind("mouseleave").mouseleave(function() {
			$(this).poshytip('hide');
			$(this).poshytip('destroy');
		});

	});
	
	
	
	
	if (userInfo["email_frequency"] == 604800) {
		$('input[name=account-frequency]:eq(2)').attr('checked', 'checked');
	} else if (userInfo["email_frequency"] == 259200) {
		$('input[name=account-frequency]:eq(1)').attr('checked', 'checked');
	} else {
		$('input[name=account-frequency]:eq(0)').attr('checked', 'checked');
	}


	showMessage("Here's where you decide what type of deals you'll see in your email and on the website.",
				"",
				8000);

	
}



function selectFrequencyButton(elem) {
	$("#email .frequency-button").each(function() {
		$(this).removeClass("down");
	});
	$(elem).addClass("down");
}







function updateFiltersUIBasedOnGlobals() {
	debug("> updateFiltersUIBasedOnGlobals()");


	setNumberHidden();
	
	setYelp(showYelp);
	
	$("#filter-company").val(showCompany);
	

}







function loadAndDisplay(doNotReloadList) {
	debug("> loadAndDisplay(doNotReloadList)");

	updateFiltersUIBasedOnGlobals();

	if (view == "MAP") {
		loadAndDisplayMarkers();
	} else if (view == "LIST") {
		if (doNotReloadList) {
		} else {
			loadAndDisplayDeals();
		}
		$(document).scrollTop(0);
	} else if (view == "SINGLE-CATEGORY") {
		loadAndDisplayDeals();
	} else if (view == "SINGLE-DEAL") {
		loadAndDisplaySingleDeal(mapViewDealID, cityEdition);
	} else if (view == "EMAIL") {
		loadAndDisplayEmail();
	}
}








function applySlidersToListView() {
	debug("> applySlidersToListView()");

	var divs = $("#container > div");
	for (var i = 0; i < divs.length; i++) {									
		var id = parseInt(divs[i].getAttribute("id-at"));
	
		$('#slider-' + id).anythingSlider({
			hashTags: false,
			buildArrows: true,
			buildNavigation: false,
			buildStartStop: false,
			resizeContents: false,
			autoPlay: false,
			autoPlayLocked: true,
			pauseOnHover: true,
			resumeDelay: 3000
		});
	}
}									





function loadAndDisplayDeals(appendItems) {
	debug("> loadAndDisplayDeals(appendItems)");

	
	if(!appendItems) {
		pages = 1;
	}

	var prefix = "";
		
	// LIST VIEW
	if ((view == "LIST" || view == "SINGLE-CATEGORY") && query == "") {		
		dealXML = "/deal_html_from_url_params.php" + currentStateAsString() + "&p=" + pages;
		if (currentSortBy == "DISTANCE") {
			dealXML += "&z=" + zip;
		}
		
		dealXML += "&uz=" + userZipCode;
		
	// SEARCH RESULTS
	} else if (view == "LIST" && query != "") {
		dealXML = "/deal_html_from_url_params.php" + currentStateAsString() + "&p=" + pages;
		dealXML += "&uz=" + userZipCode;
	}
	
	
	debug(dealXML);

	if (!appendItems) {
		$("#loading-div").center().show();
	}
	
	
		jQuery.ajax({
			type: "GET",
			url: dealXML,
			success: function (data) {
				
				
				
				
				if (!appendItems) {
					$('#container').empty();
				}
				
				var $boxes = $(data);
				
				$boxes.imagesLoaded(function(){
				
					

					var $container = $('#container');
					
					
					
					$boxes.find("img.deal-image").each(function () {
						var w = this.width;
						var h = this.height;
						
						if (w != 229) {
							this.style.width = columnWidth + "px";
							this.style.height = "auto";
						}
						
						/*
						if (w/h > 2.5) {
							this.style.width = "470px";
							this.style.height = "auto";
						
							$(this).closest(".top-div").css("width", "470px");
						
						}
						*/
						
					});
					
					
					$container.append($boxes).masonry('appended', $boxes /* Not adding true because it creates odd gaps in the pages... */);

					$container.masonry({
						itemSelector : '.box',
						gutterWidth : columnSpacing,
						isAnimated: false,
						isFitWidth: true
					});
					
					// applySlidersToListView();

					// Not sure why, but overflows are set to hidden by Masonry
					// so reseting so we see shadows properly.
					$container.css({ "overflow": "visible"});
					
					$container.find(".box").each(function() {
						$(this).removeClass("box-focus");
						$(this).find(".expansion").hide();
					});

					
					$container.masonry('reload');
					
					var numDeals = parseInt($("#list-view-data").attr("num-deals"));
					
					if (maxDealDistance == 0 || cityEdition != userEdition) {
						$("#" + prefix + "list-view-sorted-by").html(" - " + numDeals + " deals");
					} else {
						var optionalS = "";
						if (maxDealDistance > 1) {
							optionalS = "s";
						}
						$("#" + prefix + "list-view-sorted-by").html(" - " + numDeals + " deals within <b><a href='javascript:void(0);' onclick='changeView(\"EMAIL\"); loadAndDisplayEmail(); pushState();'>" + maxDealDistance + " mile" + optionalS + " </a></b> of " + userZipCode);
					}
					
					
					
					
					registerListViewCallBacks();
					
					$("#loading-div").hide();
					
					setTimeout("infiniteScrollTimeout = 1", 100);
					
					google.maps.event.trigger(listMap, 'resize');
	
					
					$("#loading-div").center().hide();
					
					
				});

			},
			async: true
		});
		
	


}


function expandDeal() {

	clearTimeout(galleryMapTimeout);
	$("#gallery-map-container").stop().css("opacity", "1.0");

	var id = parseInt($(this).closest(".top-div").attr("id-at"));

	$(this).addClass("box-focus");
	$(this).find(".expansion").show();
	$("#fb-buttons-" + id).hide();

	var lat = this.getAttribute("lat-at");
	var lng = this.getAttribute("lng-at");
	
	if (lat != "") {
		debug("lat: " + lat + " lng: " + lng);		
		listMap.setCenter(new google.maps.LatLng(lat, lng));		
		listMarker.position = new google.maps.LatLng(lat, lng);
		listMarker.setMap(listMap);
		$("#gallery-map-container").show();
		google.maps.event.trigger(listMap, 'resize');

	} else {
		$("#gallery-map-container").hide();
	}
	
	
	var url = $(this).closest(".top-div").attr("url-at");
	url = "http://dealupa.com" + url;

	// Check if it's less than 35 because we don't want to keep reloading the send button if it's already there
	if ($("#fb-buttons-" + id).html().length < 35) {
		createNewSendButton("#fb-buttons-" + id, url + "?utm=facebook_share", "button_count");
	}

	$("#fb-buttons-" + id).fadeIn();
	
}

function collapseDeal() {

	$(this).removeClass("box-focus");
	$(this).find(".expansion").hide();
	
	if (!galleryMapBlocker) {
		galleryMapTimeout = setTimeout('$("#gallery-map-container").fadeOut()', 750);
	}
}



function showNoMapBar() {
	debug("> showNoMapBar()");
	$("#gallery-map-panel").hide();
	$("#bottom-bar").show();
}

function showMapBar() {
	debug("> showMapBar()");
	$("#gallery-map-panel").show();
	$("#bottom-bar").show();
	google.maps.event.trigger(listMap, 'resize');
}

function showNoBar() {
	debug("> showNoBar()");
	$("#gallery-map-panel").hide();
	$("#bottom-bar").hide();
}











function registerListViewCallBacks() {
	debug("> registerListViewCallBacks()");

	var divs = $("#container > div");
	
	
	var re = /[0-9]5/;
	
	for (var i = 0; i < divs.length; i++) {
		var div = divs[i];	
		
		var lat = div.getAttribute("lat-at");
		var lng = div.getAttribute("lng-at");
		var id = div.getAttribute("id-at");
		var category_id = div.getAttribute("cat-at");
		var company = div.getAttribute("cmp-at");
		var yelp = div.getAttribute("y-at");
		var price = div.getAttribute("p-at");
		var value = div.getAttribute("v-at");

				
		var config = {
			 over: expandDeal, // function = onMouseOver callback (REQUIRED)    
			 // timeout: 500, // number = milliseconds delay before onMouseOut    
			 out: collapseDeal // function = onMouseOut callback (REQUIRED)    
		};

		$(div).unbind("mouseenter").unbind("mouseleave");
		$(div).hoverIntent(config);
			
		$(div).find("#details-" + id).unbind('click').click(function () {
		
			var id = parseInt($(this).closest(".top-div").attr("id-at"));
			var category_id = $(this).closest(".top-div").attr("cat-at");
			var company = $(this).closest(".top-div").attr("cmp-at");
			var yelp = parseFloat($(this).closest(".top-div").attr("y-at"));
			var price = parseInt($(this).closest(".top-div").attr("p-at"));
			var value = parseInt($(this).closest(".top-div").attr("v-at"));

			mpq.track("Clicked to external deal site", {
				"mp_note": "Clicked on " + id + ", company " + companies[company],
				"Company": companies[parseInt(company)],
				"Category": categories[parseInt(category_id)],
				"Yelp": yelp,
				"City": cities[cityEdition],
				"Price": price,
				"Value": value,
				"View": "LIST-button",
				"Sort": currentSortBy,
				"UserID": userID
			});


			
		});		

		$(div).find("#image-link-" + id).unbind('click').click(function () {
		
			var id = parseInt($(this).closest(".top-div").attr("id-at"));
			var category_id = $(this).closest(".top-div").attr("cat-at");
			var company = $(this).closest(".top-div").attr("cmp-at");
			var yelp = parseFloat($(this).closest(".top-div").attr("y-at"));
			var price = parseInt($(this).closest(".top-div").attr("p-at"));
			var value = parseInt($(this).closest(".top-div").attr("v-at"));

			mpq.track("Clicked to external deal site", {
				"mp_note": "Clicked on " + id + ", company " + companies[company],
				"Company": companies[parseInt(company)],
				"Category": categories[parseInt(category_id)],
				"Yelp": yelp,
				"City": cities[cityEdition],
				"Price": price,
				"Value": value,
				"View": "LIST-image",
				"Sort": currentSortBy,
				"UserID": userID
			});
		
		});
	}
	
	
	

	if (view == "LIST") {
		if (query == "") {
		    if (cityEdition == 2) {
		        $("#list-view-num-deals").html("Nationwide deals");
			} else if (cityEdition != 1) {
				$("#list-view-num-deals").html("Deals in " + cities[cityEdition]);
			} else {
				$("#list-view-num-deals").html("Deals near " + userZipCode);
			}
		} else if (query != "") {
			$("#list-view-num-deals").html("Deals for <b>" + query + "</b> in " + cities[cityEdition]);
		}
	} else if (view == "SINGLE-CATEGORY") {
		$("#list-view-num-deals").html(categories[singleCategory] + " deals in " + cities[cityEdition]);
		$("#single-category-back").html("&#171; Back to all deals in " + cities[cityEdition]);
	}

	currentSortBy == "SOLD" ? sortByString = "number sold" : sortByString = currentSortBy.toLowerCase();	
	
	
	$("#sort" + currentSortBy).addClass("sort-on");
	

	
}









function showSingleCategory(categoryToShow) {
	changeView('SINGLE-CATEGORY');
	$(document).scrollTop(0);
	singleCategory = categoryToShow;
	loadAndDisplay();
	pushState();
	
	showMessage("These are <i>just</i>&nbsp;&nbsp;the " + categories[categoryToShow] + " deals.",
				"Go back to <a href='javascript:void(0);' onclick='changeView(\"LIST\");loadAndDisplay(0);pushState();'>all deals</a> in " + cities[cityEdition] + ".",
				20000);	
	
}



function openDealInSingleDealView(dealToDisplay, edition) {
	debug("> openDealInSingleDealView(dealToDisplay, edition)");

	listScrollPosition = $(document).scrollTop();
	mapViewDealID = dealToDisplay;
	changeView("SINGLE-DEAL");
	loadAndDisplaySingleDeal(dealToDisplay, edition);
}




function singleDealPostProcessing() {
	debug("> singleDealPostProcessing()");


	$("#slider").imagesLoaded(function() {
	
		$('#slider').anythingSlider({
			hashTags: false,
			buildArrows: true,
			buildNavigation: false,
			buildStartStop: false,
			resizeContents: false,
			autoPlay: true,
			autoPlayLocked: true,
			pauseOnHover: true,
			resumeDelay: 1000
		});
						
		
		$("#single-deal-left").css({ 'opacity' : 1 })
		
	});

	singleDeal.maintitle = $("#single-deal-data").attr("title");
	singleDeal.dealID = $("#single-deal-data").attr("deal_id");
	singleDeal.categoryID = $("#single-deal-data").attr("category_id");
	singleDeal.imageUrls = eval($("#single-deal-data").attr("image_arr_str"));		
	singleDeal.edition = eval($("#single-deal-data").attr("deal_edition"));
	singleDeal.cities = eval($("#single-deal-data").attr("cities_arr_str"));
	singleDeal.lats = eval($("#single-deal-data").attr("lats_arr_str"));
	singleDeal.lngs = eval($("#single-deal-data").attr("lngs_arr_str"));	
	singleDeal.fullUrl = $("#single-deal-data").attr("full_url");

	$("#city-name").html(cities[cityEdition].toLowerCase());
		
	$(document).scrollTop(0);
	
}




function loadAndDisplaySingleDeal(dealIDToDisplay, edition) {
	debug("> loadAndDisplaySingleDeal(dealIDToDisplay, edition)");
	
	/*
	if (!initialLoadCompleted && !isLoggedIn()) {
		mpq.track("Welcome screen shown", {
			"mp_note": "Direct load of single deal view",
			"Origin": "Direct load of single deal view",
			"UTM": utm
		});

		setTimeout('showWelcome(0)', 1700);

		setTimeout('showMessage("We\'ll get to your deal in just a few seconds!",\
								"Signing into Dealupa lets us show you <i>only</i>&#160;&#160;the deals you want (and hide the rest).",\
								12000);', 3500);



	}
	*/
	
	selectedMarkerImageIndex = 0;
	
	var imageIsLandscape = 0;
	
	if (!initialLoadCompleted) {
		singleDealPostProcessing();
		debug("Returning from loadAndDisplaySingleDeal because the HTML is in the index.php file");
		return;
	}

	if (document.getElementById("image-" + dealIDToDisplay) == null) {
		// If we're here, loadAndDisplaySingleDeal is being called while the user
		// is going back in his browser history. In this case, the above image
		// will be null, so we get the aspect ratio from PHP.
		var urlString = "/is_landscape.php?ajax&deal_id=" + dealIDToDisplay;
		jQuery.ajax({
			type: "GET",
			url: urlString,
			success: function (data) {
				imageIsLandscape = parseInt(data);
			},
			async: false
		});
	} else {
		// Here, we're loading loadAndDisplaySingleDeal from the gallery view
		var w = document.getElementById("image-" + dealIDToDisplay).width;
		var h = document.getElementById("image-" + dealIDToDisplay).height;
		
		if (w/h > 1) {
			imageIsLandscape = 1;
		}
		
	}
	
	
	var urlString;
	
	if (!imageIsLandscape) {
		debug("PORTRAIT SINGLE DEAL");
		urlString = "/single_deal_html_portrait.php?logged_in=1&m=" + dealIDToDisplay + "&i=" + edition;
	} else {
		debug("LANDSCAPE SINGLE DEAL");
		urlString = "/single_deal_html_landscape.php?logged_in=1&m=" + dealIDToDisplay + "&i=" + edition;
	}
	

	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {		
			$("#single-deal-view").html(data);
			singleDealPostProcessing();
		},
		async: false
	});
	

	
	return;

}











function currentStateAsString() {
	debug("> currentStateAsString()");
	
	var currentView;

	if (view == "SINGLE-DEAL") {
		currentView = "?m=" + mapViewDealID +
					  "&i=" + cityEdition +
					  "&v=" + view;
	}


	if (view == "SINGLE-CATEGORY") {
		currentView = "?c=" + singleCategory +
					  "&y=" + showYelp + 
					  "&i=" + cityEdition +
					  "&v=" + view +
					  "&s=" + currentSortBy +
					  "&d=" + maxDealDistance;
	}

	
	if (view == "LIST") {
		currentView = "?c=" + getVisibleCategories() + 
					  "&y=" + showYelp + 
					  "&o=" + showCompany + 
					  "&i=" + cityEdition +
					  "&e=" + userEdition +
					  "&v=" + view +
					  "&s=" + currentSortBy +
					  "&d=" + maxDealDistance;

		if (currentSortBy == "DISTANCE") {
			currentView += "&z=" + zip;
		}
	}	
	
	
	if (view == "MAP") {
		currentView = "?t=" + trueRound(map.getCenter().lat(), 4) + 
					  "&g=" + trueRound(map.getCenter().lng(), 4) + 
					  "&z=" + map.getZoom() + 
					  "&c=" + getVisibleCategories() + 
					  "&y=" + showYelp + 
					  "&o=" + showCompany + 
					  "&v=" + view +
					  "&m=" + mapViewDealID +
					  "&d=" + maxDealDistance;
	}
	
	if (view == "EMAIL") {
		currentView = "?v=" + view +
					  "&u=" + userID;	
	}	
	
	if (query != "" && (view == "MAP" || view == "LIST")) {
		currentView += "&q=" + query;
	}
	
	return currentView;

}





function findMarkersArrayIndex(id) {
	for (var i = 0; i < markersArray.length; i++) {
		if (markersArray[i].dealID == id) {
			return i;
		}
	}
	return -1;
}




function parseStateString(params) {
	debug("> parseStateString(params)");
	
	var iParam = getParameter('i', params);
	var cParam = getParameter('c', params);
	var vParam = getParameter('v', params);
	var mParam = getParameter('m', params);
	var qParam = getParameter('q', params);
	var wParam = getParameter('w', params);
	var utmParam = getParameter('utm', params);

	if (iParam != "null") {
		var re = /^[0-9]+$/;
		
		// If iParam is a number...
		if (re.test(iParam)) {
			cityEdition = parseInt(iParam);

		// Else, if iParam is a string like "seattle"
		} else {
			if (citiesReverse[iParam] != null) {
				cityEdition = citiesReverse[iParam];
			} else {
				cityEdition = calculateCityEditionFromLatLng(gbLat, gbLng);
			}
		}

		map.setZoom(parseInt(cityZoom[cityEdition]));
		map.setCenter(new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]));

	}
	
	if (vParam != "null") {
		view = vParam;
	}
	
	if (mParam != "null") {
		mapViewDealID = parseInt(mParam);
	}
	
	if (qParam != query && !(qParam == "null" && query == "")) {
		// We need to force the reload of markers and deals since the query has changed
		currNELng = -1;
	}

	query = "";
	//$("#top-bar-search-box").val("");

	if (qParam != "null") {
			
		if (categoriesReverse[qParam] != null) {
			//categories_FE = eval("[" + categoriesReverse[qParam] + "]");
		} else {
			query = qParam;
			query = query.replace(/-/g, ' ') ;
			$("#top-bar-search-box").css("width", "115px");
			$("#top-bar-search-box").val(query);
			$("#clear-search-button").show();
			
		}
	}
	
	if (vParam == "null" && mParam != "null") {
		view = "SINGLE-DEAL";
	}

	if (wParam != "null") {
		welcomeScreen = wParam;
	}	

	if (utmParam != "null") {
		utm = utmParam + "_" + welcomeScreen;
	}
}







function setLastViewCookie(newCookieValue) {
	debug("> setLastViewCookie(newCookieValue)");

	debug("Setting the value of the last_view cookie to: " + newCookieValue);
	$.cookie('last_view', newCookieValue, {
		expires: 1000,
		path:'/'
	});
	
	$.cookie('timestamp', (new Date()).getTime(), {
		expires: 1000,
		path:'/'
	});
}



// One-time initializations to do if there is a logged in user

function initLoggedIn() {
	debug("> initLoggedIn()");

	// Add "logged in links" to the top bar
	$("#top-bar-links").html("\
		<a href='javascript:void(0);' onclick='changeView(\"EMAIL\"); loadAndDisplayEmail(); pushState();'>\
			Settings\
		</a>\
	");
	
	categoriesRank1DBDirtyBit = 1;
	categoriesRank2DBDirtyBit = 1;
	
	// Get the user's name (or email) and put it in the top bar
	jQuery.ajax({ 
		url: "/get_email.php?user_id=" + userID + "&token=" + userToken,
		success: function (data) {
			$("#top-bar-user-id").html(data);
			userName = data;
		},
		async: false
	});	
	
	$("#login-button").poshytip('hide');
	$("#login-div-container").hide();
	$("#login-button").hide();
	$("#signup-button").hide();
	$("#logout-button").show();
	
	$("#login-password").val("");
	
	jQuery.ajax({
		url: "/update_user.php?user_id=" + userID,
		success: function (data) {	
			debug("User's visit count, last seen date, and session created date have been update in the DB [[initLoggedIn]].");
		},
		async: false
	});
	

}




function clearYelp() {
	$('#yelp-reviews').hide();
}

function clearYelpList() {
	$('#list-yelp-reviews').hide();
}

function getParameter(name, paramString) {
	return decodeURI((RegExp('[?|&]' + name + '=' + '(.+?)(&|$)').exec(paramString) || [, null])[1]);
}



function changeEdition(newEdition) {
	debug("> changeEdition(newEdition)");
	
	if (view != "MAP") {
		view = "LIST";
		changeView(view);
	}


	// Saves the edition to the store UNLESS the user is logged in. If the user
	// is logged in, the edition field in the DB applies to the EMAIL being sent
	// so we don't want to change that unless the user explicitly asks us.
	if (!isLoggedIn()) {
		saveEdition(newEdition);
	}
	
	currentSortBy = "SOLD";
	highlightSortLink();
	
	$("#dealupa-title").poshytip('hide');
	
	cityEdition = newEdition;
	
	$("#city-name").html(cities[cityEdition].toLowerCase());
	$("#list-view-num-deals").html("Loading deals in " + cities[cityEdition] + "...");
	$("#list-view-sorted-by").empty();
	
	if (view == "LIST") {
		changedEditionWhileInListView = 1;
		loadAndDisplay();
	} else if (view == "MAP") {
		map.setZoom(cityZoom[cityEdition]);		
		map.setCenter(new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]));
		// Markers will be automatically loaded and displayed by the map's idle callback
	} else if (view == "SINGLE-DEAL") {
		map.setCenter(new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]));
		// If user is seeing a deal and changes edition, go to the LIST view
		changeView("LIST");
		loadAndDisplay();
	} else if (view == "SINGLE-CATEGORY") {
		view = "LIST";
		loadAndDisplay();
	}
}



function getSingleDealURLRelative(edition, id, title) {
	var url = "/" + cities_url[edition] + "/daily-deals/" + id + "-" + hyphenateTitle(title);
	return url;
}

function getSingleDealURLFull(edition, id, title) {
	var url = getSingleDealURLRelative(edition, id, title);
	url = "http://dealupa.com" + url;
	return url;
}

function pushState() {

	var stateObject = { stateParams: currentStateAsString() };
	
	debug("*********** PUSH ************");
	debug(currentStateAsString());
	debug("*****************************");
	
	if (BrowserDetect.browser != "Explorer") {
		if (view == "SINGLE-DEAL") {
			var urlTitle = getSingleDealURLRelative(singleDeal.edition, singleDeal.dealID, singleDeal.maintitle);
			history.pushState(stateObject, "", urlTitle);
			historyObject.push(stateObject);
		} else {
			history.pushState(stateObject, "", "/");
			historyObject.push(stateObject);
		}
	}
}



function hyphenateTitle(urlTitle) {

	// !!! IMPORTANT
	// ANY CHANGES MADE HERE MUST ALSO BE MADE IN hyphenate_title IN helpers.php

	urlTitle = urlTitle.toLowerCase();
	urlTitle = urlTitle.replace(/\b[a-z]\b/g,"");
	urlTitle = urlTitle.replace(/\b[a-z][a-z]\b/g,"");
	urlTitle = urlTitle.replace(/[0-9] (value|regular)\b/g,"");
	urlTitle = urlTitle.replace(/[0-9] (towards|spend)\b/g,"");
	urlTitle = urlTitle.replace(/\b(for|the|and|are|but|you|reg|your|more)\b/g,"");
	urlTitle = urlTitle.replace(/&[0-9a-z]+;/g,"-");
	urlTitle = urlTitle.replace(/[^a-z0-9\-]/g, '-');
	urlTitle = urlTitle.replace(/\b[0-9]+\b/g,"-");
	urlTitle = urlTitle.replace(/ /g,"-");
	urlTitle = urlTitle.replace(/-+/g,"-");
	urlTitle = urlTitle.replace(/deal-ends-soon/g,"");
	urlTitle = urlTitle.replace(/^-/g,"");
	urlTitle = urlTitle.replace(/-$/g,"");
	
	if (urlTitle == "") {
		urlTitle = "deal";
	}
	
	return urlTitle;
}

function referAFriend() {

	var validEmails = [];
	var emailText = $("#email-text").val();

	// First, check the inputs
	
	$("#refer-inputs").children().each(function() {
		if (validateEmail($(this).val())) {
			validEmails.push($(this).val());
		}
	});

	debug(JSON.stringify(validEmails));
	
	var urlString = "/refer_a_friend.php?user_id=" + userID + "&emails=" + JSON.stringify(validEmails) + "&email_text=" + emailText + "&token=" + userToken;
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			var userInfo = eval(data);
			
			if (userInfo.length > 0) {
				changeView('LIST');
				showMessage("Nice, you've invited your friends.",
							"Thanks for spreading the word about Dealupa!",
							5000);
				loadAndDisplay();
			}
		},
		async: false
	});	
	
}


function resizeMap() {
	// debug("> resizeMap()");
	

	$("#map").height($(window).height() - $("#top-bar").outerHeight());
	$(".signup-flow.wooden").each(function() { $(this).center(); });
	$("#loading-div").center();
	$("#popup-refer-a-friend").center();

	if (!(typeof(map.getCenter) === "undefined")) {
		var lat = map.getCenter().lat();
		var lng = map.getCenter().lng();

		google.maps.event.trigger(map, 'resize');

		if (lat != "" && lng != "") {
			map.setCenter(new google.maps.LatLng(lat, lng));
		}
	}

	var bigWidth = (numPerColumnBig * columnWidth) + ((numPerColumnBig - 1) * columnSpacing);
	var smallWidth = (numPerColumnSmall * columnWidth) + ((numPerColumnSmall - 1) * columnSpacing);
		
	if ($(window).width() > 1300) {
		$("#list-view-area").css("width", bigWidth + "px");
		$("#refer-a-friend-area").css("width", bigWidth + "px");
		$("#bottom-bar-content").css("margin-left", (bigWidth / -2) + "px");
		$("#top-bar-content").css("width", bigWidth + "px");
		$("#bottom-bar-content").css("width", bigWidth + "px");
	} else {
		$("#list-view-area").css("width", smallWidth + "px");
		$("#refer-a-friend-area").css("width", smallWidth + "px");
		$("#bottom-bar-content").css("margin-left", (smallWidth / -2) + "px");
		$("#top-bar-content").css("width", smallWidth + "px");
		$("#bottom-bar-content").css("width", smallWidth + "px");
	}	
	
	
	/*
	delay(function(){

		if ($(window).width() > 1300) {
			$("#top-bar-content").animate({width: '1201px'}, 400, 'easeOutQuart');
			$("#bottom-bar-content").animate({width: '1201px'}, 400, 'easeOutQuart');
			//$("#single-deal-view").animate({width: '1201px'}, 400, 'easeOutQuart');
			
			
			
		} else {
			$("#top-bar-content").animate({width: '958px'}, 400, 'easeOutQuart');
			$("#bottom-bar-content").animate({width: '958px'}, 400, 'easeOutQuart');
			//$("#single-deal-view").animate({width: '958px'}, 400, 'easeOutQuart');
		}
		
		google.maps.event.trigger(listMap, 'resize');
	
    }, 500);
	*/

	

	$("#top-bar-content-table").show();
	$("#bottom-bar-content").show();
}




function selectOnMap(dealID) {
	var index = findDealInMarkersArray(dealID);
	changeView("MAP");
	markerClick(markersArray[index])();
	map.panTo(new google.maps.LatLng(markersArray[index].position.lat(), markersArray[index].position.lng()));
}




function findDealInMarkersArray(dealID) {
	for (i in markersArray) {
		if (markersArray[i].dealID == dealID) {
			return i;
		}
	}
}

function showNextImage() {
	var imageArrLength = markersArray[selectedMarkerIndex].imageUrls.length;
	selectedMarkerImageIndex = (selectedMarkerImageIndex + 1) % imageArrLength;
	$("#left-image").attr("src", markersArray[selectedMarkerIndex].imageUrls[selectedMarkerImageIndex]);
}

function showPrevImage() {
	var imageArrLength = markersArray[selectedMarkerIndex].imageUrls.length;
	selectedMarkerImageIndex = (selectedMarkerImageIndex - 1 + imageArrLength) % imageArrLength;
	$("#left-image").attr("src", markersArray[selectedMarkerIndex].imageUrls[selectedMarkerImageIndex]);
}

function showNextImageSingle() {
	var imageArrLength = singleDeal.imageUrls.length;
	selectedMarkerImageIndex = (selectedMarkerImageIndex + 1) % imageArrLength;
	$("#single-image").attr("src", singleDeal.imageUrls[selectedMarkerImageIndex]);
}

function showPrevImageSingle() {
	var imageArrLength = singleDeal.imageUrls.length;
	selectedMarkerImageIndex = (selectedMarkerImageIndex - 1 + imageArrLength) % imageArrLength;
	$("#single-image").attr("src", singleDeal.imageUrls[selectedMarkerImageIndex]);
}



function loadAndDisplayMarkers() {
	debug("> loadAndDisplayMarkers()");


	var bounds = map.getBounds();

	if (bounds != null) {
		var swPoint = bounds.getSouthWest();
		var nePoint = bounds.getNorthEast();
	} else {
		debug("--returning without reloading markers 1");
		return;
	}
	
	if (currNELng != -1 && 
	    currSWLat < swPoint.lat() && 
		currSWLng < swPoint.lng() && 
		nePoint.lat() < currNELat && 
		nePoint.lng() < currNELng && query == "") {
		displayMarkers();
		
		debug("--returning without reloading markers 2");
		return;
	}

	// We will load deals for a bounding box that is double the current
	// view port's size so that later if the user moves the map just a
	// small amount, we won't need to do a reload from the database.
	var mySWLat = swPoint.lat() - ((nePoint.lat() - swPoint.lat()));
	var mySWLng = swPoint.lng() - ((nePoint.lng() - swPoint.lng()));
	var myNELat = nePoint.lat() + ((nePoint.lat() - swPoint.lat()));
	var myNELng = nePoint.lng() + ((nePoint.lng() - swPoint.lng()));

	var markerXML = "/marker_xml.php?swLat=" + mySWLat + "&swLng=" + mySWLng + "&neLat=" + myNELat + "&neLng=" + myNELng;
	
	if (query != "") {
		markerXML += "&q=" + query;
	}

	//if (cityEdition == VACATIONS_CITY_ID) {
	//	markerXML = markerXML + "&category_id=9";
	//}
	
	debug("reloading markers: " + markerXML);

	$("#loading-div").center().show();
	
	
	jQuery.ajax({
		url: markerXML,
		success: function (data) {
			// First, delete all markers that are already on the map.
			selectedMarkerIndex = 0;
			deleteOverlays(markersArray);
			
			debug("Deleted markersArray, whose length is now " + markersArray.length);
			
			// Go through each XML marker j keeps track of each
			// displayed marker's index in the global markersArray
			// array.
			var markers = data.documentElement.getElementsByTagName("marker");

		    // We keep an object whose properties will store all the titles we've seen. We will throw away markers which have titles we've already seen.
			// This is our quick and dirty way of handling duplicates in the frontend. FUCK YEAH!
		    var seenTitles = new Object;

			for (var i = 0; i < markers.length; i++) {
			
				var roundedLat = trueRound(markers[i].getAttribute("latitude"), 2);
				var roundedLng = trueRound(markers[i].getAttribute("longitude"), 2);
				
				var key = markers[i].getAttribute("company_id") + ":" + roundedLat + ":" + roundedLng + ":" + markers[i].getAttribute("title");
				
			    if (markers[i].getAttribute("title") != "" &&
					seenTitles.hasOwnProperty(key)) {
					continue;
			    } else {
					seenTitles[key] =1;
			    }

				var latlng = new google.maps.LatLng(parseFloat(markers[i].getAttribute("latitude")), parseFloat(markers[i].getAttribute("longitude")));

				/*
				var expired_e;
				if (isExpired(mysqlTimeStampToDate(markers[i].getAttribute("deadline"))) == 1) {
					expired_e = "e";
				} else {
					expired_e = "";
				}
				*/

				var yelp_rating;
				if (markers[i].getAttribute("yelp_rating") == "") {
					yelp_rating = -1;
				} else {
					yelp_rating = parseFloat(markers[i].getAttribute("yelp_rating"));
				}

				var marker = new google.maps.Marker({
				
					dealID: parseInt(markers[i].getAttribute("id")),

					address_id: parseInt(markers[i].getAttribute("address_id")),

					position: latlng,

					icon: eval("marker" + 0),
					//shadow: eval("markerShadow" + expired_e),

					categoryIDs: [],

					// Since the backend gives us one marker per <marker>
					// tag, we can set the index of this marker to i
					index: markersArray.length,

					maintitle: markers[i].getAttribute("title"),
					subTitle: markers[i].getAttribute("subtitle"),
					subTitle: markers[i].getAttribute("subtitle"),

					name: markers[i].getAttribute("name"),
					street: markers[i].getAttribute("street"),
					city: markers[i].getAttribute("city"),
					state: markers[i].getAttribute("state"),
					zip: markers[i].getAttribute("zip"),

					price: parseInt(markers[i].getAttribute("price")),
					numPurchased: markers[i].getAttribute("num_purchased"),
					value: markers[i].getAttribute("value"),
					discount: Math.round(100 * (parseFloat(markers[i].getAttribute("value")) - parseFloat(markers[i].getAttribute("price"))) / parseFloat(markers[i].getAttribute("value"))),
					savings: parseFloat(markers[i].getAttribute("value")) - parseFloat(markers[i].getAttribute("price")),

					dealUrl: markers[i].getAttribute("url"),
					affiliateUrl: markers[i].getAttribute("affiliate_url"),
					deadline: markers[i].getAttribute("deadline"),
					discovered: markers[i].getAttribute("discovered"),

					imageUrls: [],
					website: markers[i].getAttribute("website"),

					//isExpired: isExpired(mysqlTimeStampToDate(markers[i].getAttribute("deadline"))),
					upcoming: markers[i].getAttribute("upcoming"),
					expired: markers[i].getAttribute("expired"),
					age: getAge(mysqlTimeStampToDate(markers[i].getAttribute("discovered"))),

					//title: markers[i].getAttribute("title"),
					companyID: markers[i].getAttribute("company_id"),

					yelpRatingStr: yelp_rating.toString(),
					yelpUrl: markers[i].getAttribute("yelp_url"),
					yelpCount: markers[i].getAttribute("yelp_review_count"),

					yelpUser: [markers[i].getAttribute("yelp_user1"), markers[i].getAttribute("yelp_user2"), markers[i].getAttribute("yelp_user3")],
					yelpUserUrl: [markers[i].getAttribute("yelp_user_url1"), markers[i].getAttribute("yelp_user_url2"), markers[i].getAttribute("yelp_user_url3")],
					yelpUserPhoto: [markers[i].getAttribute("yelp_user_image_url1"), markers[i].getAttribute("yelp_user_image_url2"), markers[i].getAttribute("yelp_user_image_url3")],
					yelpUserExcerpt: [markers[i].getAttribute("yelp_excerpt1"), markers[i].getAttribute("yelp_excerpt2"), markers[i].getAttribute("yelp_excerpt3")],
					yelpReviewUrl: [markers[i].getAttribute("yelp_review_url1"), markers[i].getAttribute("yelp_review_url2"), markers[i].getAttribute("yelp_review_url3")],
					yelpUserRating: [markers[i].getAttribute("yelp_rating1"), markers[i].getAttribute("yelp_rating2"), markers[i].getAttribute("yelp_rating3")]

				});
				
				markersArray.push(marker);
				
				var childNodes = markers[i].childNodes;
				var topCategoryRank = -1;
				marker.icon = eval("marker0");
				
				for (var k = 0; k < childNodes.length; k++) {
					if (childNodes[k].nodeName == "category") {
					        categoryId = parseInt(childNodes[k].getAttribute("category_id"));
					        categoryRank = parseInt(childNodes[k].getAttribute("rank"));

						marker.categoryIDs.push(categoryId);
						if (categoryRank > topCategoryRank) {						
							topCategoryRank = categoryRank;

							marker.icon = eval("marker" + categoryIDToSetID[categoryId]);
	
						}
					} else if (childNodes[k].nodeName == "image") {
						var imageUrl = childNodes[k].getAttribute("image_url");
						marker.imageUrls.push(imageUrl);
					}
				}

				//if (marker.price > vacationsMax && cityEdition == VACATIONS_CITY_ID) {
				//	vacationsMax = marker.price;
				//}				

				if (marker.categoryIDs.length == 0) {
					// If no categories, set to uncategorized
					marker.categoryIDs.push(0);
				}

				if (marker.imageUrls.length == 0) {
					// If no images set first image to empty string
					marker.imageUrls.push("");
				}


			} // End iterating through markers xml results

			selectedMarkerIndex = findMarkersArrayIndex(mapViewDealID);
			
			if (selectedMarkerIndex == -1) selectedMarkerIndex = 0;
			
			highlightSelectedMarker(markersArray[selectedMarkerIndex]);
			markerClick(markersArray[selectedMarkerIndex])();

			displayMarkers();		

			$("#loading-div").hide();


		},
		async: true
	});

	currSWLat = mySWLat;
	currSWLng = mySWLng;
	currNELat = myNELat;
	currNELng = myNELng;
	
}




function displayMarkers() {
	debug("> displayMarkers()");

	
	for (var i = 0; i < markersArray.length; i++) {
		
		if (!isVisible(markersArray[i].categoryIDs[0])) {
			markersArray[i].setMap(null);

		} else if (showYelp != 0 && showYelp > parseFloat(markersArray[i].yelpRatingStr)) {
			// user wants to see only markers where greater than the showYelp global variable
			markersArray[i].setMap(null);
			
		} else if (showCompany != 0 && showCompany != markersArray[i].companyID) {
			// user wants to see only markers for a given company
			markersArray[i].setMap(null);
			
		} else if (markersArray[i].getMap() == null) {
			markersArray[i].setMap(map);
			google.maps.event.addListener(markersArray[i], 'click', markerClick(markersArray[i]));
			google.maps.event.addListener(markersArray[i], 'mouseover', markerOver(markersArray[i]));
			google.maps.event.addListener(markersArray[i], 'mouseout', markerOut(markersArray[i]));
		}
	}
	
	
	
}

var markerClick = function (marker) {

	return function markerClick() {
		
		$("#right-bar").hide();
		
		var urlString = "/deal_html_from_deals_index.php?map_view&deal_id=" + marker.dealID + "&edition=" + cityEdition;
		jQuery.ajax({
			url: urlString,
			success: function(data) {
			
			
				// Reset the previously selected marker's icon and update the newly selected marker's icon
				//var expired_e;
				var cat = markersArray[selectedMarkerIndex].categoryIDs[0];
				
				markersArray[selectedMarkerIndex].setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(13, 13), new google.maps.Point((0 + (categoryIDToSetID[cat] * 13)), 0), new google.maps.Point(6, 6)));
				
				mapViewDealID = parseInt(marker.dealID);

			
				$("#right-bar").html(data);
				
				$("#right-bar").find("img.deal-image").hide();
				$("#right-bar").find("div.subtitle").show();
				
				$("#right-bar").imagesLoaded(function() {
					$("#right-bar").find("img.deal-image").each(function () {
						var w = this.width;
						var h = this.height;
						
						if (w < 310) {
							this.style.width = "310px";
							this.style.height = "auto";
						}
						

						// In case the user quickly switches to list view before the markers/right bar have loaded
						if (view == "MAP") {
							$("#right-bar").find("img.deal-image").show();
							$("#right-bar").show();
						}						
					});
				});
				

				selectedMarkerIndex = marker.index;
				markersArray[selectedMarkerIndex].setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(19, 19), new google.maps.Point((169 + (categoryIDToSetID[marker.categoryIDs[0]] * 19)), 0), new google.maps.Point(9, 9)));

				
				
				$("#right-bar").find("#image-link-" + mapViewDealID).unbind('click').click(function () {
				
					var id = parseInt($(this).closest(".top-div").attr("id-at"));
					var category_id = $(this).closest(".top-div").attr("cat-at");
					var company = $(this).closest(".top-div").attr("cmp-at");
					var yelp = parseFloat($(this).closest(".top-div").attr("y-at"));
					var price = parseInt($(this).closest(".top-div").attr("p-at"));
					var value = parseInt($(this).closest(".top-div").attr("v-at"));

					mpq.track("Clicked to external deal site", {
						"mp_note": "Clicked on " + mapViewDealID + ", company " + marker.companyID,
						"Company": companies[marker.companyID],
						"Category": categories[marker.categoryIDs[0]],
						"Yelp": parseFloat(marker.yelpRatingStr),
						"City": cities[cityEdition],
						"Price": price,
						"Value": value,
						"View": "MAP-image",
						"UserID": userID
					});
				
				});

				

				$("#right-bar").find("#details-" + mapViewDealID).unbind('click').click(function () {
				
					var id = parseInt($(this).closest(".top-div").attr("id-at"));
					var category_id = $(this).closest(".top-div").attr("cat-at");
					var company = $(this).closest(".top-div").attr("cmp-at");
					var yelp = parseFloat($(this).closest(".top-div").attr("y-at"));
					var price = parseInt($(this).closest(".top-div").attr("p-at"));
					var value = parseInt($(this).closest(".top-div").attr("v-at"));

					mpq.track("Clicked to external deal site", {
						"mp_note": "Clicked on " + mapViewDealID + ", company " + marker.companyID,
						"Company": companies[marker.companyID],
						"Category": categories[marker.categoryIDs[0]],
						"Yelp": parseFloat(marker.yelpRatingStr),
						"City": cities[cityEdition],
						"Price": price,
						"Value": value,
						"View": "MAP-button",
						"UserID": userID
					});
				
				});

				$(".expansion").show();


				

			},
			async: true
		});	




	}
}




var markerOut = function(marker) {

	return function() {
	
	
		$("#hover-info").hide();
		$("#hover-info-image").attr("src", "");



		// Commented out because IE was giving problems when we try to replace the current mouseover'ed marker with
		// a different icon		

		/*
		if (selectedMarkerIndex != marker.index) {
			var expired_e;
			var cat = marker.categoryIDs[0];

			if (isExpired(mysqlTimeStampToDate(marker.deadline)) == 1) {
				marker.setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(15, 15), new google.maps.Point((230 + (cat * 23)), 0), new google.maps.Point(12, 29)));
			} else {
				marker.setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(15, 15), new google.maps.Point((0 + (cat * 23)), 0), new google.maps.Point(12, 29)));
			}
		}
		*/
	}
}

var markerOver = function(marker) {

	return function() {
	
		
		var scale = Math.pow(2, map.getZoom());
		var nw = new google.maps.LatLng(
			map.getBounds().getNorthEast().lat(),
			map.getBounds().getSouthWest().lng()
		);
		var worldCoordinateNW = map.getProjection().fromLatLngToPoint(nw);
		var worldCoordinate = map.getProjection().fromLatLngToPoint(marker.getPosition());
		var pixelOffset = new google.maps.Point(
			Math.floor((worldCoordinate.x - worldCoordinateNW.x) * scale),
			Math.floor((worldCoordinate.y - worldCoordinateNW.y) * scale)
		);

		
		if ($(window).width() - pixelOffset.x < 350) {
			$("#hover-info").css("left", pixelOffset.x - 381);		
		} else {
			$("#hover-info").css("left", pixelOffset.x + 20);		
		}
		$("#hover-info").css("top", pixelOffset.y - 30);
		
		
		
		$("#hover-info-maintitle").html(marker.maintitle);

		if (marker.subTitle.length == 0) {
			$("#hover-info-subtitle").hide();
		} else {
			$("#hover-info-subtitle").show();
			$("#hover-info-subtitle").html(marker.subTitle);
		}
	
		$("#hover-info-company").html(companies[marker.companyID]);
		
		if (marker.yelpRatingStr == "-1") {
			$("#hover-info-yelp").hide();
		} else {
			$("#hover-info-yelp").show();
			$("#hover-info-yelp-stars").attr("src", "/images/yelp/yelp_" + marker.yelpRatingStr.replace(".", "") + ".png");
			$("#hover-info-yelp-count").html(" - " + marker.yelpCount + " reviews");
		}

		$("#hover-info").show();
		$("#hover-info-image").hide();
		debug(marker.imageUrls[0]);
		$("#hover-info-image").attr("src", marker.imageUrls[0] + "?" + new Date().getTime());

		var pic_real_width, pic_real_height, ar;
		// Add random number after src to force IE 8 to load image
		$("<img/>").attr("src", $("#hover-info-image").attr("src") + "?" + new Date().getTime()).load(function() {
		
			pic_real_width = this.width;
			pic_real_height = this.height;
			ar = pic_real_width / pic_real_height;

			if (parseFloat(ar) > 1.32941176) {
				$("#hover-info-image").animate({height: "85px"}, 1, function() {
					$("#hover-info-image").show();
				});
			} else {
				$("#hover-info-image").animate({width: "113px"}, 1, function() {
					$("#hover-info-image").show();
				});
			}
			
		});
		
		
		
		// Commented out because IE was giving problems when we try to replace the current mouseover'ed marker with
		// a different icon
		
		//var cat = marker.categoryIDs[0];
		//marker.setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(31, 37), new google.maps.Point((460 + (cat * 31)), 0), new google.maps.Point(16, 37)));
	}
}



// Called in index.php
function resortListView(newSortBy) {
	debug("> resortListView(newSortBy)");



	var prefix = "";
	
	if (newSortBy == "DISTANCE") {
		if ($("#sort-zip").val() == "") {
			return;
		}
	}
	
	if (newSortBy == "DISTANCE") {
		zip = $("#sort-zip").val();
		$.cookie('zip_cookie', zip, {
			expires: 1000,
			path:'/'
		});
	}
	
	currentSortBy = newSortBy;
	highlightSortLink();
	
	loadAndDisplay();
	
	currentSortBy == "SOLD" ? sortByString = "number sold" : sortByString = currentSortBy.toLowerCase();
	// $("#" + prefix + "list-view-sorted-by").html("sorted by " + sortByString);
	
	pushState();
}


function highlightSortLink() {
	debug("> highlightSortLink()");


	$("#sortNEW").removeClass("sort-on");
	$("#sortSOLD").removeClass("sort-on");
	$("#sortPRICE").removeClass("sort-on");
	$("#sortDEADLINE").removeClass("sort-on");
	$("#sortDISTANCE").removeClass("sort-on");
	
	$("#sort" + currentSortBy).addClass("sort-on");

}







function indexOfFirstVisibleDeal(arr, prefix) {
	for (var i = 0; i < arr.length; i++) {
		if ($('#' + prefix + 'list-view-id-' + i).is(":visible")) {
			return i;
		}
	}
}









function deleteOverlays(arr) {
	debug("> deleteOverlays(arr)");

	if (arr.length > 0) {
		for (var i = 0; i < arr.length; i++) {
			arr[i].setMap(null);
		}
		arr.length = 0;

	}
}



function mysqlTimeStampToDate(timestamp) {
	var regex = /^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/;
	var parts = timestamp.replace(regex, "$1 $2 $3 $4 $5 $6").split(' ');
	return new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]);
}

function isExpired(deadlineDate) {
	d = new Date();
	var timeUTC = new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours(), d.getUTCMinutes(), d.getUTCSeconds(), d.getUTCMilliseconds());

	timeNow = new Date();
	differenceInMilliseconds = deadlineDate.getTime() - timeUTC.getTime();
	delete timeNow;

	// If time is already past...
	if (differenceInMilliseconds < 0) {
		return 1;
	} else {
		return 0;
	}
}

function getAge(discoveredDate) {
	d = new Date();
	var timeUTC = new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours(), d.getUTCMinutes(), d.getUTCSeconds(), d.getUTCMilliseconds());

	timeNow = new Date();
	differenceInMilliseconds = timeUTC.getTime() - discoveredDate.getTime();
	delete timeNow;

	var ageInDays = Math.floor(differenceInMilliseconds / 86400000);
	return ageInDays;


}

// Takes in a deadline for a deal and a target div, then outputs to the target div the amount of
// time until the deadline. If the dealine has passed, writes "Expired" to the target div

function fillInAvailabilityInfo(markerDeadline, markerUpcoming, markerExpired, targetDiv) {
	d = new Date();
	var timeUTC = new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours(), d.getUTCMinutes(), d.getUTCSeconds(), d.getUTCMilliseconds());

	timeNow = new Date();
	var deadlineDate = mysqlTimeStampToDate(markerDeadline);
	differenceInMilliseconds = deadlineDate.getTime() - timeUTC.getTime();
	delete timeNow;


	// If the deal is upcoming...
	if (markerUpcoming == 1) {
		document.getElementById(targetDiv).innerHTML = "<span style='color:#659829;font-weight:700'>UPCOMING DEAL</span>";
	}
	
	
	// If time is already past...
	else if (differenceInMilliseconds < 0) {
		document.getElementById(targetDiv).innerHTML = "<span style='color:#a20000;font-weight:700'>EXPIRED</span>";
	}
	
	else if (markerExpired == 1) {
		document.getElementById(targetDiv).innerHTML = "<span style='color:#a20000;font-weight:700'>EXPIRED</span>";
	}
	
	else if (markerDeadline == "") {
		document.getElementById(targetDiv).innerHTML = "This deal is currently available";
	}
	
	
	// Else date is still good...
	else {
		days = 0;
		hours = 0;
		mins = 0;
		secs = 0;

		out = "Time left: <span class='expires-in'>";

		differenceInMilliseconds = Math.floor(differenceInMilliseconds / 1000);
		days = Math.floor(differenceInMilliseconds / 86400);
		differenceInMilliseconds = differenceInMilliseconds % 86400;

		hours = Math.floor(differenceInMilliseconds / 3600);
		differenceInMilliseconds = differenceInMilliseconds % 3600;

		mins = Math.floor(differenceInMilliseconds / 60);
		differenceInMilliseconds = differenceInMilliseconds % 60;

		secs = Math.floor(differenceInMilliseconds);
				
		if (isNaN(days) || isNaN(hours) || isNaN(mins)) {
			document.getElementById(targetDiv).innerHTML = "No expiration information";
			return;
		}
		
		if (days != 0) {
			out += days + " " + ((days == 1) ? "<span class='expires-in-units'>d</span>" : "<span class='expires-in-units'>d</span>") + " ";
		}
		if (hours != 0) {
			out += hours + " " + ((hours == 1) ? "<span class='expires-in-units'>hr</span>" : "<span class='expires-in-units'>hr</span>") + " ";
		}

		out += mins + " " + ((mins == 1) ? "<span class='expires-in-units'>min</span>" : "<span class='expires-in-units'>min</span></span>") + " ";

		out = out.substr(0, out.length - 2);
		document.getElementById(targetDiv).innerHTML = out;

	}
}

function isExpired(deadlineDate) {
	d = new Date();
	var timeUTC = new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours(), d.getUTCMinutes(), d.getUTCSeconds(), d.getUTCMilliseconds());

	timeNow = new Date();
	differenceInMilliseconds = deadlineDate.getTime() - timeUTC.getTime();
	delete timeNow;

	// If time is already past...
	if (differenceInMilliseconds < 0) {
		return 1;
	}
	// Else date is still good...
	else {
		return 0;
	}
}

function highlightSelectedMarker() {
	if (markersArray.length == 0) {
		return;
	}
	var cat = markersArray[selectedMarkerIndex].categoryIDs[0];
	markersArray[selectedMarkerIndex].
	setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(31, 37), new google.maps.Point((460 + (cat * 31)), 0), new google.maps.Point(16, 37)));
}



function calculateCityEditionFromLatLng(lat, lng) {

	var lat = parseFloat(lat);
	var lng = parseFloat(lng);

	if (isNaN(lat) || isNaN(lat)) {
		return 0;
	}

	var minDistance = 1000000;
	var currDistance;
	
	var edition;

	for (var i = 0; i < cityLat.length; i++) {
		if (cityLat[i] != 0) {
			currDistance = distance(lat, lng, cityLat[i], cityLng[i]);
			if (currDistance < minDistance) {
				minDistance = currDistance;
				edition = i;
			}
		}
	}
	
	return edition;
}
