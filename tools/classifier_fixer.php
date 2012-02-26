<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<link rel="stylesheet" href="blueprint/screen.css" type="text/css" media="screen, projection">

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
<h2 align=center>Classifier fixer (fix misclassified deals)</h2>

<form action='/tools/classifier_fixer.php' method=get align=center>
Deal ID: <input type='text' name='deal_id' onkeypress="return validate(event)"  >
<input type=submit value='Find misclassified deal' />


</form>

<?php
// Make a MySQL Connection
$con = mysql_connect("localhost", "crawler", "daewoo");
if (!$con) {
  die('Error: could not connect. ' . mysql_error());
}
mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection



foreach ($_POST as $key=>$value) {
  if (preg_match("/^fix/", $key)) {
    $array = split("_", $key);

    if (count($array)==3) {
      $id = $array[1];
      $deal_id=$array[2];
      $category_id = $value;


      $sql = "delete from Categories777 where id=$id";
      
      echo "$sql<BR>\n";
      
      $result = mysql_query($sql, $con);
      
      if (!$result) {
	die('Error: ' . mysql_error());
      }
      

      $sql = "insert into Categories777 (deal_id, category_id, rank) value ($deal_id, $category_id, 3) on duplicate key update id=id";
      
      echo "$sql<BR>\n";
      
      $result = mysql_query($sql, $con);
      
      if (!$result) {
	die('Error: ' . mysql_error());
      }

      $sql = "update Deals777 set last_updated=UTC_TIMESTAMP() where id=$deal_id";
      echo "$sql<BR>\n";
      
      $result = mysql_query($sql, $con);
      
      if (!$result) {
	die('Error: ' . mysql_error());
      }




    }

  }
}


$categories["1"] = "Food & Drink";
$categories["2"] = "Activities & Events";
$categories["3"] = "Spa & Beauty";
$categories["4"] = "Kids & Parents";
$categories["5"] = "Shopping & Services";
$categories["6"] = "Classes & Learning";
$categories["7"] = "Fitness & Health";
$categories["8"] = "Medical & Dental";
$categories["9"] = "Hotels & Vacations";


if (isset($_GET["deal_id"]) && strlen($_GET["deal_id"]) > 0) {
  $deal_id=$_GET["deal_id"];
  $sql = "select url, title, subtitle, text from Deals777 where ".
    "id=".$deal_id;
  
  $result = mysql_query($sql, $con);
    
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  if ($row = @mysql_fetch_assoc($result)) {
    $url = $row['url'];
    $title = $row['title'];
    $subtitle = $row['subtitle'];
    $text = $row['text'];
    $text = preg_replace("/<script[^>]+>/", "", $text);

    echo "<table>\n";
    echo "  <tr><td><b>ID:</b></td><td>$deal_id</td></tr>\n";
    echo "  <tr><td><b>URL:</b></td><td><a href='http://50.57.43.108/tools/deal_info.php?deal_url=$url' target=_work_info>$url</a> (<a href='$url' target=_blank>web</a>)</td></tr>\n";
    echo "  <tr><td><b>Title:</b></td><td>$title</td></tr>\n";
    echo "  <tr><td><b>Subtitle:</b></td><td>$subtitle</td></tr>\n";
    echo "  <tr><td><b>Text:</b></td><td>$text</td></tr>\n";

    echo "</table>\n";
  }



  $sql = "select id, deal_id, category_id, rank from Categories777 where ".
    "deal_id=".$deal_id;
  
  $result = mysql_query($sql, $con);
    
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "<hr /><BR>\n";
  echo "<form action='/tools/classifier_fixer.php?deal_id=$deal_id' method=post align=center>";
  echo "<table align=center>\n";
  echo "  <tr><td><b>ID:</b></td><td>Deal ID</td><td>Category ID</td><td>Rank</td></tr>\n";

  while ($row = @mysql_fetch_assoc($result)) {
    echo "\t<tr><td><b>".$row['id']."</b></td><td>".$row["deal_id"]."</td><td>";
    echo "<select name='fix_".$row['id']."_$deal_id'>\n";
    for ($i=1; $i <= count($categories); $i++) {
      if ($row["category_id"] == $i) {
	$selected = "selected='yes'";

      } else {
	$selected = "";
      }
      echo "\t\t<option value=".$i." $selected>".
	$categories[$i]."</option>\n";
    }
    echo "</select></td><td>".$row["rank"]."</td>";
  }

  echo "</table>\n";
  echo "<input type=submit value='Fix' />";
  echo "</form>\n";

}




?>

</body>
</html>