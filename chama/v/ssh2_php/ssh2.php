<?php
//
//
class ssh2_crontab_manager
{
    private $connection;
    private $path;
    private $handle;
    private $cron_file;
    function __construct($host = "206.189.207.26", $port = 22, $username = "mutall", $pwd = "mutall")
    {
        $path_length = strrpos(__FILE__, "/");
        //
        this->path = substr(__FILE__, 0, $path_length) . '/';
        //
        $this->handle = 'crontab.txt';
        //
        $this->cron_file = "{$this->path}{$this->handle}";
        try {
            if (is_null($host) || is_null($port) || is_null($username) || is_null($pwd)) {
                throw new Exception("Please use the correct port, host, username, and password");
            }
            //
            $this->connection = ssh2_connect($host, $port);
            if (!$this->connection) throw new Exception("The SSH@ connection could not be established");
            //
            $authenticate = ssh2_auth_password($this->connection, $username, $pwd);
            //
            if (!$authenticate) throw new Exception("Could not authenticate using '{$username}' using password: '{$pwd}'.");
        } catch (Exception $e) {
            $this->error_msg($e->getMessage());
        }
    }
    //
    //
    public function exec()
    {
        $arguement_count = func_num_args();
        //
        try{
            if(! $arguement_count) throw new Exception("There is nothing to execute, no arguements specified.");
            //
            $arguements= func_get_arg();
            //
            $command_string= ($arguement_count>1)?implode("&&", $arguements):$arguements[0];
            //
            $stream= ssh2_exec($this->connection, $command_string);
            //
            if(! $stream) throw new Exception("Unable to execute the specified commands: <br/> {$command_string}");
        }
        catch (Exception $e){
            $this->error_message($e->getMessage());
        }
        return this;
    }
    //
    //
    public function write_to_file()
    {
        //
        if
    }
    //
    //
    public function remove_file()
    {
    }
    //
    //
    public function append_cronjob()
    {
    }
    public function remove_cronjob()
    {
    }
    public function crontab_file_exists()
    {
    }
    public function error_message()
    {
    }
}
