#!/usr/bin/php 
<?php
//
//This file supports the sending of the mutall carpark vehicle collection reports
//at 6:00 p.m everyday. For this, we need to have this file scheduled to run as a 
//crontab.
//
//The default path set by the user when accessing the credentials
$path = '/home/mutall/projects/';
//$path = $_SERVER['DOCUMENT_ROOT'];
//
set_include_path($path);
//
//Resolve the reference to the messenger
include_once $path."schema/v/code/messenger.php";
//
//Include the database to allow for execution of queries.
include_once $path."schema/v/code/schema.php";
//
//Create an instance of the database class
$dbase = new database("mutall_users", false, true);
//
//Have access to the messenger class
$messenger = new messenger();
//
//1. Retrieve the job name as part of the command line parameters.
//  N.B,; The first parameter [0] is the file name
$job_name = $argv[1];
//
//2. Formulate the query to retrieve the performance from the database
$query = file_get_contents(messenger::home . messenger::carpark_update);
//
//3. Execute the query to retrieve the vehicle collection errors
$results = $dbase->get_sql_data($query);
//
//3.1 Begin with an empty report
$report = "";
//
//When there are some results, send a message
if (count($results) > 0) {
    //
    //3.2 Retrieve the date of collecting the vehicle records
    $report .= "Date:- " . $results[0]['siku'] . "\n";
    //
    //3.3 Extract the message to send from the result
    foreach ($results as $result) {
        //
        //The operator collecting the results during that day
        $report .= "Operator:- " . $result['operator'] . "\n";
        //
        //The total number of car visits in the carpark on that day
        $report .= "Total number of visits:- " . $result['total_visits'] . "\n";
        //
        //The count of errors during that day
        $report .= "Number of errors:- " . $result['error_count'] . "\n";
        //
        //The error rate
        $report .= "Error rate:- " . $result['error_rate'] . "\n \n";
    }
}
//
//When there are no results, send the message to show that there are no results
if (count($results) < 1) {
    //
    //Compile the message to send
    $report .= "No data was collected today.";
}
//
//3.4 The subject of the message
$subject = "Carpark report" . $results[0]['siku'];
//
//3.5 .The recipient of the message is Mr. Muraya
//Construct a new standard class
$recipient = new stdClass;
//
//Provide the type of recipient
$recipient->type = "individual";
//
//The recipient's primary key
$recipient->user = [1269];
//
//4. Send the message:- Either through mail or via SMS
$errors = $messenger->send($recipient, $subject, $report);
//
//
if (count($errors) > 0) {
    //
    //Log the errors obtained to the error log file
    $messenger->report_errors($errors);
    //
    //Do not continue from this place onwards
    return;
}
//
//5. Update the database with the message sent
//
//The query to update the database with
$sql = $dbase->chk("
        UPDATE job 
        SET msg=$report 
        WHERE job.job= $job_name
    ");
//
//Run the query to update the job table with the message. WHAT HAPPENS WHEN THE
// PROGRAM FAILS. hint-> ERROR HANDLING.
$update = $dbase->query($sql);
//
//Record errors collected to the error log
if (!$update) {
    //
    //Compile the errors collected when updating the system
    $result = explode("\n", $update);
    //
    //Report the errors collected
    $messenger->report_errors($result);
    //
    //Do not continue
    return;
}
