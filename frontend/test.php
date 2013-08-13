<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

foreach ($_SERVER as $key => $value) {

  echo "$key:$value<BR>\n";
}

exit();

require("db_user.php");

$sql = "SELECT user_id, TIMESTAMPDIFF(SECOND, user_created, UTC_TIMESTAMP()) as age FROM `Users` WHERE NOT(user_created='0000-00-00 00:00:00')";

$weeks= 20;
$signups = array();
for ($i=0; $i < $weeks; $i++) {
  $signups[$i] = 0;
}

//echo "$sql<BR>\n";
$result = mysql_query($sql, $users_con);
if (!$result) {
  die("Invalid query: $sql" . mysql_error());
}

while ($row = @mysql_fetch_assoc($result)) {
  for ($i=0; $i < $weeks; $i++) {
    if ($row['age'] < ($i * 86400 * 7)) {
      $signups[$i] += 1;
    }
  }

  //  if ($row['age'] == 3020399) {
  //  echo $row['user_id']."<BR>\n";
  //}

  //echo $row['age']."<BR>\n";
}

for ($i=$weeks -1; $i >=0; $i--) {
  //echo "Week ".($weeks - $i). ": ".(9680 - $signups[$i])."<BR>\n";
  echo (9680 - $signups[$i]).",<BR>\n";


}




?>
