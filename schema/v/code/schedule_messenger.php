#!/usr/bin/php
<?php
//
//Resolve the reference to the messenger
include_once "messenger.php";
//
//Resolve the reference to the database
include_once "schema.php";
//
//Get the job number as a command line parameter
$job_no = $argv[1];
//
//Develop the query that uses the job number as a parameter to extract the body of the message
$sql = "select
            event.name,
            job.msg
        from job
            inner join event on event.event=job.event
        where job.job=$job_no
        ";
//
//Run the query and extract the message
$data = new database("mutall_users", true, true);
//
//Extract the message to send from the job
$msg = $data->get_sql_data($sql);
//
//Create a messenger class
$messenger = new messenger();
//
//Use the class to send the message and collect resulting errors if any
$send_emails = $messenger->send($recipient, $msg[0]['name'], $msg[0]['msg']);
