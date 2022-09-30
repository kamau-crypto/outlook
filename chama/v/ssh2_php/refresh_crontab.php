<?php
//
//The message of a job
//
//Instantiate the crontab scheduler
$schedule= new scheduler();
//
//Update the crontab entries that are to be executed
$schedule->refresh_cronfile($job_number);
//
//Create the crontab file
$schedule->crontab();
//


