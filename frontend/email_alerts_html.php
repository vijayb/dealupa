<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require("array_constants.php");
require("db_user.php");

$user = $_GET["user"];

$query = "SELECT * FROM EmailAlerts WHERE user_id=" . $user;

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}



$i = 0;
$sections = array();

while ($row = mysql_fetch_assoc($result)) {
	
	$params = array();
	parse_str($row["params"], $params);
	
	if (isset($params["c"])) {
		$sections[$i]["category"] = strpos($params["c"], "1") / 2;
	} else {
		$sections[$i]["category"] = "1";
	}
	
	$sections[$i]["name"] = $row["name"];

	if (isset($params["i"])) {
		$sections[$i]["edition"] = $params["i"];
	} else {
		$sections[$i]["edition"] = 3;
	}

	if (isset($params["y"])) {
		$sections[$i]["yelp"] = $params["y"];
	} else {
		$sections[$i]["yelp"] = 0;
	}
	
	if (isset($params["q"])) {
		$sections[$i]["query"] = $params["q"];
	} else {
		$sections[$i]["query"] = "";
	}
	
	$sections[$i]["id"] = $row["id"];
	
	$i++;
}



$html = "";

$html = <<<HTML
		<table id="alerts-table">
			<tbody>
HTML;

if (count($sections) == 0) {

	$html .= <<<HTML
			<tr id="alert-none">
				<td colspan=5>
					<span style="color:#aaaaaa">(You haven't set up any email alerts. Set one up below!)</span>
				</td>
			</tr>
HTML;

}

for ($i = 0; $i < count($sections); $i++) {

	$html .= <<<HTML
			<tr id="alert-{$sections[$i]["id"]}">
				<td>{$sections[$i]["query"]}</td>
				<td>{$cities[$sections[$i]["edition"]]}</td>
				<td><a class="category-{$sections[$i]["category"]}">{$categories[$sections[$i]["category"]]}</a></td>
				<td>{$sections[$i]["yelp"]}</td>
				<td><a href="javascript:void(0)" onclick="deleteAlert({$sections[$i]["id"]}); return false;">Delete</a></td>
			</tr>
HTML;

}

	$html .= <<<HTML


			<tr><td style="height:20px"></td></tr>
			<tr>
				<td style="font-size:12px; color:#999999">Keyword</td>
				<td style="font-size:12px; color:#999999">Location</td>
				<td style="font-size:12px; color:#999999">Category</td>
				<td style="font-size:12px; color:#999999">Yelp rating</td>
		
			</tr>
			
			<tr>
				<td>
					<input type="text" style="width:100px;" id="email-keyword" placeholder="">
				</td>
				<td>
					<select id="email-edition">
						<option value="3">Seattle</option>
						<option value="4">Portland</option>
						<option value="5">San Francisco</option>
						<option value="6">San Jose</option>
						<option value="7">San Diego</option>
						<option value="8">Silicon Valley</option>
						<option value="9">Los Angeles</option>
						<option value="10">Tacoma</option>
						<option value="11">New York</option>
						<option value="12">Chicago</option>
						<option value="13">Boston</option>
						<option value="14">Atlanta</option>
						<option value="15">Orlando</option>
						<option value="16">Houston</option>
						<option value="17">Washington, D.C.</option>
						<option value="18">Miami</option>
						<option value="19">Dallas</option>
						<option value="20">Denver</option>
						<option value="21">Las Vegas</option>
						<option value="22">Austin</option>
						<option value="23">Philadelphia</option>
						<option value="24">Cleveland</option>
						<option value="25">Minneapolis/St.Paul</option>
						<option value="26">Phoenix</option>
						<option value="27">Orange County</option>
						<option value="28">Baltimore</option>
						<option value="29">Kansas City</option>
						<option value="30">Detroit</option>
						<option value="31">St. Louis</option>
						<option value="32">Pittsburgh</option>
						<option value="33">San Antonio</option>
						<option value="34">New Orleans</option>
					</select>
				</td>
				<td>						
					<select id="email-category">
						<option value="-1">All categories</option>
						<option value="1">Food & Drink</option>
						<option value="2">Activities & Events</option>
						<option value="3">Spa & Beauty</option>
						<option value="4">Kids & Parents</option>
						<option value="5">Shopping & Services</option>
						<option value="6">Classes & Learning</option>
						<option value="6">Fitness & Health</option>
						<option value="7">Dental & Medical</option>
					</select>
				</td>
				<td>						
					<select id="email-yelp">
						<option value="0">Any rating</option>
						<option value="3.5">3.5+ stars</option>
						<option value="4">4+ stars</option>
						<option value="4.5">4.5+ stars</option>
						<option value="5">5 stars</option>
					</select>
				</td>				<td>
					<a href="javascript:void(0)" class="c-g save-search" id="test-alert-button" onclick="testAlert(); return false;">Test this alert</a>
					<a href="javascript:void(0)" class="c-g save-search" id="add-alert-button" onclick="addAlert(); return false;">Add this alert</a>
				</td>
			</tr>
			</tbody>

	
		</table>

HTML;


echo($html);

?>
