<?php

$server = "206.189.207.206";
//
$port = 22;
//
$user = "mutall";
//
$pw = "mutall";
//
//The name of the file on the remote server plus its extension
$file = "dir.txt";
//
$command = "dir> /home/mutall/projects/dir.txt";
$remote_dir = "/home/mutall/projects/";
$local_dir = "C:/Users/PETER/Downloads/";
//
$resource = ssh2_connect($server, $port);
if ($resource) {
    //
    if (ssh2_auth_password($resource, $user, $pw)) {
        //
        $directory = ssh2_exec($resource, $command);
        //
        if ($directory) {

            echo "Command executed successfully <br>";
            //
            $recieve = ssh2_scp_recv($resource, $remote_dir . $file, $local_dir . $file);
            //
            if ($recieve) {
                echo "the file transfer was successful <br>";
                //
                //Delete the transferred file on the server
                $delete = ssh2_exec($resource, "rm " . $remote_dir . $file);
                if ($delete) {
                    //
                    echo "the file was successfully deleted on the server <br>";
                } else {
                    //
                    echo "the deletion on the server was not successful <br>";
                }
            } else {
                echo "transfer failed <br>";
            }
        } else {
            echo "command failed <br>";
        }
    } else {
        echo "Error connecting to the server <br>";
    }
} else {
    echo "Failure, the port is incorrect ";
}
