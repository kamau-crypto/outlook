//Import app from the outlook library.
//
//Resolves reference to the asset.products data type
import * as outlook from '../../../outlook/v/code/outlook.js';
//
//Resolves the app than main extends
import * as app from "../../../outlook/v/code/app.js";
//
//Import the test msg class.
import * as msg from "./msg.js"
//
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
                        listener: ["event", ()=>{this.new_msg()}]
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
