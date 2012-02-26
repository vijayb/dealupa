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
<h2 align=center>Nation tool (set whether a deal is national or not)</h2>


<?php


echo "<form action='/tools/nation_tool.php' method=post align=center>\n";

$deal_id_value = "";
if (isset($_GET['deal_id'])) {
  $deal_id_value="value='".$_GET['deal_id']."'";
}
echo "Deal ID: <input type='text' name='deal_id' $deal_id_value onkeypress='return validate(event)' />\n";
echo "<input name='set_nation' type=submit value='Mark deal as national' />\n";
echo "<input name='unset_nation' type=submit value='Remove national' />\n";

echo "</form>\n";


// Make a MySQL Connection
$con = mysql_connect("localhost", "crawler", "daewoo");
if (!$con) {
  die('Error: could not connect. ' . mysql_error());
}
mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection

if (isset($_POST["deal_id"]) && strlen($_POST["deal_id"]) > 0) {
  if (isset($_POST["set_nation"])) {
    $sql = "insert into Cities777 (deal_id, city_id) values (".
      $_POST["deal_id"].", 2) on duplicate key update id=id";
  } else if (isset($_POST["unset_nation"])) {
    $sql = "DELETE from Cities777 where deal_id=".$_POST["deal_id"]." and ".
      "city_id=2";
  }

  if (isset($sql) && strlen($sql) > 0) {
    echo "<BR><p>$sql<BR>\n";
    $result = mysql_query($sql, $con);
    
    if (!$result) {
      die('Error: ' . mysql_error());
    }

    $sql = "update Deals777 set last_updated=UTC_TIMESTAMP() where id=".
      $_POST["deal_id"];
    echo "$sql\n";
    $result = mysql_query($sql, $con);
	
    if (!$result) {
      die('Error: ' . mysql_error());
    }

    echo "<p><h2><b>Successfully updated</b><h2><BR>\n";
  }
}




?>

</body>
</html>