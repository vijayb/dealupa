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


	$("#matched_count").html($("#regex_match_count").html());

	
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
$indexes = $memcache->get("categories_index");


//////////////////////////////// PUT THE REGULAR EXPRESSION INFORMATION HERE ///////////////////

/*	Uncategorized	*/						$constant_expressions[0] = 'Uncategorized';
/*	Casual Dining	*/						$constant_expressions[1] = "(an fare)|(food and drink)|(cuisine)|(worth.*food.*drink)|(steak)|(seafood)|(cafe)|(dinner)|(lunch)|(brunch)";
/*	Quick Bites	*/							$constant_expressions[2] = "(sandwich)|(burgers)|(wings)|(pizza)|(\Wdeli\W)|(sports bar)|(bar and grill)|(pub fare)|(food truck)|(bagel)";
/*	Sweet Tooth	*/							$constant_expressions[3] = "(chocolate)|(chocolates)|(cupcake)|(ice cream)|(froyo)|(frozen yogurt)|(brownie)|(baked good)|(cake)|(donut)";
/*	Foodie	*/								$constant_expressions[4] = "(upscale)|(french)|(fine dining)|(tasting)|(prix.fixe)";
/*	Groceries and Spices	*/				$constant_expressions[5] = 'Groceries and Spices';
/*	Good for Kids	*/						$constant_expressions[6] = "(your child)|(your son)|(your daughter)|(your little one)|(your kid)|(your tyke)|(your princess)|(tutoring)|(\Wage.*older)|(\Wage.*and up)|(\Wage.*to)|(amusement park)|(bounce)";
/*	Moms and Babies	*/						$constant_expressions[7] = 'Moms and Babies';
/*	Always Learning	*/						$constant_expressions[8] = "(\Wclass\W)|(\Wclasses\W)|(lessons)|(\Wcourse\W)";
/*	Threads	*/								$constant_expressions[9] = 'Threads';
/*	Well Groomed	*/						$constant_expressions[10] = "(teeth.*white)";
/*	Forever Beautiful	*/					$constant_expressions[11] = "(facial)|(peel)|(\Wtan\W)|(tans)|(tanning)(micropeel)|( spider.*vein)|(skin.*tighten)|(permanent makeup)|(eyelash)|(microdermabrasion)|(stretch.*mark)|( makeup )|(botox)|(skin care)";
/*	Pampered	*/							$constant_expressions[12] = "(swedish massage)|(minute massage)|(deep.*massage)|(therapeutic.*massage)|(floatation therapy)|(hydromassage)|(massage)";
/*	Body Art	*/							$constant_expressions[13] = 'Body Art';
/*	Trim and Terrific	*/					$constant_expressions[14] = 'Trim and Terrific';
/*	Healthy Living	*/						$constant_expressions[15] = "(yoga)|(fitness.*class)|(boot.*camp)|(personal.*train)|(vitamin.*supplement)|(pilates)|(kickbox)|(crossfit)|( 5k )|( 10k )|(gym.*member)|(training.*session)|(krav maga)|(workout)|(spin.*class)|(fitness)|(nutrition)|(kettlebell)";
/*	Alternative Healing	*/					$constant_expressions[16] = "(acupunc)|(hypnotherapy)|(palm.*read)|(tarot.*card)|(chiroprac.*exam)|(chiroprac)|(hydro)|(cleanse)|(acupressure)";
/*	Seeing Clearly	*/						$constant_expressions[17] = "(lasik)|(eyewear)|(prescription.*glass)|(optical)|(eyeglasses)|(eye exam)";
/*	Medical and Dental	*/					$constant_expressions[18] = "(dentistry)|(dental)|(medical checkup)|(invisalign)|(orthodont)";
/*	Nightlife	*/							$constant_expressions[19] = 'Nightlife';
/*	Fun Activities	*/						$constant_expressions[20] = "(scavenger.*hunt)|(billiards)|(skating)|(walking tour)|(segway.*tour)|(skate.*rental)|(bowl)|(laser tag)|(rock.*climb)";
/*	Dancing Feet	*/						$constant_expressions[21] = "(dance.*studio)|(danc.*class)|(salsa.*class)|(danc.*workshop)|(tango)";
/*	Will Call	*/							$constant_expressions[22] = "(tickets to)|(ticket.*show)|( theater )|(symphony)|(performance)";
/*	Cultural Pursuits	*/					$constant_expressions[23] = 'Cultural Pursuits';
/*	The Outdoors	*/						$constant_expressions[24] = 'The Outdoors';
/*	Sporting Life	*/						$constant_expressions[25] = "(ticket.*game)|(golf)|(game)";
/*	Adrenaline	*/							$constant_expressions[26] = 'Adrenaline';
/*	Date Night	*/							$constant_expressions[27] = "(for 2)|(for two)|(romantic)";
/*	Once in a Lifetime	*/					$constant_expressions[28] = 'Once in a Lifetime';
/*	The Finer Things	*/					$constant_expressions[29] = 'The Finer Things';
/*	Automotive	*/							$constant_expressions[30] = "(oil.*change)|(auto.*mechanic)|( detailing )|(auto.*detail)|(car.*window)|(glass.*tint)|(car wash)|(windshield.*replac)|(windshield.*repair)|(transmission)|(car.*start)|(autocare)|(tire.*rota)|(vehicle)|( car )|( brake)|(automotive)|(garage)";
/*	Home and Garden	*/						$constant_expressions[31] = "(furniture)|(home decor)|(home.*furnish)|(fixtures)|(ceiling.*fan)|(framing)|(canvas.*print)|(mattress)|(flooring)|(design consult)|(interior design)|(organiz.*((serv)|(consult)))|(plants)|(home d.*cor)|(rugs)|(kitchen cabinet)|(design serv)|(home.*garden)|(garden)|(decal)|(interior.*design)|(home.*design)|(remodel)|( lighting )|(shutters)";
/*	Handyman	*/							$constant_expressions[32] = "(lawn.*mow)|(handyman)|( weed )|(mowing)|(air.condition)|(lawn.*fertil)|(duct.*clean)|( installation )|(pool.*cleaning)|( vent.*clean)|(gutter.*clean)|( mowing )|(landscap.*servic)|(power.*wash)|(paint.*room)|(pest control)|(window.*clean)|(window.*install)|(hardware)|(pool.*((maint)|(serv)))|(plumbing)|(electric)|(pest.*control)|(tune.up)|(chimney)|(inspection)|(repair)|(hvac)";
/*	Squeaky Clean	*/						$constant_expressions[33] = "(window.*clean)|(carpet.*clean)|(room.*clean)|( houseclean)|(spring clean)|(house clean)|(floor.*clean)|(home.*clean)";
/*	General Services	*/					$constant_expressions[34] = "(moving serv)|(resume.*service)|(dry cleaning)|(dry.cleaning)|(airport.*park)|(junk.*remov)|(laundry)|(conver.*dvd)|(dvd.*conver)";
/*	Gadgets and Gear	*/					$constant_expressions[35] = "(headphones)|(ipod)|(iphone)|(ipad)|(itunes)|(android)|(sd card)|([0-9]+GB)|([0-9]+TB)|(microphone)";
/*	Bookish	*/								$constant_expressions[36] = "(subscription)";
/*	Photographic	*/						$constant_expressions[37] = "(photo.*booth)|(photo.*shoot)|(photo.*session)";
/*	Crafty	*/								$constant_expressions[38] = "(art supplies)";
/*	Pet Lover	*/							$constant_expressions[39] = "(\Wpet\W)|(\Wdog\W)|(doggie)|(\Wvet\W)";
/*	Gift Ideas	*/							$constant_expressions[40] = 'Gift Ideas';
/*	Giving Back	*/							$constant_expressions[41] = 'Giving Back';
/*	Around the World	*/					$constant_expressions[42] = 'Around the World';
/*	Road Trip	*/							$constant_expressions[43] = "(night.*stay)|(\Winn\W)|(bed.*breakfast)|(lodge)";
/*	Good for Girls	*/						$constant_expressions[44] = 'Good for Girls';
/*	Good for Guys	*/						$constant_expressions[45] = "(\Wmen's\W)|(\Wmen\W)";
/*  Pampered + Good for Girls */			$constant_expressions[46] = "(mani.pedi)|(manicure)|(pedicure)";
/*	Well-Groomed + Good for Girls */		$constant_expressions[47] = "(hair services)|(blow dry)|(laser.*hair.*removal)|(keratin)|(partial.*highlight)|(highlights)|(bikini wax)|(brazil.*wax)|(hair extensions)|(hair styling)|(salon serv)|(haircut)";
/*	Threads + Good for Girls */				$constant_expressions[48] = "(jewelry)|(clothing)|(lingerie)|(women.*shoes)|(bracelet)|(blouse)|(earring)";
/*	Trim and Terrific + Good for Girls */	$constant_expressions[49] = "(weight.*loss)|(body.*contour)|(velashape)|(lipo.*injec)|(b.12)|(liposuction)|(body.*wrap)|(zerona)|(slimming treatment)|(liposculpture)";
/*	The Outdoors + Fun Activities */		$constant_expressions[50] = "(paddle.*board)|(kayak)|( fishing)|(horse.*ride)|(horseback)|(bike.*rent)|(bicycle.*rent)|(surf.*rent)|(boat.*rent)|(whale.*watch)";
/*	Adrenaline + The Outdoors + Fun Act */	$constant_expressions[51] = "(rafting)|( ziplin)|(zip lin)|(jet ski)|(adrenal)";
/*	The Outdoors + Always Learning */		$constant_expressions[52] = "(scuba)";
/*	Adrenaline + Once in a Lifetime */		$constant_expressions[53] = "(skydiv)";
/*	Healthy Living + Dancing Feet */		$constant_expressions[54] = "(zumba)|(dance.*fitness)|(fitness.*dance)";



$regular_expressions["pampered___good_for_girls"] = $constant_expressions[46];
$neg_regular_expressions["pampered___good_for_girls"] = $constant_expressions[11] . "|" . $constant_expressions[47];
$cat1_auto_fill["pampered___good_for_girls"] = 12;
$cat2_auto_fill["pampered___good_for_girls"] = 44;

$regular_expressions["well_groomed___good_for_girls"] = $constant_expressions[47];
$neg_regular_expressions["well_groomed___good_for_girls"] = $constant_expressions[11] . "|" . $constant_expressions[12] . "|" . $constant_expressions[46] . "|" . $constant_expressions[45];
$cat1_auto_fill["well_groomed___good_for_girls"] = 10;
$cat2_auto_fill["well_groomed___good_for_girls"] = 44;

$regular_expressions["well_groomed"] = $constant_expressions[10];
$neg_regular_expressions["well_groomed"] = $constant_expressions[18];
$cat1_auto_fill["well_groomed"] = 10;

$regular_expressions["threads___good_for_girls"] = $constant_expressions[48];
$neg_regular_expressions["threads___good_for_girls"] = $constant_expressions[8] . "|" . $constant_expressions[45];
$cat1_auto_fill["threads___good_for_girls"] = 9;
$cat2_auto_fill["threads___good_for_girls"] = 44;

$regular_expressions["trim_and_terrific___good_for_girls"] = $constant_expressions[49];
$neg_regular_expressions["trim_and_terrific___good_for_girls"] = $constant_expressions[45];
$cat1_auto_fill["trim_and_terrific___good_for_girls"] = 14;
$cat2_auto_fill["trim_and_terrific___good_for_girls"] = 44;

$regular_expressions["forever_beautiful"] = $constant_expressions[11];
$neg_regular_expressions["forever_beautiful"] = $constant_expressions[47] . "|" . $constant_expressions[12] . "|" . $constant_expressions[46] . "|" . $constant_expressions[45];
$cat1_auto_fill["forever_beautiful"] = 11;

$regular_expressions["forever_beautiful___pampered"] = "(?=.*(" . $constant_expressions[11] ."))" . "(?=.*(" . $constant_expressions[12] ."))";
$neg_regular_expressions["forever_beautiful___pampered"] = $constant_expressions[47] . "|" . $constant_expressions[45];
$cat1_auto_fill["forever_beautiful___pampered"] = 11;
$cat2_auto_fill["forever_beautiful___pampered"] = 12;

$regular_expressions["forever_beautiful___well_groomed"] = "(?=.*(" . $constant_expressions[11] ."))" . "(?=.*(" . $constant_expressions[47] ."))";
$neg_regular_expressions["forever_beautiful___well_groomed"] = $constant_expressions[46] . "|" . $constant_expressions[12] . "|" . $constant_expressions[45];
$cat1_auto_fill["forever_beautiful___well_groomed"] = 11;
$cat2_auto_fill["forever_beautiful___well_groomed"] = 10;

$regular_expressions["pampered___well_groomed___good_for_girls"] = "(?=.*(" . $constant_expressions[12] ."))" . "(?=.*(" . $constant_expressions[47] ."))";
$neg_regular_expressions["pampered___well_groomed___good_for_girls"] = $constant_expressions[11] . "|" . $constant_expressions[45];
$cat1_auto_fill["pampered___well_groomed___good_for_girls"] = 11;
$cat2_auto_fill["pampered___well_groomed___good_for_girls"] = 10;
$cat3_auto_fill["pampered___well_groomed___good_for_girls"] = 44;

$regular_expressions["pampered"] = $constant_expressions[12];
$neg_regular_expressions["pampered"] = $constant_expressions[47] . "|" . $constant_expressions[11];
$cat1_auto_fill["pampered"] = 12;

$regular_expressions["casual_dining___date_night"] = "(?=.*(" . $constant_expressions[1] ."))" . "(?=.*(" . $constant_expressions[27] ."))";
$cat1_auto_fill["casual_dining___date_night"] = 1;
$cat2_auto_fill["casual_dining___date_night"] = 27;

$regular_expressions["quick_bites___date_night"] = "(?=.*(" . $constant_expressions[2] ."))" . "(?=.*(" . $constant_expressions[27] ."))";
$cat1_auto_fill["quick_bites___date_night"] = 2;
$cat2_auto_fill["quick_bites___date_night"] = 27;

$regular_expressions["foodie___date_night"] = "(?=.*(" . $constant_expressions[4] ."))" . "(?=.*(" . $constant_expressions[27] ."))";
$cat1_auto_fill["foodie___date_night"] = 4;
$cat2_auto_fill["foodie___date_night"] = 27;

$regular_expressions["road_trip___date_night"] = "(?=.*(" . $constant_expressions[43] ."))" . "(?=.*(" . $constant_expressions[27] ."))" . "(?=.*(night))";
$cat1_auto_fill["road_trip___date_night"] = 43;
$cat2_auto_fill["road_trip___date_night"] = 27;

$regular_expressions["casual_dining"] = $constant_expressions[1];
$neg_regular_expressions["casual_dining"] = $constant_expressions[27] . "|" . $constant_expressions[2] . "|" . $constant_expressions[4];
$cat1_auto_fill["casual_dining"] = 1;

$regular_expressions["quick_bites"] = $constant_expressions[2];
$neg_regular_expressions["quick_bites"] = $constant_expressions[27] . "|" . $constant_expressions[4];
$cat1_auto_fill["quick_bites"] = 2;

$regular_expressions["sweet_tooth"] = $constant_expressions[3];
$neg_regular_expressions["sweet_tooth"] = $constant_expressions[27];
$cat1_auto_fill["sweet_tooth"] = 3;

$regular_expressions["foodie"] = $constant_expressions[4];
$cat1_auto_fill["foodie"] = 4;

$regular_expressions["road_trip"] = $constant_expressions[43];
$cat1_auto_fill["road_trip"] = 43;

$regular_expressions["the_outdoors___fun_activities___the_outdoors"] = $constant_expressions[51];
$cat1_auto_fill["the_outdoors___fun_activities___the_outdoors"] = 24;
$cat2_auto_fill["the_outdoors___fun_activities___the_outdoors"] = 20;
$cat3_auto_fill["the_outdoors___fun_activities___the_outdoors"] = 26;

$regular_expressions["the_outdoors___always_learning"] = $constant_expressions[52];
$cat1_auto_fill["the_outdoors___always_learning"] = 52;

$regular_expressions["the_outdoors___always_learning"] = $constant_expressions[53];
$cat1_auto_fill["the_outdoors___always_learning"] = 53;

$regular_expressions["good_for_kids"] = $constant_expressions[6];
$cat1_auto_fill["good_for_kids"] = 6;

$regular_expressions["well_groomed"] = $constant_expressions[10];
$cat1_auto_fill["well_groomed"] = 10;

$regular_expressions["healthy_living"] = $constant_expressions[15];
$neg_regular_expressions["healthy_living"] = $constant_expressions[21] . "|" . $constant_expressions[6] . "|" . $constant_expressions[12];
$cat1_auto_fill["healthy_living"] = 15;

$regular_expressions["healthy_living___dancing_feet"] = $constant_expressions[54];
$cat1_auto_fill["healthy_living___dancing_feet"] = 15;
$cat2_auto_fill["healthy_living___dancing_feet"] = 21;

$regular_expressions["alternative_healing"] = $constant_expressions[16];
$cat1_auto_fill["alternative_healing"] = 16;

$regular_expressions["seeing_clearly"] = $constant_expressions[17];
$cat1_auto_fill["seeing_clearly"] = 17;

$regular_expressions["medical_and_dental"] = $constant_expressions[18];
$cat1_auto_fill["medical_and_dental"] = 18;

$regular_expressions["fun_activities"] = $constant_expressions[20];
$cat1_auto_fill["fun_activities"] = 20;

$regular_expressions["dancing_feet"] = $constant_expressions[21];
$cat1_auto_fill["dancing_feet"] = 21;

$regular_expressions["will_call"] = $constant_expressions[22];
$cat1_auto_fill["will_call"] = 22;

$regular_expressions["sporting_life"] = $constant_expressions[25];
$cat1_auto_fill["sporting_life"] = 25;

$regular_expressions["automotive"] = $constant_expressions[30];
$cat1_auto_fill["automotive"] = 30;

$regular_expressions["home_and_garden"] = $constant_expressions[31];
$cat1_auto_fill["home_and_garden"] = 31;

$regular_expressions["handyman"] = $constant_expressions[32];
$cat1_auto_fill["handyman"] = 32;

$regular_expressions["squeaky_clean"] = $constant_expressions[33];
$cat1_auto_fill["squeaky_clean"] = 33;

$regular_expressions["general_services"] = $constant_expressions[34];
$cat1_auto_fill["general_services"] = 34;

$regular_expressions["gadgets_and_gear"] = $constant_expressions[35];
$cat1_auto_fill["gadgets_and_gear"] = 35;

$regular_expressions["bookish"] = $constant_expressions[36];
$cat1_auto_fill["bookish"] = 36;

$regular_expressions["photographic"] = $constant_expressions[37];
$cat1_auto_fill["photographic"] = 37;

$regular_expressions["crafty"] = $constant_expressions[38];
$cat1_auto_fill["crafty"] = 38;

$regular_expressions["pet_lover"] = $constant_expressions[39];
$cat1_auto_fill["pet_lover"] = 39;




////////////////////////////////////////////////////////////////////////////////////////////////


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
echo "<h1 align=center>Regex classifier tool</h2>\n";
echo "<span id='matched_count'></span>";
if (!isset($_POST["doc_section"])) {
  $_POST["doc_section"] = 0;
}

$options = array();
$options[] = "Title + subtitle";
$options[] = "URL";
$options[] = "Text";
$options[] = "Title + subtitle + URL";



if ($success && $indexes != false && !isset($_GET["reload"])) {

  if (isset($_POST["submit_categories"])) {
    $cat1 = getCategoryFromString($_POST["category_id1"]);
    $cat2 = getCategoryFromString($_POST["category_id2"]);
    $cat3 = getCategoryFromString($_POST["category_id3"]);
    $cat4 = getCategoryFromString($_POST["category_id4"]);


    foreach ($_POST as $key => $value) {
      echo "$key:$value<BR>\n";
      $match = array();
      if (preg_match("/^deal_id_/", $key) && preg_match("/([0-9]+)/", $key, $match)) {
	$id = "category:".$match[0];
	$row = $memcache->get($id);
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
	$memcache->set($id, $row, false, $cache_life);

	//echo "****** [".$match[0]."]\n";
      } else if (preg_match("/^nation_/", $key) && preg_match("/([0-9]+)/", $key, $match)) {
	$id = "category:".$match[0];
	$row = $memcache->get($id);
	$row["is_nation"] = 1;

	$memcache->set($id, $row, false, $cache_life);
      } else if (preg_match("/^recommend_/", $key) && preg_match("/([0-9]+)/", $key, $match)) {
	$id = "category:".$match[0];
	$row = $memcache->get($id);
	$row["recommend"] = 1;

	$memcache->set($id, $row, false, $cache_life);
      }

    }

    echo "[$cat1][$cat2][$cat3][$cat4]<BR>\n";

  }


  $regex_value = "";
  $neg_regex_value = "";
  $and_regex_value = "";
  $cat1_value = "";
  $cat2_value = "";
  $cat3_value = "";
  $cat4_value = "";


  if (isset($_POST["auto_fill"]) && isset($regular_expressions[$_POST["auto_fill"]])) {
    $_POST["regex"] = $regular_expressions[$_POST["auto_fill"]];
    if (isset($and_regular_expressions[$_POST["auto_fill"]])) {
      $_POST["andregex"] = $and_regular_expressions[$_POST["auto_fill"]];
    } else {
      $_POST["andregex"] = "";
    }

    if (isset($neg_regular_expressions[$_POST["auto_fill"]])) {
      $_POST["negregex"] = $neg_regular_expressions[$_POST["auto_fill"]];
    } else {
      $_POST["negregex"] = "";
    }


    if (isset($cat1_auto_fill[$_POST["auto_fill"]])) {
      $cat_index = $cat1_auto_fill[$_POST["auto_fill"]];
      $cat1_value = "value='".$categories[$cat_index - 1]["name"]." ($cat_index)'";
    }
    if (isset($cat2_auto_fill[$_POST["auto_fill"]])) {
      $cat_index = $cat2_auto_fill[$_POST["auto_fill"]];
      $cat2_value = "value='".$categories[$cat_index - 1]["name"]." ($cat_index)'";
    }
    if (isset($cat3_auto_fill[$_POST["auto_fill"]])) {
      $cat_index = $cat3_auto_fill[$_POST["auto_fill"]];
      $cat3_value = "value='".$categories[$cat_index - 1]["name"]." ($cat_index)'";
    }
    if (isset($cat4_auto_fill[$_POST["auto_fill"]])) {
      $cat_index = $cat4_auto_fill[$_POST["auto_fill"]];
      $cat4_value = "value='".$categories[$cat_index - 1]["name"]." ($cat_index)'";
    }


  }


  if (isset($_POST["regex"]) && strlen($_POST["regex"]) > 0) {
    //echo $_POST["doc_section"]."<BR>\n";
    $regex_value = "value=\"".$_POST["regex"]."\"";
  }

  if (isset($_POST["negregex"]) && strlen($_POST["negregex"]) > 0) {
    //echo $_POST["doc_section"]."<BR>\n";
    $neg_regex_value = "value=\"".$_POST["negregex"]."\"";
  } else {
    $_POST["negregex"] = "";
  }


  if (isset($_POST["andregex"]) && strlen($_POST["andregex"]) > 0) {
    //echo $_POST["doc_section"]."<BR>\n";
    $and_regex_value = "value=\"".$_POST["andregex"]."\"";
  } else {
    $_POST["andregex"] = "";
  }

  echo "<form action='/tools/regex_classifier_tool.php' method=post align=center>\n";
  echo "Regex: <input type='text' name='regex' $regex_value size=70 /><BR>\n";
  echo "And-Regex: <input type='text' name='andregex' $and_regex_value size=70 /><BR>\n";
  echo "&nbsp; Neg-regex: <input type='text' name='negregex' $neg_regex_value size=40 /><BR>\n";

  echo "<select name=doc_section>\n";
  for ($i=0; $i< count($options); $i++) {
    echo "\t<option value='$i' ";
    if ($_POST["doc_section"] == $i) {
      echo "selected=true";
    }
    echo " />".$options[$i]."</option>\n";
  }
  echo "</select>\n";


  echo "<select name=auto_fill>\n";
  echo "\t<option value='none'>Select auto_fill option</option>\n";
  foreach ($regular_expressions as $regex_name => $regex_value) {
    echo "\t<option value='$regex_name' ";
    if (isset($_POST["auto_fill"]) && $_POST["auto_fill"] == $regex_name) {
      echo "selected=true";
    }
    echo " />".$regex_name."</option>\n";
  }
  echo "</select>\n";


  echo "<input name='regex_search' type=submit value='Apply regex' />\n";

  

  if (isset($_POST["regex"]) && strlen($_POST["regex"]) > 0) {

    echo "<BR>\n";
    echo "<div style=\"float:right\"><br>\n";
    echo "Categories <span id=\"set-category-warning\" style=\"color:red;display:none\">&nbsp;(Please specify one or more valid categories - see list below)</span><BR>\n";
    echo "1. <INPUT id=\"category_id1\" $cat1_value type=\"text\" name=\"category_id1\" autocomplete=\"array:categories\"><BR>\n";
    echo "2. <INPUT id=\"category_id2\" $cat2_value type=\"text\" name=\"category_id2\" autocomplete=\"array:categories\"><BR>\n";
    echo "3. <INPUT id=\"category_id3\" $cat3_value type=\"text\" name=\"category_id3\" autocomplete=\"array:categories\"><BR>\n";
    echo "4. <INPUT id=\"category_id4\" $cat4_value type=\"text\" name=\"category_id4\" autocomplete=\"array:categories\"><BR>\n";
    echo "<BR><input type=\"submit\" name=\"submit_categories\" value=\"Submit\">\n";
    echo "</div>\n";

    echo "<BR><BR>\n";
    echo "<table border=1>\n";
    $matches_regex_count = 0;
    for ($i=0; $i< count($indexes); $i++) {
      $row = $memcache->get($indexes[$i]);
      
      if (isset($row['category_id1']) || isset($row['category_id2']) || isset($row['category_id3']) || isset($row['category_id4'])) {
	continue;
      }
      
      $num_addresses = $row["num_addresses"];
      $num_cities = $row["num_cities"];
      $company_id = $row["company_id"];
      $id = $row['id'];
      
      if (($num_addresses==0 && ($num_cities > 2)) ||
	  ($num_addresses==0 && $company_id==42 && !preg_match("/cleaning/i", $row['title'])) || // Plumdistrict
	  (isset($row['title']) && preg_match("/Online Deal/", $row['title'])) ||
	  (isset($row['url']) && preg_match("/\/nation/", $row['title']))) {
	$national_checked = "checked=yes";
      } else {
	$national_checked = "";
      }
      
      if (isset($_POST["regex"]) && strlen($_POST["regex"]) > 0 &&
	  matchesRegex($row, $_POST["regex"], $_POST["andregex"], $_POST["negregex"], $_POST["doc_section"])) {
	$matches_regex_count++;
	echo "\t<tr>\n";
	echo "\t\t<td width=600>\n";
	echo "<a href='http://50.57.43.108/tools/image_fixer.php?deal_id=$id' target=_fixer><img src=\"".$row["image_url"]."\" width=150px align=right></a>\n";
	echo "<input type=\"checkbox\" name=\"deal_id_".$row["id"]."\" checked=yes> &nbsp;\n";
	echo "<b>ID</b>: <a href=\"http://50.57.43.108/tools/deal_info.php?deal_url=".$row["id"]."&submitid=search+by+id\" target=_regex_classifier>".$row["id"]."</a> (<a href=\"http://50.57.43.108/tools/classifier_fixer.php?deal_id=".$row["id"]."\" target=_fixer>classify</a>)<BR>\n";
	echo "<input type=\"checkbox\" name=\"nation_".$row["id"]."\" $national_checked> &nbsp; <b>National</b><BR>\n";
	echo "<input type=\"checkbox\" name=\"recommend_".$row["id"]."\"> &nbsp; <b>Dealupa Recommends</b><BR>\n";

	if ($num_addresses >0) {
	  echo "<b><a href='http://50.57.43.108/tools/address_fixer.php?deal_id=$id' target=_fixer>Number of addresses</a></b>: $num_addresses<BR>\n";
	} else {
	  echo "<a href='http://50.57.43.108/tools/address_fixer.php?deal_id=$id' target=_fixer><span style='background-color:red'><b>Number of addresses</b>: <b>$num_addresses</b></span></a><BR>\n";
	}

	if ($num_cities >0) {
	  echo "<b><a href='http://50.57.43.108/tools/edition_tool.php?deal_id=$id' target=_fixer>Number of hubs</a></b>: $num_cities<BR>\n";
	} else {
	  echo "<a href='http://50.57.43.108/tools/edition_tool.php?deal_id=$id' target=_fixer><span style='background-color:red'><b>Number of hubs</b>: <b>$num_cities</b></span></a><BR>\n";
	}

	echo "<b>URL</b>: <a href=\"".$row["url"]."\" target=_regex_classifier>".$row["url"]."</a><BR>\n";
	echo "<b>Title</b>: ".$row["title"]."<BR>\n";
	echo "<b>Subtitle</b>: ".$row["subtitle"]."<BR>\n";
	echo "\t\t</td>\n";
	echo "\t</tr>\n";
      }
    }
    echo "</table>\n";
    echo "<h1><span id=regex_match_count>$matches_regex_count</span> number of deals match the regular expression</h1>\n";
	
  }

  echo "<BR><hr style=\"border:5px solid #000;\" /><BR>\n";

  echo "<table border=1>\n";
  for ($i=0; $i< count($indexes); $i++) {
    $row = $memcache->get($indexes[$i]);

    if (isset($row['category_id1']) || isset($row['category_id2']) || isset($row['category_id3']) || isset($row['category_id4'])) {
      continue;
    }
    
    $num_addresses = $row["num_addresses"];
    $num_cities = $row["num_cities"];
    $company_id = $row["company_id"];
    $id = $row['id'];

    if (!(isset($_POST["regex"]) && strlen($_POST["regex"]) > 0 &&
	  matchesRegex($row, $_POST["regex"], $_POST["andregex"], $_POST["negregex"], $_POST["doc_section"]))) {
      echo "\t<tr>\n";
      echo "\t\t<td width=600>\n";
      echo "<a href='http://50.57.43.108/tools/image_fixer.php?deal_id=$id' target=_fixer><img src=\"".$row["image_url"]."\" width=150px align=right></a><BR>\n";
      echo "<b>ID</b>: <a href=\"http://50.57.43.108/tools/deal_info.php?deal_url=".$row["id"]."&submitid=search+by+id\" target=_regex_classifier>".$row["id"]."</a> (<a href=\"http://50.57.43.108/tools/classifier_fixer.php?deal_id=".$row["id"]."\" target=_fixer>classify</a>)<BR>\n";

      echo "<b><a href=\"http://50.57.43.108/tools/address_fixer.php?deal_id=".$row["id"]."\" target=_fixer>Number of addresses</a></b>: $num_addresses<BR>\n";
      echo "<b><a href=\"http://50.57.43.108/tools/edition_tool.php?deal_id=".$row["id"]."\" target=_fixer>Number of hubs</a></b>: $num_cities<BR>\n";

      echo "<b>URL</b>: <a href=\"".$row["url"]."\" target=_regex_classifier>".$row["url"]."</a><BR>\n";
      echo "<b>Title</b>: ".$row["title"]."<BR>\n";
      echo "<b>Subtitle</b>: ".$row["subtitle"]."<BR>\n";
      echo "\t\t</td>\n";
      echo "\t</tr>\n";
    }
  }
  echo "</table>\n";

  echo "</form>\n";


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
  echo "<a href=\"/tools/regex_classifier_tool.php\"><b>Click here to start classifying</b></a><p>\n";
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


echo "</body></html>\n";

function regexstrcmp($str1, $str2) {
  $s1 = preg_replace("/[^A-Za-z0-9\s]/", " ", $str1);
  $s2 = preg_replace("/[^A-Za-z0-9\s]/", " ", $str2);

  $s1 = preg_replace("/\s+/", " ", $s1);
  $s2 = preg_replace("/\s+/", " ", $s2);

  //echo "$str1:$s1<BR>\n";
  //echo "$str2:$s2<BR>\n";
  //die("blah\n");

  if (strcmp($s1, $s2)==0) {
    return 1;
  } else {
    return 0;
  }
}


function matchesRegex($row, $regex, $and_regex, $neg_regex, $doc_section) {
  $section = "";
  if ($doc_section == 0) {
    $section = $row["title"]." ".$row["subtitle"];
  } else if ($doc_section == 1) {
    $section = $row["url"];
  } else if ($doc_section == 2) {
    $section = $row["text"];
  } else {
    $section = $row["url"]." ".$row["title"]." ".$row["subtitle"];
  }

  if (preg_match("/".$regex."/i", $section) &&
      ($and_regex == "" || preg_match("/".$and_regex."/i", $section)) &&
      ($neg_regex == "" || !preg_match("/".$neg_regex."/i", $section))) {
    return 1;
  }

  return 0;
}


function insertCategory($deal_id, $category_id, $rank, $con) {
  $category_sql =
    "INSERT into Categories (deal_id, category_id,rank) values ('".
    mysql_real_escape_string($deal_id)."',  ".
    $category_id.", $rank) ON DUPLICATE KEY UPDATE id=id";
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