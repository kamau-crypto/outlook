
//
//Describes the entiy names in so to maintain my code local vocabularies as 
//derived from php
export type ename = string;
//
//The database name 
export type dbname=string;
//
//These are the custom datatype for the column name to maintain a semantic meaning to my code 
export type cname=string;
//
//The types of columns available in this file
type col_type="attribute"|"primary"|"foreign"| "field";

//
//Modelling special mutall objects that are associated with a database schema.
//Database, entity, index and column extends this class. Its main characterstic
//is that it has an orgainzed error handling mechanism.
export class schema{
    //
    //The partial name is the unique identifier of this schema object it aids in 
    //logging and also in saving of this schema in an array since this name  is mostly
    //used as an index
    partial_name:string;
    //
    //Error logging is one of the major features of this schema with its ability to bash
    //its own error which affects the display of this schema 
    errors:Array<Error>;
    //
    //Define a globally accessible application url for supporting the working of
    //PHP class autoloaders.
    //The url enables us to identify the starting folder for searching for 
    //PHP classes.
    public static app_url:string;
    //
    //To create a schema we require a unique identification also called a partial 
    //name described above 
    constructor(partial_name:string){
        //
        //The unique identification of this schema 
        this.partial_name= partial_name;
        //
        //A collection of the errors saved inform of an array for further buffered for 
        //latter reporting. note these are the mojor causes for a schema object to be 
        //represented using a red color 
        this.errors=[];
    }
    //
    //displays the error in this schema object in a dive that can be appended 
    //as a node where required 
    display_errors():HTMLDivElement{
        //
        //create a div where to append the errors with an id of errors 
        const div = document.createElement('div');
        div.setAttribute("id","errors");
        //
        //add the title of this error reportin as this partial name has count no
        // of error
        const title= document.createElement('h2');
        title.textContent=`<u><b>This shema ${this.partial_name} has ${this.errors.length} not compliant 
                           with the mutall framework </u></b>`;
        div.appendChild(title);
        //
        //loop through each of the errors appending their text content to the div 
        this.errors.forEach(function(error){
           //
           const msg= document.createElement("label");
           msg.textContent=error.message;
           div.appendChild(msg);
        });
        //
        return div;
    }
    //
    //Activates static error objects retrieved from php to js errors for further 
    //altering of the display in this this schema 
    activate_errors(static_errors:object){
        //
        for (const err in static_errors) {
           const erro= new Error(err);
           //
           //offload any additional information eg the additional information
           Object.assign(erro, err);
           //
           //Add these errors to the error collection 
           this.errors.push(erro);
        }
    } 
    
}
//
//This class extends the normal Javascript error object by 
//alerting the user before logging the same to the console.
export class mutall_error extends Error{
    //
    //Every error has an error message 
    constructor(msg:string){
        //
        //Create the parent error object
        super(msg);
        //
        //Alert us about this error
        const win = window.open();
        //
        //Take of the posiblilty that the window might be a null (for whatever
        //reason
        if(win===null){
            console.log(this.message);
            return;
        }
        win.document.write(msg);
    }
}
//
//Represents the php version of the database, i.e (static_dbase). These is inorder
//to solve the datatype error required for the creation of the database
export interface Idatabase {
    name: dbname;
    //
    //The reporting string of all the errors of the database
    report:string;
    //
    //Entities/views/tables of this static database
    entities:{[index:string]:Ientity};
    //
    //Errors retrieved from php
    errors:Array<object>
}

//Is a mutall object that models a database class. Its key feature is the 
//collection of entities.
class database extends schema{
    //
    //A collection of entites for this database modeled intoa map because with 
    //an object it was difficult to test its data type 
    public entities:{[index:string]:entity};
    //
    //Databases are identified with the column name hence should be a unique string name
    //you may notice it is similar with the schema partial name but it homed here 
    //and its childern need it hence worth repeating 
    public name:string
    //
    //Construct the database from the given static database structure imported
    //from PHP
    constructor(
        //
        //The static dbase that is used to create this database it is derived from php
        //i.e the encoded version of the php database 
        public static_dbase:Idatabase
    ){
        //
        //Initialize the parent so thate we can access 'this' object
        super(static_dbase.name);
        //
        //Offload all the properties in the static structure o this new database
        Object.assign(this, static_dbase);
        //
        //Activate the entities so as to initialize the map 
        this.entities=this.activate_entities();
        //
        //activate any errors if any 
        this.activate_errors(static_dbase.errors);
        //
        //initialize the name of the database 
        this.name=static_dbase.name;
    }
    //
    //Activate the static entities collection of entities  as entities in a map with 
    //string enames as the keys and the activated entity as the value returninig a map
    //which activates this entities
    activate_entities(): {}{
        //
        //start with an empty map
        const entities :{[index:string]:entity}= {};
        //
        //Loop through all the static entities and activate each one of them setting it in
        //the object entities indexed by thr 
        for(let ename in this.static_dbase.entities){          
            //
            let static_entity = this.static_dbase.entities[ename];
            //
            //Create the active entity, passing this database as the parent
            let active_entity = new entity(this, static_entity);
            //
            //Replace the static with the active entity
            entities[active_entity.name] = active_entity;
        }
        //
        //Return the entities of this database
        return entities;
    }  
    //
    //Returns the entity if is found; otherwise it throws an exception
    get_entity(ename: ename): entity {
        //
        //Get the entity from the collection of entities in the map
        //used the $entity so as not to conflict with the class entity 
        const Entity = this.entities[ename];
        //
        //Take care of the undeefined situations by throwing an exception
        //if the entity was not found
        if (Entity === undefined){
            //
            throw new mutall_error(`Entity ${ename} is not found`);
        }
        else{
            return Entity;
        }
    }
    // 
    //Retrive the user roles from this database. 
    //A role is an entity that has a foreign key that references the 
    //user table in mutall users database.
    //The key and value properties in the returned array represent the 
    //short and long version name of the roles.
    get_roles():Array<{key:string,value:string}>{
        //
        //Get the list of entities that are in this database
        const list = Object.values(this.entities);
        //
        //Select from the list only those entities that have a column called
        //user.
        const interest= list.filter(entity=>{
            //
            //Get the column names of this entity
            const names = Object.keys(entity.columns);
            //
            //Check whether user.name is one of this names
            const exist = names.includes("user");
            return exist;
        });
        //
        //Map the entities of interest into a key value pairs.
        const roles= interest.map(entity=>{
            //
            //Key is the name of the entity
            const key = entity.name;
            //
            // Value is the title of the entity if it exists otherwise, it is
            // the same as the key.
            const value= entity.title === undefined ? entity.name : entity.title;
            //
            //
            //Return the complete role structure
            return {key,value};
        });
        return roles;   
    }  
}
//
//The static entity that is directly derived from php required to form php
//The entity before activation 
interface Ientity{
    //
    //In php an entity is defined by the ename and the dbname
    name:ename;
    dbname:dbname;
    //
    //The class name of this entity
    class_name:"view"|"entity"|"table"|"alien";
    //
    //The metadata of the entity 
    comment:string
    //
    //The dependency
    depth?:number;
    //
    //The error derived from php
    errors:Array<{message:string}>
    //
    indices:Array<index>|undefined
    //
    //The columns/fields of this entity
    columns: { [index: string]: Icolumn };
}
//
//This is the matadata that is required for an entity to be presented.
//it includes the cx, cy, visibility, purpose. They are originally stored in the 
//coment but this will soon change to catter for the views and the aliens
//NB the purpose and the visibility can be null
interface entity_metadata{
    cx:number
    cy:number
    purpose?:string
    visibility?:boolean
    color:string
}
//
//
//This are the ids that are used in the identification of this entity.
//They contain 
//1. The name the index
//2. The columns that are used to index this entity 
//3. The partial name of the entity indexed
interface index{
    name:string;
    //
    //Aprimary column cannot be used for indexing 
    columns:Array<cname>
    //
    ename:ename;
    dbname:dbname
} 

//An entity is a mutall object that models the table of a relational database
class entity extends schema{
    //
    //Every entity has a collection of column inmplemented as maps to ensure type integrity
    //since js does not support the indexed arrays and yet columns are identified using their 
    //names.
    //This property is optional because some of the entities eg view i.e selectors do not have 
    //columns in their construction  
    columns:{[index:string]:attribute|foreign|primary}
    // 
    //The long version of a name that is set from this entity's comment 
    public title?: string;
    //
    //Every entity is identified uniquely by a name 
    name:ename;
    //
    //Define the sql used for uniquely identifying a record of this entity
    //in a friendly way. The result of this sql is used for driving a record
    //selector. The sql is derived when needed. 
     id_sql_:string|null = null;
     //
    //Defne the identification index fields in terms of column objects. This
    //cannot be done at concstruction time (becase the order of building 
    //dataase.entities is not guranteed to follow dependency). Hense the 
    //use of a getter
    ids_:Array<primary|foreign|attribute>|null|null= null;
    //
    //static object of the indices that are used to activate the ids 
    indices?:Array<index>;
    //
    //the depth of this entity as derived from php
    depth?:number
    //
    //The goup tag that holds the html of this entity including the attributes 
    group:HTMLElement;
    //
    //Construct an entity using:-
    //a) the database to be its parent through the has-a hierarchy
    //b) the static information typically obtained using a s sever-side scripting
    //language, e.g. PHP
    constructor(
        //
        //The parent of this entity which is the database establishing the reverse 
        //connection from the entity to its parent. it is protected to allow this 
        //entity to be json encoded. Find out if this makes any diference in js 
        //The datatype of this parent is a database since an entity can only have a 
        //database origin
        public dbase: database,
        //
        //The static structure from which this entity is formulated. it is mostly derived 
        //from php. It is of type any since it is a object
        private static_entity: Ientity
    ) {
        //
        //Initialize the parent so thate we can access 'this' object
        super(`${dbase.name}.${static_entity.name}`);
        //
        //
        //Offload the properties of the static structure (including the name)
        Object.assign(this, static_entity);
        //
        //Use the static data to derive javascript column objects as a map 
        this.columns = this.activate_columns();
        //
        //unique name of this entity 
        this.name=static_entity.name;
        //
        this.depth=static_entity.depth;
        //
        //activate any imported errors
        this.activate_errors(static_entity.errors);
        //
        //Define the sql used for uniquely identifying a record of this entity
        //in a friendly way. The result of this sql is used for driving a record
        //selector. The sql is derived when needed. 
        this.id_sql_ = null;
        //
        //initialize the indices 
        this.indices=static_entity.indices;
        //
        //Defne the identification index fields in terms of column objects. This
        //cannot be done at concstruction time (becase the order of building 
        //dataase.entities is not guranteed to follow dependency). Hense the 
        //use of a getter
        this.ids_ = null;
        //
        //initialize the sqv group element for presentation purpses
        this.group=document.createElement('g');
    }    
    //Activate the columns of this entity where the filds are treated just like 
    //attributes for display
    activate_columns(): {}{
        //
        //Begin with an empty map collection
        let columns:{[index:string]:foreign|attribute|primary}={};
        //
        //Loop through all the static columns and activate each of them
        for(let cname in this.static_entity.columns ){
            //
            //Get the static column
            let static_column:Icolumn = this.static_entity.columns[cname];
            //
            //Define a dynamic column
            let dynamic_column:primary|attribute| foreign;
            //
            switch(static_column.class_name){
                //
                case "primary": 
                    dynamic_column = new primary(this, static_column);
                    columns[static_column.name]=dynamic_column;
                    break;
                case "attribute": 
                    dynamic_column = new attribute(this, static_column);
                    columns[static_column.name]=dynamic_column;
                    break;
                case "foreign":
                    dynamic_column = new foreign(this, static_column);
                    columns[static_column.name]=dynamic_column;
                    break;
                case "field": 
                    dynamic_column = new attribute(this, static_column);
                    columns[static_column.name]=dynamic_column;
                    break;
                default:
                    throw new mutall_error(`Unknown column type 
                    '${static_column.class_name}' for ${this.name}.${static_column.name}`)

            }
            
        }
        return columns;
    }
    
    //Defines the identification columns for this entity as an array of columns this 
    //process can not be done durring the creation of the entity since we are not sure 
    //about the if thses column are set. hence this function is a getter  
    get ids():Array<primary|foreign|attribute|undefined>|null{
        //
        //Return a copy if the ides are already avaible
        if (this.ids_!==null) return this.ids_;
        //
        //Define ids from first principles
        //
        //Use the first index of this entity. The static index imported from 
        //the server has the following format:-
        //{ixname1:[fname1, ...], ixname1:[....], ...} 
        //We cont know the name of the first index, so we cannot access directly
        //Convert the indices to an array, ignoring the keys as index name is 
        //not important; then pick the first set of index fields
        if (this.indices === undefined || null) { return null; }
        // 
        //
        const fnames:index= this.indices[0];
        //
        //If there are no indexes save the ids to null and return the null
        if(fnames.columns.length===0){return null;}
        //
        //Activate these indexes to those from the static object structure to the 
        //id datatype that is required in javascript 
        // 
        //begin with an empty array
        let ids: Array<primary | foreign | attribute | undefined> = [];
        // 
        //
        fnames.columns.forEach(name=>{
            //
            //Get the column of this index
           const col= this.columns[name];
           if (col === undefined) { }
           else{ ids.push(col)} 
        });
        return ids;
    }
    
     //Returns the relational dependency of this entity based on foreign keys
    get dependency():number|null{
        //
        //Test if we already know the dependency. If we do just return it...
        if (this.depth!==undefined) return this.depth;
        //
        //only continue if there are no errors 
        if(this.errors.length>0){return null}
    
        //...otherwise calculate it from 1st principles.
        //
        //Destructure the identification indices. They have the following format:-
        //[{[xname]:[...ixcnames]}, ...]
        //Get the foreign key column names used for identification.
        //
        //we can not get the ddependecy of an entity if the entity has no ids 
        if(this.ids===null){return null;}
         //
         //filter the id columns that are foreigners
         let columns:Array<foreign>=[];
         this.ids.forEach(col =>{if(col instanceof foreign){columns.push(col);}});
        //
        //Test if there are no foreign key columns, return 0.
        if(columns.length === 0){
            return 0;
        }
        else{
            //Map cname's entity with its dependency. 
            const dependencies = columns.map(column=>{
                //
                //Get the referenced entity name
                const ename = column.ref.table_name;
                //
                //Get the actual entity
                const entity = this.dbase.get_entity(ename);
                //
                //Get the referenced entity's dependency.
                return entity.dependency;
            });
            //
            //remove the nulls
            const valids=<Array<number>>dependencies.filter(dep=>{return dep !==null})
            //
            //Get the foreign key entity with the maximum dependency, x.
            const max_dependency = Math.max(...valids);
            //
            //Set the dependency
            this.depth=max_dependency;
        }
        //
        //The dependency to return is x+1
        return this.depth;
    }
    
}
//
//The structure of the static column 
interface Icolumn{
    //
    //a column in php is identified by the name, ename, dbname 
    name:cname;
    ename:ename;
    dbname:dbname;
    //
    //The columns in php can either be of type 
    class_name:col_type;
    //
    //Errors resolved in a column
    errors:Array<object>
}

//Modelling the column of a table. This is an absract class. 
class column extends schema{
    //
    //Every column if identified by a string name
    name:string
    //
    //Every column has a parent entity 
    entity:entity
    //
    //The static php structure used to construct this column
    static_column: any;
    //
    //Boolean that tests if this column is primary 
    is_primary: boolean;
    // 
    //This is the descriptive name of this column 
    //derived from the comment 
    public title?: string;
    //
    //Html used to display this column in a label format
    view:HTMLElement;
    //
    //The construction details of the column includes the following
    //That are derived from the information schema  and assigned 
    //to this column;- 
    //
    //Metadata container for this column is stored as a structure (i.e., it
    //is not offloaded) since we require to access it in its original form
    public comment?:string;
    //
    //The database default value for this column 
    public default?: string;
    //
    //The acceptable datatype for this column e.g the text, number, autonumber etc 
    public data_type?: string;
    //
    //defined if this column is mandatory or not a string "YES" if not nullable 
    // or a string "NO" if nullable
    public is_nullable?: string;
    // 
    //The maximum character length
    public length?: number;  
    //
    //The column type holds data that is important for extracting the choices
    //of an enumerated type
    public type?: string; 
    // 
    //The following properties are assigned from the comments  field;
    // 
    //This property is assigned for read only columns 
    public read_only?: boolean;
    // 
    //A comment for tagging columns that are urls.
    public url? :string;
    //
    //These are the multiple choice options as an array of key value 
    //pairs. 
    public select?: Array<[string, string]>

    
    //
    //The class constructor that has entity parent and the json data input 
    //needed for defining it. Typically this will have come from a server.
    constructor(parent:entity, static_column:any){
        //
        //Initialize the parent so thate we can access 'this' object
        super(`${parent.dbase.name}.${parent.name}.${static_column.name}`);
        //
        //Offload the stataic column properties to this column
        Object.assign(this, static_column);
        //
        this.entity = parent;
        this.static_column=static_column;
        this.name=static_column.name;
        //
        //Primary kys are speial; we neeed to identify thm. By default a column
        //is not a primary key
        this.is_primary = false;
        //
        //Html used to display this column in a label format
        this.view=document.createElement('label');
    }
        
}

//Modelling the non user-inputable primary key field
class primary extends column{
    //
    //The class contructor must contain the name, the parent entity and the
    // data (json) input 
    constructor(parent:entity, data:any){
        //
        //The parent colum constructor
        super(parent, data);
        //
        //This is a primary key; we need to specially identify it.
        this.is_primary = true;
    }
    
    //
    //
    //popilates the td required for creation of data as a button with an event listener 
    create_td():HTMLElement{
        //
        //Create the td to be returned
        const td= document.createElement('td');
        //
        //Set the attributes
        td.setAttribute("name", `${this.name}`);
        td.setAttribute("type", `primary`);
        td.textContent=``;
        //
        return td;
    }
    
}
//
//The reference that shows the relation data of the foreign key if the 
//1. the referenced table name , 2. the referenced column name and the referenced 
//dbname
interface ref{
    table_name:string;
    db_name:string
    cname?:string
} 


//Modellig foreign key field as an inputabble column.
class foreign extends column{
    //
    //The reference that shows the relation data of the foreign key if the 
    //1. the referenced table name , 2. the referenced column name and the referenced 
    //dbname
    ref:ref
    //
    //For thepresentation of this relation
    line?:SVGLineElement
    //
    //Construct a foreign key field using :-
    //a) the parent entity to allow navigation through has-a hierarchy
    //b) the static (data) object containing field/value, typically obtained
    //from the server side scriptig using e.g., PHP.
    constructor(parent:entity, data:any){
        //
        //Save the parent entity and the column properties
        super(parent, data);
        //
        //The referenced entity of this relation will be determined from the 
        //referenced table name on request, i.e., using a getter. Here we only
        //define the property so that it is visible from the navigator.
        this.ref=this.get_ref(); 
    }
    //
    //set the reference that shows the relation data of the foreign key if the 
    //1. the referenced table name , 2. the referenced column name and the referenced 
    //dbname
    get_ref():ref{
        //
        //activate the static ref
        return{
            table_name:this.static_column.ref.table_name,
            db_name:this.static_column.ref.db_name,
            cname:this.static_column.ref.cname
        }
    }
    //
    inputs(body:HTMLElement){
        //The text content is the name of this column
        this.view.textContent = this.name;
        //
        //Get the proper input for this column eg text, checkbox, button, ant the text area
        let input = document.createElement('button');
        //
        //Make the input visible
        this.view.appendChild(input);
        //
        //
       //Append the label to the body
        body.appendChild(this.view);
    }
    //
    //Returns the type of this relation as either a has_a or an is_a inorder to 
    //present diferently using diferent blue for is_a and black for has_a
    get_type(){
       //
       //Test if the type is undefined 
       //if undefined set the default type as undefined 
       if(this.static_column.comment.type===undefined || this.static_column.comment.type===null){
           //
           //set the default value 
           const type= 'has_a';
           return type;
       }
       //
       //There is a type by the user return the type
       else{
           const type= this.static_column.comment.type.type;
           return type;
       }
    }
           
    //The referenced entity of this relation will be determined from the 
    //referenced table name on request, hence the getter property
    get_ref_entity(){
        //
        //Let n be table name referenced by this foreign key column.
        const n = this.ref.table_name;
        //
        //Return the referenced entity using the has-hierarchy
        return this.entity.dbase.entities[n];
    }
    //
    //popilates the td required for creation of data as a button with an event listener 
    create_td():HTMLElement{
        //
        //Create the td to be returned
        const td= document.createElement('td');
        //
        //Set the inner html of this td 
        td.setAttribute('type', 'foreign');
        td.setAttribute('name', `${this.name}`);
        td.setAttribute('ref', `${this.ref.table_name}`);
        td.setAttribute('id', `0`);
        td.setAttribute('title', `["0",null]`);
        td.setAttribute('onclick', `record.select_td(this)`);
        //
        //Set the text content to the name of this column 
        td.textContent=`${this.name}`;
        //
        //return the td
        return td;
    }
    
}
    
//Its instance contains all (inputable) the columns of type attribute 
class attribute extends column{
    //
    //The column must have a name, a parent column and the data the json
    // data input 
    constructor(parent:entity, data:any){
        //
        //The parent constructor
        super(parent, data);
    }
    //
    //popilates the td required for creation of data as a button with an event listener 
    create_td(){
        //
        //Create the td to be returned
        const td= document.createElement('td');
        //
        //Set the inner html of this td 
        td.setAttribute('type', 'attribute');
        td.setAttribute('name', `${this.name}`);
        td.setAttribute('onclick', `record.select_td(this)`);
        td.innerHTML='<div contenteditable tabindex="0"></div>';             
        //
        return td;
    }   
}
//
//
export {database, entity, column, attribute, primary, foreign};