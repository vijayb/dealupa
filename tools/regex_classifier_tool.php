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



function toggleCheckbox(number) {
       var $checkbox = $("#checkbox_" + number);
       $checkbox.attr('checked', !$checkbox.is(':checked'));
}

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

	if(code == 27) {
	  if (checkCategoriesValid()) {
	    $("#categorybutton").click();
	  }
	}

	if(code == 32) {
	  //alert('hello');
	  $('option:selected', 'select').removeAttr('selected').next('option').attr('selected', 'selected');
	  $("#regexbutton").click();
	}

	//if(code == 27) {
	//  if ($('input[name=is_nation]').is(':checked')) {
	//    $('input[name=is_nation]').attr('checked', false);
	//  } else {
	//    $('input[name=is_nation]').attr('checked', true);
	//  }
	//}
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



$indexes = $memcache->get("categories_index");


//////////////////////////////// PUT THE REGULAR EXPRESSION INFORMATION HERE ///////////////////





/* Uncategorized	*/						$constant_expressions[0] = 'Uncategorized';
/* Casual Dining	*/						$constant_expressions[1] = "(casual dining)|(to spend on food)|(\Wtapas)|(fried chicken)|(worth of.*food)|(chinese food)|(an fare)|(food and drink)|(cuisine)|(worth.*food.*drink)|(steak)|(seafood)|(cafe)|(dinner)|(lunch)|(brunch)|(bistro fare)|(thai fare)|(asian.*fusion)|(an restaurant)";
/* Quick Bites	*/							$constant_expressions[2] = "(pizzeria)|(wraps)|(pub meal)|(sandwich)|(burgers)|(\Wwings)|(pizza)|(\Wdeli)|(sports bar)|(pub fare)|(food truck)|(bagel)|(\Wsubs\W)|(\Wsub\W)|(fried chicken)";
/* Sweet Tooth	*/							$constant_expressions[3] = "(truffle)|(\Wcustard)|(smoothie)|(frozen treat)|(chocolate)|(chocolates)|(cupcake)|(ice cream)|(froyo)|(frozen yogurt)|(brownie)|(baked good)|(cake)|(donut)|(\Wcookies)|(baked treat)|(sweets)|(gelato)|(doughnut)|(dessert)|(italian ice)";
/* Foodie	*/								$constant_expressions[4] = "(culinary)|(upscale)|(french)|(fine dining)|(prix.fixe)|(cheese)|(oyster)|(champagne)";
/* Groceries and Spices	*/					$constant_expressions[5] = "(groceries)|(farmer.*market)|(fresh.*produce)|(olive oil)|(\Wproduce)|(vinegar)|(spices)";
/* Good for Kids	*/						$constant_expressions[6] = "(for kids)|(kids.*class)|(your child)|(your son)|(your daughter)|(your little one)|(your kid)|(your tyke)|(your princess)|(tutoring)|(\Wage.*older)|(\Wage.*and up)|(\Wage.*to)|(amusement park)|(bounce.*house)|(\Wkid.*\Wplay)|(\Wchild.*\Wplay)|(summer camp)|(babysit)|(teddy bear)|(driving school)|(disney)|(\Wbounce)|(family fun)|(\Wtoys)|(child.*camp)|(stuffed animal)|(for children)|(children's)|(kids)|(child's)";
/* Moms and Babies	*/						$constant_expressions[7] = "(baby genius)|(maternity)|(\Wbaby)|(\Wmom)|(\Wmoms)|(stroller)";
/* Always Learning	*/						$constant_expressions[8] = "(\Wclass)|(\Wclasses)|(lesson)|(\Wlearn)|(introductory)|(academy)|(workshop)|(language.*course)";
/* Threads	*/								$constant_expressions[9] = "(\Wt\W.*shirt)|(sunglasses)|(shoes)|(footwear)|(clothes)|(sandals)|(sneakers)|(\Wwatches)";
/* Well Groomed	*/							$constant_expressions[10] = "(whitening)|(teeth.*white)|(toothbrush)";
/* Forever Beautiful	*/					$constant_expressions[11] = "(skin.*product)|(laser rejuvenation)|(dysport)|(beauty service)|(eyelid)|(cellulite)|(\Wlash\W)|(\Wlashes)|(face lift)|(facelift)|(lash extension)|(firming\W.*treatment)|(exfoliat)|(skin treatment)|(facial)|(peel)|(\Wtan\W)|(tans)|(tanning)|(micropeel)|( spider.*vein)|(skin.tighten)|(permanent makeup)|(eyelash)|(microdermabrasion)|(stretch.*mark)|( makeup )|(botox)|(skin care)|(anti.aging)|(brow.*shap)|(makeover)|(\Wmake.up)|(\Wvein.*remov)|(\Wvein.*treatment)|(skin revitalization)|(fraxel)|(beauty product)|(skincare)|(skin.*resurfac)|(sclerotherapy)|(microderm)|(face.*neck)|(eyeliner)|(\Wpigment)|(moisturizer)|(collagen)";
/* Pampered	*/								$constant_expressions[12] = "(day spa)|(spa services)|(body scrub)|(swedish massage)|(minute massage)|(deep.*massage)|(therapeutic.*massage)|(floatation therapy)|(hydromassage)|(massage)|(sauna)|(russian.*bath)|(spa package)|(salt.*room)";
/* Body Art	*/								$constant_expressions[13] = "(tattoo)";
/* Trim and Terrific	*/					$constant_expressions[14] = 'Trim and Terrific';
/* Healthy Living	*/						$constant_expressions[15] = "(\Wb12)|(protein bar)|(protein shake)|(jazzercise)|(performance.*train)|(athletic.*train)|(yoga)|(fitness.*class)|(boot.*camp)|(personal.*train)|(vitamin.*supplement)|(pilates)|(kickbox)|(crossfit)|( 5k )|( 10k )|(gym.*member)|(training.*session)|(workout)|(spin.*class)|(fitness)|(nutrition)|(kettlebell)|(\Wbarre)|(cycl.*class)|(\Wgym)|(health.*supplement)|(protein shot)|(marathon training)|(fitcamp)";
/* Alternative Healing	*/					$constant_expressions[16] = "(shockwave.*therapy)|(acupunc)|(hypnotherapy)|(palm.*read)|(tarot.*card)|(chiroprac.*exam)|(chiroprac)|(hydro)|(cleanse)|(acupressure)|(reiki)|(hypnosis)|(detox)|(spinal.*decompression)|(spine care)";
/* Seeing Clearly	*/						$constant_expressions[17] = "(lasik)|(eyewear)|(prescription.*glass)|(optical)|(eyeglasses)|(eye exam)|(frames.*lens)|(lens.*frames)";
/* Medical and Dental	*/					$constant_expressions[18] = "(dentistry)|(dental)|(medical checkup)|(invisalign)|(orthodont)|(fungus)|(podiatry)";
/* Nightlife	*/							$constant_expressions[19] = "(\Wlounge)|(\Wspend on (cocktail|drink))|(nightspot)|(limo.*night)";
/* Fun Activities	*/						$constant_expressions[20] = "(\Wair\W.*\Wshow\W)|(\Wimprov\W)|(mini.*golf)|(go.*kart)|(ticket.*movie)|(ticket.*imax)|(scavenger.*hunt)|(billiards)|(skating)|(walking tour)|(segway.*tour)|(skate.*rental)|(bowl)|(laser tag)|(rock.*climb)|(climbing)|(archery)|(karaoke)|(water park)|(movie.*night)|(movie.*popcorn)|(movie.*ticket)|(museum of history)|(foosball)|(darts)|(pool hall)|(shuffleboard)|(movie.*outing)|(\Wzoo\W)|(car.*show)|(skat.*rink)|(sightsee)";
/* Dancing Feet	*/							$constant_expressions[21] = "(dance festival)|(dance.*studio)|(danc.*class)|(salsa.*class)|(danc.*workshop)|(tango)|(burlesque)|(danc.*lesson)|(salsa dancing)|(mambo)|(bachata)|(hip.hop)|(salsa.*lesson)";
/* Will Call	*/							$constant_expressions[22] = "(theatre)|(ticket.*show)|(\Wtheater\W)|(performance)|(\Wto see\W.*\Won\W)|(concert)|(\Wproduction)|(shakespeare)|(cirque)|(admission)";
/* Cultural Pursuits	*/					$constant_expressions[23] = "(symphony)|(opera)|(ballet)|(\Wmusical\W)|(museum)";
/* The Outdoors	*/							$constant_expressions[24] = 'The Outdoors';
/* Sporting Life	*/						$constant_expressions[25] = "(driving range)|(ticket.*game)|(tennis)|(basketball)|(baseball)|(volleyball)|(hockey)|(football)|(batting.*cage)|(soccer)|(\Wvs\.\W)";
/* Adrenaline	*/							$constant_expressions[26] = 'Adrenaline';
/* Date Night	*/							$constant_expressions[27] = "(for 2)|(for two)|(romantic)";
/* Once in a Lifetime	*/					$constant_expressions[28] = 'Once in a Lifetime';
/* The Finer Things	*/						$constant_expressions[29] = 'The Finer Things';
/* Automotive	*/							$constant_expressions[30] = "(window tint)|(\Wsmog)|(detailing package)|(detail package)|(detail\W.*\Wcar)|(\Wauto.*inspection)|(auto repair)|(auto service)|(oil.*change)|(auto.*mechanic)|( detailing )|(auto.*detail)|(car.*window)|(glass.*tint)|(car wash)|(windshield.*replac)|(windshield.*repair)|(transmission)|(car.*start)|(autocare)|(tire.*rota)|(vehicle)|( car )|( brake)|(automotive)|(garage)|(exterior.*detail)|(interior.*detail)|(auto.*maintenance)|(dent.*remov)|(auto care)|(car maintenance)|(auto.*wash)|(rock.*chip)|(windshield)|(dent.*repair)|(automobile)";
/* Home and Garden	*/						$constant_expressions[31] = "(photo canvas)|(\Wcandle\W)|(\Wcandles\W)|(window.*cover)|(towel.*set)|(nightlight)|(pillow)|(furniture)|(home decor)|(\Wdecor)|(home.*furnish)|(fixtures)|(ceiling.*fan)|(framing)|(canvas.*print)|(mattress)|(flooring)|(design consult)|(interior design)|(organiz.*((serv)|(consult)))|(plants)|(home d.*cor)|(rugs)|(kitchen cabinet)|(design serv)|(home.*garden)|(garden)|(decal)|(interior.*design)|(home.*design)|(remodel)|( lighting )|(shutters)|(metal.*print)|(window treatment)|(art.*frames)|(\Wshade)|(\Wcanvas)|(fine art print)|(framed.*art)|(canvases)|(\Wwall\W.*art)|(appliance)|(poster print)|(\Wart\W.*frame)|(knife.*\Wset)|(\Wfloors)|(framed.*photo)|(framed.*picture)|(home organization)|(kitchen.*design)|(floor rug)|(wall.*mural)|(duck.*feather)|(down.*comforter)|(towel.*set)|(set.*towel)";
/* Handyman	*/								$constant_expressions[32] = "(mosquito treatment)|(pressure.*washing)|(pressure.*clean)|(room\W.*paint)|(interior.*exterior.*wash)|(landscaping)|(exterior\W.*\Wpaint)|(locksmith)|(lawn.*mow)|(lawn care)|(handyman)|( weed )|(mowing)|(air.condition)|(lawn.*fertil)|(duct.*clean)|( installation )|(pool.*cleaning)|( vent.*clean)|(gutter.*clean)|( mowing )|(landscap.*servic)|(power.*wash)|(paint.*room)|(pest control)|(window.*install)|(hardware)|(pool.*((maint)|(serv)))|(plumbing)|(electric)|(pest.*control)|(tune.up)|(chimney)|(inspection)|(repair)|(hvac)|(house.*paint)|(home energy)|(\Whome\W.*\Wpest)|(roof replacement)|(lawn.*aeration)|(lawn.*cut)|(weed.*treatment)|(\Wattic)|(angie.s.list)|(brush removal)|(\Wdriveway)|(mosquito.*control)|(mosquito)|(gutter guard)|(tree service)|(heating.*cooling)";
/* Squeaky Clean	*/						$constant_expressions[33] = "(hour.*cleaning)|(cleaning.*hour)|(residential clean)|(clean.*window)|(window.*clean)|(carpet.*clean)|(room.*clean)|(\Whouseclean)|(spring.*clean)|(house.*clean)|(floor.*clean)|(home.*clean)|(floor.*cleaning)|(cleaning.*session)|(housekeeping service)|(grout.*clean)|(clean.*bathroom)|(clean.*kitchen)|(bathroom.*clean)|(kitchen.*clean)";
/* General Services	*/						$constant_expressions[34] = "(virus.*remov)|(moving serv)|(resume.*service)|(dry cleaning)|(dry.cleaning)|(junk.*remov)|(laundry)|(conver.*dvd)|(dvd.*conver)|(wedding.*plan)|(media.*conver)|(\Wdoor\W.*\Wlock)|(storage)|(resume.*edit)|(\Wpc\W.*repair)|(computer.*repair)|(computer.*diagnosis)|(\Wprint.*servic)|(digitization)|(media.*transfer)|(shoe.*repair)|(boot.*repair)";
/* Gadgets and Gear	*/						$constant_expressions[35] = "(\Wled)|(\Wmonitor)|(1080)|(530)|(headphones)|(ipod)|(iphone)|(ipad)|(itunes)|(android)|(sd card)|([0-9]+GB)|([0-9]+TB)|(microphone)|(headset)|(\Wphone)|(\WTV)|(digital camera)|(wireless)|(dvd player)|(\Wusb)|(touch screen)|(tablet)";
/* Bookish	*/								$constant_expressions[36] = "(subscription)|(\Wbook\W.*store)|(\Wbook\W.*shop)|(\Webook)|(spend on book)";
/* Photographic	*/							$constant_expressions[37] = "(photo.*booth)|(photo.*shoot)|(photo.*session)|(photography)|(portrait.*session)|(portrait.*package)|(\Wphotowalk)|(photographic)";
/* Crafty	*/								$constant_expressions[38] = "(art supplies)|(quilt.*supplies)|(sewing.*supplies)";
/* Pet Lover	*/							$constant_expressions[39] = "(\Wpet)|(\Wdog\W)|(doggie)|(\Wvet\W)|(\Wdogs\W)";
/* Gift Ideas	*/							$constant_expressions[40] = "(photo book)|(gift package)|(gifts)|(\Wgift)|(bouquet)|(photo book)";
/* Giving Back	*/							$constant_expressions[41] = 'Giving Back';
/* Around the World	*/						$constant_expressions[42] = "(cabo san lucas)|(mexico)|(all.inclusive)|(with airfare)|(honduras)|(costa rica)";
/* Road Trip	*/							$constant_expressions[43] = "(•)|(night.*vacation)|(night.*stay)|(bed.*breakfast)|(getaway)|([0-9].night)|(b&b)";
/* Good for Girls	*/						$constant_expressions[44] = "(women's)|(\Wwomen)";
/* Good for Guys	*/						$constant_expressions[45] = "(\Wmen's)|(\Wmen\W)";

/* Self-Defense	*/							$constant_expressions[46] = "(self.defense)|(krav maga)|(boxing class)|(martial arts)|(\Wpistol)|(\Wammo)|(gun rental)|(handgun)|(\Wgun)|(shooting.range)|(firearm)|(concealed.*carry)|(weapon.*train)";
/* Cooking	*/								$constant_expressions[47] = "(cooking)|(cheese.*mak)|(sushi.*mak)|(cookbook)|(bread.*mak)|(kitchenware)";
/* Expos, fairs & festivals	*/				$constant_expressions[48] = "(festival)|(\Wexpo)";
/* Music Lover	*/							$constant_expressions[49] = "(\WDJ\W.*service)|(recording.*session)|(recording.*studio)|(\Wmusic)|(rock\W.*\Wroll)|(\Wdrum)";
/* Toyland	*/								$constant_expressions[50] = "(remote.control)";
/* Getting Around	*/						$constant_expressions[51] = "(airport.*parking)|(parking.*airport)|(ride.*airport)|(airport.*park)|(round.trip.*airport)|(round.trip.*ride)|(town car)|(one.way.*airport)|(one.way.*ride)|(parking.*shuttle)|(valet.*parking)|(airport transfer)|(chauffeur)|(sedan service)";
/* Imbibe	*/								$constant_expressions[52] = "(\Wwine)|(wine.*festival)|(wine.*tour)|(brew.*tour)|(wine.*tast)|(tasting.*wine)|(winery)|(beer.*tast)|(homebrew)|(vineyard)|(winemaker)|(\Wbeer)|(wine bar)";
/* Mix and Mingle	*/						$constant_expressions[53] = "(speed dating)";
/* Dinner Time	*/							$constant_expressions[54] = "(delivery.*meal)|(meal.*delivery)|(meal.*planning)|(prepared.*meal)|(prepared.*dinner)|(delivered.*meal)";
/* Well caffeinated	*/						$constant_expressions[55] = "(coffee)|(\Wtea\W)|(keurig)|(starbucks)";
/* On the Water	*/							$constant_expressions[56] = "(fishing)|(sailing)|(whale.*watch)|(cruise)|(boat tour)|(boat.*charter)|(\Wboat)|(riverboat)|(speedboat)|(\Wsail\W)|(\Wboating)|(water.bike)|(yacht)|(water taxi)";

/* Up in the Air	*/						$constant_expressions[57] = "(flight package)|(flight.*experience)|(pilot.*experience)|(flight.*session)|(flight.*simulation)";
/* Hole in One	*/							$constant_expressions[58] = "(golf)";
/* Under the Sea	*/						$constant_expressions[59] = "(scuba)|(scalloping)|(snorkel)";

/* Pampered + Good for Girls */				$constant_expressions[60] = "(mani.pedi)|(manicure)|(pedicure)|(foot bath)|(foot cleanse)|(nail service)";
/* Well Groomed + Good for Girls */			$constant_expressions[61] = "(hair.*remov)(haircare)|(hair care)|(hair.*straightening)|(threading)|(eyebrow)|(brow wax)|(hair.smooth)|(blowout)|(hair\W.*services)|(deep conditioning)|(cut and style)|(hair services)|(blow dry)|(laser.*hair.*removal)|(keratin)|(partial.*highlight)|(highlights)|(bikini wax)|(brazil.*wax)|(hair extensions)|(hair styling)|(salon serv)|(haircut)|(shampoo)|(cut and color)|(electrolysis)|(waxing)";
/* Threads + Good for Girls */				$constant_expressions[62] = "(\Wgowns)|(\Wgown)|(swimwear)|(swimsuit)|(\Wflats)|(\Wboots)|(\Wheels)|(\Wbras\W)|(\Wbra\W)|(clothing)|(lingerie)|(women.*shoes)|(blouse)|(\Wdress)|(fashion accessor)|(\Wcapris)|(leggings)";
/* Trim and Terrific + Good for Girls */	$constant_expressions[63] = "(sculpting.*treatment)|(vaser)|(body.shaping)|(\Wlipo\W)|(endermologie)|(weight.*loss)|(body.*contour)|(velashape)|(lipo.*injec)|(b.12)|(liposuction)|(body.*wrap)|(zerona)|(slimming treatment)|(liposculpture)|(lipotron)|(lipolaser)|(lipodissolve)|(lipo.*cavitation)|(\Wfat.*\Wreduction)|(hypoxi)|(slimming.*treatment)";
/* The Outdoors + Fun Activities */			$constant_expressions[64] = "(\Wcasting)|(obstacle run)|(obstacle.course)|(\Wmud\W.*run)|(adventure.*\Wrace)|(mud\W.*race)|(spartan.*race)|(horse.*ride)|(horseback)|(bike.*rent)|(bicycle.*rent)|(bicycle.*tour)|(arboretum)|(\Wcamping)|(segway)|(hiking)|(bike tour)|(trail ride)";
/* Adrenaline + The Outdoors + Fun Act */	$constant_expressions[65] = "(\Wziplin)|(zip.*lin)|(jet ski)|(adrenal)|(mountain.*bike.*ride)|(parasail)|(zip line)";
/* Test + Test */							$constant_expressions[66] = "";
/* Adrenaline + Once in a Lifetime */		$constant_expressions[67] = "(skydiv)";
/* Healthy Living + Dancing Feet */			$constant_expressions[68] = "(zumba)|(dance.*fitness)|(fitness.*dance)";
/* Fun Activities + Adrenaline */		    $constant_expressions[69] = "(paintball)|(racing.*experience)|(driving.*experience)|(ferrari)|(lamborghini)|(race.*nascar)|(drive a)|(speedway)";
/* Always Learning + Crafty */		        $constant_expressions[70] = "(glass.*class)|(beading.*class)|(sewing.*class)|(art.*class)|(knit.*class)|(paint.*class)|(jewel.*class)|(paint.*your.*own)|(glass.blowing)|(pottery.*class)|(pottery.*lession)|(weav.*class)|(diy.*pottery)|(\Wdrawing)|(mosaic.*class)|(quilting.*class)|(needle.*class)|(paint.*class)|(class.*paint)|(print.*class)|(ceramics.*class)|(painting.*session)|(painting.*workshop)|(sewing)|(knitting)";
/* Always Learning + Foodie */		        $constant_expressions[71] = "(gardening)|(wine)|(canning)|(sushi.*making)|(pizza.*making)|(food)";
/* Foodie + Fun Activities */				$constant_expressions[72] = "(food tour)";
/* Good for Girls + Fun Activities */		$constant_expressions[73] = "(wedding.*show)|(bridal.*show)|(ticket.*wedding)|(wedding.*expo)|(bridal.*expo)";
/* Road Trip + Date Night */				$constant_expressions[74] = "(escape for 2)|(escape for two)|(retreat for (2|two))";
/* Will Call + Night Life */				$constant_expressions[75] = "(standup comedy)|(standup)|(comedy show)|(comedy club)|(comedy)";
/* Well Groomed + Good for Guys */			$constant_expressions[76] = "(hair.*growth)|(haircut.*\Wmen)|(\Wmen.*hair)";
/* Home and Garden + Gift Ideas */			$constant_expressions[77] = "(\Wflower)|(\Wflowers)|(floral arrangement)";
/* Good for Girls + Night Life */			$constant_expressions[78] = "(girl.*night out)|(ladies.*night)";
/* On the Water + Adrenaline	*/			$constant_expressions[79] = "(watersport)|(whitewater)|(white.water)|(rafting)|(\Wtubing)";
/* On the Water + Fun Activities	*/		$constant_expressions[80] = "(paddle.*board)|(kayak)|(surf.*rent)|(boat.*rent)|(surfing)|(river.*tubing)|(canoe)|(paddleboat)|(surf.*lesson)|(kite.*board)";
/* Up in the Air + Once in a Lifetime */	$constant_expressions[81] = "(helicopter.*ride)|(aerial.*tour)|(helicopter.*tour)|(hot.*air.*balloon)";
/* Up in the Air + Always Learning */		$constant_expressions[82] = "(flight.*lesson)|(flying.*instruction)|(flying.*lesson)|(flight.*instruction)|(flight.*train)";
/* Imbimbe + Always Learning	*/			$constant_expressions[83] = "(mixology)|(bartending)";
/* Good for Girls + Gift Ideas	*/			$constant_expressions[84] = "(sterling silver)|(diamond)";
/* Cultural Pursuits + Fun Act	*/			$constant_expressions[84] = "(historic tour)|(art museum)";
/* Mix and Mingle + Nightlife	*/			$constant_expressions[85] = "(pub crawl)|(nightclub)";
/* Threads + Good for Girls + Gift */		$constant_expressions[86] = "(earring)|(bracelet)|(jewelry)|(pendant)|(necklace)";
/* Music Lover (+ Will Call) */				$constant_expressions[87] = "(live music)|(music)|(rock.*roll)";


$regular_expressions["body_art"] = $constant_expressions[13];
$cat1_auto_fill["body_art"] = 13;


$regular_expressions["pampered___good_for_girls"] = $constant_expressions[60];
$neg_regular_expressions["pampered___good_for_girls"] = $constant_expressions[11] . "|" . $constant_expressions[61];
$cat1_auto_fill["pampered___good_for_girls"] = 12;
$cat2_auto_fill["pampered___good_for_girls"] = 44;

$regular_expressions["well_groomed___good_for_girls"] = $constant_expressions[61];
$neg_regular_expressions["well_groomed___good_for_girls"] = $constant_expressions[11] . "|" . $constant_expressions[12] . "|" . $constant_expressions[60] . "|" . $constant_expressions[45];
$cat1_auto_fill["well_groomed___good_for_girls"] = 10;
$cat2_auto_fill["well_groomed___good_for_girls"] = 44;

$regular_expressions["well_groomed___pampered___good_for_girls"] = "(?=.*(" . $constant_expressions[61] ."))" . "(?=.*(" . $constant_expressions[60] ."))";
$neg_regular_expressions["well_groomed___pampered___good_for_girls"] = $constant_expressions[11] . "|" . $constant_expressions[45];
$cat1_auto_fill["well_groomed___pampered___good_for_girls"] = 12;
$cat2_auto_fill["well_groomed___pampered___good_for_girls"] = 10;
$cat3_auto_fill["well_groomed___pampered___good_for_girls"] = 44;

$regular_expressions["well_groomed___pampered___forever_beautiful___good_for_girls"] = "(?=.*(" . $constant_expressions[61] ."))" . "(?=.*(" . $constant_expressions[60] ."))" . "(?=.*(" . $constant_expressions[11] ."))";
$neg_regular_expressions["well_groomed___pampered___forever_beautiful___good_for_girls"] = $constant_expressions[45];
$cat1_auto_fill["well_groomed___pampered___forever_beautiful___good_for_girls"] = 12;
$cat2_auto_fill["well_groomed___pampered___forever_beautiful___good_for_girls"] = 10;
$cat3_auto_fill["well_groomed___pampered___forever_beautiful___good_for_girls"] = 44;
$cat4_auto_fill["well_groomed___pampered___forever_beautiful___good_for_girls"] = 11;



$regular_expressions["well_groomed___good_for_guys"] = $constant_expressions[76];
$cat1_auto_fill["well_groomed___good_for_guys"] = 10;
$cat2_auto_fill["well_groomed___good_for_guys"] = 45;

$regular_expressions["threads___good_for_girls"] = $constant_expressions[62];
$neg_regular_expressions["threads___good_for_girls"] = $constant_expressions[8] . "|" . $constant_expressions[45] . "|" . $constant_expressions[35];
$cat1_auto_fill["threads___good_for_girls"] = 9;
$cat2_auto_fill["threads___good_for_girls"] = 44;

$regular_expressions["threads___good_for_girls___gift_ideas"] = $constant_expressions[86];
$neg_regular_expressions["threads___good_for_girls___gift_ideas"] = $constant_expressions[8] . "|" . $constant_expressions[45] . "|" . $constant_expressions[35];
$cat1_auto_fill["threads___good_for_girls___gift_ideas"] = 9;
$cat2_auto_fill["threads___good_for_girls___gift_ideas"] = 44;
$cat3_auto_fill["threads___good_for_girls___gift_ideas"] = 40;


$regular_expressions["trim_and_terrific___good_for_girls"] = $constant_expressions[63];
$neg_regular_expressions["trim_and_terrific___good_for_girls"] = $constant_expressions[45] . "|" . $constant_expressions[12] . "|" . $constant_expressions[11];
$cat1_auto_fill["trim_and_terrific___good_for_girls"] = 14;
$cat2_auto_fill["trim_and_terrific___good_for_girls"] = 44;

$regular_expressions["forever_beautiful___pampered"] = "(?=.*(" . $constant_expressions[11] ."))" . "(?=.*(" . $constant_expressions[12] ."))";
$neg_regular_expressions["forever_beautiful___pampered"] = $constant_expressions[61] . "|" . $constant_expressions[45];
$cat1_auto_fill["forever_beautiful___pampered"] = 11;
$cat2_auto_fill["forever_beautiful___pampered"] = 12;

$regular_expressions["forever_beautiful___pampered_2"] = "(?=.*(" . $constant_expressions[11] ."))" . "(?=.*(" . $constant_expressions[60] ."))";
$neg_regular_expressions["forever_beautiful___pampered_2"] = $constant_expressions[61] . "|" . $constant_expressions[45];
$cat1_auto_fill["forever_beautiful___pampered_2"] = 11;
$cat2_auto_fill["forever_beautiful___pampered_2"] = 12;

$regular_expressions["forever_beautiful___well_groomed"] = "(?=.*(" . $constant_expressions[11] ."))" . "(?=.*(" . $constant_expressions[61] ."))";
$neg_regular_expressions["forever_beautiful___well_groomed"] = $constant_expressions[60] . "|" . $constant_expressions[12] . "|" . $constant_expressions[45];
$cat1_auto_fill["forever_beautiful___well_groomed"] = 11;
$cat2_auto_fill["forever_beautiful___well_groomed"] = 10;

$regular_expressions["pampered___well_groomed___good_for_girls"] = "(?=.*(" . $constant_expressions[12] ."|" . $constant_expressions[60] . "))" . "(?=.*(" . $constant_expressions[61] ."))";
$neg_regular_expressions["pampered___well_groomed___good_for_girls"] = $constant_expressions[11] . "|" . $constant_expressions[45];
$cat1_auto_fill["pampered___well_groomed___good_for_girls"] = 11;
$cat2_auto_fill["pampered___well_groomed___good_for_girls"] = 10;
$cat3_auto_fill["pampered___well_groomed___good_for_girls"] = 44;

$regular_expressions["pampered___alternative_healing"] = "(?=.*(" . $constant_expressions[12] ."))" . "(?=.*(" . $constant_expressions[16] ."))";
$neg_regular_expressions["pampered___alternative_healing"] = $constant_expressions[11] . "|" . $constant_expressions[61] . "|" . $constant_expressions[60];
$cat1_auto_fill["pampered___alternative_healing"] = 12;
$cat2_auto_fill["pampered___alternative_healing"] = 16;

$regular_expressions["pampered"] = $constant_expressions[12];
$neg_regular_expressions["pampered"] = $constant_expressions[61] . "|" . $constant_expressions[11] . "|" . $constant_expressions[15];
$cat1_auto_fill["pampered"] = 12;

$regular_expressions["well_groomed"] = $constant_expressions[10];
$neg_regular_expressions["well_groomed"] = $constant_expressions[18] . "|" . $constant_expressions[11] . "|" . $constant_expressions[12] . "|" . $constant_expressions[60] . "|" . $constant_expressions[45];
$cat1_auto_fill["well_groomed"] = 10;

$regular_expressions["forever_beautiful"] = $constant_expressions[11];
$neg_regular_expressions["forever_beautiful"] = $constant_expressions[61] . "|" . $constant_expressions[12] . "|" . $constant_expressions[60] . "|" . $constant_expressions[45] . "|" . $constant_expressions[10] . "|" . $constant_expressions[18] . "|" . $constant_expressions[15] . "|" . $constant_expressions[37];
$cat1_auto_fill["forever_beautiful"] = 11;

$regular_expressions["foodie___fun_activities"] = $constant_expressions[72];
$neg_regular_expressions["foodie___fun_activities"] = $constant_expressions[27];
$cat1_auto_fill["foodie___fun_activities"] = 4;
$cat2_auto_fill["foodie___fun_activities"] = 20;

$regular_expressions["casual_dining___date_night"] = "(?=.*(" . $constant_expressions[1] ."))" . "(?=.*(" . $constant_expressions[27] ."))";
$neg_regular_expressions["casual_dining___date_night"] = "(or four)" . "|" . $constant_expressions[4];
$cat1_auto_fill["casual_dining___date_night"] = 1;
$cat2_auto_fill["casual_dining___date_night"] = 27;

$regular_expressions["quick_bites___date_night"] = "(?=.*(" . $constant_expressions[2] ."))" . "(?=.*(" . $constant_expressions[27] ."))";
$neg_regular_expressions["quick_bites___date_night"] = "(or four)";
$cat1_auto_fill["quick_bites___date_night"] = 2;
$cat2_auto_fill["quick_bites___date_night"] = 27;

$regular_expressions["foodie___date_night"] = "(?=.*(" . $constant_expressions[4] ."))" . "(?=.*(" . $constant_expressions[27] ."))";
$neg_regular_expressions["foodie___date_night"] = "(or four)" . "|" . $constant_expressions[52];
$cat1_auto_fill["foodie___date_night"] = 4;
$cat2_auto_fill["foodie___date_night"] = 27;

$regular_expressions["foodie___date_night___imbibe"] = "(?=.*(" . $constant_expressions[4] ."))" . "(?=.*(" . $constant_expressions[27] ."))" . "(?=.*(" . $constant_expressions[52] ."))";
$neg_regular_expressions["foodie___date_night___imbibe"] = "(or four)";
$cat1_auto_fill["foodie___date_night___imbibe"] = 4;
$cat2_auto_fill["foodie___date_night___imbibe"] = 52;
$cat3_auto_fill["foodie___date_night___imbibe"] = 27;

$regular_expressions["road_trip___date_night"] = "(?=.*(" . $constant_expressions[43] ."))" . "(?=.*(" . $constant_expressions[27] ."))";
$cat1_auto_fill["road_trip___date_night"] = 43;
$cat2_auto_fill["road_trip___date_night"] = 27;

$regular_expressions["road_trip___date_night_2"] = $constant_expressions[74];
$cat1_auto_fill["road_trip___date_night_2"] = 43;
$cat2_auto_fill["road_trip___date_night_2"] = 27;

$regular_expressions["casual_dining"] = $constant_expressions[1];
$neg_regular_expressions["casual_dining"] = $constant_expressions[27] . "|" . $constant_expressions[2] . "|" . $constant_expressions[4] . "|" . $constant_expressions[52] . "|" . $constant_expressions[47] . "|" . $constant_expressions[43] . "|" . $constant_expressions[4] . "|" . $constant_expressions[52];
$cat1_auto_fill["casual_dining"] = 1;

$regular_expressions["quick_bites"] = $constant_expressions[2];
$neg_regular_expressions["quick_bites"] = $constant_expressions[27] . "|" . $constant_expressions[4];
$cat1_auto_fill["quick_bites"] = 2;

$regular_expressions["sweet_tooth"] = $constant_expressions[3];
$cat1_auto_fill["sweet_tooth"] = 3;

$regular_expressions["foodie___imbibe"] = "(?=.*(" . $constant_expressions[4] ."))" . "(?=.*(" . $constant_expressions[52] ."))";
$cat1_auto_fill["foodie___imbibe"] = 4;
$cat2_auto_fill["foodie___imbibe"] = 52;

$regular_expressions["always_learning___imbibe"] = $constant_expressions[83];
$cat1_auto_fill["always_learning___imbibe"] = 8;
$cat2_auto_fill["always_learning___imbibe"] = 52;

$regular_expressions["imbibe"] = $constant_expressions[52];
$neg_regular_expressions["imbibe"] = $constant_expressions[4] . "|" . $constant_expressions[1];
$cat1_auto_fill["imbibe"] = 52;

$regular_expressions["around_the_world"] = $constant_expressions[42];
$cat1_auto_fill["around_the_world"] = 42;

$regular_expressions["road_trip"] = $constant_expressions[43];
$cat1_auto_fill["road_trip"] = 43;

$regular_expressions["self_defense"] = $constant_expressions[46];
$neg_regular_expressions["self_defense"] = "(women)|(girl)|(paint)";
$cat1_auto_fill["self_defense"] = 46;

$regular_expressions["self_defense___good_for_girls"] = "(?=.*(" . $constant_expressions[46] ."))" . "(?=.*(" . $constant_expressions[44] ."))";
$cat1_auto_fill["self_defense___good_for_girls"] = 46;
$cat2_auto_fill["self_defense___good_for_girls"] = 44;

$regular_expressions["self_defense___always_learning"] = "(?=.*(" . $constant_expressions[46] ."))" . "(?=.*(" . $constant_expressions[8] ."))";
$cat1_auto_fill["self_defense___always_learning"] = 46;
$cat2_auto_fill["self_defense___always_learning"] = 8;

$regular_expressions["up_in_the_air___once_in_a_lifetime"] = $constant_expressions[81];
$cat1_auto_fill["up_in_the_air___once_in_a_lifetime"] = 57;
$cat2_auto_fill["up_in_the_air___once_in_a_lifetime"] = 28;

$regular_expressions["up_in_the_air___always_learning"] = $constant_expressions[82];
$cat1_auto_fill["up_in_the_air___always_learning"] = 57;
$cat2_auto_fill["up_in_the_air___always_learning"] = 8;

$regular_expressions["cultural_pursuits___fun_activities"] = $constant_expressions[84];
$cat1_auto_fill["cultural_pursuits___fun_activities"] = 23;
$cat2_auto_fill["cultural_pursuits___fun_activities"] = 20;

$regular_expressions["threads"] = $constant_expressions[9];
$neg_regular_expressions["threads"] = $constant_expressions[20] . "|" . $constant_expressions[64];
$cat1_auto_fill["threads"] = 9;

$regular_expressions["cooking"] = "(?=.*(" . $constant_expressions[8] ."))" . "(?=.*(" . $constant_expressions[47] . "))";
$cat1_auto_fill["cooking"] = 47;




$regular_expressions["expos___music_lover"] = "(?=.*(" . $constant_expressions[49] ."))" . "(?=.*(" . $constant_expressions[48] . "))";
$cat1_auto_fill["expos___music_lover"] = 48;
$cat2_auto_fill["expos___music_lover"] = 49;




$regular_expressions["always_learning___music_lover"] = "(?=.*(" . $constant_expressions[8] ."))" . "(?=.*(" . $constant_expressions[49] . "))";
$cat1_auto_fill["always_learning___music_lover"] = 8;
$cat2_auto_fill["always_learning___music_lover"] = 49;

$regular_expressions["music_lover"] = $constant_expressions[49];
$cat1_auto_fill["music_lover"] = 49;


$regular_expressions["toyland"] = $constant_expressions[50];
$cat1_auto_fill["toyland"] = 50;

$regular_expressions["getting_around"] = $constant_expressions[51];
$cat1_auto_fill["getting_around"] = 51;

$regular_expressions["mix_mingle"] = $constant_expressions[53];
$cat1_auto_fill["mix_mingle"] = 53;

$regular_expressions["dinner_time"] = $constant_expressions[54];
$cat1_auto_fill["dinner_time"] = 54;

$regular_expressions["well_caffeinated"] = $constant_expressions[55];
$cat1_auto_fill["well_caffeinated"] = 55;

$regular_expressions["adrenaline___on_the_water"] = $constant_expressions[79];
$cat1_auto_fill["adrenaline___on_the_water"] = 56;
$cat2_auto_fill["adrenaline___on_the_water"] = 26;

$regular_expressions["fun_activities___on_the_water"] = $constant_expressions[80];
$cat1_auto_fill["fun_activities___on_the_water"] = 56;
$cat2_auto_fill["fun_activities___on_the_water"] = 20;

$regular_expressions["on_the_water"] = $constant_expressions[56];
$cat1_auto_fill["on_the_water"] = 56;

$regular_expressions["up_in_the_air"] = $constant_expressions[57];
$cat1_auto_fill["up_in_the_air"] = 57;

$regular_expressions["hole_in_one"] = $constant_expressions[58];
$neg_regular_expressions["hole_in_one"] = "(mini.golf)|(minigolf)";
$cat1_auto_fill["hole_in_one"] = 58;

$regular_expressions["under_the_sea"] = $constant_expressions[59];
$cat1_auto_fill["under_the_sea"] = 59;

$regular_expressions["the_outdoors___fun_activities___adrenaline"] = $constant_expressions[65];
$cat1_auto_fill["the_outdoors___fun_activities___adrenaline"] = 24;
$cat2_auto_fill["the_outdoors___fun_activities___adrenaline"] = 20;
$cat3_auto_fill["the_outdoors___fun_activities___adrenaline"] = 26;

$regular_expressions["the_outdoors___fun_activities"] = $constant_expressions[64];
$cat1_auto_fill["the_outdoors___fun_activities"] = 24;
$cat2_auto_fill["the_outdoors___fun_activities"] = 20;

$regular_expressions["crafty___always_learning"] = $constant_expressions[70];
$cat1_auto_fill["crafty___always_learning"] = 8;
$cat2_auto_fill["crafty___always_learning"] = 38;

$regular_expressions["photographic___always_learning"] = "(?=.*(" . $constant_expressions[37] ."))" . "(?=.*(" . $constant_expressions[8] ."))";
$cat1_auto_fill["photographic___always_learning"] = 8;
$cat2_auto_fill["photographic___always_learning"] = 37;

$regular_expressions["healthy_living"] = $constant_expressions[15];
$neg_regular_expressions["healthy_living"] = "(women)" . "|" . $constant_expressions[21] . "|" . $constant_expressions[6] . "|" . $constant_expressions[12] . "|" . $constant_expressions[11] . "|" . $constant_expressions[46];
$cat1_auto_fill["healthy_living"] = 15;

$regular_expressions["healthy_living___dancing_feet"] = $constant_expressions[68];
$cat1_auto_fill["healthy_living___dancing_feet"] = 15;
$cat2_auto_fill["healthy_living___dancing_feet"] = 21;

$regular_expressions["healthy_living___good_for_girls"] = "(?=.*(" . $constant_expressions[15] ."))" . "(?=.*(" . $constant_expressions[44] ."))";
$cat1_auto_fill["healthy_living___good_for_girls"] = 15;
$cat2_auto_fill["healthy_living___good_for_girls"] = 44;

$regular_expressions["fun_activities___adrenaline"] = $constant_expressions[69];
$cat1_auto_fill["fun_activities___adrenaline"] = 20;
$cat2_auto_fill["fun_activities___adrenaline"] = 26;

$regular_expressions["once_in_a_lifetime___adrenaline"] = $constant_expressions[67];
$cat1_auto_fill["once_in_a_lifetime___adrenaline"] = 28;
$cat2_auto_fill["once_in_a_lifetime___adrenaline"] = 26;

$regular_expressions["fun_activities___good_for_girls"] = $constant_expressions[73];
$cat1_auto_fill["fun_activities___good_for_girls"] = 20;
$cat2_auto_fill["fun_activities___good_for_girls"] = 44;

$regular_expressions["alternative_healing"] = $constant_expressions[16];
$cat1_auto_fill["alternative_healing"] = 16;

$regular_expressions["seeing_clearly"] = $constant_expressions[17];
$cat1_auto_fill["seeing_clearly"] = 17;

$regular_expressions["medical_and_dental"] = $constant_expressions[18];
$neg_regular_expressions["medical_and_dental"] = $constant_expressions[10];
$cat1_auto_fill["medical_and_dental"] = 18;

$regular_expressions["fun_activities"] = $constant_expressions[20];
$cat1_auto_fill["fun_activities"] = 20;

$regular_expressions["dancing_feet"] = $constant_expressions[21];
$cat1_auto_fill["dancing_feet"] = 21;





$regular_expressions["will_call___nightlife"] = $constant_expressions[75];
$cat1_auto_fill["will_call___nightlife"] = 22;
$cat2_auto_fill["will_call___nightlife"] = 19;

$regular_expressions["will_call___cultural_pursuits"] = "(?=.*(" . $constant_expressions[22] ."))" . "(?=.*(" . $constant_expressions[23] ."))";
$cat1_auto_fill["will_call___cultural_pursuits"] = 22;
$cat2_auto_fill["will_call___cultural_pursuits"] = 23;

$regular_expressions["will_call___music_lover"] = "(?=.*(" . $constant_expressions[22] ."))" . "(?=.*(" . $constant_expressions[87] ."))";
$cat1_auto_fill["will_call___music_lover"] = 22;
$cat2_auto_fill["will_call___music_lover"] = 49;

$regular_expressions["will_call"] = $constant_expressions[22];
$neg_regular_expressions["will_call"] = $constant_expressions[23] . "|" . $constant_expressions[75];
$cat1_auto_fill["will_call"] = 22;

$regular_expressions["fun_activities___cultural_pursuits"] = $constant_expressions[84];
$cat1_auto_fill["fun_activities___cultural_pursuits"] = 20;
$cat2_auto_fill["fun_activities___cultural_pursuits"] = 23;

$regular_expressions["mix_and_mingle___nightlife"] = $constant_expressions[85];
$cat1_auto_fill["mix_and_mingle___nightlife"] = 19;
$cat2_auto_fill["mix_and_mingle___nightlife"] = 53;


$regular_expressions["nightlife"] = $constant_expressions[19];
$cat1_auto_fill["nightlife"] = 19;

$regular_expressions["good_for_girls___nightlife"] = $constant_expressions[78];
$cat1_auto_fill["good_for_girls___nightlife"] = 19;
$cat2_auto_fill["good_for_girls___nightlife"] = 53;


$regular_expressions["sporting_life"] = $constant_expressions[25];
$cat1_auto_fill["sporting_life"] = 25;

$regular_expressions["automotive"] = $constant_expressions[30];
$cat1_auto_fill["automotive"] = 30;

$regular_expressions["home_and_garden___gift_ideas"] = $constant_expressions[77];
$cat1_auto_fill["home_and_garden___gift_ideas"] = 31;
$cat2_auto_fill["home_and_garden___gift_ideas"] = 40;


$regular_expressions["home_and_garden"] = $constant_expressions[31];
$cat1_auto_fill["home_and_garden"] = 31;

$regular_expressions["handyman"] = $constant_expressions[32];
$neg_regular_expressions["handyman"] = "(bicycle)|(shoe)|(boot)|(bike)" . "|" . $constant_expressions[34];
$cat1_auto_fill["handyman"] = 32;

$regular_expressions["squeaky_clean"] = $constant_expressions[33];
$neg_regular_expressions["squeaky_clean"] = $constant_expressions[32];
$cat1_auto_fill["squeaky_clean"] = 33;

$regular_expressions["general_services"] = $constant_expressions[34];
$cat1_auto_fill["general_services"] = 34;

$regular_expressions["gadgets_and_gear"] = $constant_expressions[35];
$cat1_auto_fill["gadgets_and_gear"] = 35;

$regular_expressions["bookish"] = $constant_expressions[36];
$cat1_auto_fill["bookish"] = 36;

$regular_expressions["groceries"] = $constant_expressions[5];
$cat1_auto_fill["groceries"] = 5;

$regular_expressions["photographic"] = $constant_expressions[37];
$neg_regular_expressions["photographic"] = $constant_expressions[8];
$cat1_auto_fill["photographic"] = 37;

$regular_expressions["crafty"] = $constant_expressions[38];
$cat1_auto_fill["crafty"] = 38;

$regular_expressions["pet_lover"] = $constant_expressions[39];
$cat1_auto_fill["pet_lover"] = 39;

$regular_expressions["moms_and_babies"] = $constant_expressions[7];
$cat1_auto_fill["moms_and_babies"] = 7;

$regular_expressions["gift_ideas"] = $constant_expressions[40];
$cat1_auto_fill["gift_ideas"] = 40;

$regular_expressions["always_learning"] = $constant_expressions[8];
$neg_regular_expressions["always_learning"] = $constant_expressions[37] . "|" . $constant_expressions[15];
$cat1_auto_fill["always_learning"] = 8;

$regular_expressions["good_for_kids"] = $constant_expressions[6];
$cat1_auto_fill["good_for_kids"] = 6;

$regular_expressions["foodie"] = $constant_expressions[4];
$neg_regular_expressions["foodie"] = $constant_expressions[8] . "|" . $constant_expressions[52];
$cat1_auto_fill["foodie"] = 4;



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
echo "<span id='matched_count'>X</span>";
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
	$row["classifier_id"] = $classifier_id;
	$row["time"] = date('Y-m-d H:i:s', time());

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
	$row["classifier_id"] = $classifier_id;
	$row["time"] = date('Y-m-d H:i:s', time());

	$row["is_nation"] = 1;

	$memcache->set($id, $row, false, $cache_life);
      } else if (preg_match("/^recommend_/", $key) && preg_match("/([0-9]+)/", $key, $match)) {
	$id = "category:".$match[0];
	$row = $memcache->get($id);
	$row["classifier_id"] = $classifier_id;
	$row["time"] = date('Y-m-d H:i:s', time());

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

  echo "<form id=categoryform action='/tools/regex_classifier_tool.php' method=post align=center>\n";
  echo "Regex: <input type='text' name='regex' $regex_value size=70 /><BR>\n";
  echo "And-Regex: <input type='text' name='andregex' $and_regex_value size=70 /><BR>\n";
  echo "&nbsp; Neg-regex: <input type='text' name='negregex' $neg_regex_value size=40 /><BR>\n";

  echo "<select id=regexselector name=doc_section>\n";
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


  echo "<input id=regexbutton name='regex_search' type=submit value='Apply regex' />\n";



  if (isset($_POST["regex"]) && strlen($_POST["regex"]) > 0) {

    echo "<BR>\n";
    echo "<div style=\"float:right\"><br>\n";
    echo "Categories <span id=\"set-category-warning\" style=\"color:red;display:none\">&nbsp;(Please specify one or more valid categories - see list below)</span><BR>\n";
    echo "1. <INPUT id=\"category_id1\" $cat1_value type=\"text\" name=\"category_id1\" autocomplete=\"array:categories\"><BR>\n";
    echo "2. <INPUT id=\"category_id2\" $cat2_value type=\"text\" name=\"category_id2\" autocomplete=\"array:categories\"><BR>\n";
    echo "3. <INPUT id=\"category_id3\" $cat3_value type=\"text\" name=\"category_id3\" autocomplete=\"array:categories\"><BR>\n";
    echo "4. <INPUT id=\"category_id4\" $cat4_value type=\"text\" name=\"category_id4\" autocomplete=\"array:categories\"><BR>\n";
    echo "<BR><input id=categorybutton type=\"submit\" name=\"submit_categories\" value=\"Submit\">\n";
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
	echo "\t<tr onclick='toggleCheckbox(" . $row["id"] . ")'>\n";
	echo "\t\t<td width=600>\n";

	$image_url = "http://dealupa_images.s3.amazonaws.com/" . sha1($row["image_url"]) . "_small";

	echo "<a href='http://50.57.43.108/tools/image_fixer.php?deal_id=$id' target=_fixer><img src=\"".$image_url."\" width=150px align=right></a>\n";
	echo "<input type=\"checkbox\" id=\"checkbox_".$row["id"]."\"  name=\"deal_id_".$row["id"]."\" checked=checked> &nbsp;\n";
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
	//echo "<b>Text</b>: ".$row["text"]."<BR>\n";
	echo "\t\t</td>\n";
	echo "\t</tr>\n";
      }
    }
    echo "</table>\n";
    echo "<h1><span id=regex_match_count>$matches_regex_count</span> deals match the regular expression</h1>\n";

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
	  $image_url = "http://dealupa_images.s3.amazonaws.com/" . sha1($row["image_url"]) . "_small";
      echo "<a href='http://50.57.43.108/tools/image_fixer.php?deal_id=$id' target=_fixer><img src=\"".$image_url."\" width=150px align=right></a><BR>\n";
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
