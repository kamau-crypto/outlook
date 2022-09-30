<?php
//
//Resolve the reference to the database
require_once '../../../schema/v/code/schema.php';
//
//The scheduler is responsible for creating jobs that are repetitive and those
//that are not repetitive.
class scheduler
{
    //
    //Server address
    const server = "206.189.207.206";
    //server password
    const pwd = "mutall";
    //
    //user name
    const user = "mutall";
    //
    //port
    const port = 22;
    //
    //This is the file that generates the crontab commands that contain valid
    //crontab files
    const crontab_data_file = "mutall_data_crontab.txt";
    //
    //The crontab command with the file for refreshing a crontab file
    const crontab_command="/home/mutall/project/refresh_cronfile.sh";
    //The messenger file responsible for sending emails and text messages to all
    //users.
    const messenger = "messenger.php";
    //
    //The shell file for 
    //
    //The database object that allows for retrieving queried data
    private $database;
    //
    //The connection resource to the server.i.e.,the foundation running other
    //ssh2 commands once the connection to the server is established
    public $connection;
    //
    //THe connection to the database
    public $db;
    //
    //The recursion associated with an event
    public $recursion;
    //
    //The pk of a job
    public $pk;
    //
    //The message of a job
    public $message;
    //
    //The start date of a crontab event
    public $start_date;
    //
    //The end date of an event, and on this date, the event should be removed
    //from the database
    public $end_date;
    //
    //The start date associated with at jobs
    public $at_date;
    //
    //The start time of the at event
    public $at_time;
    //
    //The minute of a cronjob,
    public $min;
    //
    //The hour of a cronjob
    public $hr;
    //
    //The day of the month of a cronjob
    public $dom;
    //
    //The month associated with a cronjob
    public $month;
    //
    //The day of the week associated with a cronjob
    public $dow;
    //
    //constructor
    function __construct()
    {
        //
        //Establish a connection to the database
        $this->database = new database();
        //
        //Establish a connection to the server
        $this->connection = $this->initialize();
    }
    //
    //This function establishes a connection to the server enabling the scheduling
    //of jobs to our server
    private function initialize()
    {
        //
        //Create the connection to the server to allow us to run a command laterTRY CATCH
        try {
            $this->connect = ssh2_connect(self::server, self::port);
            //
            //Authenticate the user who is logging using the username and password
            try {
                $this->authenticate = ssh2_auth_password(
                    $this->connect,
                    self::user,
                    self::pwd
                );
            } catch (Exception $ex) {
                //
                //Throw an error if no connection to the database is obtained
                throw new Exception($ex . "Could not authenticate user.Invalid password"
                    . "or username");
            }
        } catch (Exception $e) {
            //
            //Throw an e
            throw new Exception($e . "Could not connect to the server. Invalid domain"
                . "or port number");
        }
    }
    //
    //Creating at jobs that only need the filename and the time of execution as
    public function at(string $file)
    {
        //
        //The query that retrieves all jobs that are not repetitive
        $sql='select 
                job,
                msg,
                recursion->>"$.send_at.start_date" as start_date,
                recursion->>"$.send_at.time" as start_time
            from job
            where recursion->>"$.repetitive"="no"';
        //
        //Get the at jobs
        $at_jobs=$this->database->get_sql_data($sql);
        //
        //Get the start time of each job???
        foreach($at_jobs as $at_job){
            //
            //The start time of that job
            $this->at_date= $at_job->start_date;
            //
            //Get the start time of the job if it is defined
            if($at_job->start_time !== NULL)$this->at_time=$at_job->start_time;
        }
        //
        //run the at job using the current date and time
        //
        return ssh2_exec($this->connection, "at -f  $file  $this->at_date $this->at_time");
    }
    //
    //Create cron jobs using the file as the only parameter to the execution of
    //the cronjob.
    public function crontab(string $file): bool
    {
        //
        //Create the crontab file at the current directory
        $this->create_cronfile();
        //
        //Update the current crontab with cron commands
        $this->update_cronfile();
        //
        //Create the crontab on the day the event is set to start using the at command
        $create_at = ssh2_exec($this->connection, "at -f $file $this->start_date");
        //
        //Create the crontab file on the condition that the crontab is successfully
        //created
        if ($create_at !== 'false') {
            //
            //Create the crontab entry for this user
            ssh2_exec($this->connection, "crontab ".self::crontab_data_file);
        }
        //
        //Remove the crontab when the end_date is due.
        return ssh2_exec($this->connection, "at -f  $file  $this->end_date");
    }
    //
    //Executing the crontab at the specific time using the at command.
    //The at command requires the date and the time
    public function exec(array $at,string $business, bool $update)
    {  
        //
        //Execute the at command
        //
        //Update the cron file if the user input provided is true
        if($update===true ){
            //
            //Get today's date
            $date=date("Y-m-d");
            //
            //Update the cronfile when the cron's start_date is today
            while($date== $at[0]){
                //
                //On this start_date, update this cron file
                $this->update_cronfile();
            }
            //
            //Update the cronfile when the cron's end_date is given
            while($date== $at[1]){
                //
                //On this end_date, update this cron file
                $this->update_cronfile();
            }
        }
    }
    //
    //Refreshing the cronfile with the newly created crontab. This method runs a
    //query that extracts all jobs that are active. i.e jobs started earlier than 
    //today and end later than today. start_date<job>end_date
    public function update_cronfile()
    {
        //
        //2. The query that gets all the current jobs that are younger than the start date
        //and younger than the end date
        $sql = 'select '
                .'job.job,'
                .'job.msg,'
                .'job.recursion->>"$.repetitive" as repetitive,'
                .'recursion->>"$.start_date" as start_date,'
                .'recursion->>"$.end_date" as end_date,'
                .'recursion->>"$.frequency" as frequency '
            .'from job '
            .'where repetitive="yes" '
            . 'and start_date<= now()< end_date';
        //
        //3. Run the query and return the results
        $jobs = $this->database->get_sql_data($sql);
        //
        //Initialize the entries
        $entries="";
        //
        //Compile the command to execute
        $command=self::crontab_command;
        //
        //4. Loop over each job, extracting the frequency as part of the entry.
        foreach ($jobs as $job) {
            //
            //Get the frequency of the job
            $freq= $job->frequency;
            //
            //The crontab entry for sending messages
            $entry="$freq $command $job->job\n";
            //
            //Add it to the list of entries
            $entries.="$entry";
        }
        //
        //5. Create a cron file that contains all crontab entries.
        file_put_contents(self::crontab_data_file, $entries);
    }
    //
    //Create cronjobs by using the start_date and the requency of the message 
    //obtained from the recursion
    private function create_cronjobs(): bool
    {   
        //
        //Construct the crontab time
        $cron_time = "$this->min $this->hr $this->dom $this->month $this->dow";
        //
        //Combine the current cron with the current user's directory
        $cron_dir = "{$cron_time}";
        //
        //Add the crontab file that will send messages to all users using
        $crontab = "{$cron_dir}/" . self::messenger;
        //
        //Create the crontab entry at the end of the the crontab file and 
        //return it
        return ssh2_exec($this->connection, "{$crontab} >>" . self::crontab_data_file);
    }
    //Since our aim is to create new crontab entries at the end of
    //the crontab file, it is better to have the crontab.txt file
    //with no crontab entries and append the current crontabs at the
    //end of the file.
    //
    //This method creates a new crontab file each time we have want to update our
    //existing crontab with the pending jobs
    private function create_cronfile():string
    {
        //
        //Set the contents of the empty crontab file to create the crontab file
        $cron = '"# Edit this file to introduce tasks to be scheduled by cron.\n'
            .'# Each task to run has to be defined through a single line\n'
            .'# indicating with different fields when the task will be run\n'
            .'# and what command to run for the task\n'
            .'# To define the time you can provide concrete values for\n'
            .'# minute(m), hour(h), day of month (dom), month(mon),\n'
            .'# and day of week (dow) or use (*) in these fields (for `any`)\n'
            .'#\n'
            .'# For example, you can run a backup of all your user accounts\n'
            .'# at 5 a.m every week with:\n'
            .'# 0 5 * * 1 tar -zcf /var/backups/home.tgz /home/\n'
            .'#\n'
            .'#This file was edited on `date`\n'
            .'#\n'
            .'# m h  dom  mon dow   command"\n';
        //
        //Create the empty crontab.txt file with a date component that displays
        //the datetime when the crontab was created.
        ssh2_exec($this->connection, "echo $cron  >" . self::crontab_data_file);
        //
        //Return the crontab file
        return self::crontab_data_file;
    }
}
