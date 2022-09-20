//
//Resolve the reference to the unindexed product(uproduct)
import * as outlook from '../../../outlook/v/code/outlook.js';
//
//Resolve the reference to the app class
import * as app from "../../../outlook/v/code/app.js";
//
//Resolve the reference to the server class
import * as server from "../../../schema/v/code/server.js";
//
//Resolve the reference to the imerge structure
import * as lib from "../../../schema/v/code/library";
//
//Resolve the reference to the merger class
import merger from "../../../outlook/v/code/merger.js";
//
//Resolve the interface reference to the outlook class
import * as mod from "../../../outlook/v/code/module.js";
//
//Resolve the reference to the schema
import * as schema from "../../../schema/v/code/schema.js";
//
//A business is a type that consists of both the business_name and the business_id
type business = {
    //
    //The id to the business
    id: string,
    //
    //The name of the business
    name: string
}
//
//Main application
export default class main extends app.app {
    //
    //Initialize the main application.
    constructor(config: app.Iconfig) {
        super(config);
        //
    }
    //
    //Returns all the inbuilt products that are specific to
    //this application
    get_products_specific(): Array<outlook.assets.uproduct> {
        return [
            {
                id: "contributions",
                title: "Manage Member Contributions",
                solutions: [
                    //
                    //Join a group.
                    {
                        title: "Manage an event",
                        id: "event_manage",
                        listener: ["crud", 'event', ['review'], '+', "mutall_chama"]
                    },
                    //
                    //Manage the members
                    {
                        title: "Membership management",
                        id: "member_manage",
                        listener: ["crud", "member", ['review'], '+', "mutall_chama"]
                    },
                    //
                    //Edit any table in this application
                    {
                        title: "Super User Table Editor",
                        id: "edit_table",
                        listener: ["event", () => this.edit_table()]
                    },
                    //
                    //Select a group or groups you belong to
                    {
                        title: "Select a group",
                        id: "select_group",
                        listener: ["event", () => this.group_selector()]
                    },
                    //
                    //Select a group or groups you belong to
                    {
                        title: "Register chama",
                        id: "register_chama",
                        listener: ["event", () => this.register_chama()]
                    },
                    //
                    //
                    {
                        title: "Tabulate Contributions",
                        id: "cross_tab",
                        listener: ["event", () => this.cross_tab()]
                    },
                    {
                        title: "Merge Contributions",
                        id: "merge_contribution",
                        listener: ["event", () => this.merge_contributions()]
                    },
                    {
                        title: "Merge General",
                        id: "merge_general",
                        listener: ["event", async () => {
                            //Create a new object
                            const consolidate = new merge_general(this, "merge_general.html");
                            //
                            await consolidate.administer();
                        }]
                    }

                ]
            },
            {
                id: "svg",
                title: "SVG development",
                solutions: [
                    {
                        title: "SVG",
                        id: "svg_development",
                        listener: ["event", async () => {
                            //Create a new object
                            //                            const consolidate = new svg(this, "../svg/svg.html");
                            //                            //
                            //                            await consolidate.administer();
                        }]
                    }
                ]
            },
            {
                id: "msg",
                title: "Messaging",
                solutions: [
                    {
                        title: "send a message",
                        id: "create_sms",
                        listener: ["event", async () => {
                            //Create a new object
                            const consolidate = new sms(this, "sms.html");
                            //
                            await consolidate.administer();
                        }]

                    },
                    {
                        title: "send an email",
                        id: "create_email",
                        listener: ["event", async () => {
                            //Create a new object
                            const consolidate = new email(this);
                            //
                            await consolidate.administer();
                        }]

                    },
                    {
                        title: "send a whatsapp message",
                        id: "create_whatsapp",
                        listener: ["event", async () => {
                            //Create a new object
                            const consolidate = new WhatsApp(this, "../whatsapp/whatsapp.html");
                            //
                            await consolidate.administer();
                        }]

                    }

                ]
            },
            {
                id: "event_management",
                title: "Manage an Event",
                solutions: [
                    {
                        title: "Create an event",
                        id: "create_event",
                        listener: ["event", async () => {
                            //Create a new object
                            const consolidate = new event_planner(this);
                            //
                            await consolidate.administer();
                        }]

                    }
                ]
            },
            {

                id: 'business',
                title: "business selector",
                solutions: [
                    //
                    //Edit any table in this application
                    {
                        title: "fill_business_selector",
                        id: "busines_selector",
                        listener: ["event", () => this.business_selector()]
                    }
                ]
            }
        ]
    }
    //
    //Register a chama
    async register_chama(): Promise<void> {
        //
        //Get a chama login credentials
    }
    //
    //Paint the messages with messages generated by the group.
    async populate_messages(): Promise<void> {
        //
        //Define the query to set the results
        const query = `
                        select
                            msg.date,
                            member.email as sender,
                            msg.text
                        from msg
                            INNER JOIN member on member.member= msg.member
                    `;
        //
        //Get the messages
        const msgs: Array<{ date: string, sender: string, text: string }>
            = await server.exec("database", ["mutall_chama"], "get_sql_data", [query]);
        //
        //Get the section to paint the messages
        const panel: HTMLElement = this.get_element("message");
        //
        for (let msg of msgs) {
            //
            //Destructure the msg array
            const { date, sender, text } = msg;
            //
            //Create the label to hold the messages panel as shown below.
            const texts = this.create_element(panel, 'div', { className: 'msg' });
            this.create_element(texts, 'div', { className: 'date', textContent: date });
            this.create_element(texts, 'div', { className: 'sender', textContent: sender });
            this.create_element(texts, 'div', { className: 'text', textContent: text });
        }
    }
    async populate_events(): Promise<void> {
        //
        //Create the events query
        /*
         *             `
            select 
                event.date,
                event.name,
                group.name as group
            from event
            inner join group on event.group= group.group
            ORDER by date DESC
            `
         */
        const sql =
            `
            select 
                date,
                name
            from event
            ORDER by date DESC
            `
        //
        //Get the events
        const events: Array<{ date: string, name: string }> = await server.exec("database", ["mutall_chama"], "get_sql_data", [sql]);
        //
        //Get the panel to paint the events
        const evt = this.get_element("event")
        //
        //Paint the events panel with the events as they arrive
        for (let event of events) {
            //
            //Destructure the events array
            const { date, name } = event;
            //
            //Create a label to paint the messages to messages panel
            const occasion = this.create_element(evt, 'div', { className: 'event' });
            this.create_element(occasion, 'div', { className: 'date', textContent: date });
            this.create_element(occasion, 'div', { className: 'name', textContent: name });
        }

    }
    //
    //Merge the contributions
    async merge_contributions(): Promise<void> {
        //
        //Create a new object
        const consolidate = new merge_contrib(this, "merge_contribution.html");
        //
        //Get the contribution data to paint it to the viewing area
        await consolidate.get_data();
        //
        await consolidate.administer();

    }
    //
    //Display the member contributions for all the group events
    async cross_tab(): Promise<void> {
        //
        //Create the view where we want to display the table
        const view: sql_viewer = new sql_viewer(this, this.config.general);
        //
        await view.get_data();
        //
        await view.administer();
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
        const Choice = new outlook.choices<string>(this.config.general, pairs, "table", undefined, "#content", "single");
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
    //Adding the Business Selector
    async group_selector(): Promise<void> {
        //
        //1. List all available Chama
        const chama = await server.exec("database", ["mutall_chama"], "get_sql_data",
            ["select `name` from `group`"]);
        //
        //Set the slected groups to accept multiple values
        const pairs = chama.map(pair => { return { key: "name", value: String(pair.name) } });
        //
        // 1.1 Use the listed chamas to create a popup
        const Choice = new outlook.choices<string>("general", pairs, "chama", "", "#content", "single");
        //
        //2. Select one or more groups
        const selected = Choice.administer();
        //
        //3. Update the Databases in both "user" and "application"
        //
        //4. Respect the business selector to all crud sql's
    }

    async business_selector() {
        //
        //Instantiate the billing class
        const select = new fill_selector(this);
        //
        //
        await select.administer();
    }
}
//
//The terminal class 
class terminal extends outlook.baby<true>{
    //
    constructor(
        //
        //The mother view to the application
        mother: outlook.page,
        //
        //The html page to load
        html: string
    ) { super(mother, html); }
    //
    //This method does nothing other than satisfying the contractual obligations
    //of a baby class.
    async get_result(): Promise<true> { return true; }
    //
    //The check method checks the inputs from the html page
    async check(): Promise<boolean> { return true; }
    //
    //The show_panels method for painting the methods from the database
    async show_panels(): Promise<void> { return }

}
// 
//This is a view is used for displaying sql data in a table
class sql_viewer extends terminal {
    //
    //
    //This is the structure of the cross tabulation records.
    public input?: Array<{ member: number, email: string, events: { [index: string]: number } }>;
    //
    //The headers to populate the cross tab table with their headings
    public headers?: Array<{ name: string }>;
    //
    constructor(
        // 
        //This popup parent page.
        mother: outlook.page,
        //
        //The html file to use
        filename: string,
        //

    ) {
        // 
        //The general html is a simple page designed to support advertising as 
        //the user interacts with this application.
        super(mother, filename);
    }
    // 
    //Reporting does not require checks and has no results to return because 
    // it is not used for data entry.
    async check(): Promise<boolean> { return true; }
    async get_result(): Promise<true> { return true }
    //
    //Add the input buttons to each email column. Here, providing the checks is
    //dependent on whether the popup window has a merge button
    add_check_box(td: HTMLTableCellElement, member: number): void { }
    //
    //Display the report 
    async show_panels() {
        // 
        //Get the access to the content panel and attach the html
        const content = this.get_element('content');
        //
        //Hide the go button from the general html since it is not useful in the 
        //the reporting
        this.get_element("go").hidden = true;
        //
        //Create a table and display the values in a proper format
        //Create the table element
        const table = this.create_element(content, 'table', {});
        //
        //Create the thead element
        const thead = this.create_element(table, 'thead', {});
        //
        //Create the table's body
        const tbody = this.create_element(table, 'tbody', {});
        //
        //
        //Use the columns to create a th
        const th = this.create_element(thead, 'tr', {});
        //
        //Populate the email th
        this.create_element(th, 'th', { textContent: "email" });
        //
        //Populate the events th
        this.headers!.forEach(header => {
            //
            //events:{[index:string]:number}
            //Destructure the header
            const { name } = header;
            //
            //Create a header associated with each event
            this.create_element(th, 'th', { textContent: name });
            //
        });
        //
        //Add the values as rows to the table's body
        this.input!.forEach(row => {
            //
            //Destructure the row
            const { member, email, events } = row;
            //
            //Use the row to create a tr
            const tr = this.create_element(tbody, 'tr', {});
            //
            //Populate the email td
            const td = this.create_element(tr, 'td', { textContent: email });
            //
            //Add the input buton at this point and it should be hidden by default
            //
            //Create an input button before the tr ***
            this.add_check_box(td, member);
            //
            //Add the input button before the email td's
            //.unshift('<input type="checkbox"> </input>');
            //
            //Populating the events
            this.headers!.forEach(header => {
                //
                //Destructure the header
                const { name } = header;
                //
                //
                const value = String(events[name] == undefined ? "" : events[name]);
                //
                //Use this header to create a td
                this.create_element(tr, 'td', { textContent: value });
            });
        });
    }
    async get_data(): Promise<void> {
        //
        //Obtain the contribution values from the database
        //
        //Formulate the query to obtain the values
        const sql = `
                select
                    member.member,
                    member.email,
                    json_objectagg(event.id,contribution.amount) as events
                from 
                    contribution
                    INNER JOIN member on contribution.member= member.member
                    INNER JOIN event on contribution.event= event.event
                group by member`;
        //
        //Execute the query
        const values: Array<{ member: number, email: string, events: string }> =
            await server.exec("database", ["mutall_chama"], "get_sql_data", [sql]);
        //
        //Expected output
        //  [{
        //  member:125, 
        //    email:"Aisha Gatheru",
        //   events: {carol:500},
        //            {ndegwa:100},
        //            {mwihaki_dad:1000}
        //           ]
        //  }]
        //Define the suitable output of the data 
        this.input =
            values.map(value => {
                //
                //
                const { member, email, events } = value;
                //
                //Convert the events string to an event array
                const events_array: { [index: string]: number } = JSON.parse(events);
                //
                //
                return { member, email, events: events_array };
            });
        //
        //Obtain the header values
        this.headers = <Array<{ name: string }>>await server.exec("database", ["mutall_chama"], "get_sql_data",
            ["select event.id as name from event order by date"]);
    }
}
//
//Merging the group contributions
class merge_contrib extends sql_viewer {
    //
    //The email column obtained from the cross tab data
    public pk?: HTMLTableCellElement;
    //
    constructor(
        // 
        //This popup parent page.
        mother: outlook.page,
        //
        //The html file to use
        filename: string
        //
        //The primary key columns,i,e the first records in the eHTMLTableCellElement
    ) {
        // 
        //The general html is a simple page designed to support advertising as 
        //the user interacts with this application.
        super(mother, filename);
        //
    }
    //
    //Execute the merge process by first obtaining the merger data from the
    // current panel. i.e.,from the processed values. 
    async merge(): Promise<void> {
        //
        //Set the database name
        const dbname = "mutall_chama";
        //
        //Set the entity name
        const ename = "member";
        //
        //Construct the members by reading off the checked values.
        //The checked values are needed to form the list of members to be merged.
        // These values are compiled in a structure and returned as values when 
        // we form the members list to complete the imerge structure.
        //Get the checked values by identifying the text boxes associated with 
        //the entry of each member.
        const inputs = document.querySelectorAll('input[type="checkbox]:checked');
        //
        //Convert the nodelist to an array
        const check: Array<Element> = Array.from(inputs);
        //
        //Move through each input button to check on whether it is clicked or not, 
        const values = check.map(input => (<HTMLInputElement>input).value);
        //
        //Pass the collected members as an array
        const players = values.join();
        //
        //Define the members sql
        const members = `
                            select member.member 
                            from member 
                            where member.member 
                            in(${players})
                            `;
        //
        //Construct the imerge object
        const imerge: lib.Imerge = { dbname, ename, members };
        //Construct the merger object
        const Merger: merger = new merger(imerge, this);
        //
        //Execute the merge operation
        await Merger.execute();
    }
    //
    //Add a check box to the given td
    add_check_box(td: HTMLTableCellElement, member: number): void {
        //
        //Resolve the call to the inherited checks method
        super.add_check_box(td, member);
        //
        //Create an input button before
        this.create_element(td, 'input', { type: "checkbox", value: String(member) });
    }
    //
    //Over ride the show panels to attach an event that triggers the merge class
    // for the merging process.
    async show_panels(): Promise<void> {
        //
        await super.show_panels();
        //
        //Get the merge button and add an event to it
        const button = <HTMLSelectElement>this.get_element("merge");
        button.onclick = () => this.merge();
    }
}
//
//
//
//Merging the group contributions
class merge_general extends terminal {
    //
    constructor(
        // 
        //This popup parent page.
        mother: outlook.page,
        //
        //The html file to use
        filename: string
        //
        //The primary key columns,i,e the first records in the eHTMLTableCellElement
    ) {
        // 
        //The general html is a simple page designed to support advertising as 
        //the user interacts with this application.
        super(mother, filename);
        //
    }
    // 
    //Reporting does not require checks and has no results to return because 
    // it is not used for data entry.
    async check(): Promise<boolean> { return true; }
    async get_result(): Promise<true> { return true; }
    //
    //Merging the general records
    async merge(): Promise<void> {
        //
        //Get the merger data
        //Get the database name
        const dbname = (<HTMLInputElement>document.getElementById("dbname")).value;
        //Get the entity name
        const ename = (<HTMLInputElement>document.getElementById("ename")).value;
        //
        const members = (<HTMLInputElement>document.getElementById("members")).value;
        //
        //Construct the imerge object
        const imerge: lib.Imerge = { dbname, ename, members };
        //Construct the merger object
        const Merger: merger = new merger(imerge, this);
        //
        //Execute the merge operation
        await Merger.execute();
    }
    //
    //Over ride the show panels to attach an event that triggers the merge class
    // for the merging process.
    async show_panels(): Promise<void> {
        //
        await super.show_panels();
        //
        //Get the merge button and add an event to it
        const button = <HTMLSelectElement>this.get_element("merge");
        button.onclick = () => this.merge();
    }
}
//
//The outlook class that allows users to develop
class sms extends terminal {
    //
    //
    //
    constructor(
        // 
        //This popup parent page.
        mother: outlook.page,
        //
        //The html file to use
        filename: string
        //
        //The primary key columns,i,e the first records in the eHTMLTableCellElement
    ) {
        // 
        //The general html is a simple page designed to support advertising as 
        //the user interacts with this application.
        super(mother, filename);
        //
    }
    async check(): Promise<boolean> { return true; }
    async get_result(): Promise<true> { return true }
    //
    //
    async show_panels(): Promise<void> {

    }
}
//
//The outlook class that allows users to develop
class email extends terminal {
    //
    //
    //
    constructor(
        // 
        //This is the parent page.
        mother: outlook.page
        //
        //The html file to use
        //filename: string
        //
        //The primary key columns,i,e the first records in the eHTMLTableCellElement
    ) {
        // 
        //The general html is a simple page designed to support advertising as 
        //the user interacts with this application.
        super(mother, "../templates/email.html");
        //
    }
    async check(): Promise<boolean> { return true; }
    async get_result(): Promise<true> { return true }
    //
    //
    async show_panels(): Promise<void> {

    }
}
//
//The outlook class that allows users to develop
class WhatsApp extends outlook.baby<void>{
    //
    //
    //
    constructor(
        // 
        //This popup parent page.
        mother: outlook.page,
        //
        //The html file to use
        filename: string
        //
        //The primary key columns,i,e the first records in the eHTMLTableCellElement
    ) {
        // 
        //The general html is a simple page designed to support advertising as 
        //the user interacts with this application.
        super(mother, filename);
        //
    }
    async check(): Promise<boolean> { return true; }
    async get_result(): Promise<void> { }
    //
    //
    async show_panels(): Promise<void> {

    }
}
//
//Allowing a user to create a new popup to help the user create events with
//start_dates, end_dates, and set all the requirements and it is triggered once
//the event start_date has arrived.
class event
    extends terminal
    implements mod.crontab {
    //
    //Define the default objects to this class
    constructor(public app: main) {
        //
        //Pass the application and the url required by the baby class
        super(app, "schedule.html");
    }
    //
    //Implement the baby class method that verifies that the user has filled the
    //required input fields in the baby window.
    async check(): Promise<boolean> {
        //
        // 1.Collect and check the data that the user has entered
        // 
        // 2. Save the data to the database
        await this.app.writer.save(this);
        // 
        // 3. Send a message to the user if the data was in the correct format
        // not
        await this.app.messenger.send(this);
        // 
        // 4. Update the account book keeping system
        await this.app.accountant.post(this);
        // 
        // 5. Schedule the task to execute if necessary
        await this.app.scheduler.exec(this);
        // 
        return true;
    }
    //
    //Save the record to the database once the data is collected
    //
    //Once you have checked the data for consistency, you can collect the data
    //to the database using this method.
    async get_result(): Promise<true> {
        return true;
    }
}
//
//The event planner class that allows a user to create sshcedules for events to support event
//planning.The user has a double chance of completing this information
class event_planner extends terminal implements
    mod.money,
    mod.questionnaire,
    mod.journal,
    mod.crontab,
    mod.message {
    //
    //The event planner
    public name?: string;
    //
    //The description of an event
    public description?: string;
    //
    //The start date of an event
    public start_date?: string;
    //
    //The end date of an event
    public end_date?: string;
    //
    //Get the members participating in an event
    public members?: number;
    //
    //The mandatory or optional contribution
    public contribution?: string | null;
    //
    //The 
    //
    //The constructor
    constructor(
        // 
        //This is the parent page.
        mother: outlook.page
        //
        //The html file to use
        //filename: string
        //
        //The primary key columns,i,e the first records in the eHTMLTableCellElement
    ) {
        // 
        //The general html is a simple page designed to support advertising as 
        //the user interacts with this application.
        super(mother, "../templates/event_form.html");
        //
    }
    //
    //Collect all inputs from the event planning form.
    get_result(): Promise<true> {
        //
        //1. Retrieve the event's compulsory inputs such as the name,members to
        //alert, the description,and the start and end dates
        //
        //1.1. Get the event's Name
        this.name = this.get_input_value("name");
        //
        //1.3. Retrieve the description of the event
        this.description = this.get_input_value("description");
        //
        //1.4. Get the event's start date
        this.start_date = this.get_input_value("start_date");
        //
        //1.5. Get the event's end date
        this.end_date = this.get_input_value("end_date");
        //
        //2. Collect the optional inputs:- the contributory of an event,the mode of communication,
        //the schedule of the event.
        //
        //2.1. The contribution amount associated with a contributory event.
        //
        //2.2. The channel of communication to communicate with the users
        //
        //2.3 The scheduler of that event.   
        return true
    }
    //
    //The check method validated whether the data collected from the form is formatted correctly
    async check(): Promise<boolean> {
        return true;
    }
    //
    //The show panels method is used for painting an output to the user
    async show_panels(): Promise<void> {
        //
        //Fill the selector with the members from the group
        this.fill_selector("member", "mutall_chama", "users");
        //
        //Set a listener to the recursion buttons. 
        const button: Array<HTMLButtonElement> = Array.from(document.querySelectorAll(".recursion"));
        //
        //Once each recursion button is clicked, it should open the event firing template
        button.forEach((element) => {
            element.addEventListener("click", () => {
                //
                //This is to support the recursion of an event, that should extend a popup.
                //Get the popup
                const recursive = new get_recursion();
                //
                const recursion = recursive.administer();
                //
                //Re
            });
        });

    }
}
//
//This supports the at command for scheduling one off events
type at = { date?: string, time: string }
//
//This is to support the data collect for scheduling repetitive events using the
//crontab
type crontab = { minute: string, hour: string, day_of_month: string, month: string, day_of_week: string }
//
//A recursion is composed of data such as the minute,hour,day of the month,month,and
//the day of the week
type recursion = { repetitive: 'no', send_date: at } | { reptitive: 'yes', start_date: string, frequency: crontab }
//
//To support the recursion of an event, the recursion button needs to event a popup
//page, that records the recursion specified from a user's template
class get_recursion extends outlook.popup<recursion>{
    //
    //
    //The constructor function
    constructor() {
        super("../templates/events_firing.html");
    }
    //
    //Collect and check the recursion data and set the result.
    async check(): Promise<boolean> {
        //
        //1. Collect and check the recursion data
        //
        //2. Set the result property to be accessed later
        //this.result =?? 
        return true
    }
    //
    //Return all inputs from a html page and show cases them to a page
    async get_result(): Promise<recursion> {
        //
        //Return the result that was set during the check.
        //return this.result;

    }
    //
    //There is no implementation for this method
    async show_panels(): Promise<void> {

    }
}
//
//This method fills in the organizationonce a user had logged in which allows
//to create services that are business specific.
class fill_selector extends terminal {
    //
    //
    public business?:business;
    constructor(
        mother: main) {
        super(mother, "../templates/general.html");
    }
    //
    //This method populates the method with business options.
    async show_panels() {
        //
        //Fill the selector specified by the user.
        this.fill_selector("business", "mutall_users", "organization");
        //
        //Get the organization selector
        const selector = <HTMLSelectElement>this.get_element("organization");
        //
        //2.2 Create the new option at the end of the option list...
        //The position of the add new business, how to specify it
        this.create_element(selector, "option", { textContent: "Add a new business", value: "0" });
        //
        //2.3. Once clicked, the user should be able to add a business name to
        //to their organization
        selector.onchange = async(event: Event) => {
            //
            //Stop the default behaviour
            event.stopPropagation();
            //
            if ((event.target as HTMLOptionElement).value == "0") {
                //
                //Call the register business class
                const new_business = new register_business();
                //
                //Administer the new business and collect the business
                this.business= await new_business.administer();
            }
        }
    }
}

//
//THis class allows a user who wants to create a new business to provide
// the business name and the business_id to support login incase the business is
//not among the ones listed.
class register_business extends outlook.popup<business>{
    //
    //constructor
    constructor(
        //
        //A business is defined by the business_name and the business_id
        public organization?: business,
        //
        //The business, after its saved to the database
        public business?: number
    ) {
        super("./new_business.html");
        //
    }
    //  
    //
    //Return all inputs from a html page and show cases them to a page
    async get_result(): Promise<business> {return this.result!;}
    //
    //Collect and check the recursion data and set the result.
    async check(): Promise<boolean> {
        //
        //1. Get and check the business name of the element
        const name:string= this.get_input_value("name");
        //
        //2. Get and check the business_id from the business
        const id:string= this.get_input_value("id");
        //
        //Initialize the result
        this.result={id, name};
        //
        //
        return true
    }
    //
    //This method sends some feedback to the user once the user has successfully
    //registered a business
    async show_panels() {
        //
        //Show an alert if a user saved the data correctly
        if (this.business === 1) alert("You have successfully created your business,\n\
         please relogin to select the business");

    }

}
