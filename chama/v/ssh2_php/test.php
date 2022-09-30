<?php
//
//Resolve the reference to the crontab
require_once './scheduler.php';
//
$scheduler = new scheduler();
//
//
$ats = ["type" => "refresh", "start_date" => "2022/08/20", "end_date" => "2022/08/22"];
//
//
$scheduler->exec($ats, true);
