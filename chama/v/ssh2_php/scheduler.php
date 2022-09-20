<?php
//
//Resolve the reference to the database
require_once '../../../schema/v/code/schema.php';
//
//The scheduler is responsible for creating jobs that are repetitive and those
//that are not repetitive.
class scheduler {
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
    const crontab_data_file="mutall_data_crontab.txt";
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
    //The start date of an event
    public $start_date;
    //
    //The frequency of the event
    public $freq;
    //constructor
    function __construct() {
        //
        //Establish a connection to the server
        $this->connection=$this->initialize(); 
    }
    //
    //This function establishes a connection to the server enabling the scheduling
    //of jobs to our server
    private function initialize(){
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
    } 
    //
    //Creating at jobs that only need the filename and the time of execution as
    public function at(string $file, string $time){
        return ssh2_exec($this->connection,"at -f ".$file." ".$time);
    }
    //
    //Create cron jobs using the file as the only parameter to the execution of
    //the cronjob.
    public function crontab():bool {
        //
        return ssh2_exec($this->connection, "crontab ".self::crontab_data_file);
    }
    //
    //Executing the crontab
    public function exec(object $crontab){
        
    }
    //
    //Refreshing the cronfile with the newly created crontab. This method runs a
    //query that extracts all jobs that are active. i.e jobs older than today and
    //lesser than the given job. start_date<job>end_date
    public function update_cron_file(){
        //
        //Establish a connection to the database
        $database= new database();
        //
        //The query that gets all the current jobs that are older than the start date
        //and younger than the end date
        $sql="select job,message,recursion"
                . "from job"
                ."where ";
        //
        //Run the query and return the results
        $jobs=$database->get_sql_data($sql);
        //
        //Loop over each job, extracting the message and recursion of the event
        foreach ($jobs as $job) {
            //
            //2.1. The job primary key
            $this->pk = $job[0];
            //
            //2.2. The message of the event
            $this->message = $job[1];
            //
            //2.3. The recursion of the event
            $this->recursion = $job[2];
        }
        //
        //From the recursion of the event extract the start_date,end_date, and the
        //frequency
        $this->create_cronjobs();
        
    }
    //
    //Create cronjobs by using the start_date and the requency of the message obtained from the user
    private function create_cronjobs() {
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
                //
                //Inside this cronjob, we need to create new crontab entry with
                //some dummy variables
            } catch (Exception $e) {
                
            }
        }
    }
    //
    //Check if there are existing crontab entries for this user, and if there none,
    //Create the new crontab entry that will hold the crontab files
    private function existing_cronjobs(){
        //
        //Run the command to check if there are existing crontabs and if none is
        //present, create a new crontab entry
        $existing_cron/*resource|false*/= ssh2_exec($this->connection,"crontab -l");
        //
        //
        if($existing_cron !== false){
            //
            //Create a new crontab entry and add a line of code at the end to
            //to ensure that we have a dummy entry with current working directory, 
            //and the file to execute
            ???
            ssh2_exec($this->connection,"crontab -e");
            //
            //Add the dummy line of code
            $crontab= "#This is a customized mutall file for creating cron jobs"
                    . "#The entries to a cronjob are 5";
            
        }
        //
        //Create a copy of the current crontab from the database
        
    }
}
