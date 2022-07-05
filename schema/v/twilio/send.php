<?php
//
//Resolve the reference to the twilio class
require_once './twilio.php';
//
//Instantiate the twilio
$twilio = new twilio();
//
//Get a random number generator
$rand = (rand(1000, 9999));
//
//Obtain the body of the message
$body = 'Your OTP password is ' . $rand . ' it is valid for twenty minutes';
//
//Send the sms
echo $twilio->send_message('+25471555770', 'OTP', $body);
