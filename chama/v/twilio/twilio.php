
<?php
//
//Start a session
session_start();
//
//Resp;ve the refence to the Twilio php autoloader
require_once './vendor/autoload.php';
//
//Instantiate the Alias to the app, the Namespace Rest, and the Class Client
use Twilio\Rest\Client;
//
class twilio
{
    //
    // Obtain the account ssid, the account token, and the account phone number 
    // from the twilio console. You must have logged in to twilio to obtain these
    //Get the twilio ACCOUNT_SID
    const sid = "ACa0da98876ae7c59dea6fd839c0543643";
    //
    //Get the account AUTH_TOKEN
    const token = "d3fc09c9b83539ac70859fd94f97df88";
    //
    //Get the account PHONE_NUMBER
    const phone = "+18593764537";
    //
    function __construct(){
    }
    //
    public function send_message(string $to, string $body): bool
    {
        //
        //Get the reciever's phone number
        $to = 'to';
        //
        //Get the body of the message
        $body = 'message';
        //
        //Instantiate a new instance of the Client class to enable senfing the messaged
        $twilio = new Client(sid, token);
        //
        //Create twilio messagess and send them using the parameters obtained from the fule
        /* From the $TWILIO->messages->create indicates that there wihin the clas client, accessible
        usiing two parameters, namely the client and the token, there is a method messaeges with the
        create closure that takes two arguements, the reciever, and an array of the message and the 
        receoients phone number,i.e., <string, Array<key:value>> */
        $message = $twilio->messages->create(
            //
            //The phone address to send the message to
            $to,
            [   //
                //The body of the message
                "body" => $body,
                //
                //The phone where the message is coming from
                "from" => phone
            ]
        );
        //
        //Determine if the message was successful or not.If successful return 
        //true and if it failed return return false
        return $this->status($message);
    }
    //
    //inspect the returned message if the delivery was successful and return false
    //if otherwise.
    function status($message):bool{
    
    }
}

?>