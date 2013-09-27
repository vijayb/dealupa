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
<h2 align=center>Image fixer</h2>

<form action='/tools/image_fixer.php' method=get align=center>
Deal ID: <input type='text' name='deal_id' onkeypress="return validate(event)"  >
<input type=submit value='Show images for this deal' />


</form>
<p><p>
<?php
// Make a MySQL Connection
$con = mysql_connect("localhost", "crawler", "daewoo");
if (!$con) {
  die('Error 1: could not connect. ' . mysql_error());
}
mysql_select_db("Deals", $con) or die(mysql_error());


$wq_con = mysql_connect("50.57.136.167", "crawler", "daewoo");
if (!$wq_con) {
  die('Error 2: could not connect. ' . mysql_error());
}
mysql_select_db("WorkQueue", $wq_con) or die(mysql_error());





// MySQL connection


/*
foreach ($_POST as $key=>$value) {
  echo "$key:$value<BR>\n";
}
*/

echo "<p>\n";
// Handle image addition
if (isset($_POST["add_image"])) {
  $update_deal_id = $_POST["deal_id"];
  if (isset($_POST["image_url"]) && strlen($_POST["image_url"]) > 10) {
    addImage($update_deal_id, $_POST["image_url"], $con);
    resetImageWork($update_deal_id, $con, $wq_con);
    echo "<font color='green'>Successfully added image</font><BR>\n";
  } else {
    echo "<font color='red'>Invalid image url, not long enough, so not adding it</font><BR>\n";
  }
}

// Handle image deletion
if (isset($_POST["delete_image"]) && isset($_POST["id"]) && isset($_GET["deal_id"])) {
  removeImage($_GET["deal_id"], $_POST["id"], $con);
  echo "<font color='green'>Successfully deleted image ".$_POST["id"]."</font><BR>\n";
}



$image_ids = array();

if (isset($_GET["deal_id"]) && strlen($_GET["deal_id"]) > 0) {
  $deal_id=$_GET["deal_id"];
  
  $result = doQuery("select id from Images where deal_id=$deal_id", $con);

  while ($row = @mysql_fetch_assoc($result)) {
    array_push($image_ids, $row["id"]);
  }
 
  $result = doQuery("select url from Deals where id=$deal_id", $con);
  if ($row = @mysql_fetch_assoc($result)) {
    $deal_url = $row['url'];
  }
} else {
  $result = doQuery("select Deals.id as deal_id,url,TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), discovered))/3600 as age from Deals left join Images on Images.deal_id=Deals.id where Images.id is null and TIME_TO_SEC(TIMEDIFF(last_updated, discovered))>1 order by age", $con);
  
  $num_orphaned = mysql_num_rows($result);

  echo "<h3 align=center>There are $num_orphaned deals without images</h3>\n";

  echo "<table>\n";
  echo "\t<form action='/tools/image_fixer.php' method=get>\n";
  echo "\t<tr><td>Deal ID</td><td>URL</td><td>Age (hours)</td><td>Add images to this deal</td></tr>\n";
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

if (isset($_GET["deal_id"]) && count($image_ids) > 0) {
  echo "<h4 align=center>Image information for deal <a href='http://50.57.43.108/tools/deal_info.php?deal_url=$deal_id&submitid=search+by+id' target=_panel>$deal_id</a>. (<a href='$deal_url' target=_blank>$deal_url</a>)</h4><BR><BR>\n";

  echo "<table>\n";
  echo 
    "\t<tr>\n".
    "<td>ID</td>".
    "<td>Image URL</td>".
    "<td>Thumbnail</td>".
    "<td></td>".
    "</tr>\n";
  for ($i=0; $i < count($image_ids); $i++) {
    $id = $image_ids[$i];
    $result = doQuery("select image_url from Images where id=$id", $con);
   
    while ($row = @mysql_fetch_assoc($result)) {
      $image_url = $row['image_url'];

      echo "\t<form name=form_$id action='/tools/image_fixer.php?deal_id=$deal_id' method=post>\n";
      
      echo "\t\t<input type='hidden' name='id' value=$id />";
      echo "\t<tr>\n";
      echo "\t\t<td>$id</td>\n";
      echo "\t\t<td style='width:200px'><input type='text' name=image_url value='$image_url' size=50 /></td>\n";
      echo "\t\t<td><img src='$image_url' height=100px></td>\n";
      echo "\t\t<td><input type='submit' name=delete_image value='delete' onclick=\"javascript:return confirm('Are you REALLY sure you want to delete the image?')\" /></td>\n";

      echo "\t</tr>\n";
      echo "\t</form>\n";
    }

  }

  echo "</table>\n";
}


if (isset($_GET["deal_id"]) && strlen($_GET["deal_id"]) > 0) {
   echo "<form action='/tools/image_fixer.php?deal_id=$deal_id' method=post >\n";
   echo "<input type='hidden' name='deal_id' value='$deal_id' />\n";
   echo "<table style='width:50%'><tr>\n";
   echo "\t\t<td style='width:200px'><input type='text' name=image_url size=70 /></td>\n";
   echo "\t\t<td><input type='submit' name=add_image value='Add image to deal $deal_id' /></td>\n";
   echo "</tr></table>\n";
   echo "</form>\n";

   echo "<BR><BR><BR><h3 align=center><a href='http://50.57.43.108/tools/image_fixer.php'>Get more image work</a></h3>\n";
}



function addImage($deal_id, $image_url, $con) {
  $sql = "insert into Images (deal_id, image_url) values ".
    "($deal_id, '$image_url')";
  echo $sql."<BR>\n";
  doQuery($sql, $con);
  updateDeal($deal_id, $con);

}

function resetImageWork($deal_id, $deals_con, $wq_con) {
  $sql = "select url,company_id from Deals where id=$deal_id limit 1";
  $result = doQuery($sql, $deals_con);
  if ($row = @mysql_fetch_assoc($result)) {
    $url = $row['url'];
    $company_id = $row['company_id'];

    $sql = "insert into WorkQueue (work, type, company_id, frequency, output_server, output_database,created) values ('".mysql_real_escape_string($url)."', 9, $company_id, 0, '50.57.43.108', 'Deals', UTC_TIMESTAMP()) on duplicate key update id=id";
    doQuery($sql, $wq_con);
    echo "[$sql]<BR>\n";

    $sql = "update WorkQueue set started=null, completed=null, status=null, status_message=null where type=9 and completed is not null ".
      "and strcmp(work, '".mysql_real_escape_string($url)."')=0";
    doQuery($sql, $wq_con);
    echo "[$sql]<BR>\n";
  } else {
    echo "Failed getting URL for id\n";
    exit;
  }

}


function removeImage($deal_id, $image_id, $con) {
  $sql = "delete from Images where id=$image_id limit 1";
  echo $sql."<BR>\n";
  doQuery($sql, $con);
  updateDeal($deal_id, $con);
}


function updateDeal($deal_id, $con) {
  $sql = "update Deals set last_updated=UTC_TIMESTAMP() where id=$deal_id";
  echo $sql."<BR>\n";
  doQuery($sql, $con);
}

function doQuery($query, $con) {
  $result = mysql_query($query, $con);
    
  if (!$result) {
    die('Error 3 [$query]: ' . mysql_error());
  }
  return $result;

}

?>

</body>
</html>