<?php
//
//This has to be teh first statement in a file
namespace tracker;
//
//Resolve the library config file
include_once "../../../schema/v/code/config.php";
//
//The local config file extends the config in the libary
class config extends \config{
    //
    //Title appearing on navigation tab should be the same as the namespace 
    //of this application.
    public string $id =__NAMESPACE__;
    // 
    //The name of the application's database.
    public string $app_db = "mutallco_rental"; 
    //
    //Subject comprises of the entity name to show in the home page
    //plus the database it comes from.
    public string $subject_ename="client";
    public array $subject;
     //
    //The full trademark name of the application
    public string $trade = "Tracking mutall_data Activities";
    //
    //For advertising purposes
    public string $tagline= "Ensuring Effectiveness in What We Do";
    //
    //Name of the application developer
    public string $developer = "Francis Nyakundi";
    //
    //The path from where this application was loaded
    public string $path=__DIR__;
    //
    function __construct(){
        //
        parent::__construct();
        //
        //Subject comprises of the entity name to show in the home page
        //plus the database it comes from.
        $this->subject= [$this->subject_ename, $this->app_db];
    }
}
