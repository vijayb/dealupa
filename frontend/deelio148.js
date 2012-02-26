/*

BEFORE PUSHING:

- Change deelioX.js and mapX.css to current version number in index.php, deal_html.php, static_top.php
- Compile deelioX.js and put the readable source code in deelio_readable.com
- Minify CSS with http://www.ventio.se/tools/minify-css/
- remove $w = 0 from index.php

*/

// Set to 1 at the very end of the load() function. Set to 0 only here.
var initialLoadCompleted = 0;

var loggedIn = "NONE";
var userID = 0;

historyObject = ["blank page"];

// If you change this, also look for "500" in
var NATIONWIDE_CITY_ID = 2;
// var SLIDER_MAX = 1000; // If you change this, change in deal_html.php, too


var markersArray = [];
var singleDealMarkersArray = [];
var singleDeal = {};		// The deal which the single-page display is showing. Analogous to markersArray , but for the SINGLE view

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

// If you change the slider in map view and then go to list view, list view does not reload. It should.
sliderDirtyBit = 1;

// Set to 1 when user stars or unstars a deal.
starredDirtyBit = 1;

// The view the user is currently seeing. MAP, LIST, or IMAGE
var view = "MAP";
var pages = 1;

// In map view, the deal ID of the current deal displayed in the right panel
var mapViewDealID = "";

// The current edition
var cityEdition = -1;
var changedEditionWhileInListView = 0;

var sessionID;
var sessionStartTime;

var cityString;

// The zip code that the distance sort uses
var zip;
var zipLat;
var zipLng;


// For e-miles tracking
var ftouch = "";

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

//cityLat[VACATIONS_CITY_ID] = 39.707187;

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
//cityLng[VACATIONS_CITY_ID] = -90.878906;


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
//cityZoom[VACATIONS_CITY_ID] = 4;

// If categoriesToShow[x] == 1, then we SHOW deals with category x.
categoriesToShow = [0, 1, 1, 1, 1, 1, 1, 1, 1, 1];

var swLat;
var swLng;
var neLat;
var neLng;

var map;

var selectedMarkerIndex = -1;
var selectedMarkerImageIndex = 0;	// Each marker can have multiple images. This keeps track of which image is currently being shown

var categories = [];
var categoriesReverse = [];
var categoriesReverseInt = [];
var companies = [];
var cities = [];
var citiesReverse = [];
var cities_url = [];
var showOnlyNew = 0;
var showYelp = 0;
var showCompany = 0;
//var hideExpired = 1;

categories[-1] = "All";
categories[0] = "Uncategorized";
categories[1] = "Food & Drink"
categories[2] = "Activities & Events"
categories[3] = "Spa & Beauty"
categories[4] = "Kids & Parents"
categories[5] = "Shopping & Services"
categories[6] = "Classes & Learning"
categories[7] = "Fitness & Health"
categories[8] = "Dental & Medical"
categories[9] = "Hotels & Vacations"

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
companies[24] = "Schwaggle";  // http://schwaggle.active.com/
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

cities[NATIONWIDE_CITY_ID] = "Dealupa Nation";
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
//cities[VACATIONS_CITY_ID] = "Dealupa Vacations";

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
citiesReverse["dc"] = 17;
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
//citiesReverse["vacations"] = VACATIONS_CITY_ID;

categoriesReverse["all"] = "0,1,1,1,1,1,1,1,1,1";
categoriesReverse["uncategorized"] = "1,0,0,0,0,0,0,0,0,0";
categoriesReverse["food-and-drink"] = "0,1,0,0,0,0,0,0,0,0";
categoriesReverse["activities-and-events"] = "0,0,1,0,0,0,0,0,0,0";
categoriesReverse["spa-and-beauty"] = "0,0,0,1,0,0,0,0,0,0";
categoriesReverse["kids-and-parents"] = "0,0,0,0,1,0,0,0,0,0";
categoriesReverse["shopping-and-services"] = "0,0,0,0,0,1,0,0,0,0";
categoriesReverse["classes-and-learning"] = "0,0,0,0,0,0,1,0,0,0";
categoriesReverse["fitness-and-health"] = "0,0,0,0,0,0,0,1,0,0";
categoriesReverse["dental-and-medical"] = "0,0,0,0,0,0,0,0,1,0";
categoriesReverse["hotels-and-vacations"] = "0,0,0,0,0,0,0,0,0,1";

categoriesReverseInt[-1] = "0,1,1,1,1,1,1,1,1,0";
categoriesReverseInt[0] = "1,0,0,0,0,0,0,0,0,0";
categoriesReverseInt[1] = "0,1,0,0,0,0,0,0,0,0";
categoriesReverseInt[2] = "0,0,1,0,0,0,0,0,0,0";
categoriesReverseInt[3] = "0,0,0,1,0,0,0,0,0,0";
categoriesReverseInt[4] = "0,0,0,0,1,0,0,0,0,0";
categoriesReverseInt[5] = "0,0,0,0,0,1,0,0,0,0";
categoriesReverseInt[6] = "0,0,0,0,0,0,1,0,0,0";
categoriesReverseInt[7] = "0,0,0,0,0,0,0,1,0,0";
categoriesReverseInt[8] = "0,0,0,0,0,0,0,0,1,0";
categoriesReverseInt[9] = "0,0,0,0,0,0,0,0,0,1";


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
cities_url[17] = "dc";
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

// The max price of any vacation in markersArray
var vacationsMax = 0;

var yelpTimeout = 0;
var yelpTimeoutList = 0;

var hoverImageTimer = 0;
var hoverImageIndex = 0;

// These need to be declared globally so closure doesn't mess with the names
var marker0 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(0, 0), new google.maps.Point(12, 29));
var marker1 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(23, 0), new google.maps.Point(12, 29));
var marker2 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(46, 0), new google.maps.Point(12, 29));
var marker3 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(69, 0), new google.maps.Point(12, 29));
var marker4 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(92, 0), new google.maps.Point(12, 29));
var marker5 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(115, 0), new google.maps.Point(12, 29));
var marker6 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(138, 0), new google.maps.Point(12, 29));
var marker7 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(161, 0), new google.maps.Point(12, 29));
var marker8 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(184, 0), new google.maps.Point(12, 29));
var marker9 = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(207, 0), new google.maps.Point(12, 29));


var marker0e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(230, 0), new google.maps.Point(12, 29));
var marker1e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(253, 0), new google.maps.Point(12, 29));
var marker2e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(276, 0), new google.maps.Point(12, 29));
var marker3e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(299, 0), new google.maps.Point(12, 29));
var marker4e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(322, 0), new google.maps.Point(12, 29));
var marker5e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(345, 0), new google.maps.Point(12, 29));
var marker6e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(368, 0), new google.maps.Point(12, 29));
var marker7e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(391, 0), new google.maps.Point(12, 29));
var marker8e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(414, 0), new google.maps.Point(12, 29));
var marker9e = new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point(437, 0), new google.maps.Point(12, 29));

var markerShadow = new google.maps.MarkerImage('/images/marker_shadow.png', new google.maps.Size(25, 14), new google.maps.Point(0, 0), new google.maps.Point(13, 6));
var markerShadowe = new google.maps.MarkerImage('/images/marker_shadow.png', new google.maps.Size(25, 14), new google.maps.Point(0, 0), new google.maps.Point(13, 6));






var login_div = $("#login-div").detach();
var change_password_div = $("#change-password-div").detach();
var forgot_password_div = $("#forgot-password-div").detach();
var city_selector = $("#city-selector").detach();

//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
///////////////////// SEPARATING GLOBAL VARIABLES ABOVE AND CODE BELOW ///////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////




// 888      .d88888b.        d8888 8888888b.  
// 888     d88P" "Y88b      d88888 888  "Y88b 
// 888     888     888     d88P888 888    888 
// 888     888     888    d88P 888 888    888 
// 888     888     888   d88P  888 888    888 
// 888     888     888  d88P   888 888    888 
// 888     Y88b. .d88P d8888888888 888  .d88P 
// 88888888 "Y88888P" d88P     888 8888888P"  


var popped = ('state' in window.history), initialURL = location.href

function load() {

	myConsoleTime("*****load*****");
		
	debug("***********************************");
	debug("********** START OF LOAD() ********");
	debug("***********************************");
	
	debug("urlParams: " + urlParams);
	debug("last_view: " + $.cookie('last_view'));
		
	$(window).bind('popstate', function(event) {
	  var initialPop = !popped && location.href == initialURL;
	  popped = true;
	  if (initialPop) return;	
		debug("||||||||||| POP |||||||||||");
		var state = event.originalEvent.state;
		if (state) {
			debug(state.stateParams);
			var viewBeforePop = view;
			parseURLParameters(state.stateParams);
			//$("#city-selector-current-city").html(cities[cityEdition]);	

			// This should probably be done elsewhere...seems like a bandaid to do this here
			if (cityEdition == NATIONWIDE_CITY_ID) {
				changeView("LIST");
				$("#view-toggle").hide();	
			} else {
				$("#view-toggle").show();
			}			
			
			changeView(view);
			
			// If the user was just looking at single-deal view, don't reload when he presses back
			if (viewBeforePop == "SINGLE" && $("#list-view").html().length > 100) {
				loadAndDisplay(1);
			} else {
				loadAndDisplay();
			}
			
			historyObject.pop();
		}
		setTimeout('window.scrollTo(0, listScrollPosition);', 15);
	});




	// Check if the user has a session cookie. If he does and the session cookie is valid, log the user in.
	if ($.cookie('session_cookie') != null && $.cookie('userid_cookie') != null && loggedIn == "NONE") {
		var cookieSessionID = $.cookie('session_cookie');
		var cookieUserID = $.cookie('userid_cookie');
		jQuery.ajax({
			url: "/check_session?user_id=" + cookieUserID + "&session_id=" + cookieSessionID,
			success: function (data) {
				if (data == "1") {
					debug("LOGGING USER IN BASED ON COOKIE");
					loggedIn = "DEALUPA";
					userID = parseInt(cookieUserID);
					initLoggedIn();
				}
			},
			async: false
		});		
	}

	cityEdition = calculateCityEditionFromLatLng(gbLat, gbLng);
	
	var initialOptions = {
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
	
	//var listMapOptions = {
	//	zoom: 12,
	//	center: new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]),
	//	mapTypeId: google.maps.MapTypeId.ROADMAP
	//}
        
	//listMap = new google.maps.Map(document.getElementById("list-view-map"), listMapOptions);	
	
	//listMarker = new google.maps.Marker({
	//	map: listMap
	//});

	$(window).resize(resizeMap);
	resizeMap();

	
	$(window).scroll(function () {
		if (view == "LIST") {
			if ($(window).scrollTop() >= $(document).height() - $(window).height() - 10) {
				pages++;
				loadAndDisplayDeals(true);
			}
		}
	});
	
/*
	var checkboxClickFunction = function(checkboxName, j) {
		return function checkboxClickFunction() {
			$(this).toggleClass("unselected");
			if (categoriesToShow[j] == 0) {


				mpq.track("Filter turned on", {
					"mp_note": "Turned on " + categories[j] + " - " + outputURL(),
					"Category": categories[j],
					"City": cities[cityEdition],
					"View": view,
					"Sort": currentSortBy
				});
				
				categoriesToShow[j] = 1;
				$("#filter-image-" + j).attr("src", "/images/check.png");
				loadAndDisplay();
			} else {
				mpq.track("Filter turned off", {
					"mp_note": "Turned off " + categories[j] + " - " + outputURL(),
					"Category": categories[j],
					"City": cities[cityEdition],
					"View": view,
					"Sort": currentSortBy
				});
				categoriesToShow[j] = 0;
				$("#filter-image-" + j).attr("src", "/images/check_off.png");
				loadAndDisplay();
			}
			replaceState();
		}
	}
	
	for (i = 1; i <= (categories.length - 1); i++) {
		$('#filter-' + i).click(checkboxClickFunction("#filter-" + i, i));
	}
	
	var showOnlys = function() {
		return function() {
			for (var j = 1; j <= (categories.length - 1); j++) {
				$("#filter-" + j + "-only").fadeIn();
			}
		}
	}

	var hideOnlys = function() {
		return function() {
			for (var j = 1; j <= (categories.length - 1); j++) {
				$("#filter-" + j + "-only").fadeOut();
			}
		}
	}
	
	$("#filters-bar").mouseenter(showOnlys());
	$("#filters-bar").mouseleave(hideOnlys());	
	
	
	$("#filter-hide-expired").click(function hideExpiredFunction() {
		if ($("#filter-hide-expired").is(":checked")) {
			hideExpired = 1;
			loadAndDisplay();
			replaceState();
		} else {
			hideExpired = 0;
			loadAndDisplay();
			replaceState();
		}
	});
	
	$("#filter-show-new").click(function showOnlyNewFunction() {
		if ($("#filter-show-new").is(":checked")) {
			showOnlyNew = 1;
			loadAndDisplay();
			replaceState();
		} else {
			showOnlyNew = 0;
			loadAndDisplay();
			replaceState();
		}
	});
	
	
	*/

	/*
	$("#filter-yelp").change(function showOnlyYelpFunction() {

		var newYelp = $("#filter-yelp").val();

		mpq.track("Yelp filter changed", {
			"mp_note": "Changed to " + newYelp + " - " + outputURL(),
			"Yelp": newYelp,
			"City": cities[cityEdition],
			"View": view,
			"Sort": currentSortBy
		});
		

		showYelp = newYelp;
		loadAndDisplay();
		replaceState();
	});
	
	$("#filter-company").change(function() {
		showCompany = $("#filter-company").val();
		loadAndDisplay();
	});
	*/
	
	
	
	
	
	
	
	
	$("#unhide-categories").poshytip({
		className: 'tip-black',
		showOn: 'none',
		alignTo: 'target',
		alignX: 'center',
		offsetX: 0,
		offsetY: 15
	});
	
	$("#dealupa-title").poshytip({
		className: 'tip-black tip-city-selector',
		content: city_selector.html(),
		showOn: 'none',
		alignTo: 'target',
		alignX: 'center',
		offsetX: 0,
		offsetY: 0
	});
	$("#city-selector").remove();
	
	$("#login-button").poshytip({
		className: 'tip-black',
		content: login_div.html(),
		showOn: 'none',
		alignTo: 'target',
		alignX: 'center',
		offsetX: 0,
		offsetY: 15
	});	
	
	
	
	
	
	
/*	
	$('html').click(function() {
		if ($("#city-selector").is(":visible")) {
			$("#city-selector").hide();
		}
	});

	$('#edition').click(function(event) {
		$('#city-selector').css("display", "block");
		$('#city-selector').css("zIndex", "999999");
		event.stopPropagation();
	});

	$('#city-selector').click(function(event) {
		event.stopPropagation();
	});
*/
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

	/*
	$("#filter-company").change(function () {
		showSingleCompany = $("#filter-company").val();
		loadAndDisplay();
	});
		
	*/
	
	//disableSelection(document.getElementById("filters-bar"));
	//disableSelection(document.getElementById("edition"));

	//infoMenu();

	// If the user doesn't have a welcome_cookie, set it to 1
	if ($.cookie('welcome_cookie') == null) {
		$.cookie('welcome_cookie', '1', {
			expires: 1000,
			path:'/'
		});
		
	// Otherwise, increment the welcome_cookie counter
	} else {
		var currCookieVal = parseInt($.cookie('welcome_cookie'));
		$.cookie('welcome_cookie', (currCookieVal + 1), {
			expires: 1000,
			path:'/'
		});	
	}
	
	// If there's a zip code stored in the cookie, set the distance text box
	if ($.cookie('zip_cookie') != null) {
		zip = $.cookie('zip_cookie');
		$("#sort-zip").val(zip);
	}


	//$("#edition").addClass("selected-tab");
	/*
	$("#top-bar-search-box").qtip({
		content: {
			text: "Try 'massage' or 'wine'"
		},
		position: {
			my: "top left",
			at: "bottom right"
		}
	});
	*/
	
	$("#top-bar-search-box").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { executeSearch(); e.preventDefault(); }
	});

	$("#move-map-input").keyup(function(event) {
		if (event.keyCode == 13) { $("#move-map-button").click(); }
	});
	
	$("#login-password").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { login(); e.preventDefault(); }
		if(code == 27) { removeLogin(); e.preventDefault(); }
	});	
	
	$("#forgot-email").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { sendNewPassword(); e.preventDefault(); }
		if(code == 27) { removeLogin(); e.preventDefault(); }
	});		
	
	$("#signup-email, #signup-password").keyup(function(e) {
		var code = e.keyCode || e.which;
		if(code == 13) { signupUser(); e.preventDefault(); }
		if(code == 27) { removeWelcome(1); e.preventDefault(); }
	});
	
	$("a").click(function(e){
		if (e.metaKey || e.ctrlKey){
			debug("YOU ARE HOLDING DOWN CTRL!");
			return false;
		}
	});
		
	google.maps.event.addListener(map, 'idle', function mapIdle() {
		if (view == "LIST" || view == "SINGLE" || view == "STARRED") return;
		cityEdition = calculateCityEditionFromLatLng(map.getCenter().lat(), map.getCenter().lng());
		$("#city-name").html(cities[cityEdition].toLowerCase());
		loadAndDisplay();
		replaceState();
		
		if (BrowserDetect.browser == "Explorer") {
			// Hack so that all parts of the map are grabbable to drag
			$("div").css("background-color", "");
		}
		
	});
	
	debug("Welcome: " + $.cookie('welcome_cookie'));
	


	
	if (urlParams != "?" && urlParams != "") {
		parseURLParameters(urlParams);
		//$("#city-selector-current-city").html(cities[cityEdition]);	
		changeView(view);
		loadAndDisplay();
	} else if ($.cookie('last_view') != null) {
		parseURLParameters($.cookie('last_view'));
		//$("#city-selector-current-city").html(cities[cityEdition]);
		changeView(view);
		loadAndDisplay();
	} else {
		changeView(view);
		changeEdition(cityEdition);
	}
	replaceState();

	
	
	
	var d = new Date();	
	sessionID = parseInt((d.getTime() * parseInt(ipAddress.replace(/\./g,  ""))) / 1000000000000);
	sessionStartTime = d.getTime();
	
		
	var whichWelcomeScreenUserSees;
	if (isLoggedIn()) {
		whichWelcomeScreenUserSees = 0;
	} else {
		whichWelcomeScreenUserSees = welcomeScreen;
	}
		

	mpq.track("Site loaded successfully", {
		"mp_note": "Welcome screen " + whichWelcomeScreenUserSees + " - " + outputURL(),
		"Welcome": whichWelcomeScreenUserSees,
		"UserID": userID,
		"Logged In": isLoggedIn(),
		"View": view,
		"UTM": utm
	});

	
	myConsoleTimeEnd("*****load*****");

	initialLoadCompleted = 1;
	
	debug("===================================");
	debug("=========== END OF LOAD() =========");
	debug("===================================");
	debug("");
	
	//changeView("EMAIL");
	//loadAndDisplayEmail();
	
	
} // END OF LOAD FUNCTION


function showEditionSelector(elem) {
	$("#dealupa-title").poshytip('show');

	event.stopPropagation();
	$('html').one("click", function(event) { $("#" + elem.getAttribute("id")).poshytip('hide'); });
	$('#' + elem.getAttribute("id") + '-tip').unbind("click");
	$('#' + elem.getAttribute("id") + '-tip').click(function(event) { event.stopPropagation(); });
	
}


function hideCategory(categoryToHide) {
	categoriesToShow[categoryToHide] = 0;
	setNumberHidden();
}

function showCategory(categoryToShow) {
	categoriesToShow[categoryToShow] = 1;
	setNumberHidden();
	$("#unhide-categories").poshytip('hide');
}

function setNumberHidden() {
	var total = 0;
	for (var i = 0; i < categoriesToShow.length; i++) {
		if (categoriesToShow[i] == 0) {
			total++;
		}
	}
	
	// Subtract 1 because "uncategorized" is a category that's always
	// technically hidden but invisible to the user
	total--;
	
	if (total == 0) {
		$("#num-hidden-categories").fadeOut();
		$("#unhide-categories").fadeOut();
	} else if (total == 1) {
		$("#num-hidden-categories").html("Hiding " + total + " category&#160;&#160;");
		$("#num-hidden-categories").fadeIn();
		$("#unhide-categories").fadeIn();
	} else {
		$("#num-hidden-categories").html("Hiding " + total + " categories&#160;&#160;");
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



function toggleShowOnlyNew() {

	showOnlyNew = showOnlyNew ? 0 : 1;
	
	if (showOnlyNew) {
		$("#filter-show-only-new").addClass("down");
	} else {
		$("#filter-show-only-new").removeClass("down");
	}

}


function toggleHiddenCategories(elem) {

	var html = "<div>";
	
	// Start at 1 because 0 is "Uncategorized"
	for (var i = 1; i < categoriesToShow.length; i++) {
		if (categoriesToShow[i] == 0) {
			html += "<span class='category category-" + i + "'>" + categories[i] + "</span> - <a href='javascript:void(0);' onclick='showCategory(" + i + "); loadAndDisplay(); pushState();'>Show</a><br><br>";
		}
	}
	html = html.substring(0, html.length - "<br><br>".length);
	html += "</div>";
	
	$("#unhide-categories").poshytip('update', html);
	$("#unhide-categories").poshytip('show');
	
	event.stopPropagation();
	$('html').one("click", function(event) { $("#" + elem.getAttribute("id")).poshytip('hide'); });
	$('#' + elem.getAttribute("id") + '-tip').unbind("click");
	$('#' + elem.getAttribute("id") + '-tip').click(function(event) { event.stopPropagation(); });
}








function isLoggedIn() {
	if (loggedIn == "FACEBOOK" || loggedIn == "DEALUPA") {
		return true;
	} else {
		return false;
	}
}





function showLogin(elem) {
	$("#login-button").poshytip('update', login_div.html());
	$("#login-button").poshytip('show');

	event.stopPropagation();
	$('html').one("click", function(event) { $("#" + elem.getAttribute("id")).poshytip('hide'); });
	$('#' + elem.getAttribute("id") + '-tip').unbind("click");
	$('#' + elem.getAttribute("id") + '-tip').click(function(event) { event.stopPropagation(); });

}

function removeLogin() {
	$("#change-message").html("");
	$("#login-message").html("");
	
	$("#login-email").val("");
	$("#login-password").val("");

	$("#change-email").val("");
	$("#change-current-password").val("");
	$("#change-new-password").val("");
	
	$("#login-button").poshytip('hide');
}


function showChangePassword() {

	$("#login-button").poshytip('update', change_password_div.html());
	$("#login-button").poshytip('show');

	$("#change-email").focus();	
	$("#change-message").val("");
	$("#login-message").val("");
}

function showForgotPassword() {
	$("#login-button").poshytip('update', forgot_password_div.html());
	$("#login-button").poshytip('show');


	$("#forgot-email").focus();	
	$("#forgot-message").val("");
	$("#login-message").val("");
}


function login() {
	
	if ($("#login-email").val() == "" || $("#login-password").val() == "") {
		return;
	}
	
	var password_hash = hex_sha256($("#login-password").val());

	var d = new Date();	
	var sessionID = d.getTime();
	
	var argString = "email=" + $("#login-email").val() + "&password_hash=" + password_hash + "&session_id=" + sessionID;
	
	
	jQuery.ajax({
		type: "POST",
		url: "/login_user.php",
		data: argString,
		success: function (data) {
			if (parseInt(data) == 0) {
				$("#login-message").html("Oops, wrong password.");
				$("#login-message").show();
				$("#login-password").val("").focus();
				setTimeout(function() { $("#login-message").fadeOut() }, 3000);

			} else if (parseInt(data) == -1) {
			
			} else {

				loggedIn = "DEALUPA";
				userID = parseInt(data);

				mpq.track("Logged in via Dealupa", {
					"mp_note": outputURL(),
					"UserID": userID,
					"UTM": utm
				});
				
				
				$.cookie('session_cookie', sessionID, {
					expires: 10000000,
					path:'/'
				});

				$.cookie('userid_cookie', userID, {
					expires: 10000000,
					path:'/'
				});			
				
				$("#login-div-container").hide();
				
				initLoggedIn();
			}
		},
		async: true
	});
}	


function changePassword() {
	
	var email = $("#change-email").val()
	var curr_password_hash = hex_sha256($("#change-current-password").val());
	var new_password_hash = hex_sha256($("#change-new-password").val());

	var d = new Date();	
	var sessionID = d.getTime();
	
	var urlString = "/change_password.php?email=" + email + 
	                "&curr_password_hash=" + curr_password_hash +
					"&new_password_hash=" + new_password_hash +
					"&session_id=" + sessionID;

					
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			
			if (parseInt(data) == 0) {
				$("#change-message").html("Oops, wrong password.");
				$("#change-message").show();
				$("#change-current-password").val("").focus();
				$("#change-new-password").val("");
				setTimeout(function() { $("#change-message").fadeOut() }, 3000);

			} else if (parseInt(data) == -1) {
			
			} else {
			
				loggedIn = "DEALUPA";
				userID = parseInt(data);

				$.cookie('session_cookie', sessionID, {
					expires: 10000000,
					path:'/'
				});

				$.cookie('userid_cookie', userID, {
					expires: 10000000,
					path:'/'
				});			
				$("#login-div-container").hide();
				initLoggedIn();
			}
		},
		async: true
	});
}	

function logout() {
	userID = 0;
	loggedIn = "NONE";

	if (fbIsValid()) {
		FB.logout(function(response) {
		});
	}

	$.cookie('session_cookie', null);
	$.cookie('userid_cookie', null);
	initLoggedOut();	
}

function removeWelcome(recordAsSignupDismissal) {


	// removeWelcome is called when the user dismisses the signup screen AND at other times.
	// The argument determined whether this call was as a result of the user's dismissal of
	// the signup screen.
	
	if (recordAsSignupDismissal) {
		mpq.track("Sign up box dismissed", {
			"mp_note": "Welcome screen " + welcomeScreen + " - " + outputURL(),
			"Welcome": welcomeScreen,
			"UserID": userID,
			"UTM": utm
		});
	}
		

	$("#black-background").hide();

	if (welcomeScreen == 100) {
		$("#welcome-100").hide();
	} else if (welcomeScreen == 200) {
		$("#welcome-200").slideUp();
	} else if (welcomeScreen == 300) {
		$("#welcome-300").slideUp();
	} else if (welcomeScreen >= 400 && welcomeScreen < 500) {
		$("#welcome-400").hide();
	} else if (welcomeScreen == 500) {
		$("#welcome-500").hide();
	}
	
}

function executeSearch() {


	myConsoleTime("*****search to load*****");

	if (view == "SINGLE" || view == "STARRED") {
		view = "LIST";
		changeView(view);
	}
	
	resetFiltersGlobals();
	
	query = $("#top-bar-search-box").val();
	$("#top-bar-search-box").select().focus();
	
	if (query == "") {
		return;
	}

	
	mpq.track("Did a search", {
		"mp_note": "Searched for " + query + " - " + outputURL(),
		"View": view
	});
	
	
	debug("query! [" + query + "]");

	$("#clear-search-button").show();
	$("#sort-options").hide();
	
	
	loadAndDisplay();



	pushState();
	
}


function clearSearch() {

	
	$("#search-box").val("");
	$("#top-bar-search-box").val("");
	
	$("#clear-search-button").hide();
	$("#sort-options").show();
	
	query = "";
	
	debug("SETTING currNELng to -1 (2)");
	currNELng = -1;
	
}



function showWelcome(firstTimeCalled) {
	
	if (welcomeScreen == 100) {

		mpq.track("Sign up box displayed", {
			"mp_note": "Welcome screen " + welcomeScreen + " - " + outputURL(),
			"Welcome": welcomeScreen,
			"UserID": userID,
			"UTM": utm
		});			
	
		$("#welcome-100").center();
		$("#welcome-headline").html("Get all the best daily deals in " + cities[cityEdition] + " in a single email.");	
		$("#black-background").show();
		$("#welcome-100").show();
		$("#black-background").click(function() {
			removeWelcome(1);
			$("#black-background").off('click');

		});
		$("#signup-email").focus();

	} else if (welcomeScreen == 200) {
		if (firstTimeCalled) {
			setTimeout('slideUpWelcome()', 3000);
		} else {
			slideUpWelcome();
		}
		
	} else if (welcomeScreen == 300) {
		if (firstTimeCalled) {
			setTimeout('slideUpWelcome()', 3000);
		} else {
			slideUpWelcome();
		}	


	} else if (welcomeScreen >= 400 && welcomeScreen < 500) {

		mpq.track("Sign up box displayed", {
			"mp_note": "Welcome screen " + welcomeScreen + " - " + outputURL(),
			"Welcome": welcomeScreen,
			"UserID": userID,
			"UTM": utm
		});

		$("#welcome-400").show();
		$("#welcome-400-overlay").center();
		
		if (categoriesToShow.indexOf(1) == categoriesToShow.lastIndexOf(1)) {
			$("#welcome-400-category").html(categories[categoriesToShow.indexOf(1)].toLowerCase());
		}
		$("#welcome-400-city").html(cities[cityEdition]);

	} else if (welcomeScreen == 500) {

		mpq.track("Sign up box displayed", {
			"mp_note": "Welcome screen " + welcomeScreen + " - " + outputURL(),
			"Welcome": welcomeScreen,
			"UserID": userID,
			"UTM": utm
		});			



		$("#welcome-500").show();
		$("#welcome-500-overlay").center();
		
		if (categoriesToShow.indexOf(1) == categoriesToShow.lastIndexOf(1)) {
			$("#welcome-500-category").html(categories[categoriesToShow.indexOf(1)].toLowerCase());
		}
		$("#welcome-500-city").html(cities[cityEdition]);
	}
}

function slideUpWelcome() {

	mpq.track("Sign up box displayed", {
		"mp_note": "Welcome screen " + welcomeScreen + " - " + outputURL(),
		"Welcome": welcomeScreen,
		"UserID": userID,
		"UTM": utm
	});

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
	var urlString = "/lookup_userid.php?fb_id=" + fbID;
	
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			userID = parseInt(data);
			initLoggedIn();

		},
		async: true
	});	
	
}


function sendNewPassword() {

	
	var email = $("#forgot-email").val();
	var urlString = "/mail_password.php?email=" + email;
	
	if (email == "") {
		return;
	}
	
	
	$("#forgot-message").html("Hang on a second...");
	
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			var result = parseInt(data);
			
			if (result == -1) {
				$("#forgot-message").html("Oops, that email isn't registered.");
				$("#forgot-message").show();
				$("#forgot-email").val("").focus();
				setTimeout(function() { $("#forgot-message").fadeOut() }, 3000);
			} else if (result == 1) {
				showLogin();
				$("#login-message").html("<span style='color:#156300; background-color:#9ac888; padding:3px 6px;'>Check your email!</span>");
				$("#login-message").show();
				setTimeout(function() { $("#login-message").fadeOut() }, 3000);
				$("#login-email").val(email);
				$("#login-password").focus();
			} else {
			
			}
			
			debug(data);

		},
		async: true
	});	
	
}


function signupUser() {
	
	var password_hash = hex_sha256($("#signup-password").val());
	var email = $("#signup-email").val();

	if (!validateEmail(email)) {
		$("#error-message").show();
		$("#error-message").html("Please enter a valid email address or <a href=\"javascript:void(0);\" onclick=\"removeWelcome(1);\" style=\"color:#687eab\">skip</a> signup.");
		$("#signup-email").val("").focus();
		$("#signup-password").val("");
		setTimeout(function() { $("#error-message").fadeOut() }, 10000);	
		return;
	}
	
	var d = new Date();
	debug("d.getTime(): " + d.getTime());
	var sessionID = d.getTime();
	debug("sessionID: " + sessionID);
	
	debug("UTM: " + utm);
	
	var urlString = "/signup_user.php?email=" + email + "&password_hash=" + password_hash + "&latitude=" + gbLat + "&longitude=" + gbLng + "&session_id=" + sessionID + "&utm=" + utm;
	
	jQuery.ajax({
		type: "GET",
		url: urlString,
		success: function (data) {
			debug("signupuser data: " + data);
			
			if (parseInt(data) == 0) {
				$("#error-message").show();
				$("#error-message").html("A user with that email already exists. Please choose another.");
				$("#signup-email").val("").focus();
				$("#signup-password").val("");
				setTimeout(function() { $("#error-message").fadeOut() }, 5000);
			} else if (parseInt(data) == -1) {
			
			
			} else {
			
				mpq.track("User signed up", {
					"mp_note": "Welcome screen " + welcomeScreen + " - " + outputURL(),
					"Welcome": welcomeScreen,
					"UserID": userID,
					"UTM": utm
				});			

				loggedIn = "DEALUPA";
				userID = parseInt(data);

				
				// Begin ftouch code for e-miles
				
				if (ftouch != "null") {
					var cache = [];

					var cacheImage = document.createElement('img');
					cacheImage.src = "http://www.e-miles.com/autocredit.do?pc=5GSXTBD4MVH7K6S&ftouch=" + ftouch + "&cs=1&id=" + userID;				
					cache.push(cacheImage);

					var cacheImage2 = document.createElement('img');
					cacheImage2.src = "http://50.57.125.179/images/company_logos/company_31.png?ftouch=" + ftouch;
					cache.push(cacheImage2);
				}
				
				// End ftouch code for e-miles

				
				$.cookie('session_cookie', sessionID, {
					expires: 1000,
					path:'/'
				});

				$.cookie('userid_cookie', userID, {
					expires: 1000,
					path:'/'
				});			
				removeWelcome();
				initLoggedIn();
			}
		},
		async: true
	});
	

}




function setFBCallbacks() {
	if (fbIsValid()) {
		FB.Event.subscribe('auth.login', function (response) {

			loggedIn = "FACEBOOK";
			
			FB.api('/me?fields=name,email,first_name,last_name,id', function (api_response) {
				debug("Current user's name: " + api_response.name);
				debug("Current user's email: " + api_response.email);
				
				urlString = "/save_user.php?user=" + api_response.id + "&first=" + api_response.first_name + "&last=" + api_response.last_name + "&email=" + api_response.email + "&latitude=" + gbLat + "&longitude=" + gbLng;
				
				debug(urlString);

				jQuery.ajax({
					url: urlString,
					success: function (data) {
						debug("User updated in the DB!");
						setUserID(FB.getUserID());
					},
					async: true
				});
				
			});
			loadAndDisplay();
		});
		
		FB.Event.subscribe('auth.statusChange', function (response) {
			debug("!!!!!!!!!!!!  FB STATUS CHANGE !!!!!!!!!!!! ");
		});

		FB.Event.subscribe('auth.logout', function (response) {

			loggedIn = "NONE";
			initLoggedOut();
			loadAndDisplay();
		});
	}
}



function fbIsValid() {
	return !(typeof FB === "undefined");
}

/*
function sliderCallback(event, ui) {
	if (ui.values[1] < SLIDER_MAX) {
		$("#amount").val("$" + ui.values[0] + " - $" + ui.values[1]);
	} else {
		$("#amount").val("$" + ui.values[0] + " - $" + ui.values[1] + " or more");
	}
	vacationsSliderLow = ui.values[0];
	vacationsSliderHigh = ui.values[1];
	sliderDirtyBit = 1;
	loadAndDisplay();
}
*/


function createNewSendButton(element, url, size) {

	if (fbIsValid()) {
		var elem = $(document.createElement("fb:like"));
		elem.attr("href", url);
		elem.attr("send", "true");
		elem.attr("layout", size);
		elem.attr("show_faces", "true");
		$(element).empty().append(elem).hide().fadeIn(500);
		FB.XFBML.parse($(element).get(0));
	}
}








function debug(string) {
	if (this.console && typeof console.log != "undefined") {
		console.log(string);
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

	myConsoleTime("*****changeView*****");

	
	if ((newView == "MAP" || newView == "LIST" || newView == "STARRED") && (newView != view)) {
		mpq.track("Changed view", {
			"mp_note": "Changed to " + newView + " - " + outputURL(),
			"View": view,
			"UTM": utm
		});
	}
	
	
	view = newView;
	//$("html").css("overflow", "auto");

	if (newView == "MAP") {

		//////////// USER CHANGED VIEW TO MAP /////////////

		$("#map-view-toggle").addClass("down");
		$("#list-view-toggle").removeClass("down");
		$("#map").show();
		$("#list-view-area").hide();
		$("#right-bar").hide();
		//$("#list-view-map").hide();
		$("#filters-bar").show();
		//$("#left-panels").show();
		//$("#edition").show();
		$("#single-deal-view").hide();
		$("#email-settings").hide();
		
		//$("html").css("overflow", "hidden");
		
		//google.maps.event.trigger(listMap, 'resize');
		
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
		
	} else if (newView == "LIST") {
	
		//////////// USER CHANGED VIEW TO LIST /////////////
		
		$("#map").hide();
		$("#email").hide();
		$("#right-bar").hide();
		
		$("#list-view").show();
		$("#list-view-num-deals").show();
		$("#filters-bar").show();
		//$("#edition").show();
		$("#list-view-area").show();
		if (query == "") {
			$("#sort-options").show();
		}


		$("#map-view-toggle").removeClass("down");
		$("#list-view-toggle").addClass("down");
				
		if ($(window).height() >= ($("#filters-bar").height() + 55 + 190 + 5)) {
			//$("#list-view-map").show();
		}
		
		$("#starred-list-view-num-deals").hide();
		$("#city-name").html(cities[cityEdition].toLowerCase());
		
		$("#single-deal-view").hide();
		
		
		//google.maps.event.trigger(listMap, 'resize');

	} else if (newView == "STARRED") {
	
		//////////// USER CHANGED VIEW TO STARRED /////////////

		// Pass 1 because we do *not* want the resetting of filter globals to replace state since pushState() is called
		// after changing view to STARRED. The pushState() call is in the onclick of the My Starred link itself.
		resetFiltersGlobals(1);
		
		// We want to make sure users see their expired starred deals
		//hideExpired = 0;
		updateFiltersUIBasedOnGlobals();
		
		$("#map").hide();
		$("#email").hide();
		$("#map-view-toggle").removeClass("down");
		$("#list-view-toggle").removeClass("down");

		$("#right-bar").hide();
		$("#list-view-num-deals").hide();
		$("#list-view").hide();
		$("#list-view-sorted-by").empty();
		$("#sort-options").hide();

		$("#list-view-area").show();
		$("#list-view").show();
		$("#starred-list-view-num-deals").show();
		
		if ($(window).height() >= ($("#filters-bar").height() + 55 + 190 + 5)) {
			//$("#list-view-map").show();
		}

		$("#filters-bar").show();
		//$("#edition").show();
		$("#single-deal-view").hide();


	} else if (newView == "SINGLE") {


		$("#single-deal-view").show();
		$("#email").hide();
		$("#map").hide();
		$("#right-bar").hide();
		$("#filters-bar").hide();
		//$("#edition").show();
		$("#list-view-area").hide();
		//$("#list-view-map").hide();
		$("#starred-list-view-num-deals").hide();
		$("#city-name").html(cities[cityEdition].toLowerCase());





	} else if (newView = "EMAIL") {
	
		$("#email").show();
		
		$("#single-deal-view").hide();
		$("#map").hide();
		$("#right-bar").hide();
		$("#filters-bar").hide();
		//$("#edition").show();
		$("#list-view-area").hide();
		//$("#list-view-map").hide();
		$("#starred-list-view-num-deals").hide();
		
		$("#city-name").html(cities[cityEdition].toLowerCase());
	
	
	
	
	
	}

	//showProperLeftPanel();
	myConsoleTimeEnd("*****changeView*****");
}



function loadAndDisplayEmail() {

	// First load the user's deals into the alerts div

	var urlString = "/email_alerts_html.php?user=" + userID;	
	jQuery.ajax({
		url: urlString,
		success: function (data) {
			$("#email-alerts").html(data);
			debug(data);
			
		
		},
		async: true
	});

	
	
	
	// Render the input div
	
	

	// 



}


function testAlert() {

	if ($("#email-keyword").val() == "") return;
	
	var category = $("#email-category").val();
	var edition = $("#email-edition").val();
	var keywords = $("#email-keyword").val();
	var yelp = $("#email-yelp").val();
	
	var params = "?c=" + categoriesReverseInt[category] + "&n=0&y=" + yelp + "&o=0&i=" + edition + "&v=LIST&l=0&h=1000&x=1&s=SOLD&q=" + keywords + "&p=0";
	
	testAlertXML = "/deal_html_from_url_params.php" + params;
	
	debug(testAlertXML);
	
	jQuery.ajax({
		type: "GET",
		url: testAlertXML,
		success: function (data) {
			$("#test-alerts-list").html(data);
			var numDeals = parseInt($("#list-view-data").attr("num-deals"))
			$("#test-alerts-title").html(numDeals + " deals match that alert");
		},
		async: true
	});
	
	// Move the test results section to the right spot
	$("#test-alerts").css("top", $("#email-top-panel").offset().top + $("#email-top-panel").height() + 30);
	
}

function addAlert() {

	var category = $("#email-category").val();
	var edition = $("#email-edition").val();
	var keywords = $("#email-keyword").val();
	var yelp = $("#email-yelp").val();
	
	debug("Adding: category - " + category + ", edition - " + edition + ", keywords: " + keywords);

	var params = encodeURIComponent("c=" + categoriesReverseInt[category] + "&n=0&y=" + yelp + "&o=0&i=" + edition + "&v=LIST&l=0&h=1000&x=1&s=SOLD&q=" + keywords);
	var urlString = "/add_email_alert.php?url=" + params + "&user=" + userID;
	
	
	$("#alert-none").remove();
	
	debug(params);
	
	jQuery.ajax({
		url: urlString,
		success: function (data) {
			var id = parseInt(data);
			$("#alerts-table tr:nth-child( " + ($("#alerts-table tr").length - 2) + ")").before("\
				<tr id='alert-" + id +"'>\
					<td>" + keywords + "</td>\
					<td>" + cities[edition] + "</td>\
					<td><a class='category-" + category + "'>" + categories[category] + "</td>\
					<td>" + yelp + "</td>\
					<td><a href='javascript:void(0)' onclick='deleteAlert(" + id + "); return false;'>Delete</a></td>\
				</tr>");

			// Move the test results section to the right spot
			$("#test-alerts").css("top", $("#email-top-panel").offset().top + $("#email-top-panel").height() + 30);

		},
		async: true
	});	
	
}


function deleteAlert(id) {

	var urlString = "/delete_email_alert.php?id=" + id;
	
	jQuery.ajax({
		url: urlString,
		success: function (data) {
			$("#alert-" + id).remove();

			// Move the test results section to the right spot
			$("#test-alerts").css("top", $("#email-top-panel").offset().top + $("#email-top-panel").height() + 30);
		},
		async: true
	});	


	
}



function getEmailAlerts() {
	myConsoleTime("*****getEmailAlerts*****");
	
	if (!isLoggedIn()) return;
	
	var emailAlerts;
	
	var urlString = "/email_alerts_html.php?user=" + userID;
	jQuery.ajax({
		url: urlString,
		success: function(data) {
			emailAlerts = data;
			
		},
		async: false
	});
	
	myConsoleTimeEnd("*****getEmailAlerts*****");
	return emailAlerts;
}



function resetFiltersGlobals(doNotReplaceState) {

	categoriesToShow = [0, 1, 1, 1, 1, 1, 1, 1, 1, 1];
	
	/*
	if (cityEdition == VACATIONS_CITY_ID) {
		categoriesToShow = [0, 0, 0, 0, 0, 0, 0, 0, 0, 1];
	}
	*/
	
	showOnlyNew = 0;
	//hideExpired = 1;
	showYelp = 0;
	showCompany = 0;
	//vacationsSliderLow = 0;
	//vacationsSliderHigh = SLIDER_MAX;	

	updateFiltersUIBasedOnGlobals();
	
	if (!doNotReplaceState) {
		replaceState();
	}
}



function updateFiltersUIBasedOnGlobals() {

	/*
	for (i in categoriesToShow) {
		if (categoriesToShow[i] == 1) {
			$("#filter-" + i).removeClass("unselected");
			$("#filter-image-" + i).attr("src", "/images/check.png");
		} else {
			$("#filter-" + i).addClass("unselected");
			$("#filter-image-" + i).attr("src", "/images/check_off.png");
		}
	}
	*/

	setNumberHidden();
	
	if (showOnlyNew == 0) {
		$("#filter-show-new").prop("checked", false);
	} else {
		$("#filter-show-new").prop("checked", true);
	}
	/*
	if (hideExpired == 0) {
		$("#filter-hide-expired").prop("checked", false);
	} else {
		$("#filter-hide-expired").prop("checked", true);
	}
	*/

	//$("#filter-yelp").val(showYelp);
	setYelp(showYelp);
	
	$("#filter-company").val(showCompany);
	
	/*
	$("#slider-range").slider({
		range: true,
		min: 0,
		max: SLIDER_MAX,
		values: [vacationsSliderLow, vacationsSliderHigh],
		step: 50
	});

	if (vacationsSliderHigh < SLIDER_MAX) {
		$("#amount").val("$" + vacationsSliderLow + " - $" + vacationsSliderHigh);
	} else {
		$("#amount").val("$" + vacationsSliderLow + " - $" + vacationsSliderHigh + " or more");
	}
	*/
}





function loadAndDisplay(doNotReloadList) {
	updateFiltersUIBasedOnGlobals();

	// We don't want to remember a single-deal view and starred view as the last_view for UX reasons

	var newCookieValue = outputURL().replace(/&q=.*/, "");	
	if (view != "SINGLE") {
		$.cookie('last_view', newCookieValue, {
			expires: 1000,
			path:'/'
		});
		debug("cookie changed to: " + newCookieValue);
	}


	if (view == "MAP") {
		loadAndDisplayMarkers();
	} else if (view == "LIST") {
		if (doNotReloadList) {
		} else {
			loadAndDisplayDeals();
		}
		$(document).scrollTop(0);
	} else if (view == "STARRED") {
		loadAndDisplayDeals();
		$(document).scrollTop(0);
	} else if (view == "SINGLE") {
		loadAndDisplaySingle(mapViewDealID, cityEdition);
	} else if (view == "EMAIL") {
		loadAndDisplayEmail();
	}
}


/*
function setOnly(cat) {
	
	mpq.track("Set only turned on", {
		"mp_note": "Set only " + categories[cat] + " - " + outputURL(),
		"Category": categories[cat],
		"City": cities[cityEdition],
		"View": view
	});	
	
	categoriesToShow = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
	categoriesToShow[cat] = 1;

	for (var i = 1; i < categoriesToShow.length + 1; i++) {
		$("#filter-" + i).addClass("unselected");
		$("#filter-image-" + i).attr("src", "/images/check_off.png");
	}
	$("#filter-image-" + cat).attr("src", "/images/check.png");
	$("#filter-" + cat).removeClass("unselected");
	
	
	replaceState();
	loadAndDisplay();
}

*/
/////////////////////////////////////////////



function loadAndDisplayDeals(appendItems) {
	
	myConsoleTime("*****loadAndDisplayDeals*****");
	
	if(!appendItems) {
		pages = 1;
	}

	var prefix = "";
	
	// We don't want to show the "loading" message for the very first load of the page in the session since
	// we *do* want to give the illusion that the page has fully loaded.
	if (initialLoadCompleted) {
		$("#loading-div").center().show();
		//$("#list-view-area").css({ opacity: 0.4 });
	
	
	// If the initial load has not yet completed, we needn't reload the list because index.php arrives to
	// the client with the deals already in place.	
	} else {
		var numDeals = parseInt($("#list-view-data").attr("num-deals"))
		$("#" + prefix + "list-view-sorted-by").html(" - " + numDeals + " deals");
		return;
	}
	
	
	// STARRED DEALS
	if (view == "STARRED") {
		prefix = "starred-";
		dealXML = "/deal_html_from_url_params.php" + outputURL() + "&p=0";
		
		
	// LIST VIEW
	} else if (view == "LIST" && query == "") {		
		dealXML = "/deal_html_from_url_params.php" + outputURL() + "&p=" + pages;
		if (currentSortBy == "DISTANCE") {
			dealXML += "&z=" + zip;
		}
		
		
	// SEARCH RESULTS
	} else if (view == "LIST" && query != "") {
		dealXML = "/deal_html_from_url_params.php" + outputURL() + "&p=0";
	}
	
	
	debug(dealXML);

	myConsoleTime("*****time to transfer html, insert, and update*****");
	myConsoleTime("*****data transfer time for deal_html HTML*****");
	
		jQuery.ajax({
			type: "GET",
			url: dealXML,
			success: function (data) {
				myConsoleTimeEnd("*****data transfer time for deal_html HTML*****");
				
				myConsoleTime("*****inserting the html into the list*****");
				
				if (!appendItems) {
					$('#container').empty();
				}
				
				var $boxes = $(data);
				$boxes.imagesLoaded(function(){

					var $container = $('#container');
					
					$boxes.find("img.deal-image").each(function () {
						var w = this.width;
						var h = this.height;
						
						// If it's a very wide landscape image...
						if ((w/h) > 1.75) {
							// debug("w/h of " + this.src + ": " + (w/h));
							this.style.height = "250px";
							this.style.width = "auto";
							
							debug(this.src + ": A (" + w + " x " + h + ")");
						}
						
						// If it's a portrait image, nudge it as needed
						if ((w/h) < 1 && h > 310) {
							var nudge = (h - 310) / -2;
							// debug("nudge of " + this.src + ": " + nudge);
							this.style.marginTop = nudge + "px";
							
							debug(this.src + ": B (" + w + " x " + h + ")");
						}
						
						debug(this.src + ": C (" + w + " x " + h + ")");
					});
					
					$container.append($boxes).masonry('appended', $boxes /* Not adding true because it creates odd gaps in the pages... */);

					$container.masonry({
						itemSelector : '.box',
						gutterWidth : 14,
						isAnimated: true,
						isFitWidth: true
					});


					
					
					// Not sure why, but overflows are set to hidden by Masonry
					// so reseting so we see shadows properly.
					$container.css({ "overflow": "visible"});
					$container.masonry('reload');
					
					var numDeals = parseInt($("#list-view-data").attr("num-deals"))
					$("#" + prefix + "list-view-sorted-by").html(" - " + numDeals + " deals");
					
					myConsoleTimeEnd("*****inserting the html into the list*****");
					
					registerListViewCallBacks();
					
					$("#loading-div").hide();

					myConsoleTimeEnd("*****search to load*****");
					myConsoleTimeEnd("*****time to transfer html, insert, and update*****");
					myConsoleTimeEnd("*****loadAndDisplayDeals*****");
					
				});

			},
			async: true
		});
		
	


}


function registerListViewCallBacks() {
	myConsoleTime("*****registerListViewCallBacks*****");
	var divs = $("#container > div");
	
	
	var re = /[0-9]5/;
	
	var starredDeals = getStarredDealsID();
	debug("starredDeals:" + starredDeals);
	
	
	
	
	myConsoleTime("*****updatelist loop*****");
	for (var i = 0; i < divs.length; i++) {
		var dealDiv = divs[i];	
		
		var lat = dealDiv.getAttribute("lat-at");
		var lng = dealDiv.getAttribute("lng-at");
		var id = dealDiv.getAttribute("id-at");
		var category_id = dealDiv.getAttribute("cat-at");
		var company = dealDiv.getAttribute("cmp-at");
		var yelp = dealDiv.getAttribute("y-at");
		var price = dealDiv.getAttribute("p-at");
		var value = dealDiv.getAttribute("v-at");

		var element = "#list-deal-star-" + id;
		if (!isLoggedIn()) {
			$(element).html("");
		} else if ($.inArray(parseInt(id), starredDeals) > -1) {
			$(element).html("<a href='javascript:void(0);' onclick='unsaveDeal(\"" + id + "\", \"" + element + "\")'><img src='/images/heart_red.png'></a>");
		} else {
			$(element).html("<a href='javascript:void(0);' onclick='saveDeal(\"" + id + "\", \"" + element + "\")'><img src='/images/heart_gray.png'></a>");
		}


		$(dealDiv).mouseenter(function() {
			$(this).addClass("box-focus");
			$(this).find(".expansion").show();
			//$(this).find(".on-black").css({ 'background-color' : 'rgba(0, 0, 0, .95)' });
		});

		$(dealDiv).mouseleave(function() {
			$(this).removeClass("box-focus");
			$(this).find(".expansion").hide();
			//$(this).find(".on-black").css({ 'background-color' : 'rgba(0, 0, 0, .7)' });
		});		
	
		

		////////////////////////////////////////////////////////////////////////
		// Callback registration below /////////////////////////////////////////
		
		//$(dealDiv).mouseenter(listItemEnter(dealDiv, lat, lng, id, category_id));
		//$(dealDiv).mouseleave(listItemLeave(dealDiv, lat, lng, id, category_id));

		
		$(dealDiv).find("#title").click(function () {
		
			var id = parseInt($(this).closest(".top-div").attr("id-at"));
			var category_id = $(this).closest(".top-div").attr("cat-at");
			var company = $(this).closest(".top-div").attr("cmp-at");
			var yelp = parseFloat($(this).closest(".top-div").attr("y-at"));
			var price = parseInt($(this).closest(".top-div").attr("p-at"));
			var value = parseInt($(this).closest(".top-div").attr("v-at"));

		
			mpq.track("Clicked to single deal view", {
				"mp_note": "Clicked on " + id + " - " + outputURL(),
				"Company": companies[company],
				"Category": categories[category_id],
				"Yelp": yelp,
				"City": cities[cityEdition],
				"Price": price,
				"Value": value,
				"View": "LIST",
				"Sort": currentSortBy,
				"UserID": userID
			});
			openDealInSingleDealView(id, cityEdition);
			return false;
		});




		$(dealDiv).find("#details-" + id).click(function () {
		
			var id = parseInt($(this).closest(".top-div").attr("id-at"));
			var category_id = $(this).closest(".top-div").attr("cat-at");
			var company = $(this).closest(".top-div").attr("cmp-at");
			var yelp = parseFloat($(this).closest(".top-div").attr("y-at"));
			var price = parseInt($(this).closest(".top-div").attr("p-at"));
			var value = parseInt($(this).closest(".top-div").attr("v-at"));
		
			mpq.track("Clicked to external deal site", {
				"mp_note": "Clicked on " + id + " - " + outputURL(),
				"Company": companies[company],
				"Category": categories[category_id],
				"Yelp": yelp,
				"City": cities[cityEdition],
				"Price": price,
				"Value": value,
				"View": "LIST-button",
				"Sort": currentSortBy,
				"UserID": userID
			});
		});		
		

		$(dealDiv).find("#image-" + id).click(function () {
		
			var id = parseInt($(this).closest(".top-div").attr("id-at"));
			var category_id = $(this).closest(".top-div").attr("cat-at");
			var company = $(this).closest(".top-div").attr("cmp-at");
			var yelp = parseFloat($(this).closest(".top-div").attr("y-at"));
			var price = parseInt($(this).closest(".top-div").attr("p-at"));
			var value = parseInt($(this).closest(".top-div").attr("v-at"));
		
			mpq.track("Clicked to external deal site", {
				"mp_note": "Clicked on " + id + " - " + outputURL(),
				"Company": companies[company],
				"Category": categories[category_id],
				"Yelp": yelp,
				"City": cities[cityEdition],
				"Price": price,
				"Value": value,
				"View": "LIST-image",
				"Sort": currentSortBy,
				"UserID": userID
			});
		});


		
		
		/*
		
		$("#yelp-link-" + id).mouseover(function () {
			
			var dealID = parseInt($(this).attr("id").replace("yelp-link-", ""))
			
			$("#list-yelp-reviews").html($("#yelp-reviews-" + dealID).html());			
	
			var topOffset = $("#yelp-link-" + dealID).offset().top;
			var windowOffset = $(window).scrollTop();
			var yelpOffset = parseInt(topOffset) - ($("#list-yelp-reviews").height() / 2);
			if (yelpOffset < (windowOffset + 50)) yelpOffset = 50 + windowOffset;

			var scrollBottom = $(window).scrollTop() + $(window).height();
			if ((yelpOffset + $("#list-yelp-reviews").height()) > (scrollBottom - 50)) yelpOffset = scrollBottom - $("#list-yelp-reviews").height() - 30;

			$("#list-yelp-reviews").css("top", yelpOffset + "px");
		});

		$("#yelp-link-" + id).mouseover(function () {
			$("#list-yelp-reviews").show();
			clearTimeout(yelpTimeoutList);
		});

		$("#yelp-link-" + id).mouseout(function () {
			yelpTimeoutList = setTimeout("clearYelpList()", 200);
		});		
		
		*/
		
		
		
		
		
		
		
		
		
		
		
		
		/*

		
		$("#l-d-logo-" + id).click(function () {
		
			var id = parseInt($(this).closest(".top-div").attr("id-at"));
			var category_id = $(this).closest(".top-div").attr("cat-at");
			var company = $(this).closest(".top-div").attr("cmp-at");
			var yelp = parseFloat($(this).closest(".top-div").attr("y-at"));
			var price = parseInt($(this).closest(".top-div").attr("p-at"));
			var value = parseInt($(this).closest(".top-div").attr("v-at"));
		
			mpq.track("Clicked to external deal site", {
				"mp_note": "Clicked on " + id + " - " + outputURL(),
				"Company": companies[company],
				"Category": categories[category_id],
				"Yelp": yelp,
				"City": cities[cityEdition],
				"Price": price,
				"Value": value,
				"View": "LIST-logo",
				"Sort": currentSortBy,
				"UserID": userID
			});
		});		
		
		





		

		
		$("#l-d-s-" + id).mouseover(function () {
			var dealID = parseInt($(this).attr("id").replace("l-d-s-", ""))		
			var url = $(this).parent().parent().parent().attr("url-at");
			url = "http://dealupa.com" + url;
			// Check if it's less than 35 because we don't want to keep reloading the send button if it's already there
			if ($("#l-d-s-" + dealID).html().length < 35) {
				// createNewSendButton("#l-d-s-" + dealID, url + "?utm=facebook_share", "button_count");	
				createNewSendButton("#l-d-s-" + dealID, url + "?utm=facebook_share", "button_count");	
			}
		});		
		
		// End callback registration ///////////////////////////////////////////
		////////////////////////////////////////////////////////////////////////
		*/

		
	}
	myConsoleTimeEnd("*****updatelist loop*****");

	
	if (view == "STARRED") {
		$("#starred-list-view-num-deals").html("My Starred Deals");
	} else if (query == "") {
		$("#list-view-num-deals").html("<b>Daily deals in " + cities[cityEdition] + "</b>");
	} else if (query != "") {
		$("#list-view-num-deals").html("Deals for <b>" + query + "</b> in " + cities[cityEdition]);
	}

	prefix = (view == "STARRED") ? "starred" : "";
	
	currentSortBy == "SOLD" ? sortByString = "number sold" : sortByString = currentSortBy.toLowerCase();	
	
	/*
	$("#" + prefix + "list-view-sorted-by").html("sorted by " + sortByString);
	if (query != "") {
		$("#" + prefix + "list-view-sorted-by").html("sorted by relevance");
	}
	*/
	
	$("#sort" + currentSortBy).addClass("sort-on");
	

	myConsoleTimeEnd("*****registerListViewCallBacks*****");
}











function openDealInSingleDealView(dealToDisplay, edition) {
	debug("openDealInSingleDealView");
	listScrollPosition = $(document).scrollTop();
	mapViewDealID = dealToDisplay;
	changeView("SINGLE");
	loadAndDisplaySingle(dealToDisplay, edition);
	pushState();
}




function loadAndDisplaySingle(dealIDToDisplay, edition) {
	selectedMarkerImageIndex = 0;
	

	var dealXML = "/single_deal_html.php?m=" + dealIDToDisplay + "&i=" + edition;

	jQuery.ajax({
		type: "GET",
		url: dealXML,
		success: function (data) {
			$("#single-deal-view").html(data);
		},
		async: false
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
	
	var singleMapOptions = {
		zoom: 12,
		center: new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]),
		mapTypeId: google.maps.MapTypeId.ROADMAP
	}

	singleMap = new google.maps.Map(document.getElementById("single-map"), singleMapOptions);
	

	if (singleDeal.imageUrls.length <= 1) {
		$("#single-image-prev").fadeOut();
		$("#single-image-next").fadeOut();
	} else {
		$("#single-image-prev").fadeIn();
		$("#single-image-next").fadeIn();
	}




	$("#single-image").attr("src", singleDeal.imageUrls[0]);
	$("#single-image").hide();
	var pic_real_width, pic_real_height, ar;
	$("<img/>").attr("src", $("#single-image").attr("src")  + "?" + new Date().getTime()).load(function() {
	
		pic_real_width = this.width;
		pic_real_height = this.height;
		ar = pic_real_width / pic_real_height;

		if (parseFloat(ar) > 1.73684211) {
			var newWidth = parseInt(350 * ar);
			newWidth = newWidth + "px";
			$("#single-image").animate({width: newWidth}, 0);
			$("#single-image").animate({height: "350px"}, 0, function() {
				$("#single-image").show();
			});
		} else {
			var newHeight = parseInt(630 / ar);
			newHeight = newHeight + "px";
			$("#single-image").animate({width: "630px"}, 0);
			$("#single-image").animate({height: newHeight}, 0, function() {
				$("#single-image").show();
			});
		}
		
	});

	
	$("#single-deal-send").mouseover(function () {
		// Check if it's less than 35 because we don't want to keep reloading the send button if it's already there
		if ($("#single-deal-send").html().length < 45) {
			createNewSendButton("#single-deal-send", singleDeal.fullUrl + "?utm=facebook_share", "standard")
		}
	});		

	

	
	
	$("#city-name").html(cities[cityEdition].toLowerCase());
	//$("#city-selector-current-city").html(cities[cityEdition].toLowerCase());	

	deleteOverlays(singleDealMarkersArray);
	var latLngBounds = new google.maps.LatLngBounds();

	if (singleDeal.lats == undefined || singleDeal.lngs == undefined) {
		$("#single-map").hide();
	
	} else {
		$("#single-map").show();
		google.maps.event.trigger(singleMap, 'resize');
		singleMap.setCenter(new google.maps.LatLng(singleDeal.lats[0], singleDeal.lngs[0]));
		
		for (var m = 0; m < singleDeal.lats.length; m++) {
			var markerLatLng = new google.maps.LatLng(singleDeal.lats[m], singleDeal.lngs[m]);
			latLngBounds.extend(markerLatLng);
			
			var marker = new google.maps.Marker({
				position: markerLatLng,
				icon: eval("marker" + singleDeal.categoryID),
				shadow: markerShadow,
				map: singleMap
			});			
			singleDealMarkersArray.push(marker);
		}
		
		if (singleDeal.lats.length > 1) {
			singleMap.fitBounds(latLngBounds);
		} else {
			singleMap.setZoom(12);
		}
		
	}

	$(document).scrollTop(0);
	google.maps.event.trigger(singleMap, 'resize');
	return;

}



function outputURL() {

	var urlString;

	
	if (view == "SINGLE") {
		urlString = "?m=" + mapViewDealID +
					"&v=" + view;
	}

	
	////////////////////////////////////////////////////////////////////////////
	
	
	if (view == "LIST") {
		urlString = "?c=" + categoriesToShow + 
					"&n=" + showOnlyNew + 
					"&y=" + showYelp + 
					"&o=" + showCompany + 
					"&i=" + cityEdition + 
					"&v=" + view +
					//"&l=" + vacationsSliderLow +
					//"&h=" + vacationsSliderHigh +
					//"&x=" + hideExpired +
					"&s=" + currentSortBy;

		if (currentSortBy == "DISTANCE") {
			urlString += "&z=" + zip;
		}
	}
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	if (view == "MAP") {
		urlString = "?t=" + trueRound(map.getCenter().lat(), 4) + 
					"&g=" + trueRound(map.getCenter().lng(), 4) + 
					"&z=" + map.getZoom() + 
					"&c=" + categoriesToShow + 
					"&n=" + showOnlyNew + 
					"&y=" + showYelp + 
					"&o=" + showCompany + 
					"&v=" + view +
					//"&l=" + vacationsSliderLow +
					//"&h=" + vacationsSliderHigh + 
					//"&x=" + hideExpired +
					"&m=" + mapViewDealID;
	}
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	if (view == "STARRED") {
		urlString = "?c=" + categoriesToShow + 
					"&n=" + showOnlyNew + 
					"&y=" + showYelp + 
					"&o=" + showCompany + 
					"&i=" + cityEdition + 
					"&v=" + view +
					//"&l=" + vacationsSliderLow +
					//"&h=" + vacationsSliderHigh +
					//"&x=" + hideExpired + 
					"&s=" + currentSortBy + 
					"&u=" + userID;
	}
	
	if (view == "EMAIL") {
		urlString = "?v=" + view +
					"&u=" + userID;	
	}
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	if (query != "" && (view == "MAP" || view == "LIST")) {
		urlString += "&q=" + query;
	}
	
	return urlString;

}

function findMarkersArrayIndex(id) {
	for (var i = 0; i < markersArray.length; i++) {
		if (markersArray[i].dealID == id) {
			return i;
		}
	}
	return -1;
}

function parseURLParameters(params) {

	
	var iParam = getParameter('i', params);
	var tParam = getParameter('t', params);
	var gParam = getParameter('g', params);
	var zParam = getParameter('z', params);
	var cParam = getParameter('c', params);
	var yParam = getParameter('y', params);
	var oParam = getParameter('o', params);
	var nParam = getParameter('n', params);
	var xParam = getParameter('x', params);
	var vParam = getParameter('v', params);
	var lParam = getParameter('l', params);
	var hParam = getParameter('h', params);
	var sParam = getParameter('s', params);
	var mParam = getParameter('m', params);
	var qParam = getParameter('q', params);
	var utmParam = getParameter('utm', params);
	var wParam = getParameter('w', params);

	ftouch = getParameter('ftouch', params);
	
	
	
	if (zParam != "null") {
	
		// Error check
		if (isNaN(zParam)) {
			zParam = 12;
		}
	
		map.setZoom(parseInt(zParam));
	}
	
	if (tParam != "null" || gParam != "null") {

		// Error check
		if (isNaN(tParam) || isNaN(gParam)) {
			if (!isNaN(gbLat) || !isNaN(gbLng)) {
				tParam = gbLat;
				gParam = gbLng;
			} else {
				tParam = cityLat[12];
				gParam = cityLng[12];
			}
		}
		
		map.setCenter(new google.maps.LatLng(tParam, gParam));
	}
	
	if (iParam != "null") {
		var re = /^[0-9]+$/;
		
		// If iParam is a number...
		if (re.test(iParam)) {
			cityEdition = parseInt(iParam);
			
		// *** maybe delete: even when user loads /seattle/list, it's rewritten to i=3 by mod_rewrite and urlparams is set to ...i=X...
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

	if (cParam != "null") {
		categoriesToShow = eval("[" + cParam + "]");
		
		//if (arrays_equal(categoriesToShow, [0,0,0,0,0,0,0,0,0,1])) {
		//	cityEdition = VACATIONS_CITY_ID;
		//}
		
	}

	if (yParam != "null") {
		showYelp = parseFloat(yParam);
	}

	if (oParam != "null") {
		showCompany = parseInt(oParam);
	}
	
	if (nParam != "null") {
		showOnlyNew = parseInt(nParam);
	}
	/*
	if (xParam != "null") {
		hideExpired = parseInt(xParam);
	}
	*/

	if (sParam != "null") {
		currentSortBy = sParam;
		$("#sort" + currentSortBy).addClass("sort-on");
		
	}
	
	if (vParam != "null") {
		view = vParam;
		if (view == "STARRED" && !isLoggedIn()) {
			view = "LIST";
		}
	}
/*
	if (lParam != "null") {
		vacationsSliderLow = lParam;
	}

	if (hParam != "null") {
		vacationsSliderHigh = hParam;
	}
	*/
	
	
	if (mParam != "null") {
		mapViewDealID = parseInt(mParam);
	}

	
	if (qParam != query && !(qParam == "null" && query == "")) {
		// We need to force the reload of markers and deals since the query has changed
		currNELng = -1;
	}

	query = "";
	$("#top-bar-search-box").val("");

	if (qParam != "null") {
			
		if (categoriesReverse[qParam] != null) {
			categoriesToShow = eval("[" + categoriesReverse[qParam] + "]");
		} else {
			query = qParam;
			query = query.replace(/-/g, ' ') ;
			$("#top-bar-search-box").val(query);
			$("#clear-search-button").show();
		}
	}
	
	if (vParam == "null" && mParam != "null") {
		view = "SINGLE";
	}

	if (wParam != "null") {
		welcomeScreen = wParam;
	}	

	if (utmParam != "null") {
		utm = utmParam + "_" + welcomeScreen;
	} else {
		utm += "_" + welcomeScreen;
	}
}




function updateStarsInListView() {
	myConsoleTime("*****updateStarsInListView*****");
	
	if (view != "LIST" && view != "STARRED") return;
	
	var divs = $("#list-view > div");
	
	var starredDeals = getStarredDealsID();
	
	for (var i = 0; i < divs.length; i++) {
	
		var id = divs[i].getAttribute("id-at");
		var element = "#list-deal-star-" + id;
		
		if (!isLoggedIn()) {
			$(element).html("");
		} else if ($.inArray(parseInt(id), starredDeals) > -1) {
			$(element).html("<a href='javascript:void(0);' onclick='unsaveDeal(\"" + id + "\", \"" + element + "\")'><img src='/images/heart_red.png'></a>");
		} else {
			$(element).html("<a href='javascript:void(0);' onclick='saveDeal(\"" + id + "\", \"" + element + "\")'><img src='/images/heart_gray.png'></a>");
		}
	}

	myConsoleTimeEnd("*****updateStarsInListView*****");
}


function getStarredDealsID() {
	myConsoleTime("*****getStarredDealsID*****");
	
	if (!isLoggedIn()) return;
	
	var starredDeals;
	
	var urlString = "/get_saved_deals.php?user=" + userID;
	jQuery.ajax({
		url: urlString,
		success: function (data) {
			starredDeals = eval("[" + data + "]");
			
		},
		async: false
	});
	
	myConsoleTimeEnd("*****getStarredDealsID*****");
	return starredDeals;
}





// One-time initializations to do if there is a logged in user

function initLoggedIn() {

	if (userID == 7 || userID == 1446 || userID == 115) {

		$("#top-bar-links").html("\
			<a href='javascript:void(0);' onclick='changeView(\"EMAIL\"); loadAndDisplayEmail(); pushState();'>\
				Email\
			</a>\
			&nbsp;&nbsp;&nbsp;\
			<a href='javascript:void(0);' onclick='changeView(\"STARRED\"); loadAndDisplayDeals(); pushState();'>\
				&nbsp;My Starred\
			</a>\
		");
	
	
	} else {
	
		$("#top-bar-links").html("\
			<a href='javascript:void(0);' onclick='changeView(\"STARRED\"); loadAndDisplayDeals(); pushState();'>\
				&nbsp;My Starred\
			</a>\
		");
	}
	
	jQuery.ajax({ 
		url: "/07012011.php?user=" + userID,
		success: function (data) {
			
			$("#top-bar-user-id").html(data);
		},
		async: true
	});
	
	setHeartIcon(mapViewDealID, "#deal-star");
	
	removeWelcome(0);
	//displaySearches();

	
	
	$("#login-button").poshytip('hide');
	$("#login-div-container").hide();
	$("#login-button").hide();
	$("#signup-button").hide();
	$("#logout-button").show();
	
	$("#login-password").val("");
	$("#change-message").val("");
	$("#login-message").val("");
	
	//$("#deal-star").qtip('disable');
	//$("#save-search-button-span").qtip('disable');	
	
	
	jQuery.ajax({
		url: "/update_user.php?user=" + userID,
		success: function (data) {
			
			debug("User updated in the db");
		},
		async: true
	});
	
	updateStarsInListView();

}







function initLoggedOut() {

/*
	$("#deal-star").qtip({
		content: {
			text: "Log in to star deals"
		},
		position: {
			my: "top right",
			at: "left bottom"
		}
	});

	$("#save-search-button-span").qtip({
		content: {
			text: "Log in to save views"
		},
		position: {
			my: "top left",
			at: "right bottom"
		}
	});
*/
	$("#saved-searches").empty();
	$("#top-bar-links").html("");
	$("#top-bar-user-id").html("");
	setHeartIcon(mapViewDealID, "#deal-star");
//	$("#deal-star").qtip('enable');
	//$("#save-search-button-span").qtip('enable');
	//$("#save-search-button").qtip('disable');

	$("#login-button").show();
	$("#signup-button").show();
	$("#logout-button").hide();
	
	if (view == "STARRED" || view == "EMAIL") {
		changeView("LIST");
	}
	
	updateStarsInListView();
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

/*
function showProperLeftPanel() {
	
	if (cityEdition == VACATIONS_CITY_ID && view != "STARRED") {
		$("#city-name").html(cities[VACATIONS_CITY_ID].toLowerCase());
		$("#filters-cities").hide();
		$("#filters-vacations").show();
		$("#vacations").addClass("selected-tab");
		$("#edition").addClass("selected-tab");
	} else {
		$("#filters-cities").show();
		$("#filters-vacations").hide();
		$("#vacations").removeClass("selected-tab");
		$("#edition").addClass("selected-tab");		
	}
}
*/

function changeEdition(newEdition) {

	mpq.track("Changed edition", {
		"mp_note": "Changed to " + cities[newEdition] + " - " + outputURL(),
		"View": view
	});


	
	// If the user was just seeing vacation deals, it means markersArray contained vacation deals _only_.
	// We need to now force a reload of markers so that markers of _all_ categories are loaded. 
	// Basically, any time you transition from vacation to city (or back), we need to reload markers.
	//if (newEdition == VACATIONS_CITY_ID || cityEdition == VACATIONS_CITY_ID) {
	//	debug("SETTING currNELng to -1 (4)");
	//	currNELng = -1;
	//}
	
	cityEdition = newEdition;

	// Pass 1 because we do *not* want the resetting of filter globals to replace state since anyone who calls changeEdition
	// will also call pushState().
	resetFiltersGlobals(1);

	
	//$("#city-selector").hide();

	
	if (cityEdition == NATIONWIDE_CITY_ID) {
		changeView("LIST");
		$("#view-toggle").slideUp();	
	} else {
		$("#view-toggle").slideDown();
	}
	
	if (view == "LIST") {
		changedEditionWhileInListView = 1;
		//showProperLeftPanel();
		loadAndDisplay();
	} else if (view == "MAP") {
		//if (newEdition == VACATIONS_CITY_ID) {
		//	selectedMarkerIndex = 0;
		//	deleteOverlays(markersArray);
		//}
		map.setZoom(cityZoom[cityEdition]);		
		map.setCenter(new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]));
		//showProperLeftPanel();
		// Markers will be automatically loaded and displayed by the map's idle callback
	} else if (view == "STARRED") {
		map.setCenter(new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]));
		changeView("LIST");
		loadAndDisplay();
	} else if (view == "SINGLE") {
		map.setCenter(new google.maps.LatLng(cityLat[cityEdition], cityLng[cityEdition]));
		
		// If user is seeing a deal and changes edition, go to the LIST view
		changeView("LIST");
		loadAndDisplay();
	}


	//$("#edition").show();
	$("#city-name").html(cities[cityEdition].toLowerCase());
	//$("#city-selector-current-city").html(cities[cityEdition]);	
	

}


function pushState() {

	var urlString = "/" + outputURL();

	var stateObject = {
		stateParams: outputURL()
	};
	
	debug("*********** PUSH ************");
	debug("* " + outputURL());
	//debug("Caller is " + arguments.callee.caller.toString());
	debug("*****************************");
	
	if (BrowserDetect.browser != "Explorer") {
		if (view == "SINGLE") {
			var urlTitle = getSingleDealURLRelative(singleDeal.edition, singleDeal.dealID, singleDeal.maintitle);
			history.pushState(stateObject, "", urlTitle);
			historyObject.push(urlString);
		} else {
			history.pushState(stateObject, "", urlString);
			historyObject.push(urlString);
		}
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


function replaceState(doNotUpdateURL) {

	var urlString = "/" + outputURL();

	var stateObject = {
		stateParams: outputURL()
	};
	
	debug("............ REPLACE ............");
	debug("* " + outputURL());
	//debug("Caller is " + arguments.callee.caller.name);
	debug(".................................");

	if (BrowserDetect.browser != "Explorer") {
	
		if (view == "SINGLE") {
			var urlTitle = getSingleDealURLRelative(singleDeal.edition, singleDeal.dealID, singleDeal.maintitle);
			if (doNotUpdateURL) {
				history.replaceState(stateObject, "");
			} else {
				history.replaceState(stateObject, "", urlTitle);
			}
			historyObject[historyObject.length - 1] = urlTitle;
		} else {
			if (doNotUpdateURL) {
				history.replaceState(stateObject, "");
			} else {
				history.replaceState(stateObject, "", urlString);
			}
			historyObject[historyObject.length - 1] = urlString;
		}

	}
}



function hyphenateTitle(urlTitle) {

	// !!! IMPORTANT
	// ANY CHANGES MADE HERE MUST ALSO BE MADE IN hyphenate_title IN deal_html.php

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


function resizeMap() {

	$("#map").height($(window).height() - $("#top-bar").outerHeight());
	$("#welcome-100").center();
	$("#welcome-400-overlay").center();
	$("#welcome-500-overlay").center();
	$("#loading-div").center();

	var lat = map.getCenter().lat();
	var lng = map.getCenter().lng();

	google.maps.event.trigger(map, 'resize');

	if (lat != "" && lng != "") {
		map.setCenter(new google.maps.LatLng(lat, lng));
	}

	if ($(window).height()  >= (55 + $("#filters-bar").height() + 15 + 190 + 60)) {
		if (view == "LIST" || view == "STARRED") {
			//$("#list-view-map").show();
		}
	} else {
			//$("#list-view-map").hide();
	}
/*
	if ($(window).height() < 600) {
		$("#div-left-image").height(130);
		$("#right-bar").height($(window).height() - 70);
		$("#left-image-prev").css("top", "55px");
		$("#left-image-next").css("top", "55px");
	} else {
		$("#right-bar").height("");
		$("#div-left-image").show();
		$("#div-left-image").height(190);
		$("#left-image-prev").css("top", "80px");
		$("#left-image-next").css("top", "80px");
	}
*/
	
	if ($(window).width() > 1300) {
		$("#list-view-area").css("width", "1282px");
		$("#top-bar-content").css("width", "1282px");
		$("#bottom-bar-content").css("width", "1282px");
	} else {
		$("#list-view-area").css("width", "958px");
		$("#top-bar-content").css("width", "958px");
		$("#bottom-bar-content").css("width", "958");
	}

	
	
}

/*
function saveSearch() {

	var searchName = $("#search-name").val();
	if (searchName == "") return;
	$("#search-name").val("");

	if (isLoggedIn()) {
		var url = outputURL();
		jQuery.ajax({
			url: "/save_search.php?url=" + url + "&user=" + userID + "&name=" + searchName,
			success: function (data) {
				displaySearches();
			},
			async: true
		});	
	}
}


function unsaveSearch(url, name) {

	if (isLoggedIn()) {
		jQuery.ajax({
			url: "/unsave_search.php?url=" + url + "&user=" + userID + "&name=" + name,
			success: function (data) {
				displaySearches();
			},
			async: true
		});
	}
}


function displaySearches() {

	$("#saved-searches").empty();

	jQuery.ajax({
		url: "/search_xml.php?user=" + userID,
		success: function (data) {
			var searches = data.documentElement.getElementsByTagName("search");

			for (var i = 0; i < searches.length; i++) {
				$("#saved-searches").append('<div class="saved-search"><a href="javascript:void(0);" onclick="loadSearch(\'' + searches[i].getAttribute('search_url') + '\'); pushState();">' + searches[i].getAttribute("name") + '</a>&nbsp;&nbsp;&nbsp;<a style="float:right;" href="javascript:void(0);" onclick="unsaveSearch(\'' + searches[i].getAttribute('search_url') + '\', \'' + searches[i].getAttribute('name') + '\')"><img src="/images/x.png"></a></div>');
			}

		},
		async: true
	});
}

function loadSearch(searchString) {
	parseURLParameters(searchString);
	changeView(view);
	updateFiltersUIBasedOnGlobals();
	loadAndDisplay();
}
*/

function saveDeal(dealID, element) {

	if (isLoggedIn()) {
		
		$(element).html("<a href='javascript:void(0);' onclick='unsaveDeal(\"" + dealID + "\", \"" + element + "\")'><img src='/images/heart_red.png'></a>");

		jQuery.ajax({
			url: "/save_deal.php?deal=" + dealID + "&user=" + userID,
			success: function (data) {
				if (data == "1") {
					starredDirtyBit = 1;
				} else {
					$(element).html("<a href='javascript:void(0);' onclick='saveDeal(\"" + dealID + "\", \"" + element + "\")'><img src='/images/heart_gray.png'></a>");
				}				
			},
			async: true
		});
	}
}

function unsaveDeal(dealID, element) {

	if (isLoggedIn()) {
	
		$(element).html("<a href='javascript:void(0);' onclick='saveDeal(\"" + dealID + "\", \"" + element + "\")'><img src='/images/heart_gray.png'></a>");
	
		jQuery.ajax({
			url: "/unsave_deal.php?deal=" + dealID + "&user=" + userID,
			success: function (data) {
				if (data == "1") {
					starredDirtyBit = 1;
				} else {
					$(element).html("<a href='javascript:void(0);' onclick='unsaveDeal(\"" + dealID + "\", \"" + element + "\")'><img src='/images/heart_red.png'></a>");
				}					
			},
			async: true
		});
		
		if (view == "STARRED") {
			$(element).parent().parent().slideUp();
			
			// Not sure why the -1 is needed, but without it we're one off
			var numStarredDeals = $("#list-view>div:visible").length - 1;
			$("#starred-list-view-num-deals").html("My Starred Deals <span style='color:#bbb;'> - " + numStarredDeals + " deals</span>");
		}


	}

}


function isSaved(dealID) {

	var returnValue = 0;

	urlString = "/is_saved.php?deal=" + dealID + "&user=" + userID;
	jQuery.ajax({
		url: urlString,
		success: function (data) {
			if (data >= 1) {
				returnValue = 1;
			} else {
				returnValue = 0;
			}
		},
		async: false
	});
	return returnValue;
}


// If the deal with url is saved, set element to a yellow heart, otherwise to a white heart
// If there is no logged in user, set the icon to a white heart


function setHeartIcon(dealID, element) {
	if (isLoggedIn()) {
		var urlString = "/is_saved.php?deal=" + dealID + "&user=" + userID;
		jQuery.ajax({
			url: urlString,
			success: function (data) {
				if (data >= 1) {
					$(element).html("<a href='javascript:void(0);' onclick='unsaveDeal(\"" + dealID + "\", \"" + element + "\")'><img src='/images/heart_red.png'></a>");
				} else {
					$(element).html("<a href='javascript:void(0);' onclick='saveDeal(\"" + dealID + "\", \"" + element + "\")'><img src='/images/heart_gray.png'></a>");
				}
			},
			async: true
		});
	} else {
		$(element).html("");
	}
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


/*
// markers begin
// 88888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888
// 88888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888
// 88888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888
// 88888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888

888b     d888        d8888 8888888b.  888    d8P  8888888888 8888888b.   .d8888b.  
8888b   d8888       d88888 888   Y88b 888   d8P   888        888   Y88b d88P  Y88b 
88888b.d88888      d88P888 888    888 888  d8P    888        888    888 Y88b.      
888Y88888P888     d88P 888 888   d88P 888d88K     8888888    888   d88P  "Y888b.   
888 Y888P 888    d88P  888 8888888P"  8888888b    888        8888888P"      "Y88b. 
888  Y8P  888   d88P   888 888 T88b   888  Y88b   888        888 T88b         "888 
888   "   888  d8888888888 888  T88b  888   Y88b  888        888  T88b  Y88b  d88P 
888       888 d88P     888 888   T88b 888    Y88b 8888888888 888   T88b  "Y8888P"  

// 88888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888
// 88888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888
// 88888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888
// 88888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////*/ 





function loadAndDisplayMarkers() {

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

				var expired_e;
				if (isExpired(mysqlTimeStampToDate(markers[i].getAttribute("deadline"))) == 1) {
					expired_e = "e";
				} else {
					expired_e = "";
				}

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

					// TODO.. do we need to do this here since we do it below?
					icon: eval("marker" + 0 + expired_e),
					shadow: eval("markerShadow" + expired_e),

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

					isExpired: isExpired(mysqlTimeStampToDate(markers[i].getAttribute("deadline"))),
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
				for (var k = 0; k < childNodes.length; k++) {
					if (childNodes[k].nodeName == "category") {
					        categoryId = parseInt(childNodes[k].getAttribute("category_id"));
					        categoryRank = parseInt(childNodes[k].getAttribute("rank"));

						marker.categoryIDs.push(categoryId);
						if (categoryRank > topCategoryRank) {
							marker.icon = eval("marker" + categoryId + expired_e);
							categoryRank = topCategoryRank;
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
	
	for (var i = 0; i < markersArray.length; i++) {
		if (categoriesToShow[markersArray[i].categoryIDs[0]] == 0) {
			markersArray[i].setMap(null);
		

		} else if (showOnlyNew == 1 && getAge(mysqlTimeStampToDate(markersArray[i].discovered)) > 0) {
			// don't show deals with age greater than 0 if the user wants to see only new deals
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
	
		if (!marker) {
			return;
		}


		mpq.track("Clicked on map marker", {
			"mp_note": "Clicked on " + marker.dealID + " (" + companies[marker.companyID] + ", " + categories[marker.categoryIDs[0]] + ") - " + outputURL(),
			"Company": companies[marker.companyID],
			"Category": categories[marker.categoryIDs[0]],
			"Yelp": marker.yelpRatingStr,
			"City": cities[cityEdition],
			"Price": marker.price,
			"Value": parseInt(marker.value),
			"Discount": marker.discount,
			"View": view,
			"UserID": userID
		});
		
		
		selectedMarkerImageIndex = 0;
		
		debug("marker: " + marker.index);
		
		$("#hover-info").hide();

		// Reset the previously selected marker's icon and update the newly selected marker's icon
		var expired_e;
		var cat = markersArray[selectedMarkerIndex].categoryIDs[0];

		if (isExpired(mysqlTimeStampToDate(markersArray[selectedMarkerIndex].deadline)) == 1) {
			markersArray[selectedMarkerIndex].setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point((230 + (cat * 23)), 0), new google.maps.Point(12, 29)));
		} else {
			markersArray[selectedMarkerIndex].setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point((0 + (cat * 23)), 0), new google.maps.Point(12, 29)));
		}
		
		mapViewDealID = parseInt(marker.dealID);
		
		setHeartIcon(marker.dealID, "#deal-star");

		//fillInAvailabilityInfo(marker.deadline, marker.upcoming, marker.expired, 'deal-expires');

		var singleDealUrl = getSingleDealURLFull(calculateCityEditionFromLatLng(marker.position.lat(), marker.position.lng()), marker.dealID, marker.maintitle);
		
		var truncatedString = marker.maintitle.substring(0, 55);
		if (marker.maintitle.length > 55) truncatedString += "...";
		$("#maintitle-link").html(truncatedString);
		$("#maintitle-link").attr("href", singleDealUrl);		
		$("#maintitle-link").unbind();
		$("#maintitle-link").click(function() {
			mpq.track("Clicked to single deal view", {
				"mp_note": "Clicked on " + marker.dealID + " (" + companies[marker.companyID] + ", " + categories[marker.categoryIDs[0]] + ") - " + outputURL(),
				"Company": companies[marker.companyID],
				"Category": categories[marker.categoryIDs[0]],
				"Yelp": marker.yelpRatingStr,
				"City": cities[cityEdition],
				"Price": marker.price,
				"Value": parseInt(marker.value),
				"Discount": marker.discount,
				"View": view,
				"UserID": userID
			});

			openDealInSingleDealView(marker.dealID, calculateCityEditionFromLatLng(marker.position.lat(), marker.position.lng()));
			return false;		
		});
		
		
		truncatedString = marker.subTitle.substring(0, 75);
		if (marker.subTitle.length > 75) truncatedString += "...";
		
		$("#deal-subtitle").html(truncatedString);
		$("#deal-marker-id").html(marker.index);

		if (marker.website != "") {
			$("#deal-name").html("<a href='" + marker.website + "' target='_blank'>" + marker.name + "</a>");
		} else {
			$("#deal-name").html(marker.name);
		}

		$("#deal-street").html(marker.street);
		$("#deal-city").html(marker.city);


		// WITH "MOVE TO" LINK: $("#deal-state").html(marker.state + " - <a href='javascript:void(0);' onclick='map.panTo(new google.maps.LatLng(" + marker.position.lat() + ", " + marker.position.lng() + "));'>Move to</a>");
		$("#deal-state").html(marker.state);
		$("#deal-zip").html(marker.zip);
		
		$("#deal-price").html("$" + marker.price);
		
		if (isNaN(marker.price)) {
			$("#deal-price").html("");
		}

		if (marker.numPurchased == "") {
			$("#purchased").hide();
		} else {
			$("#purchased").show();
			$("#deal-num_purchased").html(marker.numPurchased);
		}
		

		if (marker.age == 0) {
			$("#deal-discovered").html("Posted today!");
		} else if (marker.age == 1) {
			$("#deal-discovered").html("Posted yesterday");
		} else {
			$("#deal-discovered").html("Posted " + marker.age + " days ago");
		}

		if (marker.value != "") {
			$("#coupon-pvd-tr1").html("<td>price</td><td>value</td><td>discount</td>");
			$("#coupon-pvd-tr2").html("<td>$" + marker.price + "</td><td>$" + marker.value + "</td><td>" + marker.discount + "%</td>");
		} else {
			$("#coupon-pvd-tr1").html("<td>price</td>");
			$("#coupon-pvd-tr2").html("<td>$" + marker.price + "</td>");
		}

		$("#category").html(categories[marker.categoryIDs[0]]);
		$("#company").html("<img src='/images/company_logos/company_" + marker.companyID + ".png'>");






		if (marker.affiliateUrl != "") {
			$("#details-button").attr("href", marker.affiliateUrl);	
		} else {
			$("#details-button").attr("href", marker.dealUrl);	
		}
		
		$("#details-button").html("See deal at " + companies[marker.companyID]);	
	
/*	
		document.getElementById('details-button').onclick = function () {			
			mpq.track("Clicked to external deal site", {
				"mp_note": "Clicked on " + marker.dealID + " (" + companies[marker.companyID] + ", " + categories[marker.categoryIDs[0]] + ") - " + outputURL(),
				"Company": companies[marker.companyID],
				"Category": categories[marker.categoryIDs[0]],
				"Yelp": marker.yelpRatingStr,
				"City": cities[cityEdition],
				"Price": marker.price,
				"Value": parseInt(marker.value),
				"Discount": marker.discount,
				"View": view,
				"UserID": userID
			});
		};
*/
		var singleDealUrlFull = getSingleDealURLFull(calculateCityEditionFromLatLng(marker.position.lat(), marker.position.lng()), marker.dealID, marker.maintitle);
		createNewSendButton("#deal-send", singleDealUrlFull + "?utm=facebook_share", "button_count");


		// Don't show the Yelp section if there is no rating
		if (marker.yelpRatingStr == "-1") {
			$("#rating").hide();
		} else {
			$("#rating").show();
			$("#yelp-stars").attr("src", "/images/yelp/yelp_" + marker.yelpRatingStr.replace(".", "") + ".png");
			$("#yelp-link").attr("href", marker.yelpUrl);
			$("#yelp-review-count").html(" - " + marker.yelpCount + " reviews");
		}


		var existingClass = $("#category").attr("class");
		$("#category").removeClass(existingClass);
	//	document.getElementById('category').className += "category-" + marker.categoryIDs[0];

		
		if (marker.imageUrls.length <= 1) {
			$("#left-image-prev").fadeOut();
			$("#left-image-next").fadeOut();
		} else {
			$("#left-image-prev").fadeIn();
			$("#left-image-next").fadeIn();
		}

		$("#yelp-reviews").empty();
		



		$("#left-image").attr("src", marker.imageUrls[0]);
		$("#left-image").hide();
		var pic_real_width, pic_real_height, ar;
		$("<img/>").attr("src", $("#left-image").attr("src") + "?" + new Date().getTime()).load(function() {
			pic_real_width = this.width;
			pic_real_height = this.height;
			ar = pic_real_width / pic_real_height;

			if (parseFloat(ar) > 1.73684211) {
				var newWidth = parseInt(190 * ar);
				newWidth = newWidth + "px";
				$("#left-image").animate({width: newWidth}, 0);
				$("#left-image").animate({height: "190px"}, 0, function() {
					$("#left-image").show();
				});
			} else {
				var newHeight = parseInt(330 / ar);
				newHeight = newHeight + "px";
				$("#left-image").animate({width: "330px"}, 0);
				$("#left-image").animate({height: newHeight}, 0, function() {
					$("#left-image").show();
				});
			}
		});
		
		


		for (var k = 0; k < 3; k++) {

			if (marker.yelpUser[k] != "" && marker.yelpUserExcerpt[k] != "") {

				$('#yelp-reviews').append('<div>  <table>    <tr>      <td style="width:45px; vertical-align:top;">        <a href="" id="yelp-user-url-1-' + k + '" target=_blank><img id="yelp-user-photo-' + k + '" src="" style="width:40px; height:40px"></a>      </td>      <td style="vertical-align:top; padding:0px 0 0 5px;">        <img id="yelp-user-rating-' + k + '" src="">&nbsp;<a href="" id="yelp-user-url-2-' + k + '" target=_blank><span id="yelp-user-' + k + '" style="font-size:12px;"></span></a>        <br>        <span id="yelp-user-excerpt-' + k + '"></span> <a href="" id="yelp-review-url-' + k + '" target=_blank">Read more</a>     </td>    </tr>  </table></div>');

				$('#yelp-reviews').append('<hr class="dotted">');

				if (marker.yelpUser[k + 1] == "" || k == 2) {
					$('#yelp-reviews').append('<a href="' + marker.yelpUrl + '" target=_blank><img src="/images/powered_by_yelp.png" style="float:right; margin-top:10px;"></a>');
				}

				// Yelp user excerpt panel
				$("#yelp-user-" + k).html(marker.yelpUser[k]);
				$("#yelp-user-url-1-" + k).attr("href", marker.yelpUserUrl[k]);
				$("#yelp-user-url-2-" + k).attr("href", marker.yelpUserUrl[k]);
				$("#yelp-user-photo-" + k).attr("src", marker.yelpUserPhoto[k]);
				$("#yelp-user-excerpt-" + k).html(marker.yelpUserExcerpt[k]);
				$("#yelp-review-url-" + k).attr("href", marker.yelpReviewUrl[k]);
				$("#yelp-user-rating-" + k).attr("src", "/images/yelp/yelp_small_" + marker.yelpUserRating[k].toString().replace(".", "") + ".png");
			}
		}			

		selectedMarkerIndex = marker.index;
		markersArray[selectedMarkerIndex].setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(31, 37), new google.maps.Point((460 + (marker.categoryIDs[0] * 31)), 0), new google.maps.Point(16, 37)));
		
		// In case the user quickly switches to list view before the markers/right bar have loaded
		if (view == "MAP") {
			$("#right-bar").show();
		}





		var yelpBoxOffSet = $("#yelp-reviews").height() / -2;
		$("#yelp-reviews").css("top", yelpBoxOffSet);

		replaceState();
		
		preload(marker.imageUrls);

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
				marker.setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point((230 + (cat * 23)), 0), new google.maps.Point(12, 29)));
			} else {
				marker.setIcon(new google.maps.MarkerImage('/images/markers.png', new google.maps.Size(23, 29), new google.maps.Point((0 + (cat * 23)), 0), new google.maps.Point(12, 29)));
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
			$("#hover-info-subtitle").html("<br>" + marker.subTitle);
		}
	
		$("#hover-info-company").html(companies[marker.companyID]);
		
		if (marker.yelpRatingStr == "-1") {
			$("#hover-info-yelp").hide();
		} else {
			$("#hover-info-yelp").show();
			$("#hover-info-yelp-stars").attr("src", "/images/yelp/yelp_small_" + marker.yelpRatingStr.replace(".", "") + ".png");
			$("#hover-info-yelp-count").html(" - " + marker.yelpCount + " reviews");
		}

		$("#hover-info").show();
		$("#hover-info-image").hide();
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

	var prefix = "";
	if (view == "STARRED") prefix = "starred-";
	
	if (newSortBy == "DISTANCE") {
		if ($("#sort-zip").val() == "") {
			return;
		}
	}
	

	mpq.track("List view resorted", {
		"mp_note": "Sorted to " + newSortBy + " -  |  " + outputURL()
	});

	
	$("#sortNEW").removeClass("sort-on");
	$("#sortSOLD").removeClass("sort-on");
	$("#sortPRICE").removeClass("sort-on");
	$("#sortDEADLINE").removeClass("sort-on");
	$("#sortDISTANCE").removeClass("sort-on");
	$("#sort" + newSortBy).addClass("sort-on");
	
	if (newSortBy == "DISTANCE") {
		zip = $("#sort-zip").val();
		$.cookie('zip_cookie', zip, {
			expires: 1000,
			path:'/'
		});
	}
	
	currentSortBy = newSortBy;
	loadAndDisplay();
	
	currentSortBy == "SOLD" ? sortByString = "number sold" : sortByString = currentSortBy.toLowerCase();
	// $("#" + prefix + "list-view-sorted-by").html("sorted by " + sortByString);
	
	pushState();


	
	
}





function indexOfFirstVisibleDeal(arr, prefix) {
	for (var i = 0; i < arr.length; i++) {
		if ($('#' + prefix + 'list-view-id-' + i).is(":visible")) {
			return i;
		}
	}
}


/*
var listItemEnter = function(dealDiv, lat, lng, id, category_id) {
	return function() {
		this.children[0].style.backgroundColor = "#f9ede3";
		
		if (lat == null || lng == null || lat == "" || lng == "") {
			//$("#list-view-map").fadeOut();
			return;
		} else {
			//$("#list-view-map").show();
		}
		
		//listMap.setCenter(new google.maps.LatLng(lat, lng));		
		//listMarker.position = new google.maps.LatLng(lat, lng);
		//listMarker.icon = eval("marker" + category_id);
		//listMarker.shadow = markerShadow;
		//listMarker.setMap(listMap);
	}
}






var listItemLeave = function(index, prefix) {
	return function() {
		this.children[0].style.backgroundColor = "#eeeeee";
	}
}
*/



function deleteOverlays(arr) {
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

function arrays_equal(a,b) { return !(a<b || b<a); }



