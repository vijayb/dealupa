<?php
set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once("check_email.php");
require_once("db_user.php");

$emails = getEmails($users_con);

$count = 0;
foreach ($emails as $user_id => $email) {
  if ($count >= 1000) {
    break;
  }

  echo $email."<BR>\n";


  // the email to validate  
  //$email = "vijayb@gsdfsdmail.com";
  // an optional sender  
  $sender = 'thewire1978@hotmail.com';  
  // instantiate the class  
  $SMTP_Valid = new SMTP_validateEmail();  
  // do the validation  
  $result = $SMTP_Valid->validate($email, $sender);  
  // view results  
  //var_dump($result);  
  echo $email.' is ';
  if ($result) {
    setBogus(0, $user_id, $users_con);
    echo "valid<BR>\n";
  } else {
    setBogus(1, $user_id, $users_con);
    echo "INvalid<BR>\n";
  }

  $count++;
}

exit;


function setBogus($bogus, $user_id, $users_con) {
  $sql = "update Users set bogus=$bogus where user_id=$user_id";
  echo "$sql\n";

  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die('Invalid query: ' . mysql_error());
  }
}


function getEmails($users_con) {
  $emails = array();

  $sql = "select user_id, email from Users where bogus is null";

  echo "$sql<BR>\n";
  $result = mysql_query($sql, $users_con);
  if (!$result) {
    die('Invalid query: ' . mysql_error());
  }

  while ($row = @mysql_fetch_assoc($result)) {
    $emails[$row['user_id']] = $row['email'];
  }
  
  return $emails;
}

?>  