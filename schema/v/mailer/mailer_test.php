<?php
//
//Fix the references to the php mailer class
use PHPMailer\PHPMailer\PHPMailer;
//
//Fix the reference to the php mailer's SMTP class
use PHPMailer\PHPMailer\SMTP;
//
//Introduce the Exception handler class
use PHPMailer\PHPMailer\Exception;
//
//The PHPMailer Abstract class reference defined through composer
require_once "vendor/autoload.php";
//
//The mailer class that supports sending of emails to the clients via an SMTP 
//server.
class mailer
{
    //
    //The function constructor that supports the development of more and more
    function __construct()
    {
        //
        //Instantiate the PHPMailer method and passing a value of true allows 
        //for exception handling
        $this->mailer = new PHPMailer(true);
        //
        //Set up the system configuration
        $this->server_config();
        //
        //Set up the administration addresses
        $this->set_addresses();
    }
    //
    //The configuration details to the server that will open a connection to the
    // server to enable us to send emails to our clients
    private function server_config()
    {
        //
        //Set the SMTP DEBUGGER that allows for verbose(detailed) error messages
        // for better debugging
        $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        //
        //Instruct the mailer to only send emails using SMTP
        $this->mailer->isSMTP();
        //
        //Set the host the SMTP server will use to send the emails through.
        //Gmail usually uses the ('smtp.gmail.com') as the server default.
        $this->mailer->Host = 'smtp.gmail.com';
        //
        //Enable SMTP Authentication
        $this->mailer->SMTPAuth = true;
        //
        //Set the server usernmae. When using gmail as the HOST server, 
        //this parameter is usually the google email address.
        $this->mailer->Username = "kamaupeter343@gmail.com";
        //
        //Set the server password.
        $this->mailer->Password = 'ggswhvvwynkvjspo';
        //
        //Enable TLS encryption for the server
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        //
        //Define the server port to use for this connection. Google uses port 
        //587 when SMTP is set to use TLS encryption. 
        //When not using TLS encyrption, the port that is used is 465
        $this->mailer->Port = 587;
    }
    //
    //This method allows for the specification of the senders and the recievers 
    //for the email address namely the sender, the reciever, and the CC
    public function set_addresses()/* Array<sender:string,Array<recievers:string>> */
    {
        //
        //Set the email sender. Two paramaters, the email and name to identify 
        //the sender of the email.i.e. ("mutalldata@gmail.com","MUTALL DATA")
        $this->mailer->setFrom("@gmail.com", "PETER");
        //
        //Set the email to reply to. The email and the reply keyword are the two
        // parameters in this section
        $this->mailer->addReplyTo("kamaupeter343@gmail.com", "Reply");
        //
        //The CC to the sent email.
        $this->mailer->addCC("kamaupeter343@outlook.com");
        //
        //The BCC to the sent email
        //$this->mailer->addBcc("");
    }
    //
    //Allows a user to add attachments while composing the email.
    public function send_message(
        //
        //Reciever email
        string $receiver,
        //
        //The message body
        string $body,
        //
        //user name
        string $name = "",
        //
        //The subject of the email
        string $subject = "",
        //
        //The Attachments
        string $attachment = "",
        //
        //The type of the body
        bool $is_html = false
    ) {
        //
        //Set the email address to recieve the email. the email and name as the 
        //two paramters.i.e., ("kamaupeter343@gmail.com","Peter Kamau")
        $this->mailer->addAddress($receiver, $name);
        //
        //Set the template to be viewed as a HTML
        $this->mailer->isHTML($is_html);
        //
        //Set the subject of the email
        $this->mailer->Subject = $subject;
        //
        // The body of the email. This can be styled to look like a normal html 
        // template
        $this->mailer->Body = $body;
        //
        //The alternative to the html.i.e., incase the mailviewer doesnot support html views
        $this->mailer->AltBody = "";
        //
        //This is the path to attachment. Set it to be conditional since we are unsure
        //of whether an attachment is provided or not
        if (!($attachment == null)) {
            $this->mailer->addAttachment($attachment);
        }
    }
    //
    //Send an email after setting the recipients, the configuration, 
    //and composing the message
    public function send_email()
    {
        //
        //Get the configurations
        $this->server_config();
        //
        //Get the mail recipients and senders
        $this->set_addresses();
        //
        //Compose the email and provide the necessary attachments
        // $this->send_message($receiver, $b);
        //
        //Set the exception handler to identify errors that may arise in while 
        //sending the email
        try {
            //
            //Send the email to the user(s)
            $this->mailer->send();
            //
            return "ok";
        } catch (Exception $e) {
            //
            //Get the exception
            return "Error sending mail:" . $this->mailer->ErrorInfo;
        }
    }
}
