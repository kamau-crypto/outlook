//
//Resolve reference to the server
import * as server from "../../../schema/v/code/server.js";
//
//Resolve reference to the library
import * as lib from "../../../schema/v/code/library";
//
//Resolve references to the questionnaire that facilitates saving data to the files
import * as quest from "../../../schema/v/code/questionnaire.js";
//
//The module class for all our developed modules
abstract class modules {
    constructor() {

    }
}

//
//The aim of this class is to support scheduling of tasks similar to how "LINUX'S
//CRONTAB" command schedules tasks to automatically occur.(The scheduler executes
// a crontab)
export class schedule extends modules implements crontab {
    constructor() {
        //
        //
        super();
    }
    //
    //
    execute(i: crontab) {

    }
}
//
//This is the default crontab interface which contains the specifications that
//allow the automated scheduling of tasks.
export interface crontab {
    //

}
//
//This class supports the registrar module developed for supporting recording of
// data to the database for all our template forms.(the writer saves the 
// questionnaire)
class writer extends modules implements questionnaire {
    //
    //The class writer implements the save method implements the questionnaire interface
    async save(i: questionnaire): Promise<boolean> {
        //
        //1.Get the layout from the input questionnaire
        //
        //2. Use the layout and the questionniare class to load the data to a database
        //
        //3. Check the results on whether they were successful.If not successful,
        //report an error and return false to this method. If successful, return
        //
        return true
        
    }
}
//
//This is the questionnaire interface that drives collecting information from the level 2 registration form
//in either a label or tabular . A writer saves a questionnaire
export interface questionnaire {

}
//
//The accounting class that captures transaction data in a double entry format
//which then proceeds to split into the refined data as per the DEALER model. Once
//done the transaction it is labelled as a debit or credit within an application.
//(the accounting class posts a journal)  
class accounting implements journal {
    //
    //Post the given account to the general ledger and return the value of true
    //if the record is saved successfully.
    async post(i: journal): Promise<boolean> {
        return true;
    }
    //
    //Get the business unique identifier
    get_business_id():string{
        //
        //Get the id of the business
        return ""
    }
    //
    //Create a journal record for the transaction
    get_je():string{
        //
        //Get the reference number to that transaction
        const journ:string= document.getElementById("reference_number")!.textContent!;
        //
        //
        return journ;
        
    }
    //
    //Obtain the amount ot be debited to an account
    get_debit():string{
        return "";
        
    }
    //
    //Obtain the amount to be credited to an account
}
//
//The double entry interface allows capturing transaction data, which is later used
//to populate the different accounts.i.e., the office account
export interface journal {
    //
    //Get the id of the business from the tea_delivery template
    get_business_id(): string;
    //
    //Once a transaction is captured, it is added into the journal entry
    get_je(): {
        //
        //The reference number of the transaction
        ref_num: string,
        //
        //The purpose of the trasaction, this allows us to tell whether the
        //payment is debit or a credit in the specific account
        purpose: string,
        //
        //The date the transaction was recorded
        date: string,
        //
        //The amount involved in the transaction
        amount: number
    }
    //
    //post the trasaction as a debit as specified by the account type
    get_debit(): string;
    //
    //post the transaction as a credit depending on the account type. Refer at
    //the dealer platform
    get_credit(): string;
}

//
//The messenger class supports sending of messages from one user to another but
//the functionality changes in different applications.(The messenger sends a 
//message)
export default class messenger implements message {
    //
    //The structure of the message
    //public message:string;
    //
    constructor() { }
    //
    //Allows the user to send a message
    async send(i: message): Promise<boolean> {
        //
        //Get the sender's address
        //
        //Get the reciever's address
        return true;
    }
    //
    //Get the message from the form.Messages can be of three forms namely of type 
    //sms,whatsapp media, and type email
//     get_message(): Promise<message> {
//         //
//         //Get the message
//         //Get the sender of the message
//         const sender:string = this.get_sender();
//         //
//         //Get the body of the of the message
//         const body: string = this.get_body();
//         return msg={sender,body};
//     }
    //
    //Get the sender of the message
    get_sender(): string{
        return "";
    }
    //
    //Obtain the body of the message. the body of the message should contain the
    //address of the reciever, and the message itself.
    get_body():string{
        //
        //Get the reciever of the message
        //
        //Get the body itself
        //
        //create an array of both the body:- namely the reciever and the message
        //itself.
        return "";
    }
    //
    //obtain the message of the body, and translation of words
//    translate_message(message: message) {
//        //
//        //retrieve the body of the text
//        const msg = message.body;
//    }
    //
    //Once a user has selected a type of message, they should be able to reply 
    //to it
//    reply_message(msg:message):Promise<boolean>{
//        //
//        //Get the message you intend to reply
//        const message = await this.get_message(message);
//        //
//        //Get the reply to the message
//        const reply:message= this.get_element("textarea").textContent;
//        //
//        //Send the reply to the user
//        const send = this.send(message);
//        //
//        return true; 
//    }

}
//
//
//
//The reciever's address can either be a number, or an email
//type reciever={ to:Array<number|string>}
//
//The body of the message is an array
//
//The messages interface that allows sending messages from one user to another.
//However, since the margs in the server.exec method only accepts an array as an
//arguement,the body and the sender, must be an iterable array.
export interface message {
    //
    //The reciever of the message. To send a message, the message will either be
    //a phone number or an email address
    get_sender(): string;
    //
    //The body of the message
    get_body():string;
    /*This method works, but implementing each of them individually is much
     * and convinient. Mr. Muraya's arguement is better*/

}
//
//The twilio class that will support sending messages from one user to the other
class twilio extends messenger {
    //
    constructor() {
        super();
    }
    //
    //TO send the user data from twilio, two parameters are needed, namely the recievers phone number
    // and the text message
//     async get_message(): Promise<message> {
//         return message;
//     }
    //
    //Send the message to their recipient
    async send(sms: message): Promise<boolean> {
        //
        //Use the twilio php class to send the message
        const result = await server.exec(
            //
            //The php class name
            "twilio",
            //
            //An array of arguements for constructing the class object
            [],
            //
            //The name of the method to execute
            "send_message",
            //
            //An array of arguements that are parameters to the method
            [sms.get_sender(), sms.get_body()]
        );
        // 
        return true;
    }

}
//
//The Whatsapp class that supports sending messages to WhatsAp group members
class whatsapp extends messenger implements message {
    //
    //
    constructor() {
        super();
    }
    //
    //Send the message to the whatsapp groups. In this case, should the
    async send(media: message): Promise<boolean> {
        //
        return true;
    }

}
//
//The mailer class that supports sending emails to users with valide email addresses
class mailer extends messenger implements message {
    //
    //
    constructor() {
        super();
    }
    //
    //Get the email message
//     async get_message(): Promise<message> {
//         return message;
//     }
    // //
    //Send the email to the user with valid user emails.
    async send(email: message): Promise<boolean> {
        
     //
        //Use the twilio php class to send the message
        const result = await server.exec(
            //
            //The php class name
            "twilio",
            //
            //An array of arguements for constructing the class object
            [],
            //
            //The name of the method to execute
            "send_message",
            //
            //An array of arguements that are parameters to the method
            [sms.get_sender(), reciever,sms.get_body(), subject,attachment,is_html ]);
        //
        //
        return true
    }
}