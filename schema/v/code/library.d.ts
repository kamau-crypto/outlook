//
// 
import * as schema from "./schema.js"
import * as quest from "./questionnaire.js"

//We don't need to export things from a d.ts.
//export {node, database,editor,record,ename,dbname };
//
//This represents the modal.
export type Ifuel = Array<{ [index: string]: basic_value }>;
//
//Modelling the database connection
class database {
    //
    //Testing an explicit constructor
    //
    //The login credentials for this database are fed in a config file 
    constructor(

        //The database name.
        name: string,
        //
        //An optional boolean that indicates whether we desire a database
        //complete with its entities or not. The the default is complete.
        // If not  complete an empty shell is returned; this may be useful when 
        //quering the database directly, i.e., without the need of the object model
        complete: boolean = true,
        //
        //An optional Throws an error as soon as they are found the  default is 
        //true. If false the error will be buffered in a property called errors
        //and can be accessed through the method report
        throw_exception: boolean = true
    );
    //
    //Returns data as an array of simple objects after executing 
    //the given sql on this database
    get_sql_data(sql: string): Ifuel;
    // 
    //Returns the accounting details of a specified account 
    accounting(accname: string): Ifuel;
    //
    //Returns a complete database structure, .i.e one that is populated with 
    //entities and columns
    //We return an any because we do not wish to check the structure of our data  
    export_structure(): schema.Idatabase;
    //
    //The query command is used for executing the insert,
    //update and delete statements. The return value is the number of affected rows.
    query(sql: string): number;
    //
    //Use the given credentials to authenticate the matching user.
    authenticate(name: string, password: string): boolean;
    //
    //Use the given credentials to create a new user account.
    register(name: string, password: string): void;

}
//
//The sql statement string, e.g, select client.name from client 
type sql = string;
//
//This class models a select statement that retrieves all the columns 
//of an entity. In particular the foreign keys values are accompanied 
//by their friendly names. These names are useful for editing/looking up
// the foreign key values.
class editor {
    //
    constructor(
        //
        //This is the entity name from which we are doing the selection
        ename: ename,
        //
        //The name of the database in which the entity is defined
        dbname: dbname
    );
    //
    //Returns the standard string representation of a select sql statement
    stmt(): string;
    //
    //This method returns the metadata necessary for driving a 
    //CRUD table.
    //The last paramete maximum length is returned as a string
    describe(): [schema.Idatabase, Array<cname>, sql, string];
}
///Mutall label format for exporting data to a database.
//The data has the following structure [dbname, ename, alias, cname, value].
export type dbname = string;
//
//The table in the named dbname where the data will be saved 
export type ename = string;
//
//The number of similar records being saved
type alias = Array<number>;
//
//COlumn name in the database table to be saved 
type cname = string;
//
//The primary key value is a number formated as a string
export type pk = string;
//
//The location of a td in a crud table. This is important for saving and 
//restoring a crud td. 
export type position = [rowIndex, cellIndex];
//
//The actual value being sent to the server 
export type basic_value = boolean | number | string | null;

//
//An atom is the smallest datatype that is aware of its origin.
export type atom = [basic_value, position?];
//
//The exact position of the td with the data to be saved relative to the 
//table 
type cellIndex = number;
type rowIndex = number;
//
//The complete label format 
type label = [dbname, ename, alias, cname, atom];

//
//The index of the value being saved
type col_position = number;
//
//TARBULAR DATA FORMAT
interface tabular {
    header: Array<[dbname, ename, alias, cname, col_position]>,
    //
    //There are as many entries of the values as there are header 
    //options
    body: Array<basic_value>
}

//
//This interface is designed for reporting runtime results
//
//This interface is designed for reporting runtime results
interface runtime {
    //
    //The class name that distinguishes between this and syntax errors
    class_name: "runtime";
    //
    //A runtime result is an array of row indexed entries  
    result: Array<
        {
            //
            //The CRUD table's row index
            rowIndex: integer,
            //
            //An entry is either....
            entry:
            //
            //..an ERROR....
            {
                error: true,
                //
                //...in which case it msut be accompanied by a message
                msg: string
            }
            //..or a befriended PRIMARY key
            | {
                error: false,
                //
                //..in which case it must have the primary key....
                pk: integer;
                //
                //...and its friendly component
                friend: string
            }
        }
    >;
}

//
//This interface is designed for reporting syntax errors.
interface syntax {
    //
    //The type of update/write result
    class_name: "syntax";
    //
    //This is a list of error messages when the class_name is
    // syntax
    errors: Array<string>;
}
//
//
//The imala is either a runtime or a syntax result
type Imala = syntax | runtime;
//
//This is special construct for mporting a partialy complete 
//tree structure that reprresents files and folders from
// the  server. Partialy complete means some folder are rich 
//with children and others are not. The rich ones are part of
//the intial path specification. 
export interface Inode {
    // 
    //This the full name of the path 
    name: string;
    // 
    //The type of the path, i.e leaf or branch.
    //Leaf are maped to files and branches to folders.
    class_name: "leaf" | "branch";
}
//
//A leaf is a node that has no children by design. 
export interface Ileaf extends Inode { }
//
//A branch is a node that can have children. If the children 
//are defined then this is a rich branch else it is not.
export interface Ibranch extends Inode {
    // 
    //The children of a rich branch are nodes
    children?: Array<Inode>;
}
//
//This is the php version of the js node. mainly used for 
//housing the export method.
export class node {
    //
    constructor(
        // 
        //The name of this node 
        name: string,
        //
        //Full name of this node's parent 
        full_name: string
    )

    //Form a complete node structure using the initial path and return 
    //a static node.
    static export(
        //
        //e.g  absolute: /pictures/water/logo.jpeg.
        //     relative:  pictures/water/logo.jpeg.
        initial_path: string,
        target: "file" | "folder"
    ): Inode;

}

//The PHP tracker class, to be re-placed in the tracker.d.ts later
export class tracker {
    constructor();
    //
    //Re-establish the links between the user and the application sub-systems.
    relink_user(links: Array<{ ename, cname }>): boolean;
}
//
//The questionnaire format for loading large tables
export class questionnaire {
    //
    constructor(milk: quest.Iquestionnaire | string/*excel_filename*/);
    //
    //The general style of loading that data tat can be customised
    //to other types, e.g., common, user_inputs, etc.
    load(
        //
        //XML file for logging the loadin process
        xmlfile: string = "log.xml",
        //
        //Error file for logging table loading exceptions
        errorfile: string = "file.html"
    ): quest.Imala;

    //The most common way of calling questionaire::load, returning a html 
    //report
    load_common(
        //
        //XML file for logging the loadin process
        xmlfile: string = "log.xml",
        //
        //Error file for logging table loading exceptions
        errorfile: string = "error.html"
    ): string/*html report|Ok*/;

    //
    //Load user inputs from a crud page, returning a result fit for
    //updatng the page.
    load_user_inputs(): Imala;
}

//To suppoty the accounting subsystem
export class accounting {
    constructor(bus: string, acc: string, date: string);
    records(dis_type: "closed" | "open" | "all"): Ifuel;
    closed_records(): Ifuel;
    close_books(): Imala;
}
//
//Results of interrogating products. This is a special case of I fuel 
export interface Iproduct {
    id: string,
    title: string,
    cost: number | null,
    solution_id: string,
    solution_title: string,
    listener: string,
    is_global: 'yes' | 'no'
}
//
//The php extension of athe class in app.ts
export class app {
    constructor(app_id: string);
    get_products(): Array<Iproduct>
    customed_products(): Array<{ role_id: string, product_id: string }>
    //
    //Returns those produts available to a user for this application. These
    //are free products as well as any that the user has are paid 
    //for, a.k.a., assets
    available_products(name: string): Array<{ product_id: string }>
}

export class app {
    constructor(app_id: string);
    get_products(): Array<Iproduct>
    customed_products(): Array<{ role_id: string, product_id: string }>
    subscribed_products(name: string): Array<{ product_id: string }>
}
//
//The following data types support the data merging operations. In future
//these declarions will be managed by a local file, rather than this
//global ones 
//
export type sql = string;
//
export interface Imerge {
    dbname: string,
    ename: string,
    members: sql,
    minors?: sql,
    principal?: number
}

type interventions = Array<intervention>;

interface intervention {
    cname: cname,
    value: basic_value
}

type conflicts = Array<conflict>;

interface conflict {
    cname: cname,
    values: Array<basic_value>
}

//A pointer is a foreign key column of a contributor whose integrity would
//be violated if there is an attempt to delete a reference record. The pointer
//would need re-direction before being deleted.
export type pointer = {
    //
    //Name of the column/field
    cname: cname,
    //
    //Name of the table containing the column
    ename: ename,
    //
    //Name of the database in which the table is contained. 
    dbname: dbname,
    //
    //Is the foreign key a cross member or not. This is important 
    //for addressing possibiliy for cyclic referencing
    is_cross_member: boolean,
};

//Indices of the pointer's away table that can 
//be violated by re-direction
export type index = {
    //
    //Index name (for reporting purposes)
    ixname: string;
    //
    //An sql that generates a merging singature bases on the
    //violaters, i.e., the columns of an index needed to determine
    //integrity violaters. The sql has the shape:
    //Array<{signature}>
    signatures: Array<{ signature: string }>,
    //
    //The sql that is to be constrained by a specific to generate
    //the pointer members that need to be merged. he sql has the shape:-
    //Array<{signature, member}
    members: sql
}
//  
//The merger class that supports the cleaning of i
export class merger {
    constructor(imerge: Imerge);

    get_players(): { principal: number, minors: sql } | null;

    get_values(): sql;
    //
    get_consolidation(): { clean: interventions, dirty: conflicts };

    update_principal(consolidations: interventions): void;
    //
    delete_minors(): Array<pointer> | 'ok';
    //
    redirect_pointer(pointer: pointer): Array<index> | 'ok'
}
//
//A recipient is either an individual or a group.
export type recipient =
    //
    //A group has a business id used to get all members associated with that group
    { type: 'group', business: outlook.business }
    //
    //The individual has a name which is used to retrieve his/her email address or
    // the mobile number
    | { type: 'individual', user: Array<string> };
//
//The messenger class that supports sending of sms's and emails to all users of 
//the currently logged in user's business
export class messenger {
    constructor();
    //
    //Sending a message requires three major parameters, namely the business, 
    //subject, and body of the message 
    send(
        //
        //The primary key of the business the user is currently logged in as
        recipient: recipient,
        //
        //The subject of the communication
        subject: string,
        //
        //The body of the communicated message
        body: string
    ): Array<string>
}
//
//The at command is either for:-
export type at =
    //
    //- sending a message indirectly using a job number(from which the message
    //can be extracted from the database)
    { type: "message", datetime: string, message: number, recipient: recipient }
    //
    //- or for initiating a fresh cronjob on the specified date
    | { type: "refresh", datetime: string };
//
//The scheduler class that supports scheduling of automated tasks on the server
export class scheduler {
    constructor();
    //
    //Executing a scheduler requires that we get the array of at jobs
    execute(
        //
        //The event's repetitive type
        repetitive: boolean,
        //
        //The array of at commands which consists of the date and the message of
        //the event
        at: Array<at>
    ): Array<string>;
    //
    //Update the crontab file
    update_cronfile():void;
}
//
//This class implements the selector sql which is useful for populating selectors
//with data from the database
export class selector {
    //
    constructor(
        //
        //The entity name
        ename: string,
        //
        //The database name
        dbname: string);
    //
    //The execute method runs the query that returns the output of the data
    execute(): Ifuel;
}
//
//This class supports the collection of untranslated words from the database and
//using them in the database
export class untranslated {
    //
    constructor(
        //
        //The constructor requires the database 
        dbase: string
    );
    //
    //This method runs the query to fetch the untranslated KIKUYU words.
    get_query(): string;
}