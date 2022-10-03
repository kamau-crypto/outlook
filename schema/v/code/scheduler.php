<?php
//
//Resolve the reference to the database
require_once '../../../schema/v/code/schema.php';
//
//The scheduler is responsible for creating jobs that are repetitive and those
//that are not repetitive.
class scheduler extends component
{

    //
    //This is the full path to the file constructed from the document root and
    //the partial path.
    public $crontab_data_file;
    //
    //The database object that allows for retrieving queried data(why private??)
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
        //The crontab data file
        $this->crontab_data_file = component::home . component::crontab_file;
        //
        //
        //Establish a connection to the database(using an incomplete database)
        $this->database = new database("mutall_users", false);
    }
    //
    //Scheduling the requested job
    public function execute(
        //
        //It is true when we need to rebuild the crontab, otherwise it is false
        bool $update,
        //
        //The list of at commands as defined in the run_at_commands() below
        array/*<at>*/ $ats
    ): array /*errors|output*/ {
        //
        //2. Refresh the crontab if necessary
        if ($update) $this->update_cronfile();
        //
        //1. Issue the at commands
        //Loop through all the at commands and execute each one of them
        foreach ($ats as $at) {
            $this->run_at_command($at);
        }
        //
        //Return the collected errors
        return $this->errors;
    }
    //
    //Run the at commands on a given date with a specific message
    /**
     * 
    The at command is either for:-
    type at =
        //
        //- sending a message indirectly using a job number(from which the message
        //can be extracted from the database)
        { type: "message", date: string, message: number,recipient:recipient }
        //
        //- or for initiating a fresh cronjob on the specified date
        | { type: "refresh", start_date: string, end_date: string };
     */
    public function run_at_command(stdClass $at): void
    {
        //
        //Directory reference for the comand.
        $home = component::home;
        //
        //The file to record the errors if any.
        $log = component::log_file;
        //
        //Get the date of when to send the message.
        $date = $at->start_date;
        //
        //There are two types of at commands:- 
        switch ($at->type) {
                //
            case "message":
                //
                //A command for sending a message to a user at a specified time.
                //
                //Get the message to send as a job.
                $msg = $at->message;
                //
                //We also need the type of recipient(individual or group) 
                //to send the message.
                $type = $at->recipient->type;
                //
                //Get the message recipeint depending on the type.
                $recipient = $type === "group" ? $at->recipient->business->id : $at->recipient->user;
                //
                //Formulate the linux command to run.
                //
                //The command parameters. They are:- msg(job_number),type(of
                //recipient), and extra(further details depending on the type
                //of recipient).
                $parameters = "$msg $type $recipient";
                //
                //The command to execute at the requested time.
                $command = "$home/scheduler_messenger.php $parameters";
                break;
                //
            case "refresh":
                //
                //The command for rebuilding the crontab
                $command = "$home/scheduler_crontab.php";
                break;
                //
            case "other":
                //
                //Define the command to run.
                $command = $at->command;
                break;
        }
        //
        //Construct the command to be executed at the requested date. All the at 
        //commands are constrained to run at midday.
        $exe = "echo '$command' | at 12:00 $date >> $log";
        //
        //Execute the command and collect the results.
        //(Put the shell_exe in component class).
        $result = shell_exec($exe);
        //
        //Check the reults.
        if ($result !== "ok") {
            //
            //Result itself is an error message.
            //
            //Report the error to the programmer
            throw new Exception("This command '$exe' returned the following message '$result'.");
        } else {
            echo ("The command '$exe' was succesfull with the following message '$result'.");
        }
    }
    //
    //Refreshing the cronfile with the newly created crontab. This method runs a
    //query that extracts all jobs that are active. i.e jobs started earlier than 
    //today and end later than today. start_date<job>end_date
    public function update_cronfile(): void
    {
        //
        //1. Formulate the query that gets all the current jobs 
        //i.e., those whose start date is older than now and their end date is
        //younger than now(start_date <= now()< end_date)
        $sql = '
        select 
            job.name,
            job.msg,
            job.command,
            job.recursion->>"$.repetitive" as repetitive,
            recursion->>"$.start_date" as start_date,
            recursion->>"$.end_date" as end_date,
            recursion->>"$.frequency" as frequency 
        from job 
        where job.recursion->>"$.repetitive"="yes" 
        and recursion->>"$.start_date"<= now()<recursion->>"$.end_date"
        ';
        //
        //2. Run the query and return the results
        $jobs = $this->database->get_sql_data($sql);
        //
        //3. Initialize the crontab entries
        $entries = "";
        //
        //4. Loop over each job, extracting the frequency as part of the entry.
        foreach ($jobs as $job) {
            //
            //Get the frequency of the job
            $freq = $job['frequency'];
            //
            //Compile the user
            $user = component::user;
            //
            //The directory where the command file is located
            $directory = component::home;
            //
            //The php command file
            $file = $job['command'];
            //
            //The arguments passed
            $arg = $job['name'];
            //
            //The log file
            $log = ">> " . $directory . component::log_file;
            //
            //The crontab file and the job number makes up the command
            $command = "" . $directory . $file . " " . $arg . "";
            //
            //The crontab entry for sending messages
            $entry = "$freq $command $log \n";
            //
            //Add it to the list of entries
            $entries .= $entry;
            //
            //modify the permissions to allow saving the job to the database
            shell_exec("chmod 777 $command");
        }
        //
        //5. Create a cron file that contains all crontab entries.
        file_put_contents($this->crontab_data_file, $entries);
        //
        //Modify the file permissions
        shell_exec("chmod 777 $this->crontab_data_file");
        //
        //6. Compile the cronjob. 
        //NOTE:- The php user is identified by www-data
        //and a user needs permissions to set up a crontab otherwise it wont execute
        $command = "crontab " . component::user . $this->crontab_data_file . "";
        //$command= "lss -l";
        //  
        //7. Run the cron job
        $result = shell_exec($command);
        //
        //At this the shell command executed successfully
        if (is_null($result)) {
            //
            //This is a successful execution. Return nothing
            return;
        }
        //At this the shell command executed successfully or it failed. Test whether
        //it failed or not.
        if (!$result)
            throw new Exception("The crontab command for '$command' failed with the "
                . "following '$result'");
        //
        //The shell command succeeded with a resulting (error) message. Add it to
        //the error collection for reporting purposes.
        array_push($this->errors, $result);
    }
}
