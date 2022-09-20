<?php
//
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
//
//Get the reference to the mailerer in composer
require_once "vendor/autoload.php";
//
//Instantiate the php mailerer object
$mailer = new PHPMailer(true);
try {
    //
    //Configure the settings to the server
    //
    $mailer->SMTPDebug = SMTP::DEBUG_SERVER;
    //
    $mailer->isSMTP();
    $mailer->Host = 'smtp.gmail.com';
    $mailer->SMTPAuth = true;
    $mailer->Username = 'kamaupeter343@gmail.com';
    $mailer->Password = 'ggswhvvwynkvjspo';
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Port = 587;

    //
    //The sender of the emailer
    $mailer->setFrom("kamaupeter343@gmail.com", "FULL STACK DEVELOPER");
    //
    //The address of the emailer's recipient, The recipient's name is optional
    $mailer->addAddress("kamaupeter343@yahoo.com", "PETER");
    //
    //Address to which to reply to
    $mailer->addReplyTo("kamaupeter343@gmail.com", "Reply");
    //
    //CC and BCC to the emailer
    $mailer->addCC("kamaupeter343@outlook.com");
    //$mailer->addBCC("");
    //
    //Should a person want to send email attachments, he should add them here.
    //The file path to the file should also be included.
    //$mailer->addAttachment("../document/CV PETER KAMAU KUNGU.pdf");
    //
    //This allows the user to send an emailer as a simple HTML or plain text.
    $mailer->isHTML();
    //
    //The Subject of the emailer address
    $mailer->Subject = "TESTING TESTING";
    //
    //The body of the message is also knownn as the message
    $mailer->Body = "<h1>MUTALL DATA INTERNSHIP PROGRAM</h1>"
        . "<p>The mutall data mentorship program offers a internship program"
        . " to undergraduates and post gradauates</p>"
        . "<p>Internships are of two kinds:</p>"
        . "<ol><li>Fixed term internship</li>"
        . "<li>Non fixed term internships</li></ol>"
        . "Students are encouraged to signup to the internship program "
        . "<a href='form.com'>here</a>";
    //
    //the alternative to the html body
    $mailer->AltBody = "This for non-HTML mail clients";

    //
    //Send the email to the user
    $mailer->send();
    //
    echo "mail sent successfully";
} catch (Exception $e) {
    echo "Error sending mail: " . $mailer->ErrorInfo;
}
