<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<link rel="stylesheet" href="blueprint/screen.css" type="text/css" media="screen, projection">

<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?sensor=false"></script>

<script type="text/javascript">

function geocode(form) {

	document.forms[form].city.value = "";
	document.forms[form].state.value = "";
	document.forms[form].country.value = "";
	document.forms[form].zipcode.value = "";
	document.forms[form].street.value = "";
	document.forms[form].country.value = "";
	

	geocoder = new google.maps.Geocoder();

	address_string = document.forms[form].raw_address.value.replace(/(\r\n|\n|\r)/gm,"");
	
	geocoder.geocode( { 'address': address_string}, function(results, status) {
	  if (status == google.maps.GeocoderStatus.OK) {
		
		
		var arrAddress = results[0].address_components;
		
		var street_number, street_name, city, zip, state, country;
		
		for (var k = 0; k < arrAddress.length; k++) {		
			if (arrAddress[k].types.indexOf("locality") > -1)	{
				city = arrAddress[k].short_name;
			}
			if (arrAddress[k].types.indexOf("administrative_area_level_1") > -1)	{
				state = arrAddress[k].short_name;
			}
			if (arrAddress[k].types.indexOf("country") > -1)	{
				country = arrAddress[k].long_name;
			}
			if (arrAddress[k].types.indexOf("postal_code") > -1)	{
				zip = arrAddress[k].short_name;
			}
			if (arrAddress[k].types.indexOf("route") > -1)	{
				street_name = arrAddress[k].short_name;
			}
			if (arrAddress[k].types.indexOf("street_number") > -1)	{
				street_number = arrAddress[k].short_name;
			}
		}
		
		if (street_number != undefined && street_name != undefined) {
			document.forms[form].street.value = street_number + " " + street_name;
		} else if (street_number == undefined && street_name != undefined) {
			document.forms[form].street.value = street_name;
		}
		
		
		if (city != undefined) document.forms[form].city.value = city;
		if (state != undefined) document.forms[form].state.value = state;
		if (zip != undefined) document.forms[form].zipcode.value = zip;
		if (country != undefined) document.forms[form].country.value = country;
		
		document.forms[form].latitude.value = results[0].geometry.location.lat();
		document.forms[form].longitude.value = results[0].geometry.location.lng();
		
	  } else {	
		alert("Geocode was not successful for the following reason: " + status);
	  }
	});

} 
 
function validate(key) {
   //getting key code of pressed key
   var keycode = (key.which) ? key.which : key.keyCode;
   var phn = document.getElementById('txtPhn');
   //comparing pressed keycodes
   if ((keycode < 48 || keycode > 57)) {
     return false;
   } else {
     //Condition to check textbox contains ten numbers or not
     if (phn.value.length <10) {
       return true;
     } else {
       return false;
     }
   }
 }
 
   
</script>

</head>

<body>
<h2 align=center>Address fixer</h2>

<form action='/tools/address_fixer.php' method=get align=center>
Deal ID: <input type='text' name='deal_id' onkeypress="return validate(event)"  >
<input type=submit value='Show address for this deal' />


</form>
<p><p>
<?php
// Make a MySQL Connection
$con = mysql_connect("localhost", "crawler", "daewoo");
if (!$con) {
  die('Error: could not connect. ' . mysql_error());
}
mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection


/*
foreach ($_POST as $key=>$value) {
  echo "$key:$value<BR>\n";
}
*/

echo "<p>\n";
// Handle address addition
if (isset($_POST["add_address"])) {
  $update_deal_id = $_POST["deal_id"];
  if (isset($_POST["raw_address"]) && strlen($_POST["raw_address"]) > 5) {
    addAddress($update_deal_id, $_POST["raw_address"], $con);
    echo "<font color='green'>Successfully added address</font><BR>\n";
  } else {
    echo "<font color='red'>Invalid address, not long enough, so not adding it</font><BR>\n";
  }
}

// Handle address deletion
if (isset($_POST["delete_address"]) && isset($_POST["id"]) && isset($_GET["deal_id"])) {
  removeAddress($_GET["deal_id"], $_POST["id"], $con);
  echo "<font color='green'>Successfully deleted address ".$_POST["id"]."</font><BR>\n";
}

// Handle address update

if (isset($_POST["submit_address"]) && isset($_POST["id"]) && isset($_GET["deal_id"])) {
  if (preg_match('/^-?(\d+(\.\d+)?)$/', $_POST["latitude"]) &&
      preg_match('/^-?(\d+(\.\d+)?)$/', $_POST["longitude"])) {

    updateAddress($_GET["deal_id"],
		  $_POST["id"],
		  $_POST["raw_address"],
		  $_POST["street"],
		  $_POST["city"],
		  $_POST["state"],
		  $_POST["country"],
		  $_POST["zipcode"],
		  $_POST["latitude"],
		  $_POST["longitude"],
		  $con);
		  
      echo "<font color='green'>Successfully updated address ".$_POST["id"]."</font><BR>\n";

  } else {
      echo "<font color='red'>Invalid latitude or longitude, not adding address</font><BR>\n";
  }
}



$address_ids = array();

if (isset($_GET["deal_id"]) && strlen($_GET["deal_id"]) > 0) {
  $deal_id=$_GET["deal_id"];
  
  $result = doQuery("select id from Addresses where deal_id=$deal_id", $con);

  while ($row = @mysql_fetch_assoc($result)) {
    array_push($address_ids, $row["id"]);
  }
 
  $result = doQuery("select url from Deals where id=$deal_id", $con);
  while ($row = @mysql_fetch_assoc($result)) {
    $deal_url = $row['url'];
  }
} else {
  $result = doQuery("select url,TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), discovered))/3600 as age, Addresses.id, deal_id, raw_address from Addresses left join Deals on Addresses.deal_id=Deals.id where raw_address is not null and latitude is null and TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), discovered))>3600 order by age", $con);
  
  $num_orphaned = mysql_num_rows($result);

  echo "<h3 align=center>There are $num_orphaned addresses that weren't geocoded correctly</h3>\n";

  echo "<table>\n";
  echo "\t<form action='/tools/address_fixer.php' method=get>\n";
  echo "\t<tr><td>Deal ID</td><td>Address</td><td>Age (hours)</td><td>Fix this address</td></tr>\n";
  while ($row = @mysql_fetch_assoc($result)) {
    $deal_url = $row['url'];
    $deal_id = $row['deal_id'];
    $age = $row['age'];
    $address = $row['raw_address'];

    echo "\t<tr>\n";
    echo "\t\t<td><a href='http://50.57.43.108/tools/deal_info.php?deal_url=$deal_id&submitid=search+by+id' target=_panel>$deal_id</a></td>\n";
    echo "\t\t<td width=40%><a href='$deal_url' target=_blank>$address</a></td>\n";
    echo "\t\t<td>$age</td>\n";
    echo "\t\t<td><input type=submit name=deal_id value='$deal_id'/></td>\n";
    echo "\t</tr>\n";

  }
  echo "</form>\n";
  echo "<table>\n";

}

if (isset($_GET["deal_id"]) && count($address_ids) > 0) {
  echo "<h4 align=center>Address information for deal <a href='http://50.57.43.108/tools/deal_info.php?deal_url=$deal_id&submitid=search+by+id' target=_panel>$deal_id</a>. (<a href='$deal_url' target=_blank>$deal_url</a>)</h4><BR><BR>\n";

  echo "<table>\n";
  echo 
    "\t<tr>\n".
    "<td>ID</td>".
    "<td>Raw address</td>".
    "<td>Street</td>".
    "<td>City</td>".
    "<td>State</td>".
    "<td>Zipcode</td>".
    "<td>Country</td>".
    "<td>Latitude</td>".
    "<td>Longitude</td>".
    "<td></td>".
    "<td></td>".
    "<td></td>".
    "</tr>\n";
  for ($i=0; $i < count($address_ids); $i++) {
    $id = $address_ids[$i];
    $result = doQuery("select raw_address, street, city, state, zipcode, ".
		      "country, latitude, longitude from ".
		      "Addresses where id=$id", $con);
   
    while ($row = @mysql_fetch_assoc($result)) {
      $raw_address = $row['raw_address'];
      $street = $row['street'];
      $city = $row['city'];
      $state = $row['state'];
      $zipcode = $row['zipcode'];
      $country = $row['country'];
      
      $latitude = $row['latitude'];
      $longitude = $row['longitude'];

      echo "\t<form name=form_$id action='/tools/address_fixer.php?deal_id=$deal_id' method=post>\n";
      
      echo "\t\t<input type='hidden' name='id' value=$id />";
      echo "\t<tr>\n";
      echo "\t\t<td>$id</td>\n";
      echo "\t\t<td style='width:230px'><textarea style='height:auto;width:220px' style='width:auto' name='raw_address' rows='3' >$raw_address</textarea></td>\n";
      echo "\t\t<td style='width:200px'><input type='text' name=street value='$street' size=17 /></td>\n";
      echo "\t\t<td><input type='text' name=city value='$city' size=11 /></td>\n";
      echo "\t\t<td><input type='text' name=state value='$state' size=3 /></td>\n";
      echo "\t\t<td><input type='text' name=zipcode value='$zipcode' size=5 /></td>\n";
      echo "\t\t<td><input type='text' name=country value='$country' size=12 /></td>\n";

      echo "\t\t<td><input type='text' name=latitude value='$latitude' size=7 /></td>\n";
      echo "\t\t<td><input type='text' name=longitude value='$longitude' size=7 /></td>\n";


      echo "\t\t<td><input type='button' value='geocode' onClick='geocode(\"form_$id\")' />";
      echo "\t\t<td><input type='submit' name=submit_address value='submit' /></td>\n";
      echo "\t\t<td><input type='submit' name=delete_address value='delete' onclick=\"javascript:return confirm('Are you REALLY sure you want to delete the address?')\" /></td>\n";

      echo "\t</tr>\n";
      echo "\t</form>\n";
    }

  }

  echo "</table>\n";
}


if (isset($_GET["deal_id"]) && strlen($_GET["deal_id"]) > 0) {
   echo "<form action='/tools/address_fixer.php?deal_id=$deal_id' method=post >\n";
   echo "<input type='hidden' name='deal_id' value='$deal_id' />\n";
   echo "<table style='width:50%'><tr>\n";
   echo "\t\t<td><textarea style='height:auto;width:250px' style='width:auto' name='raw_address' rows='3' ></textarea></td>\n";
   echo "\t\t<td><input type='submit' name=add_address value='Add address to deal $deal_id' /></td>\n";
   echo "</tr></table>\n";
   echo "</form>\n";

   echo "<BR><BR><BR><h3 align=center><a href='http://50.57.43.108/tools/address_fixer.php'>Get more address work</a></h3>\n";
}



function addAddress($deal_id, $raw_address, $con) {
  $sql = "insert into Addresses (deal_id, raw_address) values ".
    "($deal_id, '$raw_address')";
  echo $sql."<BR>\n";
  doQuery($sql, $con);
  updateDeal($deal_id, $con);

}

function removeAddress($deal_id, $address_id, $con) {
  $sql = "delete from Addresses where id=$address_id limit 1";
  echo $sql."<BR>\n";
  doQuery($sql, $con);
  updateDeal($deal_id, $con);
}

function updateAddress($deal_id,
		       $address_id,
		       $raw_address,
		       $street,
		       $city,
		       $state,
		       $country,
		       $zipcode,
		       $latitude,
		       $longitude,
		       $con) {
  $update_vals= "latitude=$latitude,longitude=$longitude";
  if (strlen($raw_address)) {
    $update_vals = $update_vals.",raw_address='$raw_address'";
  }

  if (strlen($street)) {
    $update_vals = $update_vals.",street='$street'";
  }
  if (strlen($city)) {
    $update_vals = $update_vals.",city='$city'";
  }
  if (strlen($state)) {
    $update_vals = $update_vals.",state='$state'";
  }
  if (strlen($country)) {
    $update_vals = $update_vals.",country='$country'";
  }
  if (strlen($zipcode)) {
    $update_vals = $update_vals.",zipcode='$zipcode'";
  }

  $sql = "update Addresses set $update_vals where id=$address_id";
  echo "$sql<BR>\n";
  doQuery($sql, $con);
  updateDeal($deal_id, $con);
}
/*
    updateAddress($_GET["deal_id"],
		  $_POST["id"],
		  $_POST["raw_address"],
		  $_POST["street"],
		  $_POST["city"],
		  $_POST["state"],
		  $_POST["country"],
		  $_POST["zipcode"],
		  $_POST["latitude"],
		  $_POST["longitude"],
		  $con);
*/

function updateDeal($deal_id, $con) {
  $sql = "update Deals set last_updated=UTC_TIMESTAMP() where id=$deal_id";
  echo $sql."<BR>\n";
  doQuery($sql, $con);
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