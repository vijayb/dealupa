<html>

<head>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>

<script>

function atLeastOneRadio() {
    return ($('input[type=radio]:checked').size() > 0);
}


var instant = 0;

$(document).ready(function() {
    $(document).keyup(function(e) {
	var code = e.keyCode || e.which;
	if(code == 13) {
	  if (atLeastOneRadio()) {
	    $("form#myform1").submit();
	  }
	}
	if(code == 70) {
		$('input[name=category_id1]:eq(0)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}

	if(code == 65) {
		$('input[name=category_id1]:eq(1)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}

	if(code == 83) {
		$('input[name=category_id1]:eq(2)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}
	if(code == 75) {
		$('input[name=category_id1]:eq(3)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}
	if(code == 82) {
		$('input[name=category_id1]:eq(4)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}
	if(code == 67) {
		$('input[name=category_id1]:eq(5)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}
	if(code == 72) {
		$('input[name=category_id1]:eq(6)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}
	if(code == 77) {
		$('input[name=category_id1]:eq(7)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}
	if(code == 86) {
		$('input[name=category_id1]:eq(8)').attr('checked', 'checked');
		if (instant) { $("form#myform1").submit(); }
	}


    });  
  });	
</script>




</head>



<body>


<?php

set_time_limit(0);
$cache_life = 3600;

// Make a MySQL Connection
$con = mysql_connect("localhost", "crawler", "daewoo");
if (!$con) {
  die('Error: could not connect. ' . mysql_error());
}
mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection


$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);
$categories_index = $memcache->get("categories_index");

if ($success && $categories_index != false && !isset($_GET["reload"])) {
  //echo "$categories_index <BR>\n";
  $indexes = preg_split("/\n/", $categories_index);
  echo count($indexes), " deals in cache <BR>\n";
  $num_classified = 0;
  $unclassified = array();
  for ($i=0; $i< count($indexes); $i++) {
    $row = $memcache->get($indexes[$i]);
    
    if (isset($_POST["url"]) && (isset($_POST["category_id1"]) || isset($_POST["category_id2"]))) {
      // Look for the URL of the article that was classified and comes to us in a POST.
      // When we find it, set its categories appropriately, and put it back in the cache
      if (strcmp($_POST["url"], $row["url"]) == 0) {
	if (isset($_POST["category_id1"])) {
	  $row["category_id1"] = $_POST["category_id1"];
	}
	if (isset($_POST["category_id2"])) {
	  $row["category_id2"] = $_POST["category_id2"];
	}
	echo "Setting category for <b>[".$row["url"]."]</b> to : ".$_POST["category_id1"]."<BR>\n";
	$memcache->set($indexes[$i], $row, false, $cache_life);
      }
      // Add title matching in the if below for simple duplicate detection.
      // Deals, with the same title and company_id are assumed to be dups
      // so we classify them as the same as the article that was classified
      else if (isset($_POST["company_id"]) &&
	  $row["company_id"] == $_POST["company_id"] &&
	  isset($_POST["title"]) &&
	  strlen($_POST["title"]) > 3 &&
	  isset($row["title"]) &&
	  strlen($row["title"]) > 3 &&
	  strcmp($_POST["title"], $row["title"]) == 0) {
	if (isset($_POST["category_id1"])) {
	  $row["category_id1"] = $_POST["category_id1"];
	}
	if (isset($_POST["category_id2"])) {
	  $row["category_id2"] = $_POST["category_id2"];
	}
	echo "Setting category for DUP [".$row["url"]."] to : ".$_POST["category_id1"]."<BR>\n";	
	$memcache->set($indexes[$i], $row, false, $cache_life);
      }
    }
    
    if (isset($row['category_id1']) || isset($row['category_id2'])) {
      $num_classified++;
      
      if (isset($_GET["submit"]) && isset($row['category_id1'])) {
	$category_sql = "INSERT into DealCategories (deal_url, category_id,rank) values ('".
	  mysql_real_escape_string($row["url"])."',  ".$row["category_id1"].", 3) ".
	  "ON DUPLICATE KEY UPDATE deal_url=deal_url";
	$result = mysql_query($category_sql, $con);
	
	if (!$result) {
	  die('Error: ' . mysql_error());
	}
	echo "[$category_sql]<BR>\n";
      }
      if (isset($_GET["submit"]) && isset($row['category_id2'])) {
	$category_sql = "INSERT into DealCategories (deal_url, category_id,rank) values ('".
	  mysql_real_escape_string($row["url"])."',  ".$row["category_id2"].", 2) ".
	  "ON DUPLICATE KEY UPDATE deal_url=deal_url";
	$result = mysql_query($category_sql, $con);
	
	if (!$result) {
	  die('Error: ' . mysql_error());
	}
	echo "[$category_sql]<BR>\n";
      }			
    } else {
      $unclassified[count($unclassified)] = $i;		
    }
  }
  
  
  if (isset($_GET["submit"])) {
    echo "<BR><b>Submission complete</b> <a href=\"/tools/classifier_tool.php?reload\">Click here to reload more work</a><BR>\n";
    exit;
  }
  
  if (count($unclassified) ==0) {
    echo "<BR>No more deals left to classify, <a href=\"/tools/classifier_tool.php?submit\"><b>time to submit them!</b></a><BR>\n";
    exit;
  }
  
  // for ($j=0; $j < count($unclassified); $j++) {
  // echo "Not classified index: ".$unclassified[$j]."<BR>\n";
  
  // }
  echo "Of which ",$num_classified," are classified<BR>\n";
  
  $chosen_deal = $unclassified[rand(0, count($unclassified)-1)];
  $row = $memcache->get($indexes[$chosen_deal]);
  $url = $row['url'];
  $title = $row['title'];
  $company_id = $row['company_id'];
  $image_url = $row['image_url'];
  $subtitle = $row['subtitle'];
  $yelp_categories = $row['yelp_categories'];
  $text = $row['text'];
  
  echo "<BR>\n";
  echo "<form id=\"myform1\" name=\"myform1\" action=\"/tools/classifier_tool.php\" method=\"POST\">";
  echo "<input type=hidden name=title value='".htmlentities($title)."'>\n";
  echo "<input type=hidden name=company_id value='".$company_id."'>\n";
  echo "<table><tr>\n";
  echo "<td width='700px'>\n";
  echo "<b>URL: </b> <a href='$url' target=blank>".htmlentities($url)."</a><BR>\n";
  echo "<b>Title: </b> $title<BR>\n";
  echo "<b>Subtitle: </b> $subtitle<BR>\n";
  echo "<b>Yelp categories: </b> $yelp_categories<BR>\n";
  echo "<img src='$image_url'><br>\n";
  $text = preg_replace("/<script[^>]+>/", "", $text);
  echo "<b>Text: </b><BR>\n\n<blockquote>$text\n\n</blockquote>\n";
  echo "</td>\n";
  echo "<td width='10%'></td>\n";
  
  echo "<td valign=top>\n";
  echo "Category 1<BR>\n";
  
  echo "<div align=\"left\"><br>
<input type=hidden name=url value='".htmlentities($url)."'>
<input type=\"radio\" id=\"category_id1-1\" name=\"category_id1\" value=\"1\">Food & Drink<br>
<input type=\"radio\" id=\"category_id1-2\" name=\"category_id1\" value=\"2\">Activities & Events<br>
<input type=\"radio\" id=\"category_id1-3\" name=\"category_id1\" value=\"3\">Spa & Beauty<br>
<input type=\"radio\" id=\"category_id1-4\" name=\"category_id1\" value=\"4\">Kids & Parents<br>
<input type=\"radio\" id=\"category_id1-5\" name=\"category_id1\" value=\"5\">Retail & Services<br>
<input type=\"radio\" id=\"category_id1-6\" name=\"category_id1\" value=\"6\">Classes & Learning<br>
<input type=\"radio\" id=\"category_id1-7\" name=\"category_id1\" value=\"7\">Health & Fitness<br>
<input type=\"radio\" id=\"category_id1-8\" name=\"category_id1\" value=\"8\">Medical & Dental<br>
<input type=\"radio\" id=\"category_id1-9\" name=\"category_id1\" value=\"9\">Vacations & Hotels<br>

</div>\n";
  echo "</td>\n";
  
  echo "<td valign=top>\n";
  echo "Category 2<BR>\n";
  
  echo "<div align=\"left\"><br>
<input type=\"radio\" name=\"category_id2\" value=\"1\">Food & Drink<br>
<input type=\"radio\" name=\"category_id2\" value=\"2\">Activities & Events<br>
<input type=\"radio\" name=\"category_id2\" value=\"3\">Spa & Beauty<br>
<input type=\"radio\" name=\"category_id2\" value=\"4\">Kids & Parents<br>
<input type=\"radio\" name=\"category_id2\" value=\"5\">Retail & Services<br>
<input type=\"radio\" name=\"category_id2\" value=\"6\">Classes & Learning<br>
<input type=\"radio\" name=\"category_id2\" value=\"7\">Health & Fitness<br>
<input type=\"radio\" name=\"category_id2\" value=\"8\">Medical & Dental<br>
<input type=\"radio\" name=\"category_id2\" value=\"9\">Vacations & Hotels<br>
<input type=\"submit\" value=\"Submit\">
</div>
</form>\n";
  echo "</td>\n";
  echo "</tr></table>\n";


} else {
  $sql="SELECT url,company_id,title,subtitle,text,yelp_categories FROM Deals LEFT JOIN DealCategories ".
    "ON Deals.url=DealCategories.deal_url WHERE category_id IS NULL";
  
  $result = mysql_query($sql, $con);
  
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  
  $num=mysql_num_rows($result);
  echo "<b>$num deals don't have categories assigned to them</b><p>\n";
  echo "<a href=\"/tools/classifier_tool.php\"><b>Click here to start classifying</b></a><p>\n";
  $categories_index = "";
  $first = 1;
  while ($row = @mysql_fetch_assoc($result)) {
    $url = $row['url'];
    
    $image_sql = "SELECT image_url FROM DealImages where strcmp(deal_url, '".
      mysql_real_escape_string($url)."')=0 limit 1";
    $image_result = mysql_query($image_sql, $con);
    if (!$image_result) {
      die('Error: ' . mysql_error());
    }
    if (mysql_num_rows($image_result)==1) {
      $image_url = mysql_result($image_result, 0, "image_url");
      $row['image_url'] = $image_url;
    }
    
    
    echo $url, "<BR>\n";
    $index = "category".$url;
    if ($first) {
      $categories_index = $index;
      $first = 0;
    } else {
      $categories_index = $categories_index."\n".$index;
    }
    $memcache->set($index, $row, false, $cache_life);
  }
  
  $memcache->set('categories_index', $categories_index, false, $cache_life);
}



?>

</body>
</html>