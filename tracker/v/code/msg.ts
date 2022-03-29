//Resolves reference to the asset.products data type
import * as outlook from '../../../outlook/v/code/outlook.js';
import * as server from '../../../schema/v/code/server.js';
import * as schema from '../../../schema/v/code/schema.js';

//
export type Imsg = {msg: string};
//
//Use a pop-up to create a new message.
export class msg extends outlook.popup<Imsg> {
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
        //Get the message.
        const msg = <HTMLTextAreaElement>this.get_element("msg");
        //
        //Test if the message is text area...
        //if (!(msg instanceof HTMLTextAreaElement)){//Not sure why this failed
        //if (msg.nodeName!=='TEXTAREA'){
            //
            //...and if it is not, alert the user
            //throw new schema.mutall_error(`Element of 'msg' is not a HTMLTextAreaElement`);
        //}
        //
        //Compile the message.
        const msg_value: Imsg = {msg: msg.value}
        //
        return msg_value;
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
