<?php
//
$filename="ebill.txt";
//
//get th contents of the file
$file= file_get_contents($filename);
//
//Convert the contents into an array
$commands= explode("\n", $file);
//
//Open the json file
$json_file= file_get_contents("output.json");
//
//Convert the string to a an array
$json= json_decode($json_file,true);
//
//append the message details into an array
$details= $commands;
//
array_push($json,$details);
//
//Save the contents of the file
$json_output= json_encode($json);
//
//Open the file
$new_file=fopen("output.json", "w");
//
//Append the new record
fwrite($new_file, $json_output);
//
//Close the file
fclose($new_file);
//
//Convert the json array into a string
$json_decode= json_decode($json_file);
//
//Open the text file and check to see if there are additional components
$string_file= fopen($filename,"w");
//
//Once the file is opened, append the string
