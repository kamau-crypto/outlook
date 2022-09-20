
<?php
//
//Resp;ve the refence to the Twilio php autoloader
require_once './vendor/autoload.php';
//
//Get the reference to the php dot env files
require_once '../../../dotenv/vendor/autoload.php';
//
//Instantiate the Alias to the app, the Namespace Rest, and the Class Client
use Twilio\Rest\Client;
//Instantiate the dotenv class
use Dotenv\Dotenv;
//
//The variables should not chage once they are set
$dotenv = Dotenv::createImmutable(__DIR__);
//
//Load the required data
$dotenv->load();
//
//Explicit validation of environment variables
$dotenv->required('TWILIO_ACCOUNT_SID', 'TWILIO_AUTH_TOKEN', 'TWILIO_PHONE_NUMBER');
//
//Get a random number generator
$rand = (rand(1000, 9999));
// Obtain the account ssid, the account token, and the account phone number from the
// twilio console. You must have logged in to twilio to obtain these
//Get the twilio ACCOUNT_SID
$sid = $_ENV['TWILIO_ACCOUNT_SID'];
//
//Get the account AUTH_TOKEN
$token = $_ENV['TWILIO_AUTH_TOKEN'];
//
//Get the account PHONE_NUMBER
$phone = $_ENV['TWILIO_PHONE_NUMBER'];
//
//Obtain the phone number of the recipient
$to = '+254715555770';
//
//Obtain the body of the message
$body = 'Your OTP password is ' . $rand . ' it is valid for twenty minutes';
//
//Instantiate a new instance of the Client class to enable senfing the messaged
$twilio = new Client($sid, $token);
//
//Create twilio messagess and send them using the parameters obtained from the fule
/* From the $TWILIO->messages->create indicates that there wihin the clas client, accessible
usiing two parameters, namely the client and the token, there is a method messaeges with the
create closure that takes two arguements, the reciever, and an array of the message and the 
receoients phone number,i.e., <string, Array<key:value>> */
$message = $twilio->messages
    ->create(
        $to, // to
        [
            "body" => $body,
            "from" => $phone,
            //
            //Here a webhook is needed to get the response from the email once it is sent
            //"statusCallback" => "http://206.189.207.206/test/call__back.php"
        ]
    );
?>