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
    const crontab_command = "/home/mutall/project/refresh_cronfile.sh";
    //The messenger file responsible for sending emails and text messages to all
    //users.
    const messenger = "messenger.sh";
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
    public $date;
    //
    //The errors associated with the event
    public $errors = [];
    //
    //The errors that are flagged during the execution of at jobs
    public $at_error;
    //
    //Errors flagged during the update of cron jobs
    public $update_error;
    //
    //constructor
    function __construct()
    {
        //
        //Establish a connection to the database
        //$this->database = new database("mutall_users");
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
    //Scheduling the requested job
    public function exec(array $ats, bool $update)
    {
        //
        //1. Issue the at commands
        //Loop through all the at commands and execute each one of them
        foreach ($ats as $at) {
            //
            //
            $at_error = $this->run_at_command($at);
        }
        //
        //2. Refresh the crontab if necessary
        if ($update === true) $update_error = $this->update_cronfile();
        //
        //Compile the result and return it
        return $this->compile_error($update_error, $at_error);
    }
    //
    //Run the at commands on a given date with a specific message
    public function run_at_command(stdClass $at): string
    {
        //
        //Using the type of at type provided from the user input...
        switch ($at->type) {
                //
                //send the message
            case "message":
                //
                //Get the date of the event
                $date = $at->date;
                //
                //The job number
                $msg = $at->message;
                //
                //We also need the recipient/business to send a message
                $type = $at->recipient->type;
                //
                //The extra component 
                $extra = $type === "group" ? $at->recipient->business->id : $at->recipient->user;
                //
                //The command parameters. They are:- msg(job_number),type(of
                //recipient), and extra(further details depending on the type
                //of recipient).
                $parameters = "$msg $type $extra";
                //
                //Construct the command to be executed at the requested date
                //(How shall we report errors when the schedule messenger fails)
                //Investigate the at command
                $command = 'echo "./schedule_messenger.php "'
                    . $parameters
                    . ' | at '
                    . $date;
                //
                //Schedule the at job to be executed with the job number
                $result = ssh2_exec($this->connection, $command);
                //
                break;
            case "refresh":
                //
                //Compile the command to run at the start_Date
                $start_job = 'echo "./schedule_crontab.php" | at ' . $at->start_date;
                //
                //Start the job's execution
                $exec_start = ssh2_exec($this->connection, $start_job);
                //
                //Compile the command to run at the end date
                $remove_job = 'echo "./schedule_crontab.php | at ' . $at->end_date;
                //
                //End the job's execution
                $end_refresh = ssh2_exec($this->connection, $remove_job);
                //
                //Return the result
                $result = $exec_start && $end_refresh;
                //
                break;
        }
        return $result;
    }
    //
    //Compile the errors that arise when updating the cron file, and while scheduling the at job
    public function compile_error($update_error, $at_error): array
    {
        //
        //Add the errors that occur when updating the crontab
        array_push($this->errors, $update_error);
        //
        //Add errors that arise when scheduling at jobs
        array_push($this->errors, $at_error);
        //
        //Return the array errors
        return $this->errors;
    }
    //
    //Refreshing the cronfile with the newly created crontab. This method runs a
    //query that extracts all jobs that are active. i.e jobs started earlier than 
    //today and end later than today. start_date<job>end_date
    public function update_cronfile()
    {
        //
        //1. Formulate the query that gets all the current jobs 
        //i.e., those whose start date is older than now and their end date is
        //younger than now(start_date <= now()< end_date)
        $sql = 'select '
            . 'job.job,'
            . 'job.msg,'
            . 'job.recursion->>"$.repetitive" as repetitive,'
            . 'recursion->>"$.start_date" as start_date,'
            . 'recursion->>"$.end_date" as end_date,'
            . 'recursion->>"$.frequency" as frequency '
            . 'from job '
            . 'where repetitive="yes" '
            . 'and start_date<= now()< end_date';
        //
        //2. Run the query and return the results
        $jobs = $this->database->get_sql_data($sql);
        //
        //3. Initialize the entries
        $entries = "";
        //
        //Compile the command to execute
        $command = self::crontab_command;
        //
        //4. Loop over each job, extracting the frequency as part of the entry.
        foreach ($jobs as $job) {
            //
            //Get the frequency of the job
            $freq = $job->frequency;
            //
            //The crontab entry for sending messages
            $entry = "$freq $command $job->job\n";
            //
            //Add it to the list of entries
            $entries .= $entry;
        }
        //
        //5. Create a cron file that contains all crontab entries.
        file_put_contents(self::crontab_data_file, $entries);
        //
        //6. Run the cron job
        $result = ssh2_exec($this->connection, "crontab " . self::crontab_data_file);
        //
        return $result;
    }
}
