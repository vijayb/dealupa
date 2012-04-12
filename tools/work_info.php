<html>
<head>
<link rel="stylesheet" href="blueprint/screen.css" type="text/css" media="screen, projection">


</head>
<body>
<h2 align=center>Work Information</h2>

<form action='/tools/work_info.php' method=get align=center>

<input type='text' name='work' />
<input type=submit value='search for work' />

</form>
<p>

<?php

$con = mysql_connect("localhost", "crawler", "daewoo");

if (!$con) {
   die('Error: could not connect. '. mysql_error());
}

mysql_select_db("WorkQueue", $con) or die('Error' . mysql_error());

$where_clause = "true";
if (isset($_GET["id"])) {
  $where_clause = $where_clause." and id=".$_GET["id"];
}
if (isset($_GET["type"])) {
  $where_clause = $where_clause." and type=".$_GET["type"];
}

if (isset($_GET["work"])) {
  $where_clause = $where_clause." and strcmp(work, '".mysql_real_escape_string($_GET["work"])."')=0";
}

if (isset($_GET["company_id"])) {
  $where_clause = $where_clause." and company_id=".$_GET["company_id"];
}

if (isset($_GET["worker_ip"]) && isset($_GET["worker_pid"])) {
  $where_clause = $where_clause." and strcmp(worker_ip, '".$_GET["worker_ip"].
    "')=0 and worker_pid=".$_GET["worker_pid"];
}
$sql = "SELECT id, work, type, company_id, frequency, output_server, output_database, worker_ip, worker_pid, created, started, completed, status, status_message from WorkQueue where ($where_clause) order by started desc limit 100";




$result = mysql_query($sql);
if (!$result) {
   die('Error: ' .mysql_error());
}


$types["1"] = "Hub adder";
$types["2"] = "Hub crawler";
$types["3"] = "Deal crawler";
$types["4"] = "Geo coder";
$types["5"] = "Yelp reviewer";
$types["6"] = "XML feed crawler";
$types["7"] = "Restaurant.com yelp info gatherer";
$types["8"] = "Restaurant.com feed crawler";
$types["9"] = "Image crawler";
$types["10"] = "Geo fixer";
$types["101"] = "Cache reloader";
$types["102"] = "Solr rebuilder";
$types["103"] = "Email sender";
$types["201"] = "Work reaper";
$types["202"] = "Worker restarter";

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





echo "<table>\n";
echo "     <tr>\n";
echo "        <td><b>ID</b></td>\n";
echo "        <td><b>Work</b></td>\n";
echo "        <td><b>Type</b></td>\n";
echo "        <td><b>Company_ ID</b></td>\n";
echo "        <td><b>Frequency</b></td>\n";
echo "        <td><b>Worker IP</b></td>\n";
echo "        <td><b>PID</b></td>\n";
echo "        <td><b>Created</b></td>\n";
echo "        <td><b>Started</b></td>\n";
echo "        <td><b>Completed</b></td>\n";
echo "        <td><b>Status</b></td>\n";
echo "        <td><b>Status Message</b></td>\n";
echo "     </tr>\n";

for ($i=0;$i < mysql_num_rows($result); $i++) {
  $id = mysql_result($result, $i, "id");
  $work = mysql_result($result, $i, "work");
  $type = mysql_result($result, $i, "type");
  $company_id = mysql_result($result, $i, "company_id");
  $frequency = mysql_result($result, $i, "frequency");
  $output_server = mysql_result($result, $i, "output_server");
  $output_database = mysql_result($result, $i, "output_database");
  $worker_ip = mysql_result($result, $i, "worker_ip");
  $worker_pid = mysql_result($result, $i, "worker_pid");
  $created = mysql_result($result, $i, "created");
  $started = mysql_result($result, $i, "started");
  $completed = mysql_result($result, $i, "completed");
  $status = mysql_result($result, $i, "status");
  $status_message = mysql_result($result, $i, "status_message");



  echo "     <tr>\n";
  echo "        <td>$id</td>\n";
  echo "        <td><a href='/tools/work_info.php?work=$work' target=_work_info>$work</a> (<a href='$work' target=_blank>web</a>) (<a href='http://50.57.43.108/tools/deal_info.php?submiturl&deal_url=".urlencode($work)."' target=_deal_info>db</a>)</td>\n";
  echo "        <td><a href='/tools/work_info.php?type=$type' target=_work_info>$types[$type]</a></td>\n";
  echo "        <td><a href='/tools/work_info.php?company_id=$company_id' target=_work_info>$companies[$company_id] ($company_id)</a></td>\n";

  echo "        <td>$frequency</td>\n";
  echo "        <td><a href='/tools/work_info.php?worker_ip=$worker_ip&worker_pid=$worker_pid' target=_work_info>$worker_ip</a></td>\n";
  echo "        <td><a href='/tools/work_info.php?worker_ip=$worker_ip&worker_pid=$worker_pid' target=_work_info>$worker_pid</a></td>\n";

  echo "        <td>$created</td>\n";
  echo "        <td>$started</td>\n";
  echo "        <td>$completed</td>\n";
  echo "        <td>$status</td>\n";
  echo "        <td>$status_message</td>\n";
  echo "     </tr>\n";
}

echo "</table>\n";

echo "$sql<BR>\n";
?>


</body>
</html>
