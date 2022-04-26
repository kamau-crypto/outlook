<?php
//
//Get the file to process.
$file= file_get_contents("mpesa_texts.json");
//
//Convert the file into an array
$values= json_decode($file, TRUE);
//
forEach($values as $value){
    //
    //Retrieve the account number
    $account=preg_match('/(?<=Account:\s)\w+/', $value);
    //
    //Retrieve the name of the account holder
    $name= preg_match('/(?<=Name:\s)\w+\s\w+\s\w+/',$value);
    //Get the amount due
    $amount= preg_match('/(?<=Amount Due:\s)\w+\S\w+\s\w+\s\w+/', $value);
    //
    //Get the due date
    $due_date= preg_match('(?<=Due Date: ).*', $value);
    //
}
?>


