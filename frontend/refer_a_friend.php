<?php

require("db_user.php");
require("helpers.php");


if (!isset($_GET["user_id"]) || !isset($_GET["token"])) {
	exit();
}

$user_id = $_GET["user_id"];
$token = $_GET["token"];

if (!check_token($user_id, $token)) {
	exit();
}

$sql = "SELECT email FROM Users WHERE user_id=$user_id";
$result = mysql_query($sql, $users_con);
$row = mysql_fetch_assoc($result);
$sender_email = $row["email"];

$emails = array();
$emails = json_decode($_GET["emails"]);
$email_text = $_GET["email_text"];

$email_sent_successfully = array();


for ($i = 0; $i < count($emails); $i++) {

	// First, check if the email address is already signed up

	$sql = "SELECT email FROM Users WHERE email LIKE '" . $emails[$i] . "'";
	$result = mysql_query($sql, $users_con);

	if (mysql_num_rows($result) == 0) {
		$recipient = $emails[$i];

 		$html = <<<HTML

		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		</head>
		<body>
		Your friend ($sender_email) has recommended Dealupa to you! Dealupa is a fresh new way to browse daily deals on the web. Tell Dealupa a bit about what you're into, and we'll bring you high-quality deals you'll <i>actually</i> like.
		<br><br>
		Your friend says:
		<br>
		{$email_text}
		<br><br>
		<a href="{$domain_ac}/?referrer={$user_id}&email={$recipient}">Take me to Dealupa!</a>
		<br><br>
		</body>
		</html>

HTML;

		sendEmail($recipient, "\"$sender_email\" <$sender_email>", "Your friend ($sender_email) wants you to try Dealupa", $html);
		array_push($email_sent_successfully, $recipient);
	}

		
}

echo(json_encode($email_sent_successfully));

?>
