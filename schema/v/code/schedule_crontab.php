#!/usr/bin/php
<?php
//Use the database to construct and submit a fresh cronjob
//
//Catch all errors, including warnings.
set_error_handler(function (
    $errno,
    $errstr,
    $errfile,
    $errline /*, $errcontext*/
) {
    throw new ErrorException($errstr, $errno, E_ALL, $errfile, $errline);
});
try{
    //
    //Resolve the reference to the scheduler
    include_once "./scheduler.php";
    //
    //1. Create a new instance of the scheduler
    $cron = new scheduler();
    //
    //Get the parameters to added through the command line
    //
    //2. Reconstruct a fresh cronfile and run the crontab command with the array of
    //at jobs and the boolean refresh value
    $errors = $cron->update_cronfile();
    //
    //If there are no errors print ok or print the errors if there are any
    if(count($errors)>0)$cron->report_errors($errors);
} catch (Exception $ex) {
    //
    //1. Get the error message plus the stack trace
    //
    //Replace the hash with a line break in teh terace message
    $trace = str_replace("#", "<br/>", $ex->getTraceAsString());
    //
    //Record the error message in a friendly way
    $msg= $ex->getMessage() . "<br/>$trace";
    //
    //2. Report the error(report this without using the library)
    echo $msg;
}

