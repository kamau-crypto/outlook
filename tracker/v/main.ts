//
//Import the navigator sub-system.
import {navigator} from "./navigator.js";
//
//Import app from the outlook library.
import {popup} from "../../../outlook/v/code/outlook.js";
import * as outlook from "../../../outlook/v/code/outlook.js";

import * as app from "../../../outlook/v/code/app.js";
//
//Import server
import * as server from "../../../schema/v/code/server.js";
//
//Import schema.
import * as schema from "../../../schema/v/code/schema.js";
//
//Resolve the iquestionnaire
import * as quest from "../../../schema/v/code/questionnaire.js";
//
//The shape of the data to collect.
//type msg_data = {
//    msg: string;
//    medium: Array<
//        | {type: "outlook"; id: string}
//        | {type: "whatsapp"; id: number}
//        | {type: "sms"; id: number}
//        | {type: "email"; id: string}
//    >;
//};
//
//, type: 'public'|'private'}
//
//A simple shape structure of the new message data to collect.
type Imsg = {msg: string};
//
//A simple shape structure of the new event data to collect.
type Ievent = {event: string};
//
//A simple shape structure of the new event data to collect.
type Iref = {ref: string};
//
//System for translating local Kenyan languages.
export default class main extends app.app {
    //
    static current: main;
    //
    //Initialize the main application.
    constructor(config: app.Iconfig) {
        super(config);
    }
    //
    //Returns all the inbuilt products that are specific to
    //thus application.
    get_products_specific(): Array<outlook.assets.uproduct> {
        return [
            {
                //
                id: "general_solution",
                title: "General Solution",
                solutions: [
                    {
                        title: "Load Data",
                        id: "load_data",
                        listener: ["event", () => this.load_data()],
                    },
                    {
                        title: "Translate Word",
                        id: "xlate",
                        listener: ["event", () => this.change_content()],
                    },
                    {
                        title: "Navigator",
                        id: "navigator",
                        listener: ["event", () => this.navigate()],
                    }
                ],
            },
            {
                //
                id: "messaging",
                title: "Message Module",
                solutions: [
                    {
                        title: "Create New Message",
                        id: "new_message",
                        listener: ["event", () => this.new_message()],
                    },
                    {
                        title: "Reply to Message",
                        id: "reply_message",
                        listener: ["event", () => this.reply_message()],
                    },
                ],
            },
            {
                //
                id: "payment",
                title: "Payment",
                solutions: [
                    {
                        title: "Tea Delivery",
                        id: "t_delivery",
                        listener: ["event", () => this.tea_delivery()],
                    },
                ],
            },
            {
                //
                id: "evet",
                title: "Event Module",
                solutions: [
                    {
                        title: "Create Event",
                        id: "create_event",
                        listener: ["event", () => this.create_event()],
                    },
                ],
            }
        ];
    }
    //
    //Open a file using the navigation subsystem.
    async navigate() {
        //
        //Create a baby.
        const baby = new navigator(this);
        //
        await baby.administer();
    }
    //
    //Create a new event.
    async create_event() {
        //
        //Create a new pop up.
        const popup = new event();
        //
        //Display the popup and collect the data for the newly created event.
        const result: Ievent | undefined = await popup.administer();
        //
        //Check if we have any data or the user cancelled the popup.
        if (result === undefined) return;
        //
        //Display the results.
        alert(result);
    }
    //
    //Capture data for recently delivered tea-break snacks.
    async tea_delivery(): Promise<void> {
        //
        //Create an instance of the delivery class.
        const delivery = new tea_delivery();
        //
        //Open the popup and return when the user is done.
        const result= await delivery.administer();
        //
        //Check the validity of the data.
        if (result === undefined)return;
        //
        //At this point, the result must be valid; display it.
        alert(JSON.stringify(result));
    }
    //
    //Allow the user to create a new message and save it in the database.
    async new_message(): Promise<void> {
        //
        //1. Create a pop that facilitates sending a new message.
        const Msg = new msg();
        //
        //Collect all the data from the user.
        const result: Imsg | undefined = await Msg.administer();
        //
        //Check the validity of the data.
        if (result === undefined) return;
        //
        //At this point, the result must be valid; display it.
        alert(JSON.stringify(result));
    }
    //
    //Reply to a message.
    async reply_message() {
        //
        alert("Reply message");
    }
    //
    //Use a json file in the Iquestionnaire format to current databas
    async load_data(): Promise<void> {
        //
        //Create the load data popup page
        const Loader = new load_data();
        //
        //Administer it.
        await Loader.administer();
        //
        //Refresh the home page if necessary
    }
    //
    //Translate the a user specified word into mutliple Kenyan languages.
    //
    //Replace the details in the content panel with the translation page.
    change_content() {
        //
        //The content to replace the content panel with.
        const details = `
            
            <input id="word" type="text" placeholder="Enter word to translate here"/>
            <button id="xlate">Translate</button>
            <button id="cancel">Cancel </button>
            <br/><br/>
            <table>
                <thead></thead>
                <tbody id="result"></tbody>
            </table>
        `;
        //
        //Get the content panel.
        const content = this.get_element('content');
        //
        //Replace content panel's innerHTML.
        content.innerHTML = details;
        //
        //Add the translation event on the xlate button
        //
        //Get the translation button
        const xlate = this.get_element("xlate");
        //
        //Add the translation event listener to the 'xlate' element
        xlate.onclick = async () => this.tafsiri();
    }
    //
    //Do the translation
    async tafsiri(): Promise<void> {
        //
        //Formulate the full sql statement
        //
        //Get the word element
        const word = <HTMLInputElement> this.get_element("word");
        //
        //Formulate the subquery for filtering words
        const subquery = `SELECT 
                word.name, term.term
            FROM word 
                INNER JOIN synonym ON word.word = synonym.word
                INNER JOIN translation ON synonym.translation = translation.translation
                INNER JOIN term ON translation.term = term.term
            WHERE word.name='${word.value}'`;
        //
        //Use the subquery to complete the full query
        const query = `SELECT 
                term.name as term, 
                language.name as lang, 
                word.name as word,
                translation.meaning
            FROM language 
                INNER JOIN translation ON language.language = translation.language
                INNER JOIN term ON translation.term = term.term
                INNER JOIN synonym ON translation.translation = synonym.translation
                INNER JOIN word ON synonym.word = word.word
                INNER JOIN (${subquery}) as search ON term.term = search.term`;
        //
        //Execute the query to get the fuel
        const Ifuel = await server.exec(
            "database",
            [app.app.current.dbname],
            "get_sql_data",
            [query]
        );
        //
        //Tabulate the results
        //
        //Get the result element
        const tbody = this.get_element("result");
        //
        //Attach some content to the thead.
        const thead: HTMLTableSectionElement | null = document.querySelector("thead");
        thead!.innerHTML = `
            <thead>
                <th>Term</th>
                <th>Language</th>
                <th>Translation</th>
                <th>Meaning</th>
            </thead>
        `;
        //
        //Clear the body.
        tbody.innerHTML = "";
        //
        //Loop through all the rows of the ifuel
        for (let cnames of Ifuel) {
            //
            //Create a tr and add it to the tbody
            const tr = this.create_element(tbody, "tr", {});
            //
            //Loop through all the columns of a row, attaching them to the tr
            //as td's
            for (let cname in cnames) {
                //
                //Create a td and add it to the tr
                const td = this.create_element(tr, "td", {});
                //
                //Set the txt value
                td.textContent = String(cnames[cname]);
            }
        }
    }
}
//
//The class responsible for creating a new popup in order to help the user
//create an event and the system to collect data thereon.
class event extends popup<Ievent>{
    //
    //Construct an event class.
    constructor() {
        //
        //Pass on a url to the popup class.
        super('new_event.html');
    }
    //
    //Implement a popup's abstract method to verify that indeed the user has
    //has filled in the required input fields of a popup.
    check() {
        return true;
    }
    //
    //Collect the data the user enters in the popup.
    async get_result(): Promise<Ievent> {
        //
        //Get the reference number.
        const organization = this.get_element("organization");
        //
        //Ensure we have a textarea element.
        if (!(organization instanceof HTMLInputElement)){
            //
            throw new schema.mutall_error(
                `Input  for element "organization" not found`
            );
        }
        //
        //Compile the reference number.
        const ref_no: Ievent = {event: organization.value}
        //
        return ref_no;
    }
}
//
//Create a pop up that will be used to save details about the deliveries made 
//to the office which require payment.
class tea_delivery extends popup<Iref> {
    //
    constructor() {
        super("t_delivery.html");
    }
    //
    //In future, check if a file json file containing Iquestionnaire is selected.
    //For now, do nothing
    async check():Promise<boolean> {
        return true;
    }
    //
    //Collect evidence to show whether we should update the home page or not.
    async get_result(): Promise<Iref> {
        //
        //Get the reference number.
        const ref = this.get_element("ref");
        //
        //Ensure we have a textarea element.
        if (!(ref instanceof HTMLInputElement)){
            //
            throw new schema.mutall_error(`Input  for element "ref" not found`);
        }
        //
        //Compile the reference number.
        const ref_no: Iref = {ref: ref.value}
        //
        return ref_no;
    }
}
//
//Use a pop-up to create a new message.
class msg extends popup<Imsg> {
    //
    constructor() {
        super("new_msg.html");
    }
    //
    //In future, check if a file json file containing Iquestionnaire is selected.
    //For now, do nothing
    async check():Promise<boolean> { return true; }
    //
    //Collect the message and media of communication specified by the user.
    async get_result(): Promise<Imsg> {
        //
        //Get the message text.
        const text = this.get_element('msg');
        //
        //Ensure we have a textarea element.
        if (!(text instanceof HTMLTextAreaElement)){
            //
            throw new schema.mutall_error(`Textarea  for element "msg" not found`);
        }
        //
        //Compile the message.
        const msg: Imsg = {msg: text.value};
        //
        return msg;
    }
    //
    //Get the list of members from the database.
    public async get_members(): Promise<string> {
        //
        //Get all member ids from the database.
        const members: Array<{id: string}> = await server.exec(
            "database",
            ["kentionary3"],
            "get_sql_data",
            ["SELECT id FROM member"]
        );
        //
        //Formulate the options from the members=.
        const options: Array<string> = members.map(
            (member) => `<option value='${member.id}'>${member.id}</option>`
        );
        //
        //Convert the array of options into some text separated by a break statement.
        const options_str: string = options.join("\n");
        //
        //Return the text.
        return options_str;
    }
}
//
//Modelling the popup windw for loading data from large tables. After
//the popu admnistraton we check whenther we need to refresh the home page
//or not. Hence the boolean parameter
class load_data extends popup<boolean> {
    //
    //The file input element for this popup
    public input?: HTMLInputElement;
    //
    //The element where to attach a the load report
    public report?: HTMLDivElement;
    //
    //The load button
    public load?: HTMLButtonElement;
    //
    //
    //Create a popup with default window specs settings
    constructor() {
        super("load_data.html");
    }
    //
    //In future, check if a file json file containing Iquestionnare is selected.
    //For now, do nothing
    async check():Promise<boolean> {
        return true;
    }
    //
    //Collect evidence to show whether we should update the home page or not
    async get_result(): Promise<boolean> {
        //
        ///For now asume there is none
        return false;
    }

    //Display the pop-specific panels
    async show_panels(): Promise<void> {
        //
        //Add input for the json file that has the Iquestionnaire
        //
        //Let the content node be the anchor tag
        const anchor = this.get_element("content");
        //
        //Create the label element with no child nodes
        const label = this.create_element(anchor, "label", {});
        //
        //Add the file span label as a child of the label
        const file = this.create_element(label, "span", {});
        file.textContent = "Enter the file that has the Iquestonnaire";
        //
        //Set the file input element and add it to the label children
        this.input = this.create_element(label, "input", {
            type: "file",
        });
        //
        //Add the load export data event on the ok button
        //
        //Get the load button
        const load = this.get_element("load");
        //
        //Add the file export listener to the 'go' element
        load.onclick = async () => this.load_data();
        //
        //Add the reporting element
        this.report = this.create_element(anchor, "div", {});
    }

    //Read Iquestionnaire from the input file and use it to save the referenced
    //data
    async load_data(): Promise<void> {
        //
        //Clear the reporting tag
        this.report!.textContent = "";
        //
        //Create a file reader
        const reader = new FileReader();
        //
        if (this.input!.files === null)
            throw new schema.mutall_error("Please select a file");
        //
        //Get the file blob
        const blob: Blob = this.input!.files![0];
        //
        //Use the blob to read the data
        reader.readAsText(blob);
        //
        //Wait for the data to load
        const text = <string> await new Promise((resolve) => {
            reader.onload = async () => resolve(<string> reader.result);
        });
        //
        //Parse the json string
        const Iquestionnaire = <quest.Iquestionnaire> JSON.parse(text);
        //
        //Call questionaire::export(Iquestionnaire)
        //
        const result = await server.exec(
            "questionnaire",
            [Iquestionnaire],
            "load",
            ["log.xml"]
        );
        //
        //3.Verify that writing to db was successful
        //and report to the user and throw an exception.
        this.win.alert(JSON.stringify(result));
    }
}