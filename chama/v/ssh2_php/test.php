<?php

$server = "206.189.207.206";
//
$port = 22;
//
$user = "mutall";
//
$pw = "mutall";
//
$command = "ls> /home/mutall/projects/text.txt";
//
$resource = ssh2_connect($server, $port);
if ($resource) {
    //
    if (ssh2_auth_password($resource, $user, $pw)) {
        //
        $directory= ssh2_exec($resource, $command);
        //
        if($directory){echo'done';}else{    echo 'command failed';}
    } else {
        echo"Error connecting to the server";
    }
} else {
    echo "Failure, the port is incorrect";
}
