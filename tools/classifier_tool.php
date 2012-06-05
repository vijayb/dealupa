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
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
<SCRIPT language="JavaScript" src="autocomplete.js"></SCRIPT>
<script>

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

  /*

	if(code == 70) {
	  $('input[name=category_id1]:eq(0)').attr('checked', true);
	}

	if(code == 65) {
	  $('input[name=category_id1]:eq(1)').attr('checked', true);
	}
	
	if(code == 83) {
	  $('input[name=category_id1]:eq(2)').attr('checked', true);
	}
	if(code == 75) {
	  $('input[name=category_id1]:eq(3)').attr('checked', true);
	}
	if(code == 82) {
	  $('input[name=category_id1]:eq(4)').attr('checked', true);
	}
	if(code == 67) {
	  $('input[name=category_id1]:eq(5)').attr('checked', true);
	}
	if(code == 72) {
	  $('input[name=category_id1]:eq(6)').attr('checked', true);
	}
	if(code == 77) {
	  $('input[name=category_id1]:eq(7)').attr('checked', true);
	}
	if(code == 86) {
	  $('input[name=category_id1]:eq(8)').attr('checked', true);
	  $('input[name=is_nation]').attr('checked', true);
	}
	

 */
</script>


<?php

set_time_limit(0);
$cache_life = 7200;

// Make a MySQL Connection
$con = mysql_connect("localhost", "crawler", "daewoo");
if (!$con) {
  die('Error: could not connect. ' . mysql_error());
}
mysql_select_db("Deals", $con) or die(mysql_error());
// MySQL connection

foreach ($_POST as $key => $value) {
  //echo "[$key] [$value]<BR>\n";
}

$memcache = new Memcache;
$success = $memcache->connect('localhost', 11211);

if (!$success) {
  echo "Failed to connect to memcache<BR>\n";
  exit();
}

$classifiers["1"] = "Unknown";
$classifiers["2"] = "Vijay";
$classifiers["3"] = "Sanjay";
$classifiers["4"] = "Jino";
$classifiers["5"] = "Mary";
$classifiers["6"] = "Irish";


$username = $_SERVER["PHP_AUTH_USER"];
$classifier_id = getClassifierID($username, $con, $memcache);
if ($classifier_id == 0) {
  echo "ERROR: Couldn't find classifier ID for user: [$username]<BR>\n";
  exit();
} else {
  echo "You are signed in as: ".$classifiers[$classifier_id]."<BR>\n";

}

$indexes = $memcache->get("categories_index");

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

echo "</head>\n";
echo "<body>\n";




if ($success && $indexes != false && !isset($_GET["reload"])) {
  //echo "$categories_index <BR>\n";
  echo "<b><span style='font-size:26px'>",count($indexes), "</span></b> deals in cache <BR>\n";
  $num_classified = 0;
  $num_national = 0;
  $num_recommended = 0;
  $unclassified = array();
  for ($i=0; $i< count($indexes); $i++) {
    $row = $memcache->get($indexes[$i]);
    if (!isset($row["classifier_id"]) || $row["classifier_id"] == "") {
      $row["classifier_id"] = $classifier_id;
      $time = date('Y-m-d H:i:s', time());
      $row["time"] = $time;
    }    

    if (isset($_POST["dealupa_recommends"])) {
      if (isset($_POST["url"]) && strcmp($_POST["url"], $row["url"]) == 0) {
	$row["recommend"] = 1;
	$memcache->set($indexes[$i], $row, false, $cache_life);
	echo "Setting Dealupa Recommends for <b>".$row["url"]."</b><BR>\n";
      }
    }

    if (isset($_POST["is_nation"])) {
      if (isset($_POST["url"]) && strcmp($_POST["url"], $row["url"]) == 0) {
	$row["is_nation"] = 1;
	$memcache->set($indexes[$i], $row, false, $cache_life);
	echo "Setting nation for <b>".$row["url"]."</b><BR>\n";
      } else if (isset($_POST["company_id"]) &&
	  $row["company_id"] == $_POST["company_id"] &&
	  isset($_POST["title"]) &&
	  strlen($_POST["title"]) > 3 &&
	  isset($row["title"]) &&
	  strlen($row["title"]) > 3 &&
	  regexstrcmp($_POST["title"], $row["title"])) {
	$row["is_nation"] = 1;
	$memcache->set($indexes[$i], $row, false, $cache_life);
	echo "Setting nation for DUP ".$row["url"]."<BR>\n";
      }
    }


    if (isset($_POST["url"]) && 
	(isset($_POST["category_id1"]) || isset($_POST["category_id2"]) || isset($_POST["category_id3"]) || isset($_POST["category_id4"]) )) {
      // Look for the URL of the article that was classified and comes
      // to us in a POST. When we find it, set its categories
      // appropriately, and put it back in the cache

      if (strcmp($_POST["url"], $row["url"]) == 0) {
	$cat1 = getCategoryFromString($_POST["category_id1"]);
	$cat2 = getCategoryFromString($_POST["category_id2"]);
	$cat3 = getCategoryFromString($_POST["category_id3"]);
	$cat4 = getCategoryFromString($_POST["category_id4"]);

	echo "Set category for <b>[".$row["url"]."]</b><BR>\n";
	if ($cat1 > 0) {
	  //echo $cat1.",";
	  $row["category_id1"] = $cat1;
	}
	if ($cat2 > 0) {
	  //echo $cat2.",";
	  $row["category_id2"] = $cat2;
	}
	if ($cat3 > 0) {
	  //echo $cat3.",";
	  $row["category_id3"] = $cat3;
	}
	if ($cat4 > 0) {
	  //echo $cat4.",";
	  $row["category_id4"] = $cat4;
	}
	//echo "<BR>\n";

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
	  regexstrcmp($_POST["title"], $row["title"])) {
	$cat1 = getCategoryFromString($_POST["category_id1"]);
	$cat2 = getCategoryFromString($_POST["category_id2"]);
	$cat3 = getCategoryFromString($_POST["category_id3"]);
	$cat4 = getCategoryFromString($_POST["category_id4"]);

	//echo "Setting category for <b>[".$row["url"]."]</b> to : ";
	if ($cat1 > 0) {
	  //echo $cat1.",";
	  $row["category_id1"] = $cat1;
	}
	if ($cat2 > 0) {
	  //echo $cat2.",";
	  $row["category_id2"] = $cat2;
	}
	if ($cat3 > 0) {
	  //echo $cat3.",";
	  $row["category_id3"] = $cat3;
	}
	if ($cat4 > 0) {
	  //echo $cat4.",";
	  $row["category_id4"] = $cat4;
	}
	//echo "<BR>\n";

	$memcache->set($indexes[$i], $row, false, $cache_life);
      }
    }
    
    if (isset($row['category_id1']) || isset($row['category_id2']) || isset($row['category_id3']) || isset($row['category_id4'])) {
      $num_classified++;
      
      if (isset($_GET["submit"])) { 
	if (isset($row['category_id1'])) {
	  insertCategory($row['id'], $row['category_id1'], 4, $row["classifier_id"], $row["time"], $con);
	}
	if (isset($row['category_id2'])) {
	  insertCategory($row['id'], $row['category_id2'], 3, $row["classifier_id"], $row["time"], $con);
	}
	if (isset($row['category_id3'])) {
	  insertCategory($row['id'], $row['category_id3'], 2, $row["classifier_id"], $row["time"], $con);
	}
	if (isset($row['category_id4'])) {
	  insertCategory($row['id'], $row['category_id4'], 1, $row["classifier_id"], $row["time"], $con);
	}

	if (isset($row['recommend'])) {
	  insertRecommend($row['id'], $con);
	}

      }

    } else {
      $unclassified[count($unclassified)] = $i;		
    }


    if (isset($row["recommend"])) {
      $num_recommended++;
    }

    if (isset($row["is_nation"])) {
      $num_national++;

      if (isset($_GET["submit"])) {
	insertNational($row['id'], $con);
      }
    }

    if (isset($row['title'])) {
      if (isset($title_dups[$row['title']])) {
	$title_dups[$row['title']]++;
      } else {
	$title_dups[$row['title']] = 0;
      }
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
  echo "Of which <b><span style='font-size:26px'>",$num_classified,"</span></b> are classified<BR>\n";
  echo "And <b>",$num_national,"</b> are national<BR>\n";
  echo "And <b>",$num_recommended,"</b> are Dealupa Recommends<BR>\n";
  
  $chosen_deal = $unclassified[rand(0, count($unclassified)-1)];
  $row = $memcache->get($indexes[$chosen_deal]);
  $id = $row['id'];
  $url = $row['url'];
  $num_addresses = $row['num_addresses'];
  $num_cities = $row['num_cities'];

  $title = $row['title'];
  $price = $row['price'];
  $company_id = $row['company_id'];
  $image_url = $row['image_url'];
  $subtitle = $row['subtitle'];
  $yelp_categories = $row['yelp_categories'];
  $text = $row['text'];
  $fine_print = $row['fine_print'];

  if (isset($row['title'])) {
    $num_title_dups = $title_dups[$row['title']];
  } else {
    $num_title_dups = 0;
  }


  if (($num_addresses==0 && ($num_cities > 2 || $num_title_dups > 2)) ||
      ($num_addresses==0 && $company_id==42 && !preg_match("/cleaning/i", $row['title'])) || // Plumdistrict
      (isset($row['title']) && preg_match("/Online Deal/", $row['title'])) ||
      (isset($row['url']) && preg_match("/\/nation/", $row['title']))) {
    $national_checked = "checked=yes";
  } else {
    $national_checked = "";
  }


  //$url = str_replace("'", "&#39;", $url);
  echo "[$url]<BR>\n";
  echo "<BR>\n";
  echo "<form id=\"myform1\" name=\"myform1\" action=\"/tools/classifier_tool.php\" method=\"POST\" onsubmit=\"return checkCategoriesValid()\">";
  echo "<input type=hidden name=title value=\"".htmlentities($title)."\">\n";
  echo "<input type=hidden name=company_id value='".$company_id."'>\n";
  echo "<table><tr>\n";
  echo "<td width='700px'>\n";
  echo "<b>Deal ID: </b> <a href='http://50.57.43.108/tools/deal_info.php?deal_url=$id&submitid=search+by+id' target=blank>$id</a> -  <a href='http://50.57.43.108/tools/address_fixer.php?deal_id=$id' target=_fixer>address fixer</a> -  <a href='http://50.57.43.108/tools/image_fixer.php?deal_id=$id' target=_fixer>image fixer</a> - <a href='http://50.57.43.108/tools/edition_tool.php?deal_id=$id' target=_fixer>edition fixer</a><BR>\n";
  echo "<b>URL: </b> <a href=\"$url\" target=blank>".htmlentities($url)."</a><BR>\n";
  if ($num_addresses >0) {
    echo "<b>Price</b>: <style=\"font-size:20px\">$ $price</style><BR>\n";
    echo "<b>Number of addresses</b>: $num_addresses<BR>\n";
  } else {
    echo "<a href='http://50.57.43.108/tools/address_fixer.php?deal_id=$id' target=_fixer><span style='background-color:red'><b>Number of addresses</b>: <b>$num_addresses</b></span></a><BR>\n";
  }
  if ($num_cities >0) {
    echo "<b>Number of hubs</b>: $num_cities<BR>\n";
  } else {
    echo "<a href='http://50.57.43.108/tools/edition_tool.php?deal_id=$id' target=_fixer><span style='background-color:red'><b>Number of hubs</b>: <b>$num_cities</b></span></a><BR>\n";
  }

  echo "<b>Number of title dups</b>: $num_title_dups<BR><BR>\n";

  echo "<b>Title: </b> $title<BR>\n";
  echo "<b>Subtitle: </b> $subtitle<BR>\n";
  echo "<b>Yelp categories: </b> $yelp_categories<BR>\n";

  $s3_image_url = "http://dealupa_images.s3.amazonaws.com/".sha1($image_url);
  echo "<img src='$s3_image_url' width=250px><br>\n";

  //  echo "<img width=250px height:auto src='$image_url'><br>\n";
  $text = preg_replace("/<script[^>]+>/", "", $text);
  echo "<b>Text: </b><BR>\n\n<blockquote>$text\n\n</blockquote>\n";
  echo "<BR><BR><BR><b>Fine print: </b> $fine_print<BR>\n";
  echo "</td>\n";

  echo "<td width='10%'></td>\n";
  
  echo "<td valign=top>\n";
  echo "Categories <span id=\"set-category-warning\" style=\"color:red;display:none\">&nbsp;(Please specify one or more valid categories - see list below)</span><BR>\n";
  
  echo "<div align=\"left\"><br>
<input type=hidden name=url value=\"".htmlentities($url)."\">\n";

  echo "1. <INPUT id=\"category_id1\" type=\"text\" name=\"category_id1\" autocomplete=\"array:categories\"><BR>\n";
  echo "2. <INPUT id=\"category_id2\" type=\"text\" name=\"category_id2\" autocomplete=\"array:categories\"><BR>\n";
  echo "3. <INPUT id=\"category_id3\" type=\"text\" name=\"category_id3\" autocomplete=\"array:categories\"><BR>\n";
  echo "4. <INPUT id=\"category_id4\" type=\"text\" name=\"category_id4\" autocomplete=\"array:categories\"><BR>\n";
  echo "<BR><input type=\"checkbox\" id=\"nation\" name=\"is_nation\" $national_checked>National\n";
  echo "<BR><input type=\"checkbox\" id=\"recommend\" name=\"dealupa_recommends\">Dealupa Recommends<BR>\n";
  echo "<BR><input type=\"submit\" value=\"Submit\">\n";

  $categories = getAllCategories($con);
  echo "<div class=\"wrapper\">\n";
  echo "<ol>\n";
  for ($k=0; $k < count($categories); $k++) {
    $category_id = $k + 1;
    $category_name = $categories[$k]["name"];
    echo "\t<li>$category_id - $category_name</li>\n";
  }
  echo "</ol>\n";
  echo "</div>\n";





echo "</div>\n";
  echo "</td>\n";
  
  echo "</tr></table>\n";


} else {
  $sql="SELECT Deals.id,url,company_id,title,subtitle,price,text,fine_print,yelp_categories FROM Deals LEFT JOIN Categories ".
    "ON Deals.id=Categories.deal_id WHERE dup=0 and category_id IS NULL and (discovered != last_updated or title is not null or text is not null)";
  
  echo $sql."<BR>\n";
  $result = mysql_query($sql, $con);
  
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  
  $num=mysql_num_rows($result);
  echo "<b>$num deals don't have categories assigned to them</b><p>\n";
  echo "<a href=\"/tools/classifier_tool.php\"><b>Click here to start classifying</b></a><p>\n";
  $categories_index = array();
  $first = 1;
  while ($row = @mysql_fetch_assoc($result)) {
    $id = $row['id'];
    $company_id= $row['company_id'];
    $url = $row['url'];
    $title = $row['title'];
    $subtitle = $row['subtitle'];
    
    $image_sql = "SELECT image_url FROM Images where deal_id=$id limit 1";

    $image_result = mysql_query($image_sql, $con);
    if (!$image_result) {
      die('Error: ' . mysql_error());
    }
    if (mysql_num_rows($image_result)==1) {
      $image_url = mysql_result($image_result, 0, "image_url");
      $row['image_url'] = $image_url;
    }


    $address_sql = "SELECT id FROM Addresses where deal_id=$id";

    $address_result = mysql_query($address_sql, $con);
    if (!$address_result) {
      die('Error: ' . mysql_error());
    }
    $row["num_addresses"] = mysql_num_rows($address_result);


    $city_sql = "SELECT id FROM Cities where deal_id=$id";

    $city_result = mysql_query($city_sql, $con);
    if (!$city_result) {
      die('Error: ' . mysql_error());
    }
    $row["num_cities"] = mysql_num_rows($city_result);
    
    echo $id." (".$company_id."): ".$title." ".$subtitle."<BR>\n";
    $index = "category:".$id;
    $memcache->set($index, $row, false, $cache_life);

    array_push($categories_index, $index);
  }
  
  $memcache->set('categories_index', $categories_index, false, $cache_life);
}



function regexstrcmp($str1, $str2) {
  $s1 = preg_replace("/[^A-Za-z0-9\s]/", " ", $str1);
  $s2 = preg_replace("/[^A-Za-z0-9\s]/", " ", $str2);

  $s1 = preg_replace("/\s+/", " ", $s1);
  $s2 = preg_replace("/\s+/", " ", $s2);

  if (strcmp($s1, $s2)==0) {
    return 1;
  } else {
    return 0;
  }
}

function insertCategory($deal_id, $category_id, $rank, $classifier_id, $time, $con) {
  $category_sql =
    "INSERT into Categories (deal_id, category_id,rank, classifier_id, time) values ('".
    mysql_real_escape_string($deal_id)."',  ".
    $category_id.", $rank, $classifier_id, '$time') ON DUPLICATE KEY UPDATE id=id";
  $result = mysql_query($category_sql, $con);
	
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "[$category_sql]<BR>\n";


  $update_sql =
    "UPDATE Deals set last_updated=UTC_TIMESTAMP() where id=$deal_id";

  $result = mysql_query($update_sql, $con);
	
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "[$update_sql]<BR>\n";
}

function insertNational($deal_id, $con) {
  // The city_id 2 represents national.
  $national_sql =
    "INSERT into Cities (deal_id, city_id) values ('".
    mysql_real_escape_string($deal_id)."', 2) ON DUPLICATE KEY UPDATE id=id";
  $result = mysql_query($national_sql, $con);
	
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "[$national_sql]<BR>\n";


  $update_sql =
    "UPDATE Deals set last_updated=UTC_TIMESTAMP() where id=$deal_id";

  $result = mysql_query($update_sql, $con);
	
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "[$update_sql]<BR>\n";
}


function insertRecommend($deal_id, $con) {
  $update_sql =
    "UPDATE Deals set recommend=1, last_updated=UTC_TIMESTAMP() where id=$deal_id";

  $result = mysql_query($update_sql, $con);
	
  if (!$result) {
    die('Error: ' . mysql_error());
  }
  echo "[$update_sql]<BR>\n";
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
  } else {
  }

  return $classifier_id;
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



?>

</body>
</html>