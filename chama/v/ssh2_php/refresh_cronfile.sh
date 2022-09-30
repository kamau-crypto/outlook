#!/bin/php
<?php
//
//Create a new database instance

$job_number
#
#This is the file we run to execute our at commands.
#Two [at -f refresh_crontab.sh $time] are needed, where the first created the 
#jobs and the second one removes the job on the end date of that job, while
#updating the crontab with the new file.
#
#
#Run the crontab refresher file
php refresh_crontab.php $job_number;