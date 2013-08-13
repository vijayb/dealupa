<?php

require_once("array_constants.php");

function generate_onboarding_email($first_name, $email, $edition, $zipcode) {

	global $cities;

	if ($first_name != "") {
		$greeting = "Hi $first_name,";
	} else {
		$greeting = "Hi there,";
	}
	
	
	$reset_link = generate_password_link($email);
	$logged_in_link = generate_logged_in_link($email);
	$set_preferences_link = generate_settings_link($email);
	
	if ($edition != 1) {
		$near_you = "in " . $cities[$edition];
	} else if ($edition == 1 && isset($zipcode) && strlen($zipcode) == 5) {
		$near_you = "near " . $zipcode;
	} else if ($edition == 1 && (!isset($zipcode) || strlen($zipcode) != 5)) {
		$near_you = "near you";
	}

	$html = <<<HTML

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<center>

<table border=0 width=585 cellpadding=0 cellspacing=0 bgcolor=#F9F4E0>

	<tr>
		<td>
			<a href="{$logged_in_link}"><img src="http://dealupa.com/email_images/email_top_wood.jpg"></a>
		</td>
	</tr>
	
	<tr><td height="40px"></td></tr>
	
	<tr>
		<td style="font-family:sans-serif; text-align:center; color:#3a1500">
			<span style="font-size:28px">We're ready to start sending you the<br>only daily deal email you'll ever need.</span>
			<br><br>
			<span style="font-size:17px">All the best deals {$near_you}, from the web's top deal sites<br>and matched to <i>your</i> interests.</span>
		</td>
	</tr>
	
	<tr>
		<td style="font-family:sans-serif; color:#3a1500; padding:60px;">
			<span style="font-size:13px">
{$greeting}

<br><br>

Since you signed up with us, we've been working hard to build the best way to discover daily deals you'll actually <i>like.</i> <b><a target=_dealupa href="{$set_preferences_link}" style="color:#BC5D33;">Tell us what you're into</a>, and we'll send you the hand-picked deals from around the web that you're bound to like.</b> It took us a few months (and a few name changes!) to get here, but we're confident you'll like what you see.


<br><br>

So first thing, <a target=_dealupa href="{$set_preferences_link}" style="color:#BC5D33;">tell us what kind of things you're into</a>. On that same page, you can also control how often you’ll get an email.

<br><br>

We also recommend you take a moment to <a href="{$reset_link}" style="color:#BC5D33;">create a password</a> for your account, so that you’ll be able to log in to Dealupa wherever you are.

<br><br>

It’s very important to us that you have a good experience using Dealupa, so if you have any feedback or want to see new features, please email us at founders@dealupa.com

<br><br>

If you like Dealupa, please share it with your friends.

<br><br>

Thanks, and welcome to Dealupa!

<br><br>

Vijay & Sanjay

<br><br><br>

<center>

	<a href="{$logged_in_link}" style="color:#BC5D33; text-decoration:none; padding:15px; font-size:26px; font-weight:bold;">Go to Dealupa</a>

	<br><br><br>

	<span style="font-size:11px">To unsubscribe, click <a target=_dealupa href="{$set_preferences_link}" style="color:#BC5D33; text-decoration:none;">here</a> and scroll to the bottom of the page.</span>

</center>


<br><br>
			</span>
		</td>
	</tr>

</table>

</center>

</body>

HTML;

	return $html;

}













function generate_welcome_email($first_name, $email) {

	if ($first_name != "") {
		$greeting = "Hi $first_name,";
	} else {
		$greeting = "Hi there,";
	}
	
	
	$reset_link = generate_password_link($email);
	$logged_in_link = generate_logged_in_link($email);

	$html = <<<HTML

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<center>

<table border=0 width=585 cellpadding=0 cellspacing=0 bgcolor=#F9F4E0>

	<tr>
		<td>
			<a href="{$logged_in_link}"><img src="http://dealupa.com/email_images/email_top_wood.jpg"></a>
		</td>
	</tr>
	
	<tr><td height="40px"></td></tr>
	
	<tr>
		<td style="font-family:sans-serif; text-align:center; color:#3a1500">
			<span style="font-size:24px">The best daily deals hand-picked<br>from around the web&#8212;in a single email.</span>
		</td>
	</tr>
	
	<tr>
		<td style="font-family:sans-serif; color:#3a1500; padding:60px;">
			<span style="font-size:13px">
{$greeting}

<br><br>

We built <a href="{$logged_in_link}" style="color:#BC5D33;">Dealupa</a> to help you reduce the problem of deal overload. Nobody likes getting a dozen emails every day from a dozen different deal sites. We’ll do the hard work for you by scouring the web to find deals and hand-picking the best ones to show you in a single email.

<br><br>

We will start out by emailing you once a day with deals in your area that match your preferences. If you’d like to change your preferences, such as your location or which types of deals you’re interested in, please visit your settings page.

<br><br>

On the settings page you can also control how often you’ll get an email. We also recommend you take a moment to <a href="{$reset_link}">create a password</a> for your account, so that you’ll be able to log in to Dealupa wherever you are.

<br><br>

It’s very important to us that you have a good experience using Dealupa, so if you have any feedback or want to see new features, please email us at founders@dealupa.com

<br><br>

If you like Dealupa, please share it with your friends.

<br><br>

Thanks, and welcome to Dealupa!

<br><br>

Vijay & Sanjay

<br><br><br>

<center>
<a href="{$logged_in_link}" style="color:#BC5D33; text-decoration:none; padding:15px; font-size:26px; font-weight:bold;">Go to Dealupa</a>
</center>
			</span>
		</td>
	</tr>

</table>

</center>

</body>

HTML;

	return $html;

}


?>