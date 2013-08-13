<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


require("db_user.php");
require("helpers.php");
 
/*
 * Create the body of the message (a plain-text and an HTML version).
 * $text is your plain-text email
 * $html is your html version of the email
 * If the reciever is able to view html emails then only the html
 * email will be displayed
 */

$email = $_GET["email"];

$reset_link = generate_password_link($email);

if ($reset_link == "0") {
	echo "-1";
	exit();
}

$text = "Link to reset password: $reset_link";
$html = <<<EOM
<html>
  <head></head>
  <body>
	<a href="$reset_link">Reset your password</a>
  </body>
</html>
EOM;

sendEmail($email, "Dealupa <founders@dealupa.com>", "Reset your Dealupa password", $html);

echo("1");

?>