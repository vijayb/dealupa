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

<script type="text/javascript">


$(document).ready(function() {
    $(document).keyup(function(e) {
	var code = e.keyCode || e.which;
	
	if(code == 13) {
	  
	  if (checkCategoriesValid()) {
	    //$("form#myform1").submit();
	  }
	}
	
	if(code == 52) {
	  $('#correct4').attr('checked', true);
	  $("form#myform1").submit();
	}

	if(code == 51) {
	  $('#correct3').attr('checked', true);
	  $("form#myform1").submit();
	}

	if(code == 50) {
	  $('#correct2').attr('checked', true);
	  $("form#myform1").submit();
	}

	if(code == 49) {
	  $('#correct1').attr('checked', true);
	  $("form#myform1").submit();
	}
	
      });  
  });   



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
if ($classifier_id != 2 && $classifier_id != 3) {
  exit();
}

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
<h2 align=center>Grade classifications for correctnesss</h2>

<form action='/tools/grade_classifications.php' method=get align=center>
Deal ID: <input type='text' name='deal_id' onkeypress="return validate(event)"  >
<input type=submit value='Find deal to grade' />


</form>

<?php
// Make a MySQL Connection
//$con = mysql_connect("localhost", "crawler", "daewoo");
//if (!$con) {
//  die('Error: could not connect. ' . mysql_error());
//}
//mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection


  foreach ($_POST as $key=>$value) {

    
    echo "$key:$value<BR>\n";
  }

if (isset($_POST["deal_id"]) && isset($_POST["correct"])) {
  $sql = "update Categories set score=".$_POST["correct"]." where deal_id=".$_POST["deal_id"]." limit 4";

    $result = mysql_query($sql, $con);
  if (!$result) {
    die('Error: ' . mysql_error());
  }

  echo "<span style='color:green'>$sql</span><BR>\n";
}



$sql = "select id, name from CategoryInfo";
$result = mysql_query($sql, $con);

if (!$result) {
  die('Error: ' . mysql_error());
}


while ($row = @mysql_fetch_assoc($result)) {
  $categories[$row["id"]] = $row["name"];

}

if (isset($_GET["deal_id"])) {
  $deal_id = $_GET["deal_id"];
} else {
  if (isset($_GET["classifier_id"]) && isset($_GET["score"])) {
    $deal_id=getDealToGrade($_GET["classifier_id"], $_GET["score"], $con);
  } else {
    $deal_id=getDealToGrade(0, 0, $con);
  }

}
echo "Got deal : [$deal_id]<BR>\n";



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
  $curr_cats[$count] = $categories[$row["category_id"]]." (".$row["category_id"].")";
  $count++;
}

echo "<form id=\"myform1\" name=\"myform1\" action=\"/tools/grade_classifications.php\" method=\"POST\">
";

echo "<div align=\"center\">

<table><tr>

<td>\n";
echo "<input type=hidden name=deal_id value=\"$deal_id\">\n";

echo "1. <b>$curr_cats[0]</b><BR>\n";
echo "2. <b>$curr_cats[1]</b><BR>\n";
echo "3. <b>$curr_cats[2]</b><BR>\n";
echo "4. <b>$curr_cats[3]</b><BR>\n";
echo "<td>
<div style=\"text-align:left;margin:0 auto;width: 200px;\">
<INPUT type=\"radio\"  id=correct4 name=correct value=4>Exactly correct<BR>\n
<INPUT type=\"radio\"  id=correct3 name=correct value=3>Passably correct<BR>\n
<INPUT type=\"radio\"  id=correct2 name=correct value=2>Questionable<BR>\n
<INPUT type=\"radio\"  id=correct1 name=correct value=1>Plain wrong<BR>\n
</div>
</td>";
echo "<td>";
echo "<BR><input type=\"submit\" value=\"Submit\"></td><td>\n";
echo "</td>";
echo "</tr></table>\n";



$sql = "SELECT image_url, on_s3 FROM Images where deal_id=$deal_id limit 1";

$result = mysql_query($sql);
if (!$result) {
  die('Error image query: ' .mysql_error());
}

$image_url = "";
if ($row = @mysql_fetch_assoc($result)) {
  $image_url = $row["image_url"];
}


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
  
  echo "<table><tr><td>";
  echo "<table>\n";
  echo "  <tr><td><b>ID:</b></td><td>$deal_id</td></tr>\n";
  echo "  <tr><td><b>URL:</b></td><td><a href='http://50.57.43.108/tools/deal_info.php?deal_url=$url' target=_work_info>$url</a> (<a href='$url' target=_blank>web</a>)</td></tr>\n";
  echo "  <tr><td><b>Title:</b></td><td><span style='font-size:26px'>$title</span></td></tr>\n";
  echo "  <tr><td><b>Subtitle:</b></td><td>$subtitle</td></tr>\n";
  echo "  <tr><td><b>Text:</b></td><td>$text</td></tr>\n";
  
  echo "</table>\n";
  echo "</td><td style='vertical-align:top'><img src=\"http://dealupa_images.s3.amazonaws.com/".sha1($image_url)."_small\" ></td></tr></table>\n";
}




echo "</div>\n";
echo "</form>\n";
echo "<BR><BR><BR>\n";


if (isset($_GET["performance"])) {
  $sql = "select deal_id, username, classifier_id, score from Categories left join Classifiers on Classifiers.id=Categories.classifier_id where score is not null and classifier_id > 3 and time > NOW() - INTERVAL 1 WEEK";
  $result = mysql_query($sql, $con);

  $total = 0;
  while ($row = @mysql_fetch_assoc($result)) {
    $classifier_id = $row["classifier_id"];
    $score = $row["score"];
    $classifiers[$classifier_id] = $row["username"];


    if (!isset($seen_deals[$row["deal_id"]])) {
	$seen_deals[$row["deal_id"]] = 1;
	
	if (!isset($distribution[$classifier_id][$score])) {
	  $distribution[$classifier_id][$score] =1;
	} else{
	  $distribution[$classifier_id][$score] += 1;
	}
	

	if (!isset($scores[$classifier_id])) {
	  $scores[$classifier_id] = $score;
	  $totals[$classifier_id] = 1;
	} else {
	  $scores[$classifier_id] += $score;
	  $totals[$classifier_id] += 1;
	  $total++;
	}
      }
  }
      
  foreach ($scores as $classifier_id => $total_score) {
    echo "<span style='font-size:20px'><b>".$classifiers[$classifier_id] ."</b></span>:<b>".$total_score/$totals[$classifier_id]."</b><BR>\n";

    krsort($distribution[$classifier_id]);
    foreach ($distribution[$classifier_id] as $score => $count) {
      echo "&nbsp;&nbsp;&nbsp;<b>$score</b>:$count (<b>".number_format((100*$count/$totals[$classifier_id]),2, '.','')."%</b>)<BR>\n";
    }
  }

  echo "<BR>Total deals graded: $total<BR>\n";


}


function getDealToGrade($classifier_id, $score, $con) {
  if ($classifier_id > 0) {
    $sql = "select deal_id from Categories where score <= $score and classifier_id=$classifier_id and time > NOW() - INTERVAL 1 WEEK order by rand() limit 1";
  } else {
    $sql = "select deal_id from Categories where score is null and classifier_id > 3 and time > NOW() - INTERVAL 1 WEEK order by rand() limit 1";
  }
  $result = mysql_query($sql, $con);
        
  if (!$result) {
    die('Error: ' . mysql_error());
  }

  if ($row = @mysql_fetch_assoc($result)) {
    return $row["deal_id"];
  } else {
    echo "Couldn't find any deals to grade!<BR>\n";
    exit();
  }
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

    //echo "Got classifer id from database, putting in memcache: $classifier_id<BR>\n";
  } else {
    //echo "Got classifier id from memcache : $classifier_id<BR>\n";
  }

  return $classifier_id;
}



?>

</body>
</html>