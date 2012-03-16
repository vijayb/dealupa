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


$operation_sql = "";
$where_clause = "";

$operation_sql = "";
if (isset($_POST["force_shutdown"])) {
  $operation = "force_shutdown";
  $operation_sql = "UPDATE Workers set force_shutdown=1 WHERE ";
} else if (isset($_POST["clear_shutdown"])) {
  $operation = "clear_shutdown";
  $operation_sql ="DELETE FROM Workers WHERE ";
} else if (isset($_POST["clear_zombies"])) {
  $operation = "clear_zombies";
  $operation_sql ="DELETE FROM Workers WHERE ";
}

foreach ($_POST as $key => $value) {
  //echo "$key ** $value\n";

  $info = preg_split("/:/", $key);
  if (count($info)==3) {
    if (strcmp($info[0], $operation)==0) {
      $ip = str_replace("_", ".", $info[1]);
      $pid = $info[2];
      if (strcmp($where_clause, "")==0) {
	$where_clause = "(strcmp(ip, '$ip')=0 and pid=$pid)";
      } else {
	$where_clause =
	  $where_clause." or (strcmp(ip, '$ip')=0 and pid='$pid')";
      }
    }
  }
}

$operation_sql = $operation_sql.$where_clause;
if (strcmp($where_clause, "")!= 0) {
  echo "$operation_sql<BR>\n";

  $result = mysql_query($operation_sql);
  if (!$result) {
    die('Error: ' .mysql_error());
  }
}

$sql = "SELECT ip, pid, type, status, latest_work_id, force_shutdown, TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), heartbeat)), TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), spawned)) FROM Workers order by type";

$result = mysql_query($sql);
if (!$result) {
   die('Error: ' .mysql_error());
}

//echo "$sql\n";
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

$machines["108.166.127.22"] = "crawler1";
$machines["184.106.175.144"] = "crawler2";
$machines["184.106.174.162"] = "crawler3";
$machines["50.57.36.164"] = "crawler4";


echo "<h2 align=center>Worker Status</h2>\n";
echo "<h4 align=center>".mysql_num_rows($result)." jobs</h3>\n";
echo "<p>\n";
echo "<form action='/tools/worker_status.php' method=post>";
echo "<table>\n";
echo "     <tr>\n";
echo "        <td><b>IP</b></td>\n";
echo "        <td><b>PID</b></td>\n";
echo "        <td><b>Status</b></td>\n";
echo "        <td><b>TYPE</b></td>\n";
echo "        <td><b>Latest work ID</b></td>\n";
echo "        <td><b>Uptime (hours)</b></td>\n";
echo "        <td><b>Time since last heartbeat</b></td>\n";
echo "        <td><b>Force shutdown of process</b></td>\n";
echo "     </tr>\n";

for ($i=0;$i < mysql_num_rows($result); $i++) {
    $ip = mysql_result($result, $i, "ip");
    $pid = mysql_result($result, $i, "pid");
    $type = mysql_result($result, $i, "type");
    $status = mysql_result($result, $i, "status");
    $force_shutdown = mysql_result($result, $i, "force_shutdown");


    $latest_work_id = mysql_result($result, $i, "latest_work_id");
    $last_heartbeat = mysql_result($result, $i, "TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), heartbeat))");
    $uptime = mysql_result($result, $i, "TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), spawned))");
    $uptime = round($uptime / 3600,3);


    echo "     <tr>\n";
    echo "        <td>$machines[$ip] ($ip)</td>\n";
    echo "        <td>$pid</td>\n";
    if ($status) {
      echo "        <td><span style='color:green'>running</span></td>\n";
    } else {
      echo "        <td><span style='color:red'>shutdown</span><input type='hidden' name='clear_shutdown:$ip:$pid'  /></td>\n";
    }
    echo "        <td>$types[$type] ($type)</td>\n";
    echo "        <td><a href='/tools/work_info.php?id=$latest_work_id' target=_work_info>$latest_work_id</a></td>\n";
    echo "        <td>$uptime</td>\n";
    if ($last_heartbeat >1000) {
      $color = "red";
    } else {
      $color = "green";
    }
    echo "        <td><span style='color:$color'>$last_heartbeat</span></td>\n";
    if ($force_shutdown) {
      echo "        <td><span style='color:red'>Shutdown requested</span><input type='hidden' name='clear_zombies:$ip:$pid'  /></td>\n";
    } else {
      echo "        <td><input type='checkbox' name='force_shutdown:$ip:$pid'  /></td>\n";
    }
    echo "     </tr>\n";
}

echo "     <tr>\n";
echo "        <td> </td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td><input type=submit name='force_shutdown' value='Shutdown selected jobs' /></td>\n";
echo "     </tr>\n";

echo "     <tr>\n";
echo "        <td> </td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td><input type=submit name='clear_shutdown' value='Clear shutdown jobs' /></td>\n";
echo "     </tr>\n";

echo "     <tr>\n";
echo "        <td> </td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td></td>\n";
echo "        <td><input type=submit name='clear_zombies' value='Clear zombie jobs' onclick=\"javascript:return confirm('Are you sure? Only do this if you have already requested a shutdown and believe you have zombie jobs')\" /></td>\n";
echo "     </tr>\n";

echo "</table>\n";
echo "</form>\n";
?>


</body>
</html>
