<!doctype html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>


<script>
// indexOf for IE
Array.indexOf||(Array.prototype.indexOf=function(b){for(var a=0;a<this.length;a++)if(this[a]==b)return a;return-1});Array.lastIndexOf||(Array.prototype.lastIndexOf=function(b){for(var a=this.length;0<=a;a--)if(this[a]==b)return a;return-1});
</script>


<meta charset="utf-8" />

<link rel="icon" type="image/png" href="/images/favicon.png"/>
<link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico"/>
<link href='http://fonts.googleapis.com/css?family=Lato:300,400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="/map156version.css" />
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




<style>

.box {
	float:none; 
}

</style>


<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
<script type="text/javascript" src="/helpers156version.js"></script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>


<script>


view = "GROSS";
graphData = [];
priceGraphData = [];
yelpGraphData = [];

var getEpoch = function(dateStr) {
  var r = /^\s*(\d{4})-(\d\d)-(\d\d)\s+(\d\d):(\d\d):(\d\d)\s+UTC\s*$/
    , m = (""+dateStr).match(r);
  return (m) ? (Date.UTC(m[1], m[2]-1, m[3], m[4], m[5], m[6]) / 1000) : undefined;
};


function load() {
	$("#pd-start-time").datepicker({
		minDate: -7,  
		maxDate: "0"  
	});               
	$("#pd-start-time").datepicker("option", "dateFormat", "yy-mm-dd");
	                  
	                  
	$("#pd-end-time").datepicker({
		minDate: -7,  
		maxDate: "0"  
	});	              
	$("#pd-end-time").datepicker("option", "dateFormat", "yy-mm-dd");



	$("#gb-start-time").datepicker({
		minDate: -7,
		maxDate: "0"
	});
	$("#gb-start-time").datepicker("option", "dateFormat", "yy-mm-dd");
	
	
	$("#gb-end-time").datepicker({
		minDate: -7,
		maxDate: "0"
	});	
	$("#gb-end-time").datepicker("option", "dateFormat", "yy-mm-dd");
	
}



function changeView(newView) {
	if (newView == "GROSS") {
		$("#popular-deals").hide();
		$("#gross-billings").show();	
	}
	
	
	if (newView == "POPULAR") {
		$("#popular-deals").show();
		$("#gross-billings").hide();
	}
}


function loadPopularDeals() {
	
	var company = $("#pd-company").val();
	var city = $("#pd-city").val();
	var category = $("#pd-category").val();
	
	var startTime = $("#pd-start-time").val() + " 00:00:00 UTC";
	var endTime = $("#pd-end-time").val() + " 23:59:59 UTC";
	var yelp_rating = $("#pd-yelp-rating").val();

	var low_price = $("#pd-low-price").val();
	var high_price = $("#pd-high-price").val();

	console.log("startTime: " + startTime);
	console.log("endTime: " + endTime);
	
	
	ajaxUrl = "most_popular.php?company_id=" + company + "&start_time=" + getEpoch(startTime) + "&end_time=" + getEpoch(endTime) + "&category_id=" + category + "&city_id=" + city + "&yelp_rating=" + yelp_rating + "&low_price=" + low_price + "&high_price=" + high_price;
	
	$("#loading-div").center().show();

	jQuery.ajax({
		type: "GET",
		url: ajaxUrl,
		success: function (data) {
			$("#popular-deals-output").html(data);
			$("#loading-div").hide();
		},
		async: true
	});

}

google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback();


function loadGrossBillings() {
	var company = $("#gb-company").val();
	var city = $("#gb-city").val();
	var category = $("#gb-category").val();

	var startTime = $("#gb-start-time").val() + " 00:00:00 UTC";
	var endTime = $("#gb-end-time").val() + " 23:59:59 UTC";

	
	ajaxUrl = "gross_billings.php?company_id=" + company + "&start_time=" + getEpoch(startTime) + "&end_time=" + getEpoch(endTime) + "&category_id=" + category + "&city_id=" + city;
	console.log("gross billings URL: " + ajaxUrl);	

	$("#loading-div").center().show();
	
	jQuery.ajax({
		type: "GET",
		url: ajaxUrl,
		success: function (gb_json) {
			var gb_data = jQuery.parseJSON(gb_json);
			$("#gb-total-revenue").html("$"+numberWithCommas(gb_data.total_gross_billings));
			$("#gb-num-deals").html(numberWithCommas(gb_data.num_deals));
			$("#gb-avg-price").html("$"+gb_data.avg_deal_price);
			$("#gb-avg-coupons").html(gb_data.avg_coupons_per_deal);
			$("#gb-avg-discount").html(gb_data.avg_deal_discount + "%");
			$("#gb-table").show();
			graphData = [];
			for (var key in gb_data.gross_billings) {
			  if (gb_data.gross_billings.hasOwnProperty(key)) {
			    graphData.push([key, gb_data.gross_billings[key]]);
			  }
			}			

			priceGraphData = [];
			for (var key in gb_data.price_distribution) {
			  if (gb_data.price_distribution.hasOwnProperty(key)) {
			    priceGraphData.push([key, gb_data.price_distribution[key]]);
			  }
			}			

			yelpGraphData = [];
			for (var key in gb_data.yelp_ratings) {
			  if (gb_data.yelp_ratings.hasOwnProperty(key)) {
			    yelpGraphData.push([key, gb_data.yelp_ratings[key]]);
			  }
			}			
			
			drawChart();			
			drawPriceChart();
			drawYelpChart();
			$("#loading-div").hide();
		},
		async: true
	});

}

function numberWithCommas(x) {
  return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}


function showPopularForDay(day) {
  $("#pd-company").val($("#gb-company").val());
  $("#pd-yelp-rating").val(0); // ignore yelp
  $("#pd-low-price").val(0);
  $("#pd-high-price").val(0); // ignore price
  $("#pd-start-time").val(day);
  $("#pd-end-time").val(day);
  $("#pd-city").val($("#gb-city").val());
  $("#pd-category").val($("#gb-category").val());
  loadPopularDeals();
  changeView("POPULAR");
  //alert($("#pd-start-time").val());
}

function showPopularForPriceRange(low_price, high_price) {
  $("#pd-company").val($("#gb-company").val());
  $("#pd-yelp-rating").val(0); // ignore yelp
  $("#pd-low-price").val(low_price);
  $("#pd-high-price").val(high_price);
  $("#pd-start-time").val($("#gb-start-time").val());
  $("#pd-end-time").val($("#gb-end-time").val());
  $("#pd-city").val($("#gb-city").val());
  $("#pd-category").val($("#gb-category").val());
  loadPopularDeals();
  changeView("POPULAR");


}


function showPopularForYelpRating(yelp_rating) {
  $("#pd-yelp-rating").val(yelp_rating);
  $("#pd-company").val($("#gb-company").val());
  $("#pd-start-time").val($("#gb-start-time").val());
  $("#pd-end-time").val($("#gb-end-time").val());
  $("#pd-city").val($("#gb-city").val());
  $("#pd-category").val($("#gb-category").val());
  loadPopularDeals();
  changeView("POPULAR");
  //alert($("#pd-start-time").val());
}


function drawChart() {
  var data = new google.visualization.DataTable();
  data.addColumn('string', 'Day of the month');
  data.addColumn('number', 'Gross revenue');
  
  var options = {
    width: 600, height: 240,
    title: 'Daily total revenue',
    hAxis: {title: 'Day', titleTextStyle: {color: 'black'}}
  };
  
  data.addRows(graphData);
  
  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
  // The select handler. Call the chart's getSelection() method      
  function selectHandler() {
    var selectedItem = chart.getSelection()[0];
    if (selectedItem) {
      var value = data.getValue(selectedItem.row, 0);
      showPopularForDay(value);
      //alert('The user selected ' + value);
    }
  }
  // Listen for the 'select' event, and call my function selectHandler() when
  // the user selects something on the chart. 
  google.visualization.events.addListener(chart, 'select', selectHandler);
  
  chart.draw(data, options);
}


function drawPriceChart() {
  var data = new google.visualization.DataTable();
  data.addColumn('string', 'Price');
  data.addColumn('number', 'Number of deals');
  
  var options = {
    width: 600, height: 240,
    title: 'Price distribution',
    colors: ['green'],
    hAxis: {title: 'Price point', titleTextStyle: {color: 'black'}},
    vAxis: {title: 'Number of deals', titleTextStyle: {color: 'black'}}
  };
  
  data.addRows(priceGraphData);
  
  var chart = new google.visualization.ColumnChart(document.getElementById('price_chart_div'));

  // The select handler. Call the chart's getSelection() method      
  function priceRangeHandler() {
    var selectedItem = chart.getSelection()[0];
    if (selectedItem) {
      var value = data.getValue(selectedItem.row, 0);
      var low_price = value.match(/([0-9\.]+)/g)[0];
      var high_price = value.match(/([0-9\.]+)/g)[1];
      showPopularForPriceRange(low_price, high_price);
    }
  }
  // Listen for the 'select' event, and call my function selectHandler() when
  // the user selects something on the chart. 
  google.visualization.events.addListener(chart, 'select', priceRangeHandler);



  
  chart.draw(data, options);
}



function drawYelpChart() {
  var data = new google.visualization.DataTable();
  data.addColumn('string', 'Yelp score');
  data.addColumn('number', 'Number of deals');
  
  var options = {
    width: 450, height: 240,
    title: 'Deal quality',
    colors: ['red'],
    hAxis: {title: 'Yelp rating', titleTextStyle: {color: 'black'}},
    vAxis: {title: 'Number of deals', titleTextStyle: {color: 'black'}}
  };
  
  data.addRows(yelpGraphData);
  
  var chart = new google.visualization.ColumnChart(document.getElementById('yelp_chart_div'));
  

  // The select handler. Call the chart's getSelection() method      
  function yelpHandler() {
    var selectedItem = chart.getSelection()[0];
    if (selectedItem) {
      var value = data.getValue(selectedItem.row, 0);
      showPopularForYelpRating(value);
      //alert('The user selected ' + value);
    }
  }
  // Listen for the 'select' event, and call my function selectHandler() when
  // the user selects something on the chart. 
  google.visualization.events.addListener(chart, 'select', yelpHandler);


  chart.draw(data, options);
}




</script>




<style>

#gross-billings, #popular-deals {
	top: 74px;
	position: relative;
	margin: 0px auto;
	width: 1058px;
	display: none;
}


</style>






</head>
<?php flush(); ?>
<body onload="load();">


	<div id="top-bar">
		<div style="height: 39px; width: 958px; margin: 3px auto; position: relative">
			<div style="position:absolute; top:3px; margin: 0 auto; width:100%; text-align:center;">
				<img src="/images/logo.png">
				<span style="color:#ffca9d; font-size:28px; -webkit-transform:rotate(0.05deg); position: relative; top: -7px; text-shadow: 0px -1px 1px rgba(0, 0, 0, .4);" id="city-name">trends</span>
			</div>
			<a href='javascript:void(0);' onclick='changeView("GROSS");' class="small-button brown-gradient" style="position:relative; top:10px;"><span>Gross Billings</span></a>
			<a href='javascript:void(0);' onclick='changeView("POPULAR");' class="small-button brown-gradient" style="position:relative; top:10px;"><span>Popular Deals</span></a>
			
		</div>
	</div>	



	
	<div id="gross-billings" style="display: block">
		<h2>Gross Billings</h2>
		
		<br><br>
	
		<select id="gb-company">
			<option value="1">Groupon</option>
			<option value="2">Living Social</option>
			<option value="17">Living Social Escapes</option>
			<option value="35">Living Social Adventures</option>
			<option value="12">Amazon Local</option>
			<option value="5">Travelzoo</option>
			<option value="4">Tippr</option>
			<option value="13">KGB Deals</option>
			<option value="20">Voice Daily Deals</option>
			<option value="16">EverSave</option>
			<option value="8">Yollar</option>
			<option value="19">GetMyPerks</option>
			<option value="21">Munch on Me</option>
			<option value="22">Doodle Deals</option>
			<option value="24">Schwaggle</option>
			<option value="25">Home Run</option>
			<option value="26">Bargain Bee</option>
			<option value="27">SignPost</option>
			<option value="34">OnSale</option>



		</select>

		<input type="text" id="gb-start-time" placeholder="Start time">			
		<input type="text" id="gb-end-time" placeholder="End time">	

		
		<select id="gb-city">
                  <option value="0" >All Cities</option>
                  <option value="2" >National</option>
                  <option value="3" >Seattle</option>
                  <option value="4" >Portland</option>
                  <option value="5" >San Francisco</option>
                  <option value="6" >San Jose</option>
                  <option value="7" >San Diego</option>
                  <option value="8" >Silicon Valley</option>
                  <option value="9" >Los Angeles</option>
                  <option value="10" >Tacoma</option>
                  <option value="11" >New York</option>
                  <option value="12" >Chicago</option>
                  <option value="13" >Boston</option>
                  <option value="14" >Atlanta</option>
                  <option value="15" >Orlando</option>
                  <option value="16" >Houston</option>
                  <option value="17" >Washington D.C.</option>
                  <option value="18" >Miami</option>
                  <option value="19" >Dallas</option>
                  <option value="20" >Denver</option>
                  <option value="21" >Las Vegas</option>
                  <option value="22" >Austin</option>
                  <option value="23" >Philadelphia</option>
                  <option value="24" >Cleveland</option>
                  <option value="25" >Minneapolis</option>
                  <option value="26" >Phoenix</option>
                  <option value="27" >Orange County</option>
                  <option value="28" >Baltimore</option>
                  <option value="29" >Kansas City</option>
                  <option value="30" >Detroit</option>
                  <option value="31" >St Louis</option>
                  <option value="32" >Pittsburgh</option>
                  <option value="33" >San Antonio</option>
                  <option value="34" >New Orleans</option>
                  <option value="35" >Honolulu</option>


		</select>


		<select id="gb-category">
			<option value="0">All Categories</option>
			<option value="1">Food and Drink</option>
			<option value="2">Activities and Events</option>
			<option value="3">Spa and Beauty</option>
			<option value="4">Kids and Parents</option>
			<option value="5">Shoppings and Services</option>
			<option value="6">Classes and Learning</option>
			<option value="7">Fitness and Health</option>
			<option value="8">Dental and Medical</option>
			<option value="9">Hotels and Vacation</option>
		</select>			
		
		<a href="javascript:void(0);" onclick="loadGrossBillings();" class="small-button brown-gradient"><span>Show gross billings</span></a>
		
		<div id="gross-billings-output">
			<div id="chart_div"></div>
                        <div id="gb-table" style="display:none;">
                             <table>
                                  <tr><td><b>Total revenue:</b></td><td id="gb-total-revenue"></td></tr>
                                  <tr><td><b>Number of deals:</b></td><td id="gb-num-deals"></td></tr>
                                  <tr><td><b>Average deal price:</b></td><td id="gb-avg-price"></td></tr>
                                  <tr><td><b>Average coupons sold per deal:</b></td><td id="gb-avg-coupons"></td></tr>

                                  <tr><td><b>Average deal discount:</b></td><td id="gb-avg-discount"></td></tr>
                             
                             </table>

                        </div>
			<div id="price_chart_div"></div><BR>
			<div id="yelp_chart_div"></div>
		</div>

	</div>

	
	
	
	
	


	
	
	<div id="popular-deals">
		
		<h2>Popular Deals</h2>
		
		<br><br>
	
		<select id="pd-company">
			<option value="0">All companies</option>
			<option value="1">Groupon</option>
			<option value="2">Living Social</option>
			<option value="17">Living Social Escapes</option>
			<option value="35">Living Social Adventures</option>
			<option value="12">Amazon Local</option>
			<option value="5">Travelzoo</option>
			<option value="4">Tippr</option>
			<option value="13">KGB Deals</option>
			<option value="20">Voice Daily Deals</option>
			<option value="16">EverSave</option>
			<option value="8">Yollar</option>
			<option value="19">GetMyPerks</option>
			<option value="21">Munch on Me</option>
			<option value="22">Doodle Deals</option>
			<option value="24">Schwaggle</option>
			<option value="25">Home Run</option>
			<option value="26">Bargain Bee</option>
			<option value="27">SignPost</option>
			<option value="34">OnSale</option>
		</select>

		<input type="text" id="pd-start-time" placeholder="Start time">			
		<input type="text" id="pd-end-time" placeholder="End time">	
                <input type="hidden" id="pd-yelp-rating">
                <input type="hidden" id="pd-low-price">
                <input type="hidden" id="pd-high-price">

		<select id="pd-city">
                  <option value="0" >All Cities</option>
                  <option value="2" >National</option>
                  <option value="3" >Seattle</option>
                  <option value="4" >Portland</option>
                  <option value="5" >San Francisco</option>
                  <option value="6" >San Jose</option>
                  <option value="7" >San Diego</option>
                  <option value="8" >Silicon Valley</option>
                  <option value="9" >Los Angeles</option>
                  <option value="10" >Tacoma</option>
                  <option value="11" >New York</option>
                  <option value="12" >Chicago</option>
                  <option value="13" >Boston</option>
                  <option value="14" >Atlanta</option>
                  <option value="15" >Orlando</option>
                  <option value="16" >Houston</option>
                  <option value="17" >Washington D.C.</option>
                  <option value="18" >Miami</option>
                  <option value="19" >Dallas</option>
                  <option value="20" >Denver</option>
                  <option value="21" >Las Vegas</option>
                  <option value="22" >Austin</option>
                  <option value="23" >Philadelphia</option>
                  <option value="24" >Cleveland</option>
                  <option value="25" >Minneapolis</option>
                  <option value="26" >Phoenix</option>
                  <option value="27" >Orange County</option>
                  <option value="28" >Baltimore</option>
                  <option value="29" >Kansas City</option>
                  <option value="30" >Detroit</option>
                  <option value="31" >St Louis</option>
                  <option value="32" >Pittsburgh</option>
                  <option value="33" >San Antonio</option>
                  <option value="34" >New Orleans</option>
                  <option value="35" >Honolulu</option>
		</select>

		<select id="pd-category">
			<option value="0">All Categories</option>
			<option value="1">Food and Drink</option>
			<option value="2">Activities and Events</option>
			<option value="3">Spa and Beauty</option>
			<option value="4">Kids and Parents</option>
			<option value="5">Shoppings and Services</option>
			<option value="6">Classes and Learning</option>
			<option value="7">Fitness and Health</option>
			<option value="8">Dental and Medical</option>
			<option value="9">Hotels and Vacation</option>
		</select>			
		
		<a href="javascript:void(0);" onclick="loadPopularDeals();" class="small-button brown-gradient"><span>Show the deals</span></a>
		
		<div id="popular-deals-output"></div>
			
			
	</div>
	
	
	
	
	<div id="loading-div"><img src="/images/loadinfo.gif"></div>

	
</body>

</html>
