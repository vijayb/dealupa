<!doctype html>

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">

<head>

<link href='http://fonts.googleapis.com/css?family=Lato:400,700' rel='stylesheet' type='text/css'>

<!--[if gte IE 9]>

<style type="text/css">
.gradient {
   filter: none;
}
</style>

<![endif]-->

<style>






body {
	font-family: Lato, sans-serif;
	font-size: 14px;
	background-image: url('/images/tile.png');
	padding: 0;
	margin: 0;
}

#top-bar {
	display:block;
	top: 0px;
	left: 0px;
	height: 74px;
	width: 100%;
	position: fixed;
	background-image: url('/images/top_wood.png');
	background-position: 50% 50%;
}




#list-view-area {
	top: 74px;
	position: relative;
	margin: 0px auto;
	width: 958px;
}

#container {
	position: relative;
	top: 10px;
}

.box {
	width: 310px;
	margin-bottom:14px;
	float: left;
	overflow:hidden;
	-moz-box-shadow: inset 0 0 5px #888;
	-webkit-box-shadow: inset 0 0 5px#888;
	box-shadow: inner 0 0 5px #888;
}

div.deal-image {
	max-height: 310px;
	overflow: hidden;
}

img.deal-image {
	display: block;
	width: 100%;
}


h2 {
	color: #3a1500;
	margin: 0;
	font-size: 25px;
	font-weight: 400;
	text-shadow: 0px 1px 0px rgba(255, 255, 255, 0.9);
}



.gray {
	color:#999999;
}







.orange-gradient {
	background: rgb(255,250,122);
	background: -moz-linear-gradient(top,  rgba(255,250,122,1) 1%, rgba(255,173,45,1) 4%, rgba(254,172,44,1) 14%, rgba(247,161,39,1) 31%, rgba(218,120,20,1) 75%, rgba(202,104,14,1) 94%, rgba(199,102,14,1) 97%, rgba(167,85,13,1) 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(1%,rgba(255,250,122,1)), color-stop(4%,rgba(255,173,45,1)), color-stop(14%,rgba(254,172,44,1)), color-stop(31%,rgba(247,161,39,1)), color-stop(75%,rgba(218,120,20,1)), color-stop(94%,rgba(202,104,14,1)), color-stop(97%,rgba(199,102,14,1)), color-stop(100%,rgba(167,85,13,1)));
	background: -webkit-linear-gradient(top,  rgba(255,250,122,1) 1%,rgba(255,173,45,1) 4%,rgba(254,172,44,1) 14%,rgba(247,161,39,1) 31%,rgba(218,120,20,1) 75%,rgba(202,104,14,1) 94%,rgba(199,102,14,1) 97%,rgba(167,85,13,1) 100%);
	background: -o-linear-gradient(top,  rgba(255,250,122,1) 1%,rgba(255,173,45,1) 4%,rgba(254,172,44,1) 14%,rgba(247,161,39,1) 31%,rgba(218,120,20,1) 75%,rgba(202,104,14,1) 94%,rgba(199,102,14,1) 97%,rgba(167,85,13,1) 100%);
	background: -ms-linear-gradient(top,  rgba(255,250,122,1) 1%,rgba(255,173,45,1) 4%,rgba(254,172,44,1) 14%,rgba(247,161,39,1) 31%,rgba(218,120,20,1) 75%,rgba(202,104,14,1) 94%,rgba(199,102,14,1) 97%,rgba(167,85,13,1) 100%);
	background: linear-gradient(top,  rgba(255,250,122,1) 1%,rgba(255,173,45,1) 4%,rgba(254,172,44,1) 14%,rgba(247,161,39,1) 31%,rgba(218,120,20,1) 75%,rgba(202,104,14,1) 94%,rgba(199,102,14,1) 97%,rgba(167,85,13,1) 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffad2d', endColorstr='#a7550d',GradientType=0 );
}






.brown-gradient {
	/* http://www.colorzilla.com/gradient-editor/#f6c992+3,c3965f+5,906739+97,825d3b+100;Custom */

	background: rgb(246,201,146); /* Old browsers */
	/* IE9 SVG, needs conditional override of 'filter' to 'none' */
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2Y2Yzk5MiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjQlIiBzdG9wLWNvbG9yPSIjYzM5NjVmIiBzdG9wLW9wYWNpdHk9IjEiLz4KICAgIDxzdG9wIG9mZnNldD0iMTclIiBzdG9wLWNvbG9yPSIjYzE5NDVkIiBzdG9wLW9wYWNpdHk9IjEiLz4KICAgIDxzdG9wIG9mZnNldD0iMzAlIiBzdG9wLWNvbG9yPSIjYmE4ZTU4IiBzdG9wLW9wYWNpdHk9IjEiLz4KICAgIDxzdG9wIG9mZnNldD0iNzQlIiBzdG9wLWNvbG9yPSIjOWE3MDQwIiBzdG9wLW9wYWNpdHk9IjEiLz4KICAgIDxzdG9wIG9mZnNldD0iOTElIiBzdG9wLWNvbG9yPSIjOTA2NzM5IiBzdG9wLW9wYWNpdHk9IjEiLz4KICAgIDxzdG9wIG9mZnNldD0iOTYlIiBzdG9wLWNvbG9yPSIjODg2MDM2IiBzdG9wLW9wYWNpdHk9IjEiLz4KICAgIDxzdG9wIG9mZnNldD0iMTAwJSIgc3RvcC1jb2xvcj0iIzc4NTgzNCIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgPC9saW5lYXJHcmFkaWVudD4KICA8cmVjdCB4PSIwIiB5PSIwIiB3aWR0aD0iMSIgaGVpZ2h0PSIxIiBmaWxsPSJ1cmwoI2dyYWQtdWNnZy1nZW5lcmF0ZWQpIiAvPgo8L3N2Zz4=);
	background: -moz-linear-gradient(top,  rgba(246,201,146,1) 0%, rgba(195,150,95,1) 4%, rgba(193,148,93,1) 17%, rgba(186,142,88,1) 30%, rgba(154,112,64,1) 74%, rgba(144,103,57,1) 91%, rgba(136,96,54,1) 96%, rgba(120,88,52,1) 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(246,201,146,1)), color-stop(4%,rgba(195,150,95,1)), color-stop(17%,rgba(193,148,93,1)), color-stop(30%,rgba(186,142,88,1)), color-stop(74%,rgba(154,112,64,1)), color-stop(91%,rgba(144,103,57,1)), color-stop(96%,rgba(136,96,54,1)), color-stop(100%,rgba(120,88,52,1))); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top,  rgba(246,201,146,1) 0%,rgba(195,150,95,1) 4%,rgba(193,148,93,1) 17%,rgba(186,142,88,1) 30%,rgba(154,112,64,1) 74%,rgba(144,103,57,1) 91%,rgba(136,96,54,1) 96%,rgba(120,88,52,1) 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top,  rgba(246,201,146,1) 0%,rgba(195,150,95,1) 4%,rgba(193,148,93,1) 17%,rgba(186,142,88,1) 30%,rgba(154,112,64,1) 74%,rgba(144,103,57,1) 91%,rgba(136,96,54,1) 96%,rgba(120,88,52,1) 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top,  rgba(246,201,146,1) 0%,rgba(195,150,95,1) 4%,rgba(193,148,93,1) 17%,rgba(186,142,88,1) 30%,rgba(154,112,64,1) 74%,rgba(144,103,57,1) 91%,rgba(136,96,54,1) 96%,rgba(120,88,52,1) 100%); /* IE10+ */
	background: linear-gradient(top,  rgba(246,201,146,1) 0%,rgba(195,150,95,1) 4%,rgba(193,148,93,1) 17%,rgba(186,142,88,1) 30%,rgba(154,112,64,1) 74%,rgba(144,103,57,1) 91%,rgba(136,96,54,1) 96%,rgba(120,88,52,1) 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f6c992', endColorstr='#785834',GradientType=0 ); /* IE6-8 */
}



.small-button {
	border-radius:3px;
	-moz-border-radius:3px;
	-webkit-border-radius:3px;
	border: 1px solid black;
	
	padding:2px 10px 4px 10px;

	box-shadow: 0px 1px 0px rgba(255, 255, 255, .15);
	-moz-box-shadow: 0px 1px 0px rgba(255, 255, 255, .15);
	-webkit-box-shadow: 0px 1px 0px rgba(255, 255, 255, .15);

	font-size:14px;
	color:rgb(0, 0, 0);
	text-shadow:0px 1px 0px rgba(255, 255, 255, 0.2);
}


.big-button {
	border-radius:3px;
	-moz-border-radius:3px;
	-webkit-border-radius:3px;
	border: 1px solid black;
	
	padding:8px;

	box-shadow: 0px 1px 0px rgba(255, 255, 255, .15);
	-moz-box-shadow: 0px 1px 0px rgba(255, 255, 255, .15);
	-webkit-box-shadow: 0px 1px 0px rgba(255, 255, 255, .15);

	font-size:18px;
	color:rgb(0, 0, 0);
	text-shadow:0px 1px 0px rgba(255, 255, 255, 0.3);
	width: 278px;
	text-align: center;
	font-weight:700;

	display:inline-block;	
}




.top-search-bar {
	border: 0px;
	height:20px;
	
	border-radius:3px;
	-moz-border-radius:3px;
	-webkit-border-radius:3px;
}





span.category {
	/* http://www.colorzilla.com/gradient-editor/#565656+0,212121+4,000000+80;Custom */
	
	background: rgb(86,86,86); /* Old browsers */
	/* IE9 SVG, needs conditional override of 'filter' to 'none' */
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzU2NTY1NiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjQlIiBzdG9wLWNvbG9yPSIjMjEyMTIxIiBzdG9wLW9wYWNpdHk9IjEiLz4KICAgIDxzdG9wIG9mZnNldD0iODAlIiBzdG9wLWNvbG9yPSIjMDAwMDAwIiBzdG9wLW9wYWNpdHk9IjEiLz4KICA8L2xpbmVhckdyYWRpZW50PgogIDxyZWN0IHg9IjAiIHk9IjAiIHdpZHRoPSIxIiBoZWlnaHQ9IjEiIGZpbGw9InVybCgjZ3JhZC11Y2dnLWdlbmVyYXRlZCkiIC8+Cjwvc3ZnPg==);
	background: -moz-linear-gradient(top,  rgba(86,86,86,1) 0%, rgba(33,33,33,1) 4%, rgba(0,0,0,1) 80%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(86,86,86,1)), color-stop(4%,rgba(33,33,33,1)), color-stop(80%,rgba(0,0,0,1))); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top,  rgba(86,86,86,1) 0%,rgba(33,33,33,1) 4%,rgba(0,0,0,1) 80%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top,  rgba(86,86,86,1) 0%,rgba(33,33,33,1) 4%,rgba(0,0,0,1) 80%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top,  rgba(86,86,86,1) 0%,rgba(33,33,33,1) 4%,rgba(0,0,0,1) 80%); /* IE10+ */
	background: linear-gradient(top,  rgba(86,86,86,1) 0%,rgba(33,33,33,1) 4%,rgba(0,0,0,1) 80%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#565656', endColorstr='#000000',GradientType=0 ); /* IE6-8 */

	border:0px solid rgb(27, 14, 0);
	border-radius:0 3px 3px 0;
	-moz-border-radius:0 3px 3px 0;
	-webkit-border-radius:0 3px 3px 0;
	padding:4px 10px 4px 6px;

	font-size:12px;
	color:rgb(242, 242, 242);
	text-shadow:0px 1px 0px rgba(103, 103, 103, 0.4);

	border-top-left-radius: 3px;
	border-bottom-left-radius: 3px;
}


span.category1 {
	border-left: 10px solid #953100;
}

span.category2 {
	border-left: 10px solid #980000;
}


</style>

</head>


<body>





<div id="list-view-area">
	<h2>Deals in Seattle</h2>
	<div id="container">
			<!----------------------------------------------------------------->
			<!----------------------------------------------------------------->
			<div class="box">
				<div class="deal-image">
					<img class="deal-image" src="http://a2.ak.lscdn.net/imgs/4d449338-a095-4443-ad3f-4795e73d2034/280_q60_.jpg">
				</div>
				<div style="background-color: black; color: #fff; padding:5px; overflow:auto">
					<span style="float: left"><span style="font-size:22px; font-weight:700">$25</span> <span style="position:relative; top:-7px; color:#999999">for $50 value</span></span>
					<span style="float: right"><span style="font-size:22px; font-weight:700">1,204</span> <span style="position:relative; top:-7px; color:#999999">sold</span></span>
				</div>

				<div style="background-color: rgba(0, 0, 0, 0.7); color: #fff; background-image: url('/images/grid.png'); padding:5px;">
					<span style="font-size:20px; font-weight:bold">Milk Tea & Tea from Tapioca Express in Berkeley</span>
					<br>
					<span class="gray">
						Tapioca Express, Seattle
						<br>
						25 reviews
					</span>
					<div style="margin-top: 8px">
						<span class="category category1">The finer things</span>
						<span class="category category2">Date night</span>
					</div>
					<a class="big-button orange-gradient" style="margin-top:12px; margin-bottom:3px;">Details at Gilt City</a>
					<span style="color:#999999; font-size:12px;">Posted 2 days ago - 9h 3m to go</span>
				</div>
			</div>		
			<!----------------------------------------------------------------->
			<!----------------------------------------------------------------->
			<div class="box">
				<div class="deal-image">
					<img class="deal-image" src="http://a2.ak.lscdn.net/imgs/4d449338-a095-4443-ad3f-4795e73d2034/280_q60_.jpg">
				</div>
				<div style="background-color: black; color: #fff; padding:5px; overflow:auto">
					<span style="float: left"><span style="font-size:22px; font-weight:700">$25</span> <span style="position:relative; top:-7px; color:#999999">for $50 value</span></span>
					<span style="float: right"><span style="font-size:22px; font-weight:700">1,204</span> <span style="position:relative; top:-7px; color:#999999">sold</span></span>
				</div>

				<div style="background-color: rgba(0, 0, 0, 0.7); color: #fff; background-image: url('/images/grid.png'); padding:5px;">
					<span style="font-size:20px; font-weight:bold">Milk Tea & Tea from Tapioca Express in Berkeley</span>
					<br>
					<span class="gray">
						Tapioca Express, Seattle
						<br>
						25 reviews
					</span>
					<div style="margin-top: 8px">
						<span class="category category1">The finer things</span>
						<span class="category category2">Date night</span>
					</div>
					<a class="big-button orange-gradient" style="margin-top:12px; margin-bottom:3px;">Details at Gilt City</a>
					<span style="color:#999999; font-size:12px;">Posted 2 days ago - 9h 3m to go</span>
				</div>
			</div>		
			<!----------------------------------------------------------------->
			<!----------------------------------------------------------------->
			<div class="box">
				<div class="deal-image">
					<img class="deal-image" src="http://a2.ak.lscdn.net/imgs/4d449338-a095-4443-ad3f-4795e73d2034/280_q60_.jpg">
				</div>
				<div style="background-color: black; color: #fff; padding:5px; overflow:auto">
					<span style="float: left"><span style="font-size:22px; font-weight:700">$25</span> <span style="position:relative; top:-7px; color:#999999">for $50 value</span></span>
					<span style="float: right"><span style="font-size:22px; font-weight:700">1,204</span> <span style="position:relative; top:-7px; color:#999999">sold</span></span>
				</div>

				<div style="background-color: rgba(0, 0, 0, 0.7); color: #fff; background-image: url('/images/grid.png'); padding:5px;">
					<span style="font-size:20px; font-weight:bold">Milk Tea & Tea from Tapioca Express in Berkeley</span>
					<br>
					<span class="gray">
						Tapioca Express, Seattle
						<br>
						25 reviews
					</span>
					<div style="margin-top: 8px">
						<span class="category category1">The finer things</span>
						<span class="category category2">Date night</span>
					</div>
					<a class="big-button orange-gradient" style="margin-top:12px; margin-bottom:3px;">Details at Gilt City</a>
					<span style="color:#999999; font-size:12px;">Posted 2 days ago - 9h 3m to go</span>
				</div>
			</div>	
			<!----------------------------------------------------------------->
			<!----------------------------------------------------------------->
			<div class="box">
				<div class="deal-image">
					<img class="deal-image" src="http://a2.ak.lscdn.net/imgs/4d449338-a095-4443-ad3f-4795e73d2034/280_q60_.jpg">
				</div>
				<div style="background-color: black; color: #fff; padding:5px; overflow:auto">
					<span style="float: left"><span style="font-size:22px; font-weight:700">$25</span> <span style="position:relative; top:-7px; color:#999999">for $50 value</span></span>
					<span style="float: right"><span style="font-size:22px; font-weight:700">1,204</span> <span style="position:relative; top:-7px; color:#999999">sold</span></span>
				</div>

				<div style="background-color: rgba(0, 0, 0, 0.7); color: #fff; background-image: url('/images/grid.png'); padding:5px;">
					<span style="font-size:20px; font-weight:bold">Milk Tea & Tea from Tapioca Express in Berkeley</span>
					<br>
					<span class="gray">
						Tapioca Express, Seattle
						<br>
						25 reviews
					</span>
					<div style="margin-top: 8px">
						<span class="category category1">The finer things</span>
						<span class="category category2">Date night</span>
					</div>
					<a class="big-button orange-gradient" style="margin-top:12px; margin-bottom:3px;">Details at Gilt City</a>
					<span style="color:#999999; font-size:12px;">Posted 2 days ago - 9h 3m to go</span>
				</div>
			</div>	
			<!----------------------------------------------------------------->
			<!----------------------------------------------------------------->
	</div>
</div>



<div id="top-bar">
	<div style="height: 39px; width: 958px; margin: 3px auto;">
		<a class="small-button brown-gradient" style="position:relative; top:10px;">See deals on map</a>
		<div style="position: relative; top: 20px; left:380px;">
			<input type="text" class="top-search-bar">
			<a class="small-button brown-gradient">Go</a>
		</div>
	</div>
</div>






<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="/masonry/jquery.masonry.min.js"></script>
<script>










$(function () {

	var $container = $('#container');
	
	$container.imagesLoaded(function(){
		$("img.deal-image").each(function () {
			var ar = this.width / this.height;
			if (ar > 1.75) {
				console.log(this.src + ": " + ar);
				this.style.height = "310px";
				this.style.width = "auto";

			}
			if (this.height > 310) {
				var nudge = (this.height - 310) / -2;
				console.log(this.src + ": " + nudge);
				this.style.marginTop = nudge + "px";
			}
		});


		$container.masonry({
			itemSelector : '.box',
			gutterWidth : 14,
			isAnimated: true
		});
	});
	
});

</script>



</body>


</html>