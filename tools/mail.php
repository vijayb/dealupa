<?php
 
include_once "lib/swift_required.php";
 
/*
 * Create the body of the message (a plain-text and an HTML version).
 * $text is your plain-text email
 * $html is your html version of the email
 * If the reciever is able to view html emails then only the html
 * email will be displayed
 */ 
$text = "Hi!\nHow are you? PHP Test\n";
$html = <<<EOM
<html>
  <head></head>
  <body>
    <p>Testing!<br>
       <b>Lets see if this works!!!!!!!!!!!!</b><br>
	   <img src="http://deel.io/images/company_13_gray.png">
    </p>
  </body>
</html>
EOM;
 
 
 
// This is your From email address
$from = array('deals@deel.io' => 'Deelio\'s delivery service');
// Email recipients
$to = array(
//  'vijayb@gmail.com'=>'Vijay Boyapati',
  'sanjay@gmail.com'=>'Sanjay Mavinkurve'
);
// Email subject
$subject = 'Test sendgrid from PHP';
 
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
  echo 'Message sent out to '.$recipients.' users';
}
// something went wrong =(
else
{
  echo "Something went wrong - ";
  print_r($failures);
}