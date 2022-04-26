//
//Import app from the outlook library.
import { assets, popup } from '../../../outlook/v/code/outlook.js';
import * as outlook from '../../../outlook/v/code/outlook.js';

import * as app from "../../../outlook/v/code/app.js";
//
import { io } from '../../../outlook/v/code/io.js';
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
//System for tracking assignments for employees of an organization.
//
//A column on the application database that is linked to a corresponding one
//on the user database. Sometimes this link is broken and needs to be
//re-established.
type replica = { ename: string, cname: string };
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
        const pairs = enames.map(ename => ({ key: ename, value: ename }));
        //
        //2. Use the pairs to create a new choices POPUP that returns a selected
        //table
        const Choice = new outlook.choices<string>(this.config.general, pairs, "table", null, "#content", "single");
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

}
