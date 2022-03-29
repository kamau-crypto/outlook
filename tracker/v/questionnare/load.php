<?php
//
//Catch all errors, including warnings.
\set_error_handler(function($errno, $errstr, $errfile, $errline /*, $errcontext*/) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});
//
//The schema is the base of all our applications; it is primarily used for
//supporting the database class
include_once $_SERVER['DOCUMENT_ROOT'].'/schema/v/code/schema.php';
//
//Resolve the questionnaire reference (for loading large tables)
include_once $_SERVER['DOCUMENT_ROOT'].'/schema/v/code/questionnaire.php';
//
//Get the Kentionary data (in questionnaire format) to export
$text = file_get_contents("stock.json");
//
//Convert the json to the Iquestionnaire php structure
$Iquestionnaire = json_decode($text);
//
//Use the desired data from the php object to create the questionnaire. 
//Remember questionnaire is defined in the root namspace
$q = new \questionnaire($Iquestionnaire);
//
//Export the questionnaire data and log the progress to the given xml file
$result = $q->load_common(__DIR__."\\log.xml");
//
echo json_encode($result);
