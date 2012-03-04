<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<link rel="stylesheet" href="blueprint/screen.css" type="text/css" media="screen, projection">

<script type="text/javascript">


</script>


</head>
<body>
<h2 align=center>Deal Information</h2>

<form action='/tools/deal_info.php' method=get align=center>

<input type='text' name='deal_url' />
<input type=submit name='submiturl' value='search by url' />
<input type=submit name='submitid' value='search by id' />

</form>
<p>

<?php
   

$companies["1"] = "Groupon";
$companies["2"] = "LivingSocial";
$companies["3"] = "BuyWithMe";
$companies["4"] = "Tippr";
$companies["5"] = "Travel Zoo";
$companies["6"] = "Angies List";
$companies["7"] = "Gilt City";
$companies["8"] = "Yollar ";
$companies["9"] = "Zozi";
$companies["10"] = "Bloomspot";
$companies["11"] = "ScoutMob";
$companies["12"] = "Amazon Local";
$companies["13"] = "KGB Deals";
$companies["14"] = "LifeBooker";
$companies["15"] = "DealOn";
$companies["16"] = "EverSave";
$companies["17"] = "LS Escapes";
$companies["18"] = "Google Offers";
$companies["19"] = "Get My Perks";
$companies["20"] = "Voice Daily Deals";
$companies["21"] = "Munch on Me";
$companies["22"] = "Doodle Deals";
$companies["23"] = "Juice in the City";
$companies["24"] = "Schwaggle";
$companies["25"] = "Home Run";
$companies["26"] = "Bargain Bee";
$companies["27"] = "SignPost";
$companies["28"] = "CrowdSeats";
$companies["29"] = "LandmarkGreatDeals";
$companies["30"] = "DealFind";
$companies["31"] = "Restaurant.com";
$companies["32"] = "Pinchit";
$companies["33"] = "GoldStar";



$yesno["0"] = "No";
$yesno["1"] = "Yes";


$con = mysql_connect("localhost", "crawler", "daewoo");

if (!$con) {
   die('Error 1: could not connect. '. mysql_error());
}



mysql_select_db("Deals", $con) or die('Error 2' . mysql_error());


if (isset($_POST["deal_id"]) && isset($_POST["expire"])) {
  if ($_POST["expire"] == "expire") {
    $expire_value = 1;
  } else {
    $expire_value = 0;
  }

  expireDeal($_POST["deal_id"], $expire_value, $con);

}

if (isset($_GET["deal_url"])) {
  if (isset($_GET["submitid"])) {
    $criteria = "id=".$_GET["deal_url"];
  } else {
    $url_hash = sha1($_GET["deal_url"]);
    $criteria = "url_hash=UNHEX('$url_hash')";
  }


  $sql = "SELECT id, url, affiliate_url, discovered, last_updated, dup, dup_id, company_id, title, subtitle, price, value, num_purchased, text, fine_print, expired, upcoming, deadline, expires, name, website, phone FROM Deals777 where $criteria"; //strcmp(url, '".mysql_real_escape_string($_GET["deal_url"])."')=0";

  //echo "$sql<BR>\n";
  $result = mysql_query($sql);
  if (!$result) {
    die('Error 3: ' .mysql_error());
  }
}







if (isset($result) && mysql_num_rows($result) == 1) {
  $deal_id = mysql_result($result, 0, "id");
  $url = mysql_result($result, 0, "url");
  $affiliate_url = mysql_result($result, 0, "affiliate_url");
  $discovered = mysql_result($result, 0, "discovered");
  $last_updated = mysql_result($result, 0, "last_updated");
  $dup = mysql_result($result, 0, "dup");
  $dup_id = mysql_result($result, 0, "dup_id");
  $company_id = mysql_result($result, 0, "company_id");
  $title = mysql_result($result, 0, "title");
  $subtitle = mysql_result($result, 0, "subtitle");
  $price = mysql_result($result, 0, "price");
  $value = mysql_result($result, 0, "value");
  $num_purchased = mysql_result($result, 0, "num_purchased");
  $text = mysql_result($result, 0, "text");
  $fine_print = mysql_result($result, 0, "fine_print");
  $expired = mysql_result($result, 0, "expired");
  $upcoming = mysql_result($result, 0, "upcoming");
  $deadline = mysql_result($result, 0, "deadline");
  $expires = mysql_result($result, 0, "expires");
  $name = mysql_result($result, 0, "name");
  $website = mysql_result($result, 0, "website");
  $phone = mysql_result($result, 0, "phone");

  $deal_info_url="/tools/deal_info.php?deal_url=".$_GET['deal_url'].
    "&submitid=".$_GET['submitid'];
  echo "<form action='$deal_info_url' method=post align=center>\n";
  echo "<table>\n";
  echo "  <tr><td><b>ID:</b></td><td>$deal_id</td></tr>\n";
  echo "  <tr><td><b>URL:</b></td><td><a href='http://50.57.136.167/tools/work_info.php?work=$url' target=_work_info>$url</a> (<a href='$url' target=_blank>web</a>)</td></tr>\n";
  echo "  <tr><td><b>Affiliate URL:</b></td><td>";

  if (isset($affiliate_url)) {
    echo "<a href='http://50.57.136.167/tools/work_info.php?work=$affiliate_url' target=_work_info>$affiliate_url</a> (<a href='$affiliate_url' target=_blank>web</a>)";
  }
  echo "</td></tr>\n";
  echo "  <tr><td><b>Discovered:</b></td><td>$discovered</td></tr>\n";
  echo "  <tr><td><b>Last updated:</b></td><td>$last_updated</td></tr>\n";
  echo "  <tr><td><b>Duplicate:</b></td><td>$yesno[$dup]</td></tr>\n";
  if ($dup) {
    echo "  <tr><td><b>Duplicate ID:</b></td><td><a href='http://50.57.43.108/tools/deal_info.php?deal_url=$dup_id&submitid=search+by+id'>$dup_id</a></td></tr>\n";
  }
  $dups_array = getDups($deal_id, $con);
  if (count($dups_array) >0) {
    echo "  <tr><td><b>Number of duplicates:</b></td><td>".count($dups_array)."&nbsp;";
    
    echo "<select name='myfield' onchange='this.form.submit()'>\n";
    for ($q=0;$q< count($dups_array); $q++) {
      echo "<option name=deal_url value=$dups_array[$q]>$dups_array[$q]</option>";
    }
   echo "</select>\n";



    echo "</td></tr>\n";
  }

  echo "  <tr><td><b>Company id:</b></td><td>$companies[$company_id] ($company_id)</td></tr>\n";
  echo "  <tr><td><b>Title:</b></td><td>$title</td></tr>\n";
  echo "  <tr><td><b>Subtitle:</b></td><td>$subtitle</td></tr>\n";
  echo "  <tr><td><b>Price:</b></td><td>$price</td></tr>\n";
  echo "  <tr><td><b>Value:</b></td><td>$value</td></tr>\n";
  echo "  <tr><td><b>Num purchased:</b></td><td>$num_purchased</td></tr>\n";
  echo "  <tr><td><b>Expired:</b></td><td>$yesno[$expired]&nbsp;&nbsp;";

  if ($expired) {
    $expire_switch='unexpire';
  } else {
    $expire_switch='expire';
  }

  echo "<input type=hidden name='deal_id' value='$deal_id' />";
  echo "<input type=submit name='expire' value='$expire_switch' />";


  echo "</td></tr>\n";
  echo "  <tr><td><b>Upcoming:</b></td><td>$yesno[$upcoming]</td></tr>\n";
  echo "  <tr><td><b>Deadline:</b></td><td>$deadline</td></tr>\n";
  echo "  <tr><td><b>Expires:</b></td><td>$expires</td></tr>\n";
  echo "  <tr><td><b>Business name:</b></td><td>$name</td></tr>\n";
  echo "  <tr><td><b>Website:</b></td><td><a href='$website' target=_blank>$website</a></td></tr>\n";
  echo "  <tr><td><b>Phone:</b></td><td>$phone</td></tr>\n";
  echo "</table>\n";
  echo "</form>\n";
}



if (isset($deal_id)) {
  $sql = "SELECT id, raw_address, street, city, state, country, latitude, longitude FROM Addresses777 where deal_id=$deal_id";
  $result = mysql_query($sql);
  if (!$result) {
    die('Error 4: ' .mysql_error());
  }

    $fix_addresses_link = "(<a href='http://50.57.43.108/tools/address_fixer.php?deal_id=$deal_id' target=_dealfixer>Add addresses or fix errors</a>)";

  echo "<p>\n";
  echo "<h3 align=center>Addresses $fix_addresses_link</h3>\n";
  echo "<table>\n";
  echo "     <tr>\n";
  echo "        <td><b>ID</b></td>\n";
  echo "        <td><b>Raw address</b></td>\n";
  echo "        <td><b>Street</b></td>\n";
  echo "        <td><b>City</b></td>\n";
  echo "        <td><b>State</b></td>\n";
  echo "        <td><b>Country</b></td>\n";
  echo "        <td><b>Latitude</b></td>\n";
  echo "        <td><b>Longitude</b></td>\n";
  echo "     </tr>\n";
  
  for ($i=0;$i < mysql_num_rows($result); $i++) {
    $id = mysql_result($result, $i, "id");
    $raw_address = mysql_result($result, $i, "raw_address");
    $street = mysql_result($result, $i, "street");
    $city = mysql_result($result, $i, "city");
    $state = mysql_result($result, $i, "state");
    $country = mysql_result($result, $i, "country");
    $latitude = mysql_result($result, $i, "latitude");
    $longitude = mysql_result($result, $i, "longitude");
    
    echo "     <tr>\n";
    echo "        <td>$id</td>\n";
    echo "        <td>$raw_address</td>\n";
    echo "        <td>$street</td>\n";
    echo "        <td>$city</td>\n";
    echo "        <td>$state</td>\n";
    echo "        <td>$country</td>\n";
    echo "        <td>$latitude</td>\n";
    echo "        <td>$longitude</td>\n";
    echo "     </tr>\n";
    
  }
  echo "</table>\n";

}











if (isset($deal_id)) {
  $sql = "SELECT image_url FROM Images777 where deal_id=$deal_id";

  $result = mysql_query($sql);
  if (!$result) {
    die('Error 5: ' .mysql_error());
  }

    $fix_images_link = "(<a href='http://50.57.43.108/tools/image_fixer.php?deal_id=$deal_id' target=_dealfixer>Add images or fix errors</a>)";

  echo "<p>\n";
  echo "<h3 align=center>Images $fix_images_link</h3>\n";
  echo "<table>\n";
  echo "     <tr>\n";
  echo "        <td><b>Image URL</b></td>\n";
  echo "     </tr>\n";
  
  for ($i=0;$i < mysql_num_rows($result); $i++) {
    $image_url = mysql_result($result, $i, "image_url");
    
    echo "     <tr>\n";
    echo "        <td><a href='$image_url' target=_blank>$image_url</a></td>\n";
    echo "     </tr>\n";
    
  }
  echo "</table>\n";
}

$categories["0"] = "Uncategorized";
$categories["1"] = "Food & Drink";
$categories["2"] = "Activities & Events";
$categories["3"] = "Spa & Beauty";
$categories["4"] = "Kids & Parents";
$categories["5"] = "Shopping & Services";
$categories["6"] = "Classes & Learning";
$categories["7"] = "Fitness & Health";
$categories["8"] = "Medical & Dental";
$categories["9"] = "Hotels & Vacations";


if (isset($deal_id)) {
  $sql = "SELECT category_id,rank FROM Categories777 where deal_id=$deal_id";
  $result = mysql_query($sql);
  if (!$result) {
    die('Error 6: ' .mysql_error());
  }

  echo "<p>\n";
  if (mysql_num_rows($result) > 0) {
    $fix_category_link = "(<a href='http://50.57.43.108/tools/classifier_fixer.php?deal_id=$deal_id' target=_dealfixer>fix errors</a>)";
  } else {
    $fix_category_link = "";
  }
  echo "<h3 align=center>Categories $fix_category_link</h3>\n";
  echo "<table>\n";
  echo "     <tr>\n";
  echo "        <td><b>Category ID</b></td><td>Rank</td>\n";
  echo "     </tr>\n";

  for ($i=0;$i < mysql_num_rows($result); $i++) {
    $category_id = mysql_result($result, $i, "category_id");
    $rank = mysql_result($result, $i, "rank");
    
    echo "     <tr>\n";
    echo "        <td>$categories[$category_id] ($category_id)</td><td>$rank</td>\n";
    echo "     </tr>\n";

  }
  echo "</table>\n";
}


$cities["1"] = "Unknown";
$cities["2"] = "National";
$cities["3"] = "Seattle";
$cities["4"] = "Portland";
$cities["5"] = "San Francisco";
$cities["6"] = "San Jose";
$cities["7"] = "San Diego";
$cities["8"] = "Silicon Valley";
$cities["9"] = "Los Angeles";
$cities["10"] = "Tacoma";
$cities["11"] = "New York";
$cities["12"] = "Chicago";
$cities["13"] = "Boston";
$cities["14"] = "Atlanta";
$cities["15"] = "Orlando";
$cities["16"] = "Houston";
$cities["17"] = "Washington DC";
$cities["18"] = "Miami";
$cities["19"] = "Dallas";
$cities["20"] = "Denver";
$cities["21"] = "Las Vegas";
$cities["22"] = "Austin";
$cities["23"] = "Philadelphia";
$cities["24"] = "Cleveland";
$cities["25"] = "Minneapolis";
$cities["26"] = "Phoenix";
$cities["27"] = "Orange County";
$cities["28"] = "Baltimore";
$cities["29"] = "Kansas City";
$cities["30"] = "Detroit";
$cities["31"] = "St Louis";
$cities["32"] = "Pittsburgh";
$cities["33"] = "San Antonio";
$cities["34"] = "New Orleans";

if (isset($deal_id)) {
  $sql = "SELECT city_id FROM Cities777 where deal_id=$deal_id";
  $result = mysql_query($sql);
  if (!$result) {
    die('Error 7: ' .mysql_error());
  }

  $nation_setting_link = "(<a href='http://50.57.43.108/tools/nation_tool.php?deal_id=$deal_id' target=_dealfixer>Set national or not</a>)";
  echo "<p>\n";
  echo "<h3 align=center>Cities $nation_setting_link</h3>\n";
  echo "<table>\n";
  echo "     <tr>\n";
  echo "        <td><b>City ID</b></td>\n";
  echo "     </tr>\n";
  
  for ($i=0;$i < mysql_num_rows($result); $i++) {
    $city_id = mysql_result($result, $i, "city_id");
    
    echo "     <tr>\n";
    echo "        <td>$cities[$city_id] ($city_id)</td>\n";
    echo "     </tr>\n";
    
  }
  echo "</table>\n";
}



function expireDeal($deal_id, $expired_value, $con) {
  $sql = "update Deals777 set expired=$expired_value where id=$deal_id";
  echo $sql."<BR>\n";
  doQuery($sql, $con);
  updateDeal($deal_id, $con);
}


function updateDeal($deal_id, $con) {
  $sql = "update Deals777 set last_updated=UTC_TIMESTAMP() where id=$deal_id";
  echo $sql."<BR>\n";
  doQuery($sql, $con);
}

function getDups($deal_id, $con) {
  $sql = "select id from Deals777 where dup_id=$deal_id";
  echo $sql."<BR>\n";
  $result = doQuery($sql, $con);

  $dups_array = array();
  for ($i=0;$i < mysql_num_rows($result); $i++) {
    array_push($dups_array, mysql_result($result, $i, "id"));
  }

  return $dups_array;
  
}


function doQuery($query, $con) {
  $result = mysql_query($query, $con);
    
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  return $result;

}


?>
</body>
</html>
