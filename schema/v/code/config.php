<?php
//The root configuration, e.g.., database credentials, are not expected to 
//be server-specific and should be set up is part of Outlook's installation -- 
//not when we switch to a different application
class config{
    const username = "root";
    const password =null;
    //
    //The shared users database 
    const dbname="mutall_users";
    // 
    //This is the general template for displaying the user report 
    public string $report = "/outlook/v/code/report.html";
    //
    //This is the complete path for the login template
    public string $login= "/outlook/v/code/login.html";
    //
    //The complete path of the welcome template 
    public string $welcome= "/outlook/v/code/welcome.html";
    //
    //The database for managing users and application that are 
    //running on this server 
    public string $login_db = "mutall_users";
        
    //The crud's template
    public string $crud = "/outlook/v/code/crud.html";
    // 
    //This is the general template for collecting simple user data.
    public string $general = "/outlook/v/code/general.html";
    //
    //This is the business creation template
    public string $business="outlook/v/code/new_business.html";
    // 
    //The maximum number of records that can be retrieved from 
    //the server using one single fetch. Its value is used to modify 
    //the editor sql by  adding a limit clause 
    public int $limit = 40; 
    //
    function __construct(){}
}
