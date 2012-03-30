<html>
<head>
<script type="text/javascript">

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
<h2 align=center>Edition setting tool (set edition of deal, including whether it`s national)</h2>


<?php
$cities["2"] = "Nation";
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
$cities["35"] = "Honolulu";
arsort($cities);





$deal_id_value = "";
$deal_id = "";
if (isset($_GET['deal_id'])) {
  $deal_id = $_GET["deal_id"];
  $deal_id_value="value='".$_GET['deal_id']."'";
} else if (isset($_POST['deal_id'])) {
  $deal_id = $_POST["deal_id"];
  $deal_id_value="value='".$_POST['deal_id']."'";
}

echo "<form action='/tools/edition_tool.php?deal_id=$deal_id' method=get align=center>\n";
echo "Deal ID: <input type='text' name='deal_id' $deal_id_value onkeypress='return validate(event)' />\n";
echo "<input name='set_nation' type=submit value='Mark deal as national' />\n";
echo "<input name='unset_nation' type=submit value='Remove national' />\n";
echo "<select name=edition>\n";
echo "<option value='0' />Choose edition</option>\n";
foreach ($cities as $edition_id => $city_name) {
  echo "<option value='$edition_id' />".$city_name."</option>\n";
}

echo "</select>\n";
echo "<input name='set_edition' type=submit value='Set edition' />\n";



echo "</form>\n";


//foreach ($_POST as $key => $value) {
//  echo "$key:$value<BR>\n";
//}

// Make a MySQL Connection
$con = mysql_connect("localhost", "crawler", "daewoo");
if (!$con) {
  die('Error: could not connect. ' . mysql_error());
}
mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection



if (isset($deal_id) && strlen($deal_id) > 0) {

  outputDealInfo($deal_id, $con);

  if (isset($_GET["set_nation"])) {
    $sql = "insert into Cities (deal_id, city_id) values (".
      $deal_id.", 2) on duplicate key update id=id";
  } else if (isset($_GET["unset_nation"])) {
    $sql = "DELETE from Cities where deal_id=".$deal_id." and ".
      "city_id=2";
  } else if (isset($_GET["set_edition"]) && isset($_GET["edition"]) &&
	     $_GET["edition"] != 0) {
    $sql = "insert into Cities (deal_id, city_id) values (".
      $deal_id.", ".$_GET["edition"].") on duplicate key update id=id";
  }

  if (isset($sql) && strlen($sql) > 0) {
    echo "<BR><p>$sql<BR>\n";
    $result = mysql_query($sql, $con);
    
    if (!$result) {
      die('Error: ' . mysql_error());
    }

    $sql = "update Deals set last_updated=UTC_TIMESTAMP() where id=".
      $deal_id;
    echo "$sql\n";
    $result = mysql_query($sql, $con);
	
    if (!$result) {
      die('Error: ' . mysql_error());
    }

    echo "<p><h2><b>Successfully updated</b><h2><BR>\n";
  }


} else {
  $result = doQuery("select Deals.id as deal_id,url,TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), discovered))/3600 as age from Deals left join Cities on Cities.deal_id=Deals.id where Cities.id is null and TIME_TO_SEC(TIMEDIFF(last_updated, discovered))>1 order by age", $con);

  $num_orphaned = mysql_num_rows($result);

  echo "<h3 align=center>There are $num_orphaned deals without editions</h3>\n";

  echo "<table>\n";
  echo "\t<form action='/tools/edition_tool.php' method=get>\n";
  echo "\t<tr><td>Deal ID</td><td>URL</td><td>Age (hours)</td><td>Add editions to this deal</td></tr>\n";
  while ($row = @mysql_fetch_assoc($result)) {
    $deal_url = $row['url'];
    $deal_id = $row['deal_id'];
    $age = $row['age'];

    echo "\t<tr>\n";
    echo "\t\t<td><a href='http://50.57.43.108/tools/deal_info.php?deal_url=$deal_id&submitid=search+by+id' target=_panel>$deal_id</a></td>\n";
    echo "\t\t<td><a href='$deal_url' target=_panel>$deal_url</a></td>\n";
    echo "\t\t<td>$age</td>\n";
    echo "\t\t<td><input type=submit name=deal_id value='$deal_id'/></td>\n";
    echo "\t</tr>\n";

  }
  echo "</form>\n";
  echo "<table>\n";
}

if (isset($deal_id)) {
  outputEditions($deal_id, $cities, $con);
}

echo "<BR><center><h2><a href=\"http://50.57.43.108/tools/edition_tool.php\">Find more edition setting work</a></h2></center>\n";

function doQuery($query, $con) {
  $result = mysql_query($query, $con);

  if (!$result) {
    die('Error: ' . mysql_error());
  }
  return $result;

}

function outputDealInfo($deal_id, $con) {
  $result = doQuery("select url from Deals where id=$deal_id", $con);
  if ($row = @mysql_fetch_assoc($result)) {
    echo "<center>Deal ID: <a href=\"http://50.57.43.108/tools/deal_info.php?deal_url=$deal_id&submitid=search+by+id\" target=_panel>$deal_id</a> : <a href=\"".$row["url"]."\" target=_blank>".$row["url"]."</a></center><BR>\n";
  }
  $result = doQuery("select raw_address from Addresses where deal_id=$deal_id", $con);
  $count = 1;
  while ($row = @mysql_fetch_assoc($result)) {
    echo "Address $count: ".$row["raw_address"]."<BR>\n";
    $count++;
  }

}

function outputEditions($deal_id, $cities, $con) {
  $sql = "SELECT city_id FROM Cities where deal_id=$deal_id";
  $result = mysql_query($sql, $con);
  if (!$result) {
    die('Error 7: ' .mysql_error());
  }

  echo "<p>\n";

  if (mysql_num_rows($result) == 0) {
    echo "<h3 align=center>No editions have been set for deal: $deal_id</h3>\n";
  } else {
    echo "<h3 align=center>Editions for deal: $deal_id</h3>\n";
    echo "<table>\n";
    for ($i=0;$i < mysql_num_rows($result); $i++) {
      $city_id = mysql_result($result, $i, "city_id");
    
      echo "     <tr>\n";
      echo "        <td>$cities[$city_id] ($city_id)</td>\n";
      echo "     </tr>\n";
      
    }
    echo "</table>\n";
  }


}



?>

</body>
</html>