<?php

//
//
require_once '../../../schema/v/code/schema.php';
//
//At commands run once. We only need the date in the DD.MM.YY and it only runs a file
class schedule extends database {
    //
    //Server address
    const server = "206.189.207.206";
    //server password
    const pwd = "mutall";
    //
    //user
    const user = "mutall";
    //
    //port
    const port = 22;

    //
    //The filenames to run at with the at command["messenger.php"]
    public $filename = "../ssh2_php/messenger.php";
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
    //The start date of an event
    public $start_date;
    //
    //The frequency of the event
    public $freq;
    //
    //The current working directory of a user/application
    public $dir;
    //constructor
    function __construct() {
        //
        //Create the connection to the server to allow us to run a command laterTRY CATCH
        try{
           $this->connect = ssh2_connect(self::server, self::port);
           //
           //Authenticate the user who is logging using the username and password
           try{ 
                $this->authenticate = ssh2_auth_password(
                    $this->connect, 
                    self::user, 
                    self::pwd
                );
           } catch (Exception $ex) {
               //
               //Throw an error if no connection to the database is obtained
               throw new Exception($ex."Could not authenticate user.Invalid password"
                       . "or username");
           }
        }
        catch(Exception $e){
            //
            //Throw an e
            throw new Exception($e."Could not connect to the server. Invalid domain"
                ."or port number");
        }
        //
        //Create the query that retrieves all jobs
        $this->query = "select job,message,recursion from job";
        /**
          We need the start_date here to manage jobs older than our date */
        //
    }
private function initialize(){
    
}
        
    //
    //Establish a connection to the connection to the database. We will need a cronjob that updates and checks
    //the database after every 5 minutes. Therefore to manage jobs, we will a unique identifier, prior to setting
    //them into the database.
    //
    //
    //Retrieve all jobs from the database and retrieve the events type, start_date, and the frequency of 
    //the event
    public function fetch_jobs(): array {
        //
        //1.0 Execute the query that retrieves all jobs
        $jobs = $this->get_sql_data($this->query);
        //
        //2.0 Iterate over the array to retrieve the pk, the message, and the recursion
        foreach ($jobs as $job) {
            //
            //2.1. The job primary key
            $this->pk = $job[0];
            //
            //2.2. The message of the job
            $this->message = $job[1];
            //
            //2.3. The recursion of the job
            $this->recursion = $job[2];
        }
        //
        //Return the jobs primary key, message, and the recursion
        return [$this->pk, $this->message, $this->recursion];
    }

    //
    //Creating an at job requires that a job is not of type repetitive, with the primary key
    //message, and the recursion data
    public function create_at_jobs()
    /*     * "ok"|"Error" */ {
        //
        //Otherwise extract the start_date
        if ($this->recursion->repetitive === "no") {
            //
            //Extract the start_date of the event
            $start_date = $this->recursion->start_date;
            //
            try {
                //
                //Once the event is created at the start_date, the file to send the messagers should be sent;
                $execute = ssh2_exec($this->connect, "at -f" . $this->filename. " " . $start_date);
                //
                //Update the database to indicated that the job is scheduled??
                $update = "INSERT INTO job (is_scheduled) values('yes') where job=$this->pk";
                //
                //Update the database
                $this->get_sql_data($update);

                //
                return $execute;
            } catch (Exception $ex) {
                //
                //Get the exception
                return $ex->getMessage();
            }
        }
    }

    //
    //Create cronjobs by using the start_date and the requency of the message obtained from the user
    public function create_cronjobs() {
        //
        //Create a crontab job only if the event is of type repetitive
        if ($this->recursion->repetitive === "yes") {
            //
            //The PK of the message
            $this->pk;
            //
            //The message associated with the event
            $this->message;
            //
            //Extract the start date,
            $start_date = $this->recursion->start_date;
            //
            //and the frequency of the job
            $freq = $this->recursion->frequency;
            //
            //From the requency, retrieve the..
            //1.Minute
            $min = $freq->minute;
            //
            //2. hour
            $hr = $freq->hour;
            //
            //3. day_of month
            $d_month = $freq->day_of_month;
            //
            //4. month
            $mon = $freq->month;
            //
            //5.The day of the week,
            $d_week = $freq->day_of_week;
            //
            //
            try {
                //
                //Get there current working directory on the server
                $pwd=ssh2_exec($this->connect, "pwd");
                //
                //Check if there are existing crontab entries under this directory
                $this->existing_cronjobs();
                //
                //If there are existing jobs, add to the existing jobs, and if none is present
                //create a new cron job
            } catch (Exception $e) {
                
            }
        }
    }
    //
    //Check if there are existing cronjobs for the current user working directory
    public function existing_cronjobs() {
        //
        //Run the query to check whether we have some pending cronjobs.
        
        
    }

    //
    //Remove cronjobs whose end date is older than the current date, from the database
    public function remove_jobs() {
        
    }

}
