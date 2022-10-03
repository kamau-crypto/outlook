<?php
//
//Resolve reference to the mailer
require_once './mailer.php';
//
$mail = new mailer();
//
//Compose the email
echo $mail->send_email("kamaupeter3343@yahoo.com", "TEST", "Hi, this is a sample using the php mailer class", "PETER", "");
//
