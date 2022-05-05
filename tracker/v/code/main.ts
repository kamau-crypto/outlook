//Import app from the outlook library.
//
//Resolves reference to the asset.products data type
import * as outlook from '../../../outlook/v/code/outlook.js';
//
//Resolves the app than main extends
import * as app from "../../../outlook/v/code/app.js";
//
//Import the test msg class.
import * as msg from "./msg.js";
//
//Resolve references to the server
import * as server from "../../../schema/v/code/server.js";
//
//The main class that supports all our applications.
export default class main extends app.app {
    //
    //Initialize the main application.
    constructor(config: app.Iconfig) {
        super(config);
    }

    //
    //Retuns all the products that are specific to this application. They are
    //used to exapnd those from the base application
    get_products_specific(): Array<outlook.assets.uproduct> {
        return [

            {
                title: "Actions",
                id: 'actions',
                solutions: [
                    {
                        title: "View due assignments",
                        id: "view_due_assignments",
                        listener: ["event", () => this.vue_due_assignments()]
                    },
                    {
                        title: "Manage Events",
                        id: "events",
                        listener: ["crud", 'event', ['review'], '+', "mutall_users"]
                    },
                    {
                        title: "Manage Messages",
                        id: "messages",
                        listener: ["crud", 'msg', ['review'], '+', "mutall_users"]
                    },
                    {
                        title: "Create Message",
                        id: "create_msg",
                        listener: ["event", () => { this.new_msg() }]
                    }
                ]
            }
        ]
    }
    //
    //Allow the user to create a new message and save it in the database.
    async new_msg(): Promise<void> {
        //
        //1. Create a pop that facilitates sending a new message.
        const Msg = new msg.msg();
        //
        //Collect all the data from the user.
        const result: msg.Imsg | undefined = await Msg.administer();
        //
        //Check the validity of the data.
        if (result === undefined) return;
        //
        //Use the questionnare in php class to save the data to the database.
        this.win.alert(JSON.stringify(result));
        //
    }
    //
    //List all assignments that are due and have not been reported.
    //Ordered by Date. 
    vue_due_assignments(): void {
        alert("This method is not implemented yet.")
    }
}
//this class allows users to complete the level 1 registration by getting the\
//user roles
abstract class complete_leve11_reg extends outlook.popup<true>{

    //
    //Constructor method that references the dat
    constructor(
        //
        //The dbname
        public dbname: string,
        //
        //The ename
        public ename: string,
        //
        //The selector id to the element to be appended with the organization option
        public selectorid: string
    ) {
        super("../../../");
    }
    //
    //Get the results from the users.
    async get_result(): Promise<true> {
        //
        //
        return true;
    }
    //
    //Checks whether there is something selected from the home page
    async check(): Promise<true> {
        //
        //
        return true;
    }
    //
    //Fill the html roles section with the proper roles
    async fill_roles() {

    }
    //
    //Fills the organizational selectors
    async fill_selector(dbname: string, ename: string, selectorid: string) {
        //
        //1. Get the data from the database
        //1.1 Construct the query to run the sql
        const sql = `select 
                       name as business 
                   from business`;
        //
        //Get the organization
        const organizations: Array<{ business: string }>
            = await server.exec("database", [dbname], "get_sql_data", [sql]);
        //
        //Get the section to insert the selector
        const selector: HTMLElement = this.get_element(selectorid);/*organization*/
        //
        for (let organization of organizations) {
            //
            //Destructure the organizations array to get the business
            const { business } = organization;
            //
            //Create the selector option that will fill the organizations
            const selection = this.create_element(selector, 'select', { className: 'organization' });
            this.create_element(selection, 'option', { className: 'business', value: `${business}`, textContent: business });
        }
    }
    //
    //
    async show_panels(): Promise<void> {
        //
        //1. Populate the roles fieldset
        this.fill_roles();
        //
        //2.Populate the organization
        this.fill_selector(dbname, ename, selectorid);
    }
}
