<html>
<head>

<style>
ol
{
width: 45em;  /* room for 3 columns */
}
ol li
{
  float: left;
width: 15em;  /* accommodate the widest item */
}
/* stop the floating after the list */
br
{
clear: left;
}
/* separate the list from what follows it */
div.wrapper
{
  margin-bottom: 1em;
}

</style>



<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<link rel="stylesheet" href="blueprint/screen.css" type="text/css" media="screen, projection">

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
<SCRIPT language="JavaScript" src="autocomplete.js"></SCRIPT>


<script type="text/javascript">

function checkCategoriesValid() {
   var re = /[0-9]+/;
   if (re.test($("#category_id1").val())) {
     if (($("#category_id2").val() == "" ||
	  re.test($("#category_id2").val())) &&
	 ($("#category_id3").val() == "" ||
	  re.test($("#category_id3").val())) &&
	 ($("#category_id4").val() == "" ||
	  re.test($("#category_id4").val()))) {
       return true;
     }
   }

   $("#set-category-warning").show();
   return false;
   //return ($('input[type=radio]:checked').size() > 0);
 }


$(document).ready(function() {
    $("#category_id1").focus();
    $(document).keyup(function(e) {
	var code = e.keyCode || e.which;
	
	if(code == 13) {
	  
	  if (checkCategoriesValid()) {
	    //$("form#myform1").submit();
	  }
	}
	
	if(code == 27) {
	  if ($('input[name=is_nation]').is(':checked')) {
	    $('input[name=is_nation]').attr('checked', false);
	  } else {
	    $('input[name=is_nation]').attr('checked', true);
	  }
	}
	
        
	
      });  
  });   






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

<?php

// Make a MySQL Connection
$con = mysql_connect("localhost", "crawler", "daewoo");
if (!$con) {
  die('Error: could not connect. ' . mysql_error());
}
mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection                                  

$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

if (!$success) {
  echo "Failed to connect to memcache<BR>\n";
  exit();
}

$username = $_SERVER["PHP_AUTH_USER"];
$classifier_id = getClassifierID($username, $con, $memcache);
if ($classifier_id == 0) {
  echo "ERROR: Couldn't find classifier ID for user: [$username]<BR>\n";
  exit();
}







echo "<script>\n";
$categories = getAllCategories($con);
echo "var categories = new Array(";
for ($i=0; $i< count($categories); $i++) {
  $cid= $i+1;
  echo "'".$categories[$i]["name"]." ($cid)',";
}
$cid++;
echo "'".$categories[count($categories)-1]["name"]." ($cid)'";
echo ");\n";
echo "</script>\n";


?>


</head>

<body>
<h2 align=center>Classifier fixer (fix misclassified deals)</h2>

<form action='/tools/classifier_fixer.php' method=get align=center>
Deal ID: <input type='text' name='deal_id' onkeypress="return validate(event)"  >
<input type=submit value='Find misclassified deal' />


</form>

<?php
// Make a MySQL Connection
//$con = mysql_connect("localhost", "crawler", "daewoo");
//if (!$con) {
//  die('Error: could not connect. ' . mysql_error());
//}
//mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection


if (isset($_GET["deal_id"]) && strlen($_GET["deal_id"]) > 0) {
  $deal_id = $_GET["deal_id"];

  if (isset($_POST["category_id1"]) || isset($_POST["category_id2"]) || isset($_POST["category_id3"]) || isset($_POST["category_id4"])) {
    $cat1 = getCategoryFromString($_POST["category_id1"]);
    $cat2 = getCategoryFromString($_POST["category_id2"]);
    $cat3 = getCategoryFromString($_POST["category_id3"]);
    $cat4 = getCategoryFromString($_POST["category_id4"]);

    if ($cat1 > 0 || $cat2 > 0 || $cat3 > 0 || $cat4 > 0) {
      deleteCategoriesForDeal($deal_id, $con);
    }

    echo "[$cat1][$cat2][$cat3][$cat4]<BR>\n";
    $time = date('Y-m-d H:i:s', time());
    if ($cat1 > 0) {
      insertCategory($deal_id, $cat1, 4, $classifier_id, $time, $con);
    }
    
    if ($cat2 > 0) {
      insertCategory($deal_id, $cat2, 3, $classifier_id, $time, $con);
    }
    
    if ($cat3 > 0) {
      insertCategory($deal_id, $cat3, 2, $classifier_id, $time, $con);
    }
    
    if ($cat4 > 0) {
      insertCategory($deal_id, $cat4, 1, $classifier_id, $time, $con);
    }

    updateDeal($deal_id, $con);
  }



  /*
  foreach ($_POST as $key=>$value) {
    
    echo "$key:$value<BR>\n";
  }
  */
}


$sql = "select id, name from CategoryInfo";
$result = mysql_query($sql, $con);

if (!$result) {
  die('Error: ' . mysql_error());
}


while ($row = @mysql_fetch_assoc($result)) {
  $categories[$row["id"]] = $row["name"];

}



if (isset($_GET["deal_id"]) && strlen($_GET["deal_id"]) > 0) {
  $deal_id=$_GET["deal_id"];
  $sql = "select url, title, subtitle, text from Deals where ".
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



  $sql = "select id, category_id, rank from Categories where ".
    "deal_id=".$deal_id." order by rank desc";

  $result = mysql_query($sql, $con);

  if (!$result) {
    die('Error: ' . mysql_error());
  }
  $curr_cats = array();
  $curr_cats[0] = "";
  $curr_cats[1] = "";
  $curr_cats[2] = "";
  $curr_cats[3] = "";
  $count = 0;
  while ($row = @mysql_fetch_assoc($result)) {
    $curr_cats[$count] = "value=\"".$categories[$row["category_id"]]." (".$row["category_id"].")\"";
    $count++;
  }

  echo "<h2 align=center>Select categories</h2>\n";
  echo "<form id=\"myform1\" name=\"myform1\" action=\"/tools/classifier_fixer.php?deal_id=$deal_id\" method=\"POST\" onsubmit=\"return checkCategoriesValid()\">
";

  echo "<div align=\"center\"><br>
<input type=hidden name=url value=\"".htmlentities($url)."\">\n";

  echo "1. <INPUT id=\"category_id1\" type=\"text\" name=\"category_id1\" autocomplete=\"array:categories\" ".$curr_cats[0]."><BR>\n";
  echo "2. <INPUT id=\"category_id2\" type=\"text\" name=\"category_id2\" autocomplete=\"array:categories\" ".$curr_cats[1]."><BR>\n";
  echo "3. <INPUT id=\"category_id3\" type=\"text\" name=\"category_id3\" autocomplete=\"array:categories\" ".$curr_cats[2]."><BR>\n";
  echo "4. <INPUT id=\"category_id4\" type=\"text\" name=\"category_id4\" autocomplete=\"array:categories\" ".$curr_cats[3]."><BR>\n";
  echo "<BR><input type=\"submit\" value=\"Submit\">\n";


  echo "<div class=\"wrapper\">\n";
  echo "<ol>\n";
  for ($k=1; $k < count($categories); $k++) {
    $category_id = $k;
    $category_name = $categories[$k];
    echo "\t<li>$category_id - $category_name</li>\n";
  }
  echo "</ol>\n";
  echo "</div>\n";
  echo "</div>\n";
  echo "</form>\n";
  echo "<BR><BR><BR>\n";
}


function getCategoryFromString($str) {
  if (!isset($str)) { return 0; }
  if (preg_match("/([0-9]+)/", $str, $m)) {
    //echo $m[0]."<BR>\n";
    return $m[0];
  } else {
    //echo "NO MATCH<BR>\n";
    return 0;
  }
}


function getAllCategories($con) {
  $sql =
    "select id, name, description from CategoryInfo";

  $result = mysql_query($sql, $con);
        
  if (!$result) {
    die('Error: ' . mysql_error());
  }

  $categories = array();
  while ($row = @mysql_fetch_assoc($result)) {
    array_push($categories, $row);
  }
  return $categories;
}



function deleteCategoriesForDeal($deal_id, $con) {
  $delete_sql = "delete from Categories where deal_id=$deal_id";
  $result = mysql_query($delete_sql, $con);
        
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "[$delete_sql]<BR>\n";
}

function insertCategory($deal_id, $category_id, $rank, $classifier_id, $time, $con) {
  $category_sql =
    "INSERT into Categories (deal_id, category_id,rank,classifier_id,time) values ('".
    mysql_real_escape_string($deal_id)."',  ".
    $category_id.", $rank,$classifier_id, '$time') ON DUPLICATE KEY UPDATE id=id";
  $result = mysql_query($category_sql, $con);
        
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "[$category_sql]<BR>\n";
}

function updateDeal($deal_id, $con) {
 $update_sql =
    "UPDATE Deals set last_updated=UTC_TIMESTAMP() where id=$deal_id";

  $result = mysql_query($update_sql, $con);
        
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "[$update_sql]<BR>\n";
}


function getClassifierID($username, $con, $memcache) {
  $classifier_id = $memcache->get("username:".$username);
  if (!isset($classifier_id) || $classifier_id == "") {
    $sql = "select id from Classifiers where username='$username'";
    $result = mysql_query($sql, $con);
    if ($row = @mysql_fetch_assoc($result)) {
      $classifier_id = $row['id'];
      $memcache->set("username:".$username, $classifier_id, false, 86400);
    } else {
      $classifier_id = 0; // Failed to get an ID                                                                                                               
    }

    echo "Got classifer id from database, putting in memcache: $classifier_id<BR>\n";
  } else {
    echo "Got classifier id from memcache : $classifier_id<BR>\n";
  }

  return $classifier_id;
}



?>

</body>
</html>