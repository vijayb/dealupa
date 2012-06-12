<html>
<head>
<link rel="stylesheet" href="blueprint/screen.css" type="text/css" media="screen, projection">

</head>
<body>

<?php

$con = mysql_connect("localhost", "crawler", "daewoo");

if (!$con) {
   die('Error: could not connect. '. mysql_error());
}

mysql_select_db("WorkQueue", $con) or die('Error' . mysql_error());

$companies["1"] = "Groupon";
$companies["2"] = "LivingSocial";
$companies["3"] = "BuyWithMe";
$companies["4"] = "Tippr";
$companies["5"] = "Travel Zoo";
$companies["6"] = "Angie's List";
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
$companies["19"] = "GetMyPerks";
$companies["20"] = "Voice Daily Deals";
$companies["21"] = "Munch on Me";
$companies["22"] = "Doodle Deals";
$companies["23"] = "Juice in the City";
$companies["24"] = "Schwaggle";
$companies["25"] = "Home Run";
$companies["26"] = "Bargain Bee";
$companies["27"] = "SignPost";
$companies["30"] = "DealFind";
$companies["31"] = "Restaurant.com";
$companies["32"] = "Pinchit";
$companies["33"] = "GoldStar Events";
$companies["34"] = "OnSale";
$companies["35"] = "LivingSocial Adventures";
$companies["36"] = "Entertainment.com";
$companies["37"] = "Thrillist";
$companies["38"] = "Savored";
$companies["39"] = "MSN Offers";
$companies["40"] = "CBS Local Offers";
$companies["41"] = "CrowdSavings";
$companies["42"] = "PlumDistrict";
$companies["43"] = "Mamapedia";
$companies["44"] = "DailyCandy";
$companies["45"] = "DealChicken";
$companies["46"] = "Weforia";

if (isset($_GET["company_id"])) {
  $sql = "SELECT id, work, status_message from WorkQueue where status_message is not null and type=2 and company_id=".$_GET["company_id"];
  
  $result = mysql_query($sql);
  if (!$result) {
    die('Error: ' .mysql_error());
  }

  echo "<h2 align=center>Hubs with no deals extracted for ".$companies[$_GET["company_id"]]." (".$_GET["company_id"].")</h2>\n";

  echo "<table><tr><td>Work ID</td><td>Hub URL</td></tr>\n";
  while ($row = @mysql_fetch_assoc($result)) {
    if (preg_match("/extracted 0 deal/", $row["status_message"])) {
      echo "<tr>\n";
      echo "<td><a href=\"http://50.57.136.167/tools/work_info.php?id=".$row["id"]."\" target=_work>".$row["id"]."</a></td>\n";
      echo "<td><a href=\"".$row["work"]."\" target=_blank>".$row["work"]."</a></td>\n";

      echo "</tr>";
    }
  }
  echo "</table>\n";
} else {
  $sql = "SELECT company_id, status_message from WorkQueue where status_message is not null and type=2";
  
  $result = mysql_query($sql);
  if (!$result) {
    die('Error: ' .mysql_error());
  }
  
  $company_count = array();
  $total_count = array();
  while ($row = @mysql_fetch_assoc($result)) {
    
    if (!isset($total_count[$row["company_id"]])) {
      $total_count[$row["company_id"]] = 0;
    }
    $total_count[$row["company_id"]] += 1;
    
    if (preg_match("/extracted 0 deal/", $row["status_message"])) {
      if (!isset($company_count[$row["company_id"]])) {
	$company_count[$row["company_id"]] = 0;
      }
      $company_count[$row["company_id"]] += 1;
    }
  }
  
  arsort($company_count);
  
  echo "<h2 align=center>Hubs with no deals extracted</h2>\n";
  
  if (count($company_count) > 0) {
    echo "<table align=center>\n";
    echo "<tr><td>Company</td><td>Number of hubs with no deals extracted</td><td>Total number of hubs</td><td>Percentage</td></tr>\n";
    
    foreach ($company_count as $company_id => $count) {
      $percent = round((100.0*$count/$total_count[$company_id]),2);
      if ($percent > 50) {
	echo "<tr><td><a href=\"/tools/hub_crawl_stats.php?company_id=$company_id\">".$companies[$company_id]." (".$company_id.")</a></td><td>".$count."</td><td>".$total_count[$company_id]."</td><td><font color=red>".$percent."%</font></td></tr>\n";
      } else if($percent > 10) {
	echo "<tr><td><a href=\"/tools/hub_crawl_stats.php?company_id=$company_id\">".$companies[$company_id]." (".$company_id.")</a></td><td>".$count."</td><td>".$total_count[$company_id]."</td><td><font color=#B26200>".$percent."%</font></td></tr>\n";
      } else {
	echo "<tr><td><a href=\"/tools/hub_crawl_stats.php?company_id=$company_id\">".$companies[$company_id]." (".$company_id.")</a></td><td>".$count."</td><td>".$total_count[$company_id]."</td><td>".$percent."%</td></tr>\n";
      }
    }
    echo "</table>\n";
  }
  
}
?>


</body>
</html>
