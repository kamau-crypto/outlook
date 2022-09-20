//Resolve references to the outlook file and its class methods
import * as outlook from "../../../outlook/v/code/outlook.js";
//
//Resolve references to the server class mathods
import * as server from "../../../schema/v/code/server.js";
//
//this class allows users to complete the level 1 registration by getting the\
//user roles
export default class complete_leve11_reg extends outlook.popup<true>{
    //
    //The dbname
    public dbname:string;
    //
    //The ename
    public ename:string;
    //
    //The selector id to the element to be appended with the organization option
    public selectorid:string;
    //
    //Constructor method that references the dat
    constructor(){
        super("../../../");
    }
    //
    //Get the results from the users.
    async get_result():Promise<true>{
      //
      //
        return true;  
    }
    //
    //Checks whether there is something selected from the home page
    async check():Promise<true>{
        //
        //
        return true;   
    }
    //
    //Fill the html roles section with the proper roles
    async fill_roles(){
        
    }
    //
    //Fills the organizational selectors
    async fill_selector(dbname:string,ename:string,selectorid:string){
        //
        //1. Get the data from the database
        //1.1 Construct the query to run the sql
        const sql=`select 
                       name as business 
                   from business`;
        //
        //Get the organization
        const organizations: Array<{ business:string}>
            = await server.exec("database", [dbname], "get_sql_data", [sql]);
        //
        //Get the section to insert the selector
        const selector: HTMLElement = this.get_element(selectorid);/*organization*/
        //
        for (let organization of organizations) {
            //
            //Destructure the organizations array to get the business
            const { business} = organization;
            //
            //Create the selector option that will fill the organizations
            const selection = this.create_element(selector, 'select', { className: 'organization' });
            this.create_element(selection, 'option', { className: 'business',value:`${business}`,textContent: business });
        }
    }
    //
    //
    async show_panels():Promise<void>{
        //
        //1. Populate the roles fieldset
        this.fill_roles();
        //
        //2.Populate the organization
        this.fill_selector(dbname,ename,selectorid);
       }
}