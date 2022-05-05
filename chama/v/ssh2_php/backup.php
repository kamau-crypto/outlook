<?php

/**
 * Using the at command in linux, I will schedule this file to run, and once it runs, It will open a secure file tunnel that
 * allows data reception from the server/
 */
//
//This is the default directory once the server is accessed and the string command is the command to move to the crontab file
"crontab - l lists the crontab for the user";
$directory = "cd ../etc/ && crontab-e";
//
//The php class that will support the backups to the server and transfer of the copy to the local stream_socket_server
class backup
{
    //
    //For each of the methods in this c
    function __construct(
        //
        //Define the address to the server. This can be the host name as well such as mutall.co,ke
        $server = "206.189.27.206",
        //
        //Define the port to connect to the server. Digital ocean uses port 22, mutall.co.ke uses a different one.
        //Email the cutomer care at info@kenyawebexperts.co.ke
        $port = 22,
        //
        //The access credentials to the server
        $user_name = "mutall",
        $password = "mutall"
    ) {
        //
        $this->server = $server;
        $this->port = $port;
        $this->user = $user_name;
        $this->pwd = $password;
    }
    //
    //Open a connection to the server to allow the user to work with different commands to perform various activities on the
    //server.
    public function open_connection()
    {
        //
        //Authenticate the user
        $this->connection = ssh2_connect($this->server, $this->port);
        //
        //Catch the error if the connection is not successful
        if ($this->connection) {
            $auth = ssh2_auth_password($this->connection, $this->user, $this->pwd);
            if (!$auth) {
                echo ("A connection to the server cannot be established, recheck the credentials");
            }
            //
            else {
                echo ("Confrirm that the server address is correct");
            }
        }
    }
    //
    //This method provides a facility that allows users to run commands on the server like they
    //would be run on the linux commandline.
    public  function commands()
    {
        //
        //Obtain the count of the number of commands you wish to run on the server
        $command_count = func_num_args();
        //
        //
        try {
            if (!$command_count) throw new Exception("There are no commands to execute");
            //
            $commands = func_get_args();
            //
            //Get the commands if they are more than one, insert them into an array 
            //of commands to parse them as a string in order to execute them later
            $commands_string = ($command_count > 1) ? implode("&&", $commands) : $commands[0];
            //
            //Execute the commands as a string in a stream form.i.e. running multiple commands at the same time.
            $execution = ssh2_exec($this->connection, $commands_string);
            //
            //Throw an exception if you are unable to execute commands
            if (!$execution) throw new Exception("Unable to execute the commads: <br/> {$commands_string}");
        }
        //
        //Display errors to our user
        catch (Exception $e) {
            //
            ($e->getMessage());
        }
    }
    //
    //This function allows us to create a temporary crontab file or create a blank temp.file if there are no cronjobs in the system
    public function create_temp_crontab($path, $file_name)
    {
        //
        //This is the linux command to create a temporary crontab file in the server
        $linux_command = "cd ../../etc && crontab-e";
    }
    //
    //open the ssh tunnel to allow for secure tranfer from the server the files from the server
    private function open_tunnel()
    {
        //
        //The directory to the file on the remote server]
        $remote_dir = "../../home/mutall/backups/.sql";
        //
        //The directory on which to transfer the file on the local directory
        $local_dir = "D:/backups/.sql";
        //
        //Use secure file transfer secure copy(scp) to recieve the file from server
        $backup_rcv = ssh2_scp_recv($this->connnection, $remote_dir, $local_dir);
    }
    //
    //Close the connection to the tunnel
    private function close_tunnel()
    {
    }
}
