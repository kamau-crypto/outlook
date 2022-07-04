<?php
//
//Resolve reference to the mailer
require_once './mailer.php';
//
$mail = new mailer();
//
//Compose the email
$compose = $mail->send_message("kamaupeter343@gmail.com", "Hi, this is a sample using the php mailer class", "PETER", "TEST");
//
//Send the email address
$send = $mail->send_email();
echo "email sent successfully";
