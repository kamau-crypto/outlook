<?php
//
//Resolve the reference to the twilio class
require_once "../twilio/twilio.php";
//
//Resolve the reference to the mailer class
require_once $_SERVER['DOCUMENT_ROOT'] . "/schema/v/mailer/mutall_mailer.php";
//require_once $_SERVER['DOCUMENT_ROOT']."/schema/v/mailer/mutall_mailer.php";
//
//Resolve the reference to the database
require_once $_SERVER['DOCUMENT_ROOT'] . "/schema/v/code/schema.php";
//
//This is the messenger class that focuses on sending emails and SMS's to 
//multiple users by retrieving the user's email and the user's phone_number from
//the database and sending a message for each user.
class messenger extends component
{
    //
    //The twilio class
    protected twilio $twilio;
    //
    //The mailer class
    protected mailer $mailer;
    //
    //The database class that supports querying the database
    protected database $dbase;
    //
    //The error handling mechanism for collecting errors reported when an email
    //or sms is not sent for some reason .e.g.,when an email address is invalid
    protected array $errors = [];
    //
    //Instantiate the twilio and mailer classes at this point
    function __construct()
    {
        //
        //Connect to the database
        $this->dbase = new database("mutall_users");
        //
        //Open the twilio class
        $this->twilio = new twilio();
        //
        //Open the mailer class
        $this->mailer = new mutall_mailer();
    }
    //
    //Send an email to selected individuals or a group depending on the recipient type
    private function send_emails(stdClass $recipient, string $subject, string $body)
    {
        //
        //Test if the recipient is a group
        if ($recipient->type == "group")
            //
            //The recipient is a group:- 
            //Send the email to members of the group
            $addresses = $this->send_group_addresses($recipient->business);
        //
        else
            //
            //The recipient is an individual:-
            //Send an email to the user name
            $addresses = $this->send_individual_addresses($recipient->user);
        //
        //Clear the addresses
        $this->mailer->clearAddresses();
        //
        //Build the addresses
        foreach ($addresses as $address) {
            //
            //Compile the collected address to the address queue
            try {
                //
                //Add the email address to the mailer
                $this->mailer->addAddress($address['email'], $address['name']);
            } catch (Exception $e) {
                //
                //Compile the errors collected from the messenger and show them
                //to the user.
                array_push($this->errors, $e->getMessage());
            }
        }
        //
        //Collect the subject and text of the email to send
        $this->mailer->send_email($subject, $body);
        //
        //Send the emails
        $this->mailer->send();
    }
    //
    //Send an email to the given user
    private function send_individual_addresses(
        array $users
    ): array {
        //
        //Initialize the process with a simple array
        $addresses = [];
        //
        //Get each individual user and send the message
        foreach ($users as $user_pk) {
            //
            //1.Get the email address for the user
            //
            //Formulate the sql
            $sql = "select
                        user.email,
                        user.name
                    from user
                        where user.user=$user_pk";
            //
            //2. Query the database for the email addresses
            //[{email:'kamau@gmail.com'},{email:'peter@gmail.com'}]
            $emails = $this->dbase->get_sql_data($sql);
            //
            //Check that there is at least one email.It is illegal to send a message
            //without an email
            if (count($emails) < 1) {
                //
                //Compile the error
                array_push($this->errors, "The user with this '$user_pk' does not exist");
                //
                //Disregard the current address and continue the process
                continue;
            }
            //
            //Add the address to an array
            array_push($addresses, $emails[0]);
        }
        //
        //Compile the email addresses into a lists
        return $addresses;
    }
    //
    //Send emails to members of the given business. A business is identified by
    //an id and a name:-{id,name}
    //Return the errors if any
    private function send_group_addresses(stdClass $business): array
    {
        //
        //1. The query to fetch all emails for users registered under the current
        //business
        $sql = "
            select
                user.email,
                user.name 
            from user 
                inner join member on member.user=user.user
                inner join business on business.business= member.business
            where business.id= '$business->id'
                and not(user.email is null) "
            //
            //Added for testing purposes
            . "and user.user in (1269, 1274)";
        //
        //2. Get the receivers of the emails
        $receivers = $this->dbase->get_sql_data($sql);
        //
        //Return a list of receivers
        return $receivers;
    }
    //
    //Send messages to individual members or group members
    private function send_messages(
        stdClass $recipient,
        string $subject,
        string $body
    ) {
        //
        //1. Get the phone number
        $address = $this->get_addresses($recipient, $subject, $body);
        //
        //Send a message for each address collected

        //
        //Check the type of recipient
        if ($recipient->type == "group") {
            //
            //By now the recipient is of type group
            //Send group messages
            $this->send_group_messages($recipient->business, $subject, $body);
        } else
            //
            //Send the messages to the listed individuals
            $this->send_individual_messages($recipient->user, $subject, $body);
    }
    //
    //Get the phone number for users in a group and the individual user
    private function get_addresses(stdClass $recipient, string $subject, string $body)
    {
        //
        //The queries to retrieve the phone numbers from the group
        //
        //Get the addresses for a user
    }
    //
    //
    //Send individual phone messages to each selected user using the user's primary key,
    //the subject of the text,and the body of the message.
    private function send_individual_messages(array $users, string $subject, string $body)
    {
        //
        //Get all the selected users and send the messages
        foreach ($users as $user_pk) {
            //
            //Formulate the query to retrieve the user's phone number
            $query = "
                with
                #
                #Get the primary phone number of each user
                mobile as(
                        select
                            concat(mobile.prefix,mobile.num) as num,
                            row_number() over(partition by mobile.user) as users
                        from mobile
                            inner join user on mobile.user= user.`user`
                            inner join member on member.user= user.user 

                        where 
                        user.user in ($user_pk)
                    )
                    #
                    #Select all users with phone numbers linked to a business
                    select * from mobile where users=1
            ";
            //
            //Run the query and fetch the phone number of the recipient
            $results = $this->dbase->get_sql_data($query);
            //
            //Collect the error, if from the recipients provided, no phone numbers
            //are registered
            if (count($results) < 1) {
                //
                //Compile the list of errors and show the output
                array_push($this->errors, "The phone number for '$user_pk' is missing");
                //
                continue;
            }
            //
            //Retrieve the phone number of each user
            $phone = $results[0]['num'];
            //
            //Send the message
            $this->send_phone_messages($phone, $subject, $body);
        }
    }
    //
    //Sending phone messages to members of a group using the named business and 
    //returns an array of errors if the sending of emails failed
    private function send_group_messages(stdClass $business, string $subject, string $body): void
    {
        //
        //1. Construct the query to retrieve the valid phone numbers for users
        //of the current business.
        $query = "with
            #
            #Get the primary phone number of each user
            mobile as(
                select
                    concat(mobile.prefix,mobile.num) as num,
                    row_number() over(partition by mobile.user) as users
                from mobile
                    inner join user on mobile.user= user.`user`
                    inner join member on member.user= user.user 
                    inner join business on business.business = member.business

                where 
                    business.id='$business->id'
                    and user.user in (1269, 1274)
            )
            #
            #Select all users with phone numbers linked to a business
            select * from mobile where users=1";
        //
        //2. Get the member's phone numbers
        $receivers = $this->dbase->get_sql_data($query);
        //
        //4. Send a message to each user.
        foreach ($receivers as $receiver) {
            //
            //4.1. Complete the phone number by adding the country code at the 
            //beginning of the number
            $phone = $receiver['num'];
            //
            //4.2 Send the phone messages
            $this->send_phone_messages($phone, $subject, $body);
        }
    }
    //
    //Send phone number messages
    private function send_phone_messages($phone, $subject, $body)
    {
        //
        //1. Send the phone message
        $sms_result = $this->twilio->send_message($phone, $subject, $body);
        //
        //2. Log the SMS error, if it was not sent
        if ($sms_result != "ok") array_push($this->errors, "An sms was not sent to $phone for the following reason $sms_result");
    }
    //
    //
    //This function sends emails and sms's to the given recipient.
    //The recipient is either an individual or 
    //a group.The recipient type has the following structure:-
    //{"group", business}|{"individual",user_name}
    //The return is an array of errors if any
    public function send(array $tech, stdClass $recipient, string $subject, string $body): array/*error*/
    {
        if (in_array($tech, ["email"])) {
            //
            //1. Send the emails and register errors (if any)in the errors property
            $this->send_emails($recipient, $subject, $body);
        } else
            //
            //2. Send the phone messages and register errors (if any)in the errors property
            $this->send_messages($recipient, $subject, $body);
        //
        //Return the errors if any
        return $this->errors;
    }
}
