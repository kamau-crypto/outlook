//Resolve the quiz classes, i.e., popup and baby
import * as outlook from "./outlook.js" 
//
//Resolve reference to myerror
import * as schema from "../../../schema/v/code/schema.js" 

/*
 * The viewer class supports disply of html pages during the design phase 
 */
 export class viewer extends outlook.baby<void>{
     //
     //Initialize the base class
     constructor(Page:outlook.page){
         super(Page, "/outlook/v/code/viewer.html");
     }
     //
     //This page is not used for collection data
     async get_result(){}
     //
     //The check method does all the work of a viewer. We take the specified
     //filename, create a test pop and admimister it. If the test popup 
     //satifies our template design criteria, we return an ok, otherwise we
     //return an eror message with all the help we can muster
     async check():Promise<boolean>{
         //
         //Get the specified filename
         const filename = this.get_input_value('filename');
         //
         //Use the filename to create a test popup
         const pop = new test(this, filename);
         //
         //Administer the popup, returning test result
         const result = await pop.administer();
         //
         //Continue only if the  administration was not canceled
         if (result===undefined) return false;
         //
         //Get teh reporting element
         const report = this.get_element('report');
         //
         //Report the test result
         if (result=="ok"){
             //
             report.innerHTML = "Ok";
         }else{
             //
             //Add the error class to the report
             report.innerHTML = result.message;
         }
         
         //Do not leave this page just yet
         return false;
     }
 }
 //
 //For now, the test data is booean, denoting either good or bac
type test_result = "ok" | schema.mutall_error;
 
 //The popup used for collecting test data
 class test extends outlook.baby<test_result>{
     //
     //Initialize the base constructor
     constructor(Page:outlook.page, filename:string){
         super(Page, filename);
     }
     
     async check():Promise<boolean>{
         //
         //Collect the test result here. For now its a superflous true. 
         //In future we shall analyse the template and return  a more helpful
         //result.
         this.result = "ok";
         //
         return false;
     }
     
     //
     //Rteurn the result obtained during check
     async get_result(){
         return this.result!;
     }
 }
