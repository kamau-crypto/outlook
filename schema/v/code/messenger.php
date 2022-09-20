<?php
//
//Resolve the reference to the twilio class
require_once "../twilio/twilio.php";
//
//Resolve the reference to the mailer class
require_once "../mailer/mailer.php";
//
//Resolve the reference to the database
require_once "./schema.php";
//
//This is the messenger class that focuses on sending emails and SMS's to 
//multiple users by retrieving the user's email and the user's phone_number from
//the database and sending a message for each user.
class messenger
{
    //
    //The twilio class
    private $twilio;
    //
    //The mailer class
    private $mailer;
    //
    //The database class that supports querying the database
    private $dbase;
    //
    //Instantiate the twilio and mailer classes at this point
    function __construct()
    {
        //
        //Connect to the database
        $this->dbase=new database("mutall_users");
        //
        //Open the twilio class
        $this->twilio = new twilio();
        //
        //Open the mailer class
        $this->mailer = new mailer();
    }
    //
    //This function sends emails and sms's to the users for the currently logged in database
    public function send(int $business, string $subject, string $body):bool
    {
        //
        //1. Write the query to fetch all emails and phon numbers from the from the database
        // for the currently logged in business
        $query= "select"
                    . "email, num"
                . "from user"
                    . "inner join user on user.user = mobile.user"
                    . "inner join member on member.user = user.user"
                    ."inner join business on business.business = member.business"
                . "where business.business= $business";
        //
        //1.3. Get all user's phone numbers and email adddresses
        $senders=$this->dbase->get_sql_data($query);
        //
        //2.For each user, send the message and the email
        foreach ($senders as $sender){
            //
            //Validate the email to limit sending an email to the wrong user
            $email_=preg_match('/^\w+|\S+\w+\@\w+\.\w+|\S+/',$sender->mail);
            //
            //
            if($email_[0]!== false){
                //
                //The validated email
                $email=$sender->email;
            }
            //
            //Validate the phone number by adding the country code at the beginning
            //of the text
            $phone="+254{$sender->num}";
            //
            //Send the email.
            $mail=$this->mailer($email,$subject,$body);
            //
            //Send the message
            $sms=$this->twilio($phone,$subject,$body);
        }
        //
        //3. Return true or false once the mail and sms are sent
        return($mail&&$sms=== 'ok')? true:false;
    }
}
