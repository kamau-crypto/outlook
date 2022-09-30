
<?php
//
//Start a session
session_start();
//
//Resp;ve the refence to the Twilio php autoloader
require_once './vendor/autoload.php';
//
//Instantiate the Alias to the app, the Namespace Rest, and the Class Client
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;
//
class twilio extends Twilio\Rest\Client
{
    //
    // Obtain the account ssid, the account token, and the account phone number 
    // from the twilio console. You must have logged in to twilio to obtain these
    //Get the twilio ACCOUNT_SID
    const sid = "";
    //
    //Get the account AUTH_TOKEN
    const token = "";
    //
    //Get the account PHONE_NUMBER
    const phone = "";

    //
    function __construct()
    {
        parent::__construct(self::sid, self::token);
    }
    //
    public function send_message(string $to, string $subject, string $body): string /*'ok'|error*/
    {
        //
        //Combine the body and the subject of the message
        $text = "\n" . $subject . "\n" . $body;
        //
        //Instantiate a new instance of the Client class to enable senfing the messaged
        //$twilio = new Client(self::sid, self::token);
        //
        //Create twilio messagess and send them using the parameters obtained from the fule
        /* From the $TWILIO->messages->create indicates that there wihin the clas client, accessible
        usiing two parameters, namely the client and the token, there is a method messaeges with the
        create closure that takes two arguements, the reciever, and an array of the message and the 
        receoients phone number,i.e., <string, Array<key:value>> */
        //
        try {
            //
            //Ignore the return value if successful
            /*$message =*/
            $this->messages->create(
                //
                //The phone address to send the message to
                $to,
                [   //
                    //The body of the message
                    "body" => $text,
                    //
                    //The phone where the message is coming from
                    "from" => self::phone
                ]
            );
            return 'ok';
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
}

?>