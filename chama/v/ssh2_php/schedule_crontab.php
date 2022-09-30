#!/usr/bin/php
<?php
//
//Resolve the reference to the scheduler
include_once "./scheduler.php";
//
//1. Create a new instance of the scheduler
$cron = new scheduler();
//
//2. Reconstruct a fresh cronfile and run the crontab command
$errors = $cron->exec([],true);
//
//If there are no errors print ok or print the errors if there are any
echo $errors;
