<?php
//
//This has to be the first statement in a file
namespace chama;
//
//Resolve class \config using the library
include_once "../../../schema/v/code/config.php";
//
//The local config file extends the one in the libary
class config extends \config{
    //
    //Title appearing on navigation tab should match the current namespace
    public string $id =__NAMESPACE__;
    // 
    // The title of the application
    public string $title="Unify Chama";
    //
    //The name of the application's database.
    public string $app_db = "mutall_chama"; 
    //
    //Subject comprises of the entity name to show in the home page
    //plus the database it comes from.
    public string $subject_ename="group";
    public array $subject;
    //
    //This is the logo's filename in the images folder at chamav/v/images
    public string $logo= "chama.jpg";
    //
    //The full trademark name of the application
    public string $trade = "UNIFY CHAMA";
    //
    //For advertising purposes
    public string $tagline= "Bringing together all chama needs";
    //
    //The Image of the developer
    public string $image= "Peter Kamau.jpg";
    //
    //Name of the application developer
    public string $developer = "Peter Kamau";
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
