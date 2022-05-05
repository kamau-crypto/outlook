//
//Import app from the outlook library.
import {assets, popup} from '../../../outlook/v/code/outlook.js';
import * as outlook from '../../../outlook/v/code/outlook.js';

import * as app from "../../../outlook/v/code/app.js";
//
import {io} from '../../../outlook/v/code/io.js';
//
//Import server
import * as server from '../../../schema/v/code/server.js';
//
//Import schema.
import * as schema from '../../../schema/v/code/schema.js';
//
//Resolve the iquestionnaire
import * as quest from '../../../schema/v/code/questionnaire.js';
//
//Resolve the reference to the journal interface
import * as mod from '../../../outlook/v/code/module.js';
//
//System for tracking assignments for employees of an organization.
//
//A column on the application database that is linked to a corresponding one
//on the user database. Sometimes this link is broken and needs to be
//re-established.
type replica = {ename: string, cname: string};
//
export default class main extends app.app {
    //
    //Initialize the main application.
    constructor(config: app.Iconfig) {
        super(config);
    }
    //
    //
    //Retuns all the inbuilt products that are specific to
    //thus application
    get_products_specific(): Array<outlook.assets.uproduct> {
        return [

            {
                title: "Manage Rental Account",
                id: 'rental',
                solutions: [
                    //
                    //Edit any table in this application
                    {
                        title: "Super User Table Editor",
                        id: "edit_table",
                        listener: ["event", () => this.edit_table()]
                    }
                ]
            },
            {
                title: "Save the billing records",
                id: 'rental',
                solutions: [
                    //
                    //Edit any table in this application
                    {
                        title: "Save KPLC Bill Data",
                        id: "kplc_data",
                        listener: ["event", () => this.save_bill()]
                    }
                ]
            }
        ]

    }
    //
    //Edit any table in the system
    async edit_table() {
        //
        //1. aGet all the tables from the system as key value pairs
        //
        //1.1 Get the application database
        const dbase = this.dbase!;
        //
        //1.2 Use the database to extract the entities
        const enames = Object.keys(dbase.entities);
        //
        //1.3 Map the entities to the required key value pairs
        const pairs = enames.map(ename => ({key: ename, value: ename}));
        //
        //2. Use the pairs to create a new choices POPUP that returns a selected
        //table
        const Choice = new outlook.choices<string>(this.config.general, pairs, "table", "#content", "single");
        //
        //3. Open the POPUP to select a table.
        const selected = await Choice.administer();
        //
        //4. Test whether the selection was aborted or not
        if (selected === undefined) return;
        //
        //5. Use the table to run the CRUD services.
        const subject: outlook.subject = [selected[0], this.dbname];
        const verbs: Array<outlook.assets.verb> = ['create', 'review', 'update', 'delete'];
        this.crud(subject, verbs);
    }
    //
    //Saving the KPLC bill
    async save_bill() {
        //
        //Instantiate the billing class
        const bill = new electricity_bill(this);
        //
        //
        await bill.administer();
    }

}
//
//The electricity billing class that will be used to save 
class electricity_bill extends outlook.baby<void> implements mod.journal {
    //
    //The format of the bill data
    public bill:Array<string>;
    //
    //The constructor to the class that contains the data from the module
    constructor(
        //
        //The mother view to the application
        mother: outlook.page
    ) {
        //
        //The structure of the bill to be processed
        
        //
        super(mother, "../templates/e_bill.html");
    }
    //
    //Implement the check method to ensure that the user has filled the required
    //input fields in the baby window
    async check(): Promise<boolean> {
        //
        //Get the KPLC data
        const data = await this.get_bill();
        //
        //Save the records
        const save = await this.save();
        //
        //Update the accounting records
        const journal = await this.accounting();
        return true;
    }
    async get_result(): Promise<void> {

    }
    //
    //Save the inputs into the database
    async save() {

    }
    //
    //Get the KPLC Bill inputs from the html file 
    async get_bill(): Promise<Array<string>> {
        //
        //Get all the inputs of type date,text, and number
        const inputs: HTMLElement = this.get_element("bill");
        //
        //Get the kplc text message
        const bill: string = (<HTMLInputElement> inputs).value;
        //
        //Construct the regular expression to extract the details from the message.
        //Get the Name of the KPLC account holder
        const name: Array<string> = bill.match(/(?<=Name:\s)(\w+\s){3}/g)!;
        //
        //The account number associated with the Name
        const account_number: Array<string> = bill.match(/(?<=Account number:\s)\w+/g)!;
        //
        //The meter number
        const meter_number: Array<string> = bill.match(/(?<=Meter number:\s)(\w+)/)!;
        //
        //The current balance held by the account:- positive values imply arrears
        //negative values imply excessive payments.
        const curr_bal: Array<string> = bill.match(/(?<=Balance:\s)(\w+\S){3}/g)!;
        //
        //The total unpaid amount of that account
        const unpaid_amount: Array<string> = bill.match(/(?<=Unpaid Bill Amount:\s)(\w+\S){3}/g)!;
        //
        //The unpaid amount's due date
        const due_date: Array<string> = bill.match(/(?<=Due Date:\s)(\w+\S){3}/g)!;
        //
        return [...name, ...account_number, ...meter_number, ...curr_bal, ...unpaid_amount, ...due_date];
    }
    //
    //Update the KPLC billing account once a bill is processed
    async accounting() {
        //
        //Get the business id
        const business_id: string | number = this.get_business_id();
        //
        //The journal entry
        //
        //The credit account
        const credit = this.get_credit();
        //
        //The debit account
        const debit = this.get_debit();
    }
    //
    //Get the business id
    get_business_id() {

    }
    //
    //Use the selected option of whether to credit or debit an account
    get_credit() {

    }
    //
    //Debit the account
    get_debit() {

    }
}