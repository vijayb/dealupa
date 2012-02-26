<?php
 
include_once "swift/swift_required.php";
 
require("db_user.php");

 
/*
 * Create the body of the message (a plain-text and an HTML version).
 * $text is your plain-text email
 * $html is your html version of the email
 * If the reciever is able to view html emails then only the html
 * email will be displayed
 */

$email = $_GET["email"];



// IF USER EXISTS, GET ID

$query = "SELECT user_id FROM Users WHERE email='$email'";

$result = mysql_query($query);
$num_rows = mysql_num_rows($result);

if ($num_rows == 1) {
	$row = mysql_fetch_assoc($result);
	$user = $row['user_id'];
} else {
	echo "-1";
	exit();
}


// IF USER EXISTS, UPDATE PASSWORD

$password = rand(0, 100000);
$password_hash = hash('sha256', $password);

$query = "UPDATE Users SET password_hash='" . $password_hash . "' WHERE user_id=" . $user;

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
} else {
}


 
$text = "Your new password is: $password";
$html = <<<EOM
<html>
  <head></head>
  <body>
    <p>
	<img src="http://dealupa.com/images/logo_orange.png">
	<br><br>
	Your new password is: <b>$password</b>
    </p>
  </body>
</html>
EOM;
 
 
 
// This is your From email address
$from = array('deals@deel.io' => 'Deelio\'s delivery service');
// Email recipients
$to = array(
//  'vijayb@gmail.com'=>'Vijay Boyapati',
  $email
);
// Email subject
$subject = 'Your new Dealupa password';
 
// Login credentials
$username = 'deelio';
$password = 'cheapass';
 
// Setup Swift mailer parameters
$transport = Swift_SmtpTransport::newInstance('smtp.sendgrid.net', 587);
$transport->setUsername($username);
$transport->setPassword($password);
$swift = Swift_Mailer::newInstance($transport);
 
// Create a message (subject)
$message = new Swift_Message($subject);
 
// attach the body of the email
$message->setFrom($from);
$message->setBody($html, 'text/html');
$message->setTo($to);
$message->addPart($text, 'text/plain');
 
// send message 
if ($recipients = $swift->send($message, $failures))
{
  // This will let us know how many users received this message
  echo($recipients);
}
// something went wrong =(
else
{
  echo "Something went wrong - ";
  print_r($failures);
}