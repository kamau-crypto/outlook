<?php

//
//All the classes below will be under the capure namespace to prevent
//name collision with those of the root namespace

namespace {
    //
    //Any references to myerror class applies to the version in the
    //capture namespace, rather than the one in thr root.
    use \capture\myerror as myerror;
    //
    //
    //Resolve the schema reference which allows a questionnaire to log
    //itself.
    include_once $_SERVER['DOCUMENT_ROOT'] . '/schema/v/code/schema.php';
    //
    //Resolve the join reference which allows table depths to be set
    include_once $_SERVER['DOCUMENT_ROOT'] . '/schema/v/code/sql.php';

    /**
    *This class extends the earlier defined record in order to export
    *large amounts of data large typically generated from sources other 
    *than direct human inputs -- that's a future thought)
    */
class questionnaire extends schema {
    //
    //The inputs that need to be exported expressed in the questionnaire 
    //format 
    public /* Iquestionnaire|excelfile*/ $milk;
    //
    //When labels in a questionnaire are separated from tables the result
    //is unpacked into 2 containers:-artefacts and tables
    //
    //The indexed list of aliased entities, a.k.a., artefacts
    public \Ds\Map /* <artefact,[entity, alias]> */ $artefacts;
    //
    //An array of milk tables, indexed by the table name
    public \Ds\Map /* <tname, table> */ $tables;
    //
    //Allow this questionnaire to be accessible from anywhere
    static questionnaire $current;
    //
    //Keep track of log errors from capture tables
    static $log_errors = 0;
    //
    //A questionnaire is characaterised by milk (data) organized as such.
    function __construct(/* Iquestionnaire|excel_filename */$milk) {
        //
        //Standardise the inputs to an array of table or label layouts
        $this->milk = $milk;
        //
        //Initialize the indexed arrays
        $this->artefacts = new \Ds\Map();
        $this->tables = new \Ds\Map();
        //
        //We don't have a special way of identifying a qustionnaire because 
        //there is only one in the system -- unlike databases, entities, and 
        //other shema objects
        parent::__construct('_');
        //
        //Set this questionnaire as the current one to allow global access
        self::$current = $this;
        //
        //Initialize the current )and only) barrel to be usd for savebg
        //milk tables
        capture\barrel::$current = new \capture\barrel();
    }

    /**
    Loads the data referenced by this questionnaire to the correct 
    database. 
    * @param string logfile The file for logging how loading progressed
    * @param string error_file The file for logging errors that occur when 
    * loading (large) tables.
    * Technically, this process converts static forms of label 
    and table layouts into artefacts and active tables respectively, before 
    saving them to a database. 
    *$return Imala The by-products of the loading process as either 
    * syntax errors or a run-time result.

    Imala is formally defined (in Typescript parlance) as either 
    type Imala = 

      ...a list of syntax errors...
      {class_name:'syntax', errors:Array<string>}

      ..or the result of loading table dependent or independent artefacts
      |{  class_name:'runtime', 
          labels:Array<label>, 
          tables:Array<{tname:string, errors:int, rows:Array<label>}>
       }
    where:-
        label = [dbname, ename, alias, cname, \capture\expression] 
    */  
    function load(string $logfile="log.xml", string $error_file="errors.html")/*:questionnaire.Imala*/ {
        //
        //Create the requested (xml) file for logging progress
        $log = (log::$current = new \log($logfile));
        //
        //Open the text file stream for logging table-dependent errors
        $this->error_file = $error_file;
        \questionnaire::$current->error_stream = fopen($error_file, "w");
        //
        //Compile the inputs (used to create this questionnaire) to produce 
        //active milk (rather than database) tables and artefacts
        $syntax_errors = $this->compile_inputs();
        //
        //To continue, there must be no syntax errors
        if (count($syntax_errors) > 0) {
            //
            //Compile the error objects into simplified object for reporting 
            //(typically in Javascript).
            $errors = array_map(fn($error) => "$error", $syntax_errors);
            //
            return ['class_name' => 'syntax', 'errors' => $errors];
        }
        //
        //Sort all the artefacts by order ascending of dependency to simplify 
        //the look of the (xml) logfile. NB. Dependency is a function of the
        //artefact's reltional "depth" and the size of the alias.
        $this->artefacts->sort( fn($a, $b)=>
            //
            //If both the compared artefacts belong to the same entity...
            ($a->source->name === $b->source->name)
            //
            //....then sort them based on the alias lengths...
            ? count($a->alias) <=> count($b->alias)

            //
            //...otherwise sort them based on their dependencies.
            : $a->source->depth <=> $b->source->depth
        );
        //
        //Save foreign key columns by binding their simplified forms (i.e.,
        //the 'ans' property) to their corresponding primary keys for all the 
        //available artefacts. Saving of foreigners, unlike those of 
        //attributes and primary keys, is done once irrespective of the 
        //numbers and sizes of the milk tables. This is the best place to 
        //do it.
        foreach ($this->artefacts as $artefact) $artefact->save_foreigners();
        //
        //Set the milk table name for each artefact if it (the artefact) 
        //depends on the table; null if it does not. This supports the 
        //loading of table independent and dependent artefacts separately. 
        //This process depends on the nearest artefacts in a foreign key 
        //having been set (from the previous step).
        foreach ($this->artefacts as $artefact) $artefact->set_tname();
        //
        //Set the data capture queries (insert, select and update, i.e., 
        //CRU without a D) for all the artefacts
        foreach ($this->artefacts as $artefact) $artefact->set_statements();
        //
        //Load the table-independent artefacts in a 2 phase process. 
        //Phase 1 loads non-cross members; phase 2 cross members
        $label_results = $this->export_ti_artefacts();
        //
        //Load the table-dependent artefacts, also using the 2-phase 
        //procedure as explained above. 
        $table_results= $this->export_td_artefacts();
        //
        //Close the xml log file, thus saving the results to the external file.
        $log->close();
        //
        //Close the error log file
        fclose(\questionnaire::$current->error_stream);
        //
        //Compile the processed milk, Imala, from and runtime results
        $result = [
            //
            //Yes, these are runtime results.
            'class_name' => 'runtime',
            //
            //Place the table independent results under here
            "labels" => $label_results,
            //
            //Place the table dependent results here
            "tables" => $table_results
        ];
        //
        //Otherwise return it raw
        return $result;
    }

    //This is the most common way of loading data to a database. It is an 
    //extension of the basic load process but returns a html report -- rather than
    //a structure outut that needs decoding.
    function load_common(
        //
        //Log the loading process in this xml file    
        string $logfile="log.xml", 
        //
        //Log the runtime errors in this text file    
        string $error_file="errors.html"
    ):string /*html report*/{
        //
        //Do the basic load
        $Imala = $this->load($logfile, $error_file);
        //
        //Convert the Imala to a summary report in html
        return $this->get_summary($Imala);
    }

    //Load user inputs from a crud page and return an Imala fit for
    //updating the page
    function load_user_inputs():array/*library.Imala*/{
        //
        //Perform the basic load operation with default values
        $Imala= $this->load();
        //
        //Convert the Imala result from the questionaire.Imala to 
        //library.Imala format
        return $this->get_crud_result($Imala);
    }

    //Return a crud from the given Imala input.
    //Look at the description of the output of questionnaire::load() to 
    //see the shape of Imala.
    // 
    //The following is the shape of the Imala, the CRUD (not questionnaire) 
    //version:-
    //Imala = syntax|runtime
    //syntax = {class_name:"syntax", errors:Array<string>}
    //runtime = {class_name:"runtime", result:Array<{row_index:integer, entry:entry}>}
    //where:-
    //entry = error|pk
    //and
    //error = {error:true, msg:string}
    //pk= {error:false, pk:autonumber, friend:string}
    private function  get_crud_result(array /*quest.Imala*/$result):array/*libray.Imala*/{
        //
        //Return the syntax error result as it because the library and 
        //quesionnaire versions are indentical:
        if ($result['class_name']==="syntax") return $result;
        //
        //Convert the runtime result from the questionnaire to the
        //library format.
        //
        //The questionnaire form has the following shape. 
        //{  class_name:'runtime', 
        //      labels:Array<label>, 
        //      tables:Array<{tname:string, errors:int, rows:Array<label>}>
        //}
        //This shape is expressed as an stdClass, but in fact it is an 
        //indexed array
        //
        //For CRUD reporting, the tables key is irrelevant; so consider 
        //the labels only
        $labels = $result['labels'];
        //
        //Convert the labels to an array of crud entries. There will be 2 
        //types of entries: An error or a primary  key.
        //
        //Start with an empty crud entries
        $entries= [];
        //
        //Loop through all the labels to collect the runtime CRUD entries
        foreach($labels as $label){
            //
            //Destructure the label to reveal its constituents
            list($dbname, $ename, $alias, $cname, $ans) = $label;
            //
            //Use the cname to verify the the label refers to a primary key
            //(just to appease the error checker by using all declared variables)
            if ($cname!==$ename) 
                throw new \Exception("$ename.$cname is not a primary key"); 
            //
            //Destructure the alias to get the row Index
            list($row_index) = $alias;
            //
            //If the answer is an error, compile a matching result entry
            if ($ans instanceof myerror){
                //
                //Compile the error entry. Note how we use the discriminatory
                //property to distinguish between errors and non errors.
                $entry = ['error'=>true, 'msg'=>$ans->msg];
            }else{
                //
                //Compile the primary key entry
                //
                //Get the named entity
                $entity = $this->open_dbase($dbname)->entities[$ename];
                //
                //Get the friendly compoment of the primary key value
                $friend = $entity->get_friend($ans->value);
                //
                //Add the discriminatory error property, set to false
                $entry = ['error'=>false, 'pk'=>$ans->value, 'friend'=>$friend];
            } 
            //
            //Compile the full runtime entry (including thr row index) and 
            //save to the entries collection
            array_push($entries, ['row_index'=>$row_index, 'entry'=>$entry]);
        }
        //
        //Return the runtime result
        return ['class_name'=>'runtime', 'result'=>$entries];    
    }

    //Convert the Imala structure to a summary report in html. Look at the
    //description of the output of questionnaire::load() to see the 
    //structure of Imala
    private function get_summary(array $result):string{
        //
        //Report syntax errors, if any
        if ($result['class_name']==="syntax") {
            //
            //Compile the syntax error message
            $msg = "Syntax errors<br/>"
               .implode("<br/>",
                    array_map(
                        fn($error)=>$error->to_str(), 
                        $result['errors']
                    )     
                );
            //
            //There cannot be runtime errors if we have syntax ones
            return $msg;
        }
        //
        //Report runtime errors, if any
        if ($result['class_name']!=="runtime")
            //
            throw new \Exception("Syntax or runtime errors expected");
        //
        //Output table-independent errors -- if any
        $msg = $this->summarise_labels("Table-independent artefacts", $result['labels']);
        //
        //Output table dependent errors
        //
        //Isolate tables that have errors
        $tables = array_filter(
            $result['tables'], 
            fn($table)=>$table['answer'] instanceof myerror);
        //
        //Map the erroneous tables to their corresponding summaries
        if (count($tables)>0)
            $msg .= "Table-dependent artefacts<br/>"
                .implode(
                    "<br/><br/>",
                    array_map(
                        fn($table)=>$this->summarise_labels("Summary for table ".$table['name'], $table['labels']),
                        $tables
                    )     
                );
        //
        //Add a pointer to the error log, if necessary
        if (self::$log_errors>0)
            $msg.="There are "
                .self::$log_errors
                ." table body errors<br/>"
                ."<a href='".$this->error_file."'"
                .">Open this link to see them</a>";
        //
        //Return the error message
        return $msg==""?"Ok":$msg;
    }

    //Summarise the given labels. A label destructures to:-
    //[dbname, ename, alias, cname, answer]
    private function summarise_labels(string $header,array $labels):string{
        //
        //Isolate the erroneous labels (by filtering)
        $flabels = array_filter(
            $labels, 
            fn($label)=>$label[4] instanceof myerror);
        //
        //Start with a empty error message
        $msg = "";
        //
        //Compile the report message if necessary
        if (count($flabels)>0){
            //
            //Compile message
            $msg = "$header<br/>"
                .implode("<br/>",
                    array_map(
                        function($label){
                            //
                            //Destructure the label
                            list($dbname, $ename, $alias, $cname, $ans) = $label;
                            //
                            //Compile the message, formated as, e.g., 
                            //chama.member["1"].name: Data truncated
                            return "$dbname.$ename". json_encode($alias)
                                .".$cname: .$ans.<br/>";
                         }, 
                        $flabels
                    )     
                );
        }
        //
        return $msg;
    }


    /**
     Load all the table-independent artefacts 
     * @return array Of labels
     */
    private function export_ti_artefacts(): array/*<label>*/ {
        //
        //Get the table independent artefacts
        $artefacts = array_filter(
            //
            //Remember that the artefacts are managed as a map; they are the 
            //values. Convert the values to an array
            $this->artefacts->values()->toArray(),
            //
            //An artefact is independent of any table if its tname property is null
            fn($artefact) => is_null($artefact->tname)
        );
        //
        //Save them using a empty table row (as they are independent of 
        //the milk table )
        $labels = $this->export_artefacts($artefacts, null);
        //
        return $labels;
    }

    //Export the table dependant artefacts. The way the errors are reported is 
    //different from how it is done in the table independent case. The errors
    //are reported as an array of:- 
    //tname: the table whose errors are being reported
    //report:A rich html error message thst should helpful for debugging purpose
    private function export_td_artefacts(): array/* <{tname, report}> */ {
        //
        //Prepare to return the result
        $results/* :Array<{tname, answer, rows}> */ = [];
        //
        //This process (unlike the table independent version) is driven by 
        //the collection of (milk) tables.
        foreach ($this->tables as $table) {
            //
            //Export the tables data. NB. schema::save() requires a row of data; 
            //this is irrelevant for saving a table so pass a null value.
            $ans = $table->save(null);
            //
            //Push the save result to the array
            $results[]=[
                "tname"=>$table->name,
                "answer"=>$ans,
                "rows"=>$table->rows
            ];
        }
        //
        //Return the result
        return $results;
    }

    /**
    * Load the artefacts in 2 modes; first using the non-cross members, then 
    * using the cross member columns. This is a public function because it 
    * is required both for table dependent and independent scenarios.
    * 
    * @param array<artefact> artefacts. Tables derived by extending entities with 
    * aliases
    * @param null|row row For milk table dependent cases, this is the row being 
    * processed; otherwise it is null
    * @return array<label>
    */
    public function export_artefacts(array $artefacts, $row): array{
        //
        //For each save mode (i.e.,cross or non-cross members)...
        foreach ([false, true] as $cross_member) {
            //
            //...loop through every artefact and save it.
            foreach ($artefacts as $artefact) {
                //
                //The saving is based on the requested mode, cross or non-cross
                //members
                $artefact->save_cross_members = $cross_member;
                //
                //Insert or update the artefact, ignoring the results until
                //after all the saves. Saving updates the xml log awell as 
                //writing to the database
                $artefact->save($row);
                //
                //For non-cross members, ensure that the relevant update query is 
                if ($cross_member)
                    //
                    //...again do the update and ignore the result
                    $artefact->update['cross_members']->execute();
            }
        }
        //
        //Collect the labels for reporting purposes. 
        //
        //Start with a empty list
        $labels = [];
        //
        //Iterate through all the artefacts
        foreach($artefacts as $artefact){
            //
            //Iterate through all the columns of the artefact, yielding 
            //labels
            foreach($artefact->yield_labels() as $label){
                array_push($labels, $label);
            }
        };
        //
        //Return the results
        return $labels;
    }

    //Compile the questionnare inputs into artefacts and tables, returning 
    //syntax errors if any.
    private function compile_inputs(): array/* <myerror> */ {
        //
        //Standardise the questionnaire inputs to a stataic list of labels and
        //tables. In particular, referece to an Excel file is resolved to a 
        //questionnaire
        $layouts = $this->standardise_milk();
        //
        //Compile layouts and and tables separately, statring with the tables 
        //because layouts require them.
        //
        //Start with an empty list of syntax errors...
        $syntax_errors = [];
        //
        //Tables are identified as objects
        $tables = array_filter($layouts, fn($layout) => is_object($layout));
        //
        //Compile the tables
        foreach ($tables as $table) {
            $this->compile_table($table, $syntax_errors);
        }
        //
        //Labels are identfied as arrays
        $labels = array_filter($layouts, fn($layout) => is_array($layout));
        //
        //Compile the labels
        foreach ($labels as $label) {
            $this->compile_label($label, $syntax_errors);
        }
        //
        //Return any syntax errors
        return $syntax_errors;
    }

    //
    //Converts the data held as a questionaire or as commented Excel file into 
    //the questionnaire format
    private function standardise_milk(): array/* <label|table> */ {
        //
        //If the input data, i.e., milk, is already in the questionnaire 
        //format...
        if (is_array($this->milk)) {
            //
            //...then return it as it is...
            return $this->milk;
        }
        //..otherwise it must a commented Excel Microsoft worbook
        elseif (is_string($filename = $this->milk)) {
            //
            //Assume the input milk is a commented Microsoft Excel filename. 
            //It must exist.
            //
            //Get the file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            //
            //The file must be in excel format
            if (!(ext === "xls" || $ext == "xslx"))
                throw new \Exception("Excel file expected, not $ext");
            //
            //The file must exist
            if (!is_file($filename))
                throw new \Exception("File $filename does not exist");
            //
            //Use the comments in the given Microsoft Exce file to return an 
            //array of layouts. Researah on a PHP-Excel library suitable for 
            //this purpose
            return create_excel_layout($filename);
        } else {
            //
            throw new \Exception("Unknown Iquestionaire format for " . json_encode($milk));
        }
    }

    //Destructure the given labele= and check for syntax errors. If found, throw 
    //an exception to discontinue execution; otherwise initialize
    //the referenced artefact.
    private function compile_label(array $label, array &$errors): void {
        //
        //Destructure a label layout. NB: This will fail if a user supplies 
        //less than 5 columns
        try {
            list($dbname, $ename, $alias, $cname, $Iexp) = $label;
        } catch (\Exception $ex) {
            $errors[] = new myerror($ex->getMessage() . " It may be that label " . json_encode($label) . " has less than 5 elements");
            return;
        }
        //
        //The named database must exist
        $dbase = $this->open_dbase($dbname);
        //
        //The named entity must be in the database
        if (!isset($dbase->entities[$ename])) {
            $errors[] = new myerror("Entity $dbname.$ename is not found");
            return;
        }
        $entity = $dbase->entities[$ename];
        //
        //The named column must exist in the entity
        if (!isset($entity->columns[$cname])) {
            $errors[] = new myerror("Column $dbname.$ename.$cname is not found");
            return;
        }
        //
        //The alias data type must be an array
        if (!is_array($alias)) {
            $errors[] = new myerror("Alias " . json_encode($alias) . " must be an array");
            return;
        }
        //
        //Create a capture expression using static label value
        $exp = \capture\expression::create($Iexp);
        //
        //Create the artefact if it does not exist
        if (!$this->artefacts->hasKey([$entity, $alias])) {
            //
            //The artefact does not exist: create it.
            $artefact = new \capture\artefact($entity, $alias);
            //
            //Update the artefacts collection, using entity as part of the 
            //indexing key
            $this->artefacts->put([$entity, $alias], $artefact);
        } else {
            //The artefact exist: get it
            $artefact = $this->artefacts->get([$entity, $alias]);
        }
        //
        //Save the expression under the named column. Overwriting is an
        //indicator of some potential problem.
        if (isset($artefact->columns[$cname]->exp)) {
            $errors[] = new myerror(
                    "This column $dbname.$ename.$cname for alias "
                    . json_encode($alias) . " is already set");
            return;
        }
        //Set the column's expression
        $artefact->columns[$cname]->exp = $exp;
        //
        //Set the booking status of this expression. An artefact is not booked
        //by us if its primary key is specified from the labeled input
        $this->booked = $cname === $ename ? false : true;
        //
        //Set the milk table name the artefact and all her ancestors
        if ($exp instanceof capture\lookup)
            $artefact->set_tname();
    }

    //Check the given table for syntax errors and if none, add it
    //to the collection of tabular objects. The structure of the table
    //is:-
    //type Itable = {
    //  class_name:string, 
    //  args:Array<any>
    //}
    //The arguments are the constructor arguments for the named class
    private function compile_table(stdClass $Itable, array &$errors): void {
        //
        //Get the table's class name; it must exist
        if (!isset($Itable->class_name)) {
            $errors[] = new myerror("Class name not found for table " . json_decode($Itable));
            //
            return;
        }
        //
        //Get the class name
        $class_name = $Itable->class_name;
        //
        //The constructor arguments must be known
        if (!isset($Itable->args)) {
            $errors[] = new myerror("No constructor arguents found for table " . json_encode($Itable));
            //
            return;
        }
        //Get the constructor arguments
        $args = $Itable->args;
        //
        //The constructor arguements must be supplied as an array
        if (!is_array($args)) {
            $errors[] = new myerror("Constructor arguements must be an array, for table " . json_encode($Itable));
            //
            return;
        }
        //
        //Create a new data table; remmeber to spread the arguments
        $table = new $class_name(...$args);
        //
        //Get the table's name
        $tname = $table->name;
        //
        //Duplicate table names are not allowed in the same questionnaire
        if (isset($this->tables[$tname])) {
            $errors[] = new myerror("This table name '$tname' is already used");
            //
            return;
        }
        //
        //Add the new table to this questionnaire's table collection
        $this->tables[$tname] = $table;
    }

}

}
//The capture namespace is home for a new entity -- the capture version

namespace capture {

//The short forms of type answer
    use \answer as ans;

//
//Define the 5 conditions under which the artefact writing occurs. The 
//conditions indicate a need to:-
    define("HARD_UPDATE", "Update all the fields of a record, including identifiers");
    define("ALIEN", "Report incomplete identifiers");
    define("FRAUDULENT", "Report ambguity error");
    define("INSERT", "Create brand new record");
    define("SOFT_UPDATE", "Update all the fields excluding the identifiers");

//An artefact is a (schema root) table entity qualified with an alias.
//In practice, it cannot extend the current version of a table because 
//a table's columns are defined as a method for historicl reasons -- rather
//than a property.  
    class artefact extends \table {

        //
        //The alias that makes this table an artefact
        public array $alias;
        //
        //The schema table that was used to derive this artefact. 
        public \table $source;
        //
        //The (milk) table that this artefact depends on. There may be none
        public /* string|null  $tname;
          //
          //The statement that update a record. There will be 2 versions for
          //updating the record based on cross- and non-crosss member columns.
          public array /* <update> */ $update;
        //
        //Set te following flag to true when saving an artefact based on cross 
        //member columns; otherwise it is false.
        public bool $save_cross_members;
        //
        //The insert statement for creatting new records
        public insert $insert;

        //
        public function __construct(
                //
                //The table entity that is the source of data for this artefact
                \table $source,
                //
                //This is the bit that extends a table.
                array $alias
        ) {
            //
            $this->alias = $alias;
            //
            $this->source = $source;
            //
            //Initialize the table being extended
            //
            //The database must come from the source. NB: it is protected
            $dbase = $source->get_dbase();
            //
            //The schema table name required for initializing the parent is 
            //that of the source
            $ename = $source->name;
            //
            parent::__construct($dbase, $ename);
            //
            //Set the columns of all the artefacts (before using them).
            //This is necessary because artefacts inherit \table which, from
            //the way it is constructed, does not initiallize columns.
            //Note that hese are columns in teh capture (rather than schema) 
            //namespace. PHP is not strict enough about array<\colums>
            //and array<capture\columns>. NB: capture\column extends \columns, so
            //the assignment of array<\colums> to array<capture\columns> is not
            //erroneous. Or is it?
            $this->columns = array_map(fn($col) => new column($col, $this), $source->columns);
            //
            //Set the indices of this artefact to ensure that the columns it references
            //are for this artefact. This is necessary because a table, during 
            //construction (similar to columns), does not set its indices
            $this->indices = array_map(
                    fn($name) => new index($name, $this),
                    array_keys($source->indices)
            );
        }

        //Returns the primary key of an artefact
        function pk(): column {
            //
            //Get the primary key field'sname
            $fname = $this->source->name;
            //
            //Rturn the matching primary key column
            return $this->columns[$fname];
        }

        //Set the milk table name of this artefact, so that we can decide later
        //if this artefact should be exported table dependently or indepently
        public function set_tname(): void {
            //
            //Don't waste time if the artefact's table's name is already set
            if (isset($this->tname))
                return;
            //
            //Collect the table names that match expressions for columns
            //of this artefact, starting with an empty list
            $dirty_tnames = [];
            $this->collect_tname($dirty_tnames);
            //
            //Remove duplicates
            $tnames = array_unique($dirty_tnames);
            //
            //Get the number of table names
            $count = count($tnames);
            //
            //If there are no table names, then the tname to null
            if ($count == 0)
                $this->tname = null;
            //
            //If there is one table, set it as the tname
            elseif ($count == 1)
                $this->tname = $tnames[0];
            //
            //Multiple tables is not expected
            else
                throw new \Exception(
                        "Artefact "
                        . $this->source->dbname . "."
                        . $this->source->name
                        . json_encode($this->alias)
                        . " cannot be loaded from more than one (milk) table, "
                        . json_encode($tnames)
                );
        }

        //
        //Reset all answers for all the primary and attribute columns of this 
        //artefact. Leave the foreigners alone. This should be done after saving
        //a table row
        public function reset_answers(): void {
            //
            foreach ($this->columns as $col) {
                //
                if (!$col instanceof \foreign) {
                    $col->answer = null;
                    $col->scalar = null;
                }
            }
        }

        //
        //Collect the milk table names assocociated with this artefact
        function collect_tname(array &$tnames): void {
            //
            //Loop through all the columns of this artefact to yield 
            //tnames based on their expressions
            foreach ($this->columns as $column) {
                //
                //For attribute columns, the expression must be set
                if (
                        $column->source instanceof \attribute && isset($column->exp)
                )
                    $column->exp->collect_tname($tnames);
                //
                //For foreign keys columns, the nearest artefact must be set
                elseif (
                        $column->source instanceof \foreign && isset($column->nearest) && $column->nearest instanceof artefact
                )
                    $column->nearest->collect_tname($tnames);
            }
        }

        //
        //Initialize the CRUD (without the D) statements for writing data
        function set_statements(): void {
            //
            //Initialize record CREATING statement
            $this->insert = new insert($this);
            //
            //Initalize the record REVIEWING statements as many times as there are 
            //indices
            foreach ($this->indices as $index) {
                $index->select = new select($this, $index);
            }
            //
            //Initialze the 2 record UPDATING statements, one for cross members
            //the other for the non-cross members (to forestall cylic loop 
            //possibility
            $this->update = [
                'cross_members' => new update($this, true),
                'non_cross_members' => new update($this, false)
            ];
        }

        //
        //Log the attributes of this artefact
        function log_attributes($element): void {
            //
            //Add the save mode as an attribute
            $save = $this->save_cross_members ? "cross_members" : "structurals";
            \log::$current->add_attr('alias', json_encode($this->alias), $element);
            //
            //Add the alias as an attribute.
            \log::$current->add_attr('save', $save, $element);
        }

        //
        //Writing this artefact proceeds by saving all her structural columns
        //(with primary key as the last one). The non-structurals, i.e., 
        //cross members are saved in a later phase.
        function write(/* row|null */$row): ans {
            //
            //Get the current save mode of this artefact; its the saving
            //of either the cross or non-cross members
            $is_cross = $this->save_cross_members;
            //
            //Collect all the columns that match the save mode
            $cols = array_filter(
                    $this->columns,
                    fn($col) => $col->source->is_cross_member() == $is_cross
            );
            //
            //Sort them such that the primary key is the last one; the order 
            //for the others does not matter. 
            usort($cols, fn($a, $b) => $a->source instanceof \primary ? 1 : 0);
            //
            //Save all the columns, ignoring the result
            foreach ($cols as $col) {
                $col->save($row);
            }
            //
            //Return the answer associated with the primary key
            return $this->pk()->answer;
        }
        
        //Compile the result of saving an artefact as an array of labels, 
        //for reporting purposes
        function yield_labels():\Generator{
            //
            //Get the primary key column (only)
            $column = $this->pk();
            //
            //Use the primary key column to compile the result 
            //
            //Compile the label as:-
            //[dbname, ename, alias, cname, cAns]
            $label = [
                //NB. source::dbase is protected, source->dbase->name is 
                //unreachable, so use dbname instead
                $this->source->dbname,
                $this->source->name,
                $this->alias,
                $column->source->name,
                $column->answer
            ];
            //
            //Expand the result
            yield $label;
        }
        
        //Compile this artefact to a label for reporting purposes
        public function compile_result():array/*<dbname, ename, alias, cname, error>*/{
            return [
                //
                //The dbname and entity names come from the undelying source entity
                //
                //The dbname
                $this->source->dbname,
                //
                //The ename
                $this->source->name,
                //
                //The alias must have been passed to this artefact during construction
                $this->alias,
                //
                //The column name teh primary key column, which has the same name
                //as the entity. It is not omportant for reporting. However, to
                //be considted with a label definition...
                $this->pk()->name,
                //
                //The labeled value is a string version of the primary key aswer
                "{$this->pk()->answer}"
                
            ];
        }

        //Save the foreign key columns by binding their simplified forms 
        //to the primary keys of the corresponding artefacts
        public function save_foreigners() {
            //
            //Collect all the foreign key columns of this artefact
            $foreigners = array_filter(
                    $this->columns,
                    fn($col) => $col->source instanceof \foreign
            );
            //
            //For each foreigner, do the home and away key bindings
            foreach ($foreigners as $foreigner) {
                //
                //Ignore this foreigner if its expressio is already set
                if (isset($foreigner->exp))
                    continue;
                //
                //Use the source of this column (it must be a foreigner) to get the
                //away entity
                $away = $foreigner->source->away();
                //
                //Use the away entity and the alias of this artefact to 
                //get the nearest available artefact
                //
                //Get the alias of this artefact
                $alias = $this->alias;
                //
                //Depending on whether we have a hierarchical situation or not,
                // formulate the source alias
                if ($foreigner->source->is_hierarchical()) {
                    //
                    //We have a hierarchial situation
                    //
                    //The source alias is the parent of the given one, if valid
                    //
                    //The aliased entity has no parent
                    if (count($alias) === 0) {
                        $foreigner->nearest = new myerror("Missing parent of {$foreigner->source->ename}");
                        //
                        continue;
                    }
                    //
                    //Drop the last array suffix to get the parent alias as the source
                    $source_alias = array_slice($alias, 0, count($alias) - 1);
                } else {
                    //
                    //We have a non-hierarchical case.
                    //
                    //The source alias is the same as the given one
                    $source_alias = $alias;
                }
                //
                //Get the nearest artefact. It must not be ambiguous. 
                //If there is none, return a (missing data) error
                $foreigner->nearest = $foreigner->get_nearest_artefact($away, $alias);
            }
        }

        //Save the indices of this artefact to return a conditioned 
        //expression.  
        public function save_indices(/* row|null */$row): array/* {condition, exp} */ {
            //
            //A hard update is required if the primary key expression is already 
            //set from the user input, i.e., the old guard driver scenario. This
            //is occurs when:-
            if (
                //...the primary key column is set...
                isset($this->pk()->exp)

                //... with a scalar expression (not a null for new entries)...
                && ($pk = $this->pk()->exp) instanceof scalar
                //
                //...that has a value component set...
                && isset($pk->value)
                //
                //...to a valid (autonumber) (not just the position info)
                & !is_null($pk->value)
            )
                return ["type" => HARD_UPDATE, "exp" => $pk];
            //
            //Use the identifiers to look up this artefact from the database
            //
            //Make sure that this table's indices is set otherwise throw an exeception
            if (!isset($this->indices)) {
                throw new myerror("Bad data model; no unique indentification indices found for table '$this->name'");
            }
            //
            //Save all the indices of this artefact
            $results/* Array<exp> */ = array_map(
                function($index) use ($row){
                    //
                    //Get the index save answer, i.e., myerror or a scalar
                    $ans = $index->save($row);
                    //
                    //Save it under the index, for retrieval later, to be used 
                    //for compiling more helpful error message
                    $index->ans = $ans;
                    //
                    //Return the result
                    return $ans;
                },
                $this->indices
            );
            //
            //Select the resulting expressions that indicate valid saves
            $valids = array_filter(
                    $results,
                    fn($result) => $result instanceof scalar);
            //
            //Extract the alien driver. A driver is an alien if he 
            //has no valid papers
            if (count($valids) === 0) {
                //
                //Use this artefact's indices to compile useful error message
                $errors = array_map(
                    //
                    //Map the index to its name and the result of the save    
                    fn($index)=>"$index->name:$index->ans", 
                    $this->indices
                );
                //
                //Join the error messages to a single string
                $msg = join("<br/>\n", $errors);
                //
                //Formulate the complete error message
                $exp = new myerror(
                    "No valid index found for table '{$this->name}'"
                    . " because of the following:-\n"
                    . $msg
                );
                //
                //Returns the alien condition
                return ["type" => ALIEN, "exp" => $exp];
            }
            //
            //Get and clean the licences
            $dirty_licences = array_map(
                    fn($paper) => $paper->value,
                    $valids);
            //
            //Clean them 
            $licences = array_unique($dirty_licences);
            //
            //Count the clean licences
            $no = count($licences);
            //
            //Fraud:
            //A driver is a fraud if his papers resolve to multiple licences      
            if ($no > 1) {
                //
                //Compile the inconsistency error (multiple pk error)
                $exp = new myerror("$no Primary key found associated with this entity"
                        . " $this->name consider merging");
                //
                //Return the expression
                return ["type" => FRAUDULENT, "exp" => $exp];
            }
            //
            //Get the licences obtained by inserting 
            $inserts = array_filter(
                    $valids,
                    fn($licence) => isset($licence->type) && $licence->type === 'insert'
            );
            //
            //Post_graduate 
            //A driver is a post graduate if one of his valid licences is an insert 
            if (count($inserts) > 0) {
                //
                //Get the first insert (ignoring the index names)
                $insert = array_values($inserts)[0];
                //
                //Return the insert
                return ["type" => INSERT, "exp" => $insert];
            }
            //
            // A driver is an under graduate if there are no inserts. 
            if (count($inserts) === 0) {
                //
                //Get the first member of the valid ones
                $valid = array_values($valids)[0];
                //
                return ["type" => SOFT_UPDATE, "exp" => $valid];
            }
            //
            //I will never get here, but, make peace with the compiler 
            throw new \Exception("Unusual entity write situation");
        }

    }

//The data capture column extends the root column with an alias and a data 
//source of the same type as the root version
    class column extends \column {

        //
        //The (root) source of data for this column 
        public \column $source;
        //
        //The artefact that is the home of this column
        public artefact $artefact;
        //
        //Data capture expression that is the main reason for extending the root
        //column. Examples are: new lookup('msg', 'date'). Typically this is set 
        //from the Iquestionnaire inputs 
        public expression $exp;
        //
        //This the simplified form of this column's expression during save 
        //operation. For instance the anwser of simplifying new lookup('msg', 'date') 
        //may be a simple scalar, new scalar("2020-12-01"). Typically this is set
        //on a record by record case
        public /* ans */ $answer = null;
        //
        //Holder for the (scalar) value, e.g., #2020-12-01', that is bound to a 
        //prepared statement. It is initialized to null to make the property a 
        //referencce available. Typically this is set just before excuting a prepared
        //statement
        public /* basic_value */ $scalar = null;

        //
        function __construct(\column $source, artefact $artefact) {
            $this->source = $source;
            $this->artefact = $artefact;
            //
            //Initialize the parent column
            //
            //The parent entity of the column is the same as that of the 
            //source column. NB: It is protected
            $parent = $source->get_entity();
            //
            //The name of the column is the same as that of the source
            $name = $source->name;
            //
            //Now construct the parent root column
            parent::__construct($parent, $name);
        }

        //Writing a column depends on its type. This function is the main evidence
        //that our OO model comprising of a database-entity-column-artefact needs
        //revision. For instance, capture/artefact does not extend a /table as 
        //one would expect. Instead, capture/artefact has a /table source. 
        //Likewise, capture/column doe not extend /column ; the latter is a 
        //source in teh formare.
        function write(/* row|null */$row): ans {
            //
            //Start with a null expression to return
            $exp = null;
            //
            //Save attributes
            if ($this->source instanceof \attribute) {
                //
                //Saving an attribute involves simplifying its input/capture 
                //expresssion and returning the output version
                $exp = $this->write_attribute($row);
            }
            //
            //Save foreign key columns
            elseif ($this->source instanceof \foreign) {
                //
                //If the foreign key already has an expression, use it for the next 
                //steps
                if (isset($this->exp)) {
                    //
                    $exp = $this->exp;
                }
                //
                //If the nearest artefact is an error, then let the save result
                //be the error
                elseif ($this->nearest instanceof \myerror) {
                    //
                    $exp = $this->nearest;
                }
                //If the answer of this nearest artefact is still null, something is 
                //wrong!
                elseif ($this->nearest->pk()->answer == null) {
                    $ename = $this->nearest->pk()->ename;
                    $cname = $this->nearest->pk()->name;
                    throw new \Exception("$ename.$cname is not set as expected. Check dependency sorting");
                } else {
                    //
                    //The nearest is an artefact. Bind its primary key to that of this
                    //foreigner.
                    $exp = $this->nearest->pk()->answer;
                }
                //
                //It is an error if the expression is not an answer
                if (!$exp instanceof ans)
                    throw new \Exception("The foreign key must be of type answer");
            }
            //
            //Save the primay key, by saving indices
            elseif ($this->source instanceof \primary) {
                //
                //Deriving a primary key may or may not involve inserting a record 
                //to the database -- depending on the available identification keys
                $exp = $this->write_primary($row);
            }
            //Any other type of column is not expected
            else {
                throw new \Exception(
                        "A column of type "
                        . json_encode($exp)
                        . " cannot be writen"
                );
            }
            //
            //Set the scalar value to support binding of sql statements
            if ($exp instanceof scalar)
                $this->scalar = $exp->value;
            //
            //Set the answer to support testing of whether the scalar is valid or not
            $this->answer = $exp;
            //
            //Return the expression
            return $exp;
        }

        //Write an attribute column to the database by simplifying its expression
        //to a basic value (or error).
        private function write_attribute(/* row|null */$row): ans {
            //
            //We are done, if the answer of this column is known. It may have been
            //set during the writing of table independent artefacts. It is null when
            //not set
            if (!is_null($this->answer))
                return $this->answer;
            //
            //Ensure that the attribute's size for identifiers
            //is less or equal to the size of the column. 
            if (
                    isset($this->exp) && $this->exp instanceof scalar && $this->source->data_type === "varchar" && $this->source->is_id() && ($size = strlen(strval($this->exp->simplify()))) > $this->source->length
            ) {
                $exp = new \myerror("The size of column " . $this->source->full_name()
                        . "is $size which is larger than " . $this->source->length);
            }
            //
            //If the expression is set, simplify it
            elseif (isset($this->exp)) {
                $exp = $this->exp->simplify();
            }
            //
            //The attribute's value not set; try the default.
            elseif ($this->source->default !== 'NULL' & !is_null($this->source->default)) {
                //
                //Parse the default value to get an expression.
                $exp = $this->parse_default($this->source->default);
            } else {
                //
                //Get this column's entity name
                $ename = $this->entity->name;
                //
                //Create an erroneous expression for missing data 
                $exp = new \myerror(
                        "Attribute $ename.$this->name's data is missing"
                );
            }
            //
            //Return the expression
            return $exp;
        }

        //
        //This is a scaled down version of 'parsing of default values'
        //in a mysql database.
        private function parse_default(string $default): expression {
            //
            //Special cases of default values refer to inbuilt functions, i.e.,
            //they are not merely scalar strings. They require special 
            //interpretation
            switch ($default) {
                //
                //Note, that functions referenced in default values do not have the 
                //closing parentheses and are in upper case.
                //For time stamp, return the current date string
                case 'CURRENT_TIMESTAMP':
                    return new scalar(date("Y-m-d H:i:s"));
                //
                //The general case for default value is that it is a scalar
                default :
                    return new scalar($default);
            }
        }

        //Returns the nearest artefact (contextually) as an expression. 
        //There may be ambiguty or none, in which case the corresponding error is 
        //returned
        public function get_nearest_artefact(\entity $entity, array $source_alias) {
            //
            //Collect all the aliases associated with the given entity name. They
            //are part of the keys of the artefacts \Ds\Map collection in the 
            //current questionaire
            //
            //Get the artefact/alias pairs used for indexing the artefacts
            $allkeys = \questionnaire::$current->artefacts->keys()->toArray();
            //
            //Isolate the keys that match the given entity. The entity component is
            //the first part of a key. 
            $keys = array_filter($allkeys, fn($key) => $key[0] === $entity);
            //
            //Collect all the keys' aliases. The alias is the 2nd component of a key
            $all_aliases = array_map(fn($key) => $key[1], $keys);
            //
            //Remove those aliases that are not ancestors of the source aliases
            $aliases = array_filter(
                    $all_aliases,
                    fn($alias) => $this->is_ancestor($alias, $source_alias)
            );
            //
            //Compute the contextual distances/alias pairs
            $pairs = array_map(
                    //
                    //Compute the distance of each destination alias from the source
                    fn($alias) => [
                'alias' => $alias,
                'distance' => $this->distance($source_alias, $alias)],
                    //
                    $aliases
            );
            //
            //Collect all the contextual distances of the aliases
            $distances = array_map(fn($pair) => $pair['distance'], $pairs);
            //
            //There is no nearest artefact if no distances are found
            if (count($distances) == 0)
                return new \myerror("Missing data for this foreign key");
            //
            //Get the least distance. 
            $distance = min($distances);
            //
            //Filter the alias/distance pairs with the least distance
            $least_pairs = array_filter($pairs, fn($pair) => $pair['distance'] === $distance);
            //
            //Report ambiguity
            if (count($least_pairs) > 1)
                return new \myerror('Ambiguity error', $least_pairs);
            //
            //Ensure that the pairs are indxed numerically. Get the first pair as 
            //the only one. The distance is immaterial. Its the matching alias part
            //that you want 
            $least_alias = array_values($least_pairs)[0]['alias'];
            //
            //Use the nearest alias to get the matching artefact
            $artefact = \questionnaire::$current->artefacts->get([$entity, $least_alias]);
            //
            //Return the nearest artefact
            return $artefact;
        }

        //Returns true if array A is an ancestor of array B. 
        //Consider A=[] and B = [0]. In this case, A is an ancestor of B
        function is_ancestor(array $A, array $B): bool {
            //
            //A is an accestor of B if both are equal to each other. E.g.,
            //[1,2,3] is an accestor of [1,2,3]
            //[] is an ancestor of []
            if ($A === $B)
                return true;
            //
            //If A is empty, then its ancestor of B
            if (count($A) === 0)
                return true;
            //
            //If B is empty, then it can never be an ancestor of A
            if (count($B) === 0)
                return false;
            //
            //Drop the last element of B (to get B2) and 
            //repeat the test
            $B2 = array_slice($B, 0, count($B) - 1);
            //
            //A is an ancestor of B if A is an accestor of B2
            return self::is_ancestor($A, $B2);
        }

        //Returns the contextual distance between two contexts, a.k.a, aliases
        //E.g., the distance between [3,8,6,2] and [3,8,4,3] is 4 
        //The shared elements are [3,8].
        private function distance(array $alias_source, array $alias_dest): int {
            //
            //Start with a shared length($l) of 0. Note that we need access to this
            //share lenth at a leter stage. Hence, it is placed outside of the for 
            //loop
            $l = 0;
            //
            //Loop throgh the elements of the source context and stop when ...
            for (; $l < count($alias_source); $l++) {
                //
                //...the index to the destination alias is not defined...
                if (!isset($alias_dest[$l])) {
                    break;
                }
                // 
                //... or the elements of the indexed source and destination are 
                //different
                if ($alias_source[$l] !== $alias_dest[$l]) {
                    break;
                }
            }
            //
            //At this point $l represents the number of elements in the shared array
            //
            //Return the distance as the sum of (a) the size of the source (without 
            //the shared elements) and (b) the size of the destination (also without 
            //the shared elements)
            return count($alias_source) + count($alias_dest) - 2 * $l;
        }

        //Writing the primary key column, effectively, writes the whole record
        //of underlying artefact.
        //For a table independent situation, the row is null; otherwise it is the 
        //row (index) of the record being written.
        //The return value is an answer, i.e., myerror, or scalar -- the expression 
        //resulting from saving a record.
        private function write_primary(/* row|null */$row): ans {
            //
            //Save the arterfact's indices and return the conditioned result
            $condition = $this->artefact->save_indices($row);
            //
            //Determine if the result need to be updated or not.
            switch ($condition['type']) {
                //
                //The resulting expression need to be adjusted for updates
                case HARD_UPDATE:
                case SOFT_UPDATE:
                    //
                    //Get the output expression
                    $exp1 = $condition['exp'];
                    //
                    //Save the expression, so that update can re-access it
                    $this->artefact->pk()->answer = $exp1;
                    //
                    //Ensure that the scalar property of the artefact is set
                    //as evaluaion of the following update query depends on it.
                     $this->artefact->pk()->scalar = $exp1->value;
                    //
                    //Update the non-cross members of this artefact
                    $exp = $this->artefact->update['non_cross_members']->execute();
                    break;
                //
                //The results do not need adjustment for all the other conditions
                case ALIEN:
                case FRAUDULENT:
                case INSERT:
                    $exp = $condition['exp'];
                    break;
                default:
                    throw new \Exception("Index condition '" . $condition['type'] . "' is not known");
            }
            //
            return $exp;
        }

    }

//Modelling (large) data laid out in a tabular fashion. This is the base class 
//tabular cvs file, query, fukel(array) -- and other user defined classes 
    abstract class table extends \schema {
        //
        //The table name used in specifying lookup expressions
        public string $name;
        //
        //A sample of 3 rows used for logging the status of saving a barrel, 
        //rather than logging the entire table (which might tbe too big)
        public array $rows=[];
        //
        //The following functions will need to be implemented
        //
        //Returns the header column names. This must be called after the a table
        //is opened
        abstract function get_header_columns(): array; /* <cname> */
        //
        //The fuel table header
        public array/* = Map<cname, dposition> */ $header;
        //
        //Return a row of data as an 1-d array, or false if at the end of the 
        //stream
        abstract function read(): \Generator/* array<basic_value>|false */;
        //
        //The current row Index of the table's body
        public int $row_index;
        //
        //Initialize the collection of table-based errors.
        public \Ds\Map $map/* :map<[tname, msg], count> */;
        //
        //The row number, starting from 0, where the table's body starts.        
        public int $body_start;
        //
        function __construct(string $tname, int $body_start = 0) {
            //
            $this->name = $tname;
            //
            $this->body_start = $body_start;
            //
            //Initialize the schema
            parent::__construct($tname);

            //Initialize the collection of table-based errors.
            $this->map/* :map<[tname, msg], count> */ = new \Ds\Map();
            //
            //Open this table; This means different things for different descendants
            //of this class. For example, for:-
            //  -simple arrays, i.e., ifuel, nothing happens.
            //  -sqls, a connection is made.
            //  -text, a file is opended
            $this->open();
            //
            //Set the header property to allow us lookup named column 
            //positions, specially needed in a lookup expression.
            $this->set_header();
        }

        //
        //Initialize the data stream. By default, do nothing. In future this 
        //mehod will be deprecated
        function open(): void {}

        //
        //Close the data stream. By default, this does noting. In future it will be
        //deprecated
        function close(): void {
            
        }

        //Write the data from this given table to the database. For each table 
        //row, tr:-
        //- Set the table header expressions
        //- Simplify all the labeled entities that use the table
        //See file 'questionnaire.ts' to view the structure of a (milk not 
        //database table)
        //The resulting answer is eithr a scalar (with an ok value) or a 
        //runtime error 
        function write(/* null|row */$row): ans {
            //
            //Select all the artetacts that depend on this table
            $artefacts = array_filter(
                //
                //Use the current pool of artefacts
                \questionnaire::$current->artefacts->values()->toArray(),
                //
                //An artefact depends on table, if it has a lookup
                //expression referencing this table
                fn($artefact) => isset($artefact->tname) && $artefact->tname == $this->name
            );
            //
            //Export the table's body
            //
            //For each table row, set the header expressions. (Rememeber to track 
            //the row counter in case it is used for formulating expressions)
            $ans = $this->write_body($artefacts);
            //
            //You must return answer
            return $ans;
        }

        //This test return true if the gven row of data is clean; otherwise false
        function is_clean(array $Ibarrel):bool{return true; }
        //abstract function is_clean(array $Ibarrel): bool;

        //Export the body of a table, given the artefacts that depend on it. 
        //The result is either a scalar denoting success or an error the helpful
        //details
        private function write_body(array $artefacts):ans {
            //
            //Its a sign of an error if there are no artefacts to write
            if (count($artefacts) == 0)
                throw new \Exception("No artefacts found to save for table '$this->name'");
            //
            //Start counting from row number 0
            $this->row_index = 0;
            //
            //Get the current barrel. Remember that by design, only one re-usable
            //barrel is used for writing the data, to conserve memory (for large 
            //tables). Why is barrel static?
            $barrel = barrel::$current;
            //
            //Attach the current table name to the barrel
            $barrel->tname = $this->name;
            //
            //Attach the table dependant artefacts to the barrel
            $barrel->artefacts = $artefacts;
            //
            //Start with a table error log count of 0
            $errors = 0;
            //
            //Loop through all the body rows to export them one by one.
            foreach ($this->read() as $Ibarrel) {
                //
                //If the body start is set to something greater than 0, then
                //respect it, by skipping this iteration if necessary.
                if ($this->body_start > 0 && $this->row_index < $this->body_start) {
                    //
                    //Update the row counter
                    $this->row_index++;
                    continue;
                }
                //
                //Set the barrel's row index counter
                $barrel->row_index = $this->row_index;
                //
                //Check whether the row of data, Ibarrel, is clean or not. Empty
                //rows are said to be dirty.
                //Ignore that row if it is dirty, i.e., not clean
                if (!$this->is_clean($Ibarrel)){
                    //
                    //Update the row counter. NB. Even dirty rows are counted
                    $this->row_index++;
                    //
                    continue;
                }    
                //
                //Reset/empty the non-foreign key answers of every artefact 
                //being saved, ready for the next iteration
                foreach ($artefacts as $artefact) {
                    $artefact->reset_answers();
                }
                //
                //Save the values to be accessed during evaluations of 
                //lookup functions
                $barrel->Ibarrel = $Ibarrel;
                //
                //Formulate the row object 
                $row = [
                    'row_index' => $this->row_index,
                    'tname' => $this->name
                ];
                //
                //Update the barrel's partial name. The partial name is 
                //typically for fomulating xml tags during data logging.
                $barrel->partial_name = "r$this->row_index";
                //
                //Log the barrel as you carry out the save. Log any pdo errors
                try {
                    $barrel->save($row);
                    //
                    //Sample this row -- upto a maximum of 3
                    if (count($this->rows)<3) 
                        $this->rows[]=$barrel->compile_result();
                            
                } catch (\PDOException $ex) {
                    $msg = $ex->getMessage(). " at row " 
                        . $this->row_index 
                        . " of table $this->name<br>";
                    //
                    //Log the error message in an external text file
                    fwrite(\questionnaire::$current->stream, $msg);
                    //
                    //Keep track of the log error count for this table
                    $errors++;
                }
                //
                //Increase the row counter
                $this->row_index++;
            }
            //Close the body
            $this->close();
            //
            //Keep track of questionnaire level error log count
            \questionnaire::$log_errors+=$errors;
            //
            //Return this table's error count as a scalar
            return new scalar("$errors log errors");
        }

        //Set the header property, if necessary. It is not necessary if the
        //table does not have one, e.g., a text file without header.
        //This method is called after a table is opened
        private function set_header(): void {
            //
            //Get the header column names
            $cnames = $this->get_header_columns();
            //
            //The header remains unset if the number of colmns is empty
            if (count($cnames) == 0)
                return;
            //
            //Ensure that this list is unique and report error if not
            //
            //Get the column name frequencies
            $cols = array_count_values($cnames);
            //
            //Isolate duplicate keys
            $dups = array_filter($cols, fn($freq) => $freq > 1);
            //
            //If there are duplicates, report them and stop the process
            if (count($dups) > 1) {
                //
                //Get the dulicate column names and join them with comma separation
                $dupstr = implode(", ", array_keys($dups));
                //
                //Compile the final message
                $msg = "The following header columns for table $this->tname are duplicated: $dupstr";
                //
                throw new \Exception($msg);
            }
            //Get the positions of the header columns. The positions correspond to 
            //matching data in the tables body
            $positions = array_keys($cnames);
            //
            //Save the table header as an array of postions indexed by the column
            //names. This is going to be useful for simplifying lookup expressions
            $this->header = array_combine($cnames, $positions);
        }

    }

//Modelling a simple 2-d array of basic values
    class fuel extends table {

        //
        //The 2-d array of basic values
        public array/* Array<Array<basic_value>> */ $ifuel;

        //
        function __construct(
                //
                //The table's name, used in formulating lookup expressions    
                string $tname,
                //
                //The table's header as an array of colum names indexed by their 
                //positions     
                array /* Array<position, cname> */$cnames,
                //    
                //A tables fueuel, as a double array of basic values    
                array /* Array<<Array<basic_value>>> */ $ifuel,
                //
                //Where does the body start    
                int $body_start = 0
        ) {
            $this->cnames = $cnames;
            $this->ifuel = $ifuel;
            parent::__construct($tname, $body_start);
        }

        //
        //Opening a simple array does nothing since the data is already in memory
        function open(): void {
            
        }

        //Returns the header clums
        function get_header_columns(): array/* <cname> */ {
            return $this->cnames;
        }

        //Fetch a row of data
        function read(): \Generator/* array<basic_value> */ {
            //
            //Loop through all the data rows
            foreach ($this->ifuel as $row) {
                yield $row;
            };
        }

        //Closing a an array does nothing
        function close(): void {
            
        }

    }

//Modelling a text file that is line terminated by carriage returns
    class csv extends table {

        //
        public string $filename;
        //
        //The header colmumn names. If empty, it means teh user wishes to use 
        //the default values
        public array $cnames;
        //
        //The row number, starting from 0, where column names are stored
        //A negative number means that file has no header     
        public int $header_start;
        //
        //Text stream when opened
        public $stream;

        //
        //The default specs for colummn and body locations are designed to accomodate
        //loading of a data file with no header column names, either supplied
        //by the user or fond in the data file
        function __construct(
                //
                //The name of the text table    
                string $tname,
                //
                //The filename that holds the (milk) data    
                string $filename,
                //
                //The header colmumn names. If empty, it means the user wishes to use 
                //the default values
                array $cnames = [],
                //
                //Text used as he value separator
                string $delimiter = ",",
                //
                //The row number, starting from 0, where column names are stored
                //A negative number means that file has no header     
                int $header_start = -1,
                //
                //The row number, starting from 0, where the table's body starts.        
                int $body_start = 0
        ) {
            //
            $this->filename = $filename;
            $this->cnames = $cnames;
            $this->header_start = $header_start;
            $this->delimiter = $delimiter;
            //
            parent::__construct($tname, $body_start);
        }

        //
        //Returns the header column names of a text file
        function get_header_columns(): array/* <cname> */ {
            //
            //Respect the user supplied columns as a priority. The default is an 
            //empty list
            if (count($this->cnames) > 0)
                return $this->cnames;
            //
            //Check if the header is part of the data; it is if it is a positive 
            //number
            if ($this->header_start >= 0) {
                //
                //Get the data at that row position
                for ($i = 0; ($line = fgets($this->stream)); $i++) {
                    if ($i == $this->header_start) {
                        //
                        //Parse the ans to a string, assuming csv format
                        $cnames = str_getcsv($line);
                        //
                        return $cnames;
                    }
                }
            }
            //Return an empty list to signify no column names
            return [];
        }

        //
        //
        //Open the file stream in read only mode
        function open(): void {
            $this->stream = fopen($this->filename, "r");
        }

        //
        //Fetch a row of data
        function read(): \Generator/* array<basic_value> */ {
            //
            while (!feof($this->stream)) {
                //
                //Get an unlimited line length of data and parse it using CSV format
                //to get an array of values, if successful
                $values = \fgetcsv($this->stream, 0, $this->delimiter);
                //
                //The value is not valid if it is a boolean; otherwise it is an array
                //of parsed values
                if (is_bool($values))
                    continue;
                //
                //Ignore ablank line; it is identified as an array with one null value
                if ((count($values) == 1) && is_null($values[0]))
                    continue;
                //
                //Yield the values array
                yield $values;
            }
        }

        //
        //
        //Close the text stream
        function close(): void {
            \fclose($this->stream);
        }

    }

//Modelling an sql statement as a source of body rows (of basic values) of 
//a large table. See also the following related predefined classes: fuel, csv, 
//whatsapp. Users can extend the table class to implemenet other tabular 
//sources of data 
    class query extends table {
        //
        //The sql statement and the default database name 
        public string $sql;
        public string $dbname;
        //
        //The the database connection
        public \PDO $pdo;
        //
        //The SQL statement handle that is repeatedly bound to values and
        //exceuted
        public \PDOStatement $stmt;
        //
        function __construct(
                string $tname,
                string $sql,
                string $dbname,
                int $body_start = 0
        ) {
            //
            $this->sql = $sql;
            $this->dbname = $dbname;
            //
            //Initialize the parent data table
            parent::__construct($tname, $body_start);
        }

        //Openig a query does nothomg as most operations have alleady
        //been done at in teh colstructor
        function open(): void {
            //
            //Formulate the full database name string, as required by MySql. Yes, this
            //assumed this model is for MySql database systems
            $dbname = "mysql:host=localhost;dbname=$this->dbname";
            //
            //Initialize the PDO property. The server login credentials are maintained
            //in a config file.
            $this->pdo = new \PDO($dbname, \config::username, \config::password);
            //
            //Throw exceptions on database errors, rather thn returning
            //false on querying the dabase -- which can be tedious to handle for the 
            //errors 
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            //
            //Execute the sql statement
            $this->stmt = $this->pdo->query($this->sql);
        }

        //Returns the columns of sql statement
        function get_header_columns(): array/* <cname> */ {
            //
            //Start with an empty list of columns
            $cnames = [];
            //
            //Stepth through all the columns in a statement
            for ($i = 0; $i < $this->stmt->columnCount(); $i++) {
                //
                //Get the i'th column
                $column = $this->stmt->getColumnMeta($i);
                //
                //Retrieve the ame component an add it to the list
                $cnames[] = $column['name'];
            }
            //
            //Return the names
            return $cnames;
        }

        //Fetch a row of data
        function read(): \Generator/* array<basic_value> */ {
            //
            while ($row = $this->stmt->fetch(\PDO::FETCH_NUM)) {
                yield $row;
            }
        }

        //Close the data stream
        function close(): void {
            $this->stmt->closeCursor();
        }

    }

    /**
     * This class supports processing of whatsapp messages.
     */
    class whatsapp extends table {

        //
        //This pattern should match user generated messages only
        const PATTERN_USER = "/^(\d*)\/(\d*)\/(\d*),\s(\d*):(\d*)\s([P|A]M)\s-\s([^:]*):/";
        //
        //The regular expression used to determine the current position in 
        //the newly opened document.
        //
        //This pattern should match all messages
        const PATTERN_ALL = "/^(\d*)\/(\d*)\/(\d*),\s(\d*):(\d*)\s([P|A]M)\s-\s/";

        //
        //The sourec of the whatsapp messages
        public string $filename;

        function __construct(string $tname, string $filename, int $body_start = 0) {
            $this->filename = $filename;
            parent::__construct($tname, $body_start);
        }

        //
        //Return the defaut colums of a message table
        function get_header_columns(): array {
            return ['sender', 'date', 'text'];
        }

        //Yield each row of the message body
        function read(): \Generator/* array<basic_value> */ {
            //
            //Open the text document containing the messages.
            $stream = fopen($this->filename, "r");
            //
            //There is no initial message
            $msg = null;
            //
            //Looping through all the message lines
            while ($line = fgets($stream)) {
                //
                //Define the expected matches
                $matches = [];
                //
                //Perform a regular expression match.
                $ok = preg_match(self::PATTERN_ALL, $line);
                //
                if ($ok) {
                    //This is beginning of any message (whether system or user generated)
                    //
                    //Terminate the given message.
                    yield from $this->output_message($msg);
                    //    
                    //Isolate the system generated messages
                    if (preg_match(self::PATTERN_USER, $line, $matches)) {
                        //This is a user generated message
                        //
                        //Start a new message.
                        $this->start_new_message($matches, $line, $msg);
                    } else {
                        //This is a system generated message. Discard
                        $msg = null;
                    }
                } else {
                    //The match was not successful. It means this is a continutaion of 
                    //the current message
                    //
                    if (is_null($msg)) {
                        //
                        //This must be another  system generated message. Just discard 
                        //it as well
                    } else {
                        //Expand the body of the current message
                        $msg["body"] .= $line;
                    }
                }
            }
        }

        //Start reading through a new message.
        private function start_new_message($matches, $line, &$msg) {
            //
            //Initialize the date 
            $j = $matches[1];
            $n = $matches[2];
            $y = $matches[3];
            $g = $matches[4];
            $i = $matches[5];
            $A = $matches[6];
            //
            //Create date from format: j/n/y, g:i A
            $date2 = \DateTime::createFromFormat(
                "j/n/y, g:i A", "$j/$n/$y, $g:$i $A"
            );
            //
            //Standaise as follows:YYYY-MM-DD HH:MM
            $msg["date"] = $date2->format("Y-m-d H:i");
            //
            //Initialize the sender. NB: The sender may a number or a name
            $msg["sender"] = $matches[7];
            //
            //Initialize the body
            $result = preg_split(
                self::PATTERN_USER,
                $line,
                -1,
                PREG_SPLIT_NO_EMPTY
            );
            //
            if ($result) {
                //
                //The only array element is the beginning of he message
                $msg["body"] = $result[0];
            } else {
                throw new Exception("Sorry, something went wrong!");
            }
        }
        //
        //Close and output the current message
        private function output_message(&$msg): \Generator {
            //
            //Do nothing if there is no initial message
            if (is_null($msg)) return;
            //
            //Output (echo) the requested row
            yield [$msg['sender'], $msg['date'], $msg['body']];
            //
            //Reset the mesage
            $msg = null;
        }
        
        //
        //Implementation of the str_start_with() function for PHP 8
        static function startsWith($haystack, $needle) {
            $length = strlen($needle);
            return substr($haystack, 0, $length) === $needle;
        }

    }

    //This class models a table row of data values; it was introduced to primarily 
    //support logging of individual table rows without having to create multiple 
    //copies of this instance in order to save memory for large data loads.  
    class barrel extends \schema {

        //
        //The artfacts being saved
        public array $artefacts;
        //
        //The shared barrel used for exporting table rows; it is initialized
        //when a questionnaire is created. Why is this not a property of a table? 
        static barrel $current;
        //
        //The last row of values fetched
        public array $Ibarrel;
        //
        //The table being saved
        public string $tname;
        //
        //The row being saved
        public int $row_index;

        //
        function __construct() {
            parent::__construct('barrel');
        }

        //
        //Write the barrel in 2 phases. In phase 1, the artefact is saved
        //using structural columns; in the next phase, cross members are used to
        //update the same artefact, thus preventing possibility of endless looping.
        function write(/* row */ $row): ans {
            //
            //Get the table-dependent artefacts being exported
            $artefacts = $this->artefacts;
            //
            //Save them using the given table row
            \questionnaire::$current->export_artefacts($artefacts, $row);
            //
            //You must return answer; go for a string scalar
            return new scalar('check');
        }
        
        //Compile the results of this barrel (for reporting purposes) as an 
        //array of labels.
        public function compile_result():array/*<label>*/{
            //
            //Only those artefacts that were erroneousa are considered.
            $artefacts = array_filter(
                 $this->artefacts, 
                    fn($artefact)=>
                        //
                        //Modelling of capture\myerror and \myerreor is not
                        //consistent. One would expect that capture\error extends
                        //\mysrror, but it does not. Why? Because capture\error 
                        //extends an capture\expression. This needs a re-think.
                        //For now....
                        $artefact->pk()->answer instanceof myerror
                        ||$artefact->pk()->answer instanceof \myerror
            );
            //
            //Loop through all the artefacts of this barrel and....
            $labels = array_map(
                //Compile an artegact to a label for reporting purpose
                fn($artefact)=>$artefact->compile_result(),
                $artefacts    
            );
            //
            return $labels;
        }
    }

    //Modelling expression in the questionnare namespace, i.e., was designed to
    //support data inputs beyond simple. In contrast, the expression modelled
    //in the root namespace was primarily designed to support the sql views.
    abstract class expression extends \mutall implements \operand {
        //
        //An expression can have a position associated with it. This is 
        //important for holding such data when its available.
        public /*[row_index, col_index?]|null*/ $position = null; 
        //
        function __construct(){}

        //Create an expression class object from the given input
        static function create(/* Iexp */ $Iexp): expression {
            //
            //SCALAR:Basic values are converted to the scalar object. In the sql 
            //namespace, this is referred to as a literal
            if (is_scalar($Iexp)) {
                return new scalar($Iexp);
            }
            //
            //All other forms of functions are specified as a tupple with 2 
            //arguments: [fname, ...arg]
            if (!is_array($Iexp)) {
                //
                return new myerror(json_encode($Iexp) . " must be a multi-element tupple");
            }
            //
            //Destructure the tuple to get the function's name and its
            //arguements
            //
            //The first element is the functions's name; its must be set
            if (!isset($Iexp[0]))
                return new myerror("Name of the function not found");
            $fname = $Iexp[0];
            //
            //The rest of the arguments depend on the function
            $args = array_slice($Iexp, 1);
            //
            //Create the function and catch any construction error.
            try {
                //Formulate the function and return it......
                return new $fname(...$args);
            } catch (Exception $ex) {
                return new myerror("Function error in '$fname': " . $ex->getMessage());
            }
        }

        //collect table names that this expression depends. 
        abstract function collect_tname(array &$tnames): void;

        //
        //Check this expression for integrity errors
        abstract function pass_integrity(&$error): bool;
    }

//The capture version of a scalar. The scalar version of teh schema namespace
//implements an expression interface while that of this namespace extends
//an actual expression class. However, they implement certain methods teh same 
//way, hence the trait. 
    class scalar extends expression implements \answer {

        //
        //Borrow the shared methods between scalar in the root and capture namespaces
        use \scalar_trait;

        //
        //The type is particulary useful in insert and update operations
        function __construct($value, $type = null) {
            //
            //The data typ of a scalar's value should be text, number or boolean. 
            //See PHP  definition of a scalar. Null is not a scalar; it is an object.
            if (!(is_scalar($value) || is_null($value))) {
                throw new \Exception(
                        'The value of a literal must be a scalar or null. Found '
                        . json_encode($value)
                );
            }
            //
            //save the value
            $this->value = $value;
            $this->type = $type;
            //
            parent::__construct();
        }

        //A scalar does not yield any tname
        function collect_tname(array &$tnames): void {
            
        }

        //A scalar expression always passe integrity checks always
        function pass_integrity(&$error): bool {
            return true;
        }

    }

//A lookup is an expresion associated with a specified column of a give 
//table
    class lookup extends expression {

        //
        //The name of the table to be looked up
        public string $tname;
        //
        //The column to be used for simplifying a look expression can be 
        //specified as either a name or a (0-based) position
        public /* string|int */ $cname;

        //Creates a lookup function from the given arguments. 
        function __construct(string $tname, /* int|string */ $cname) {
            //
            $this->tname = $tname;
            $this->cname = $cname;
            //
            parent::__construct();
        }

        //A lookup expression yields the exprsession's table name
        function collect_tname(array &$tnames): void {
            $tnames[] = $this->tname;
        }

        //Simplify a lookup function by looking it up its values in the last fetched
        //ones in the underlying  table's body
        function simplify(): ans {
            //
            //Get the (milk) table name of this this lookup expression
            $tname = $this->tname;
            //
            //The table must exist
            if (!isset(\questionnaire::$current->tables[$tname]))
                return new myerror("Table '$tname' is not found");
            //
            //Get the table that is referenced
            $table = \questionnaire::$current->tables[$tname];
            //
            //Get the colum associated wth the looku expression
            $cname = $this->cname;
            //
            //Get the position of the required value. Remember:-
            //1- No position computations are necessary if the column is an integer
            //2- The header are column positions indexed by the column 
            //names
            $position = null;
            //
            //If the column is a name, then there must be a header associated with 
            //the table 
            if (is_string($cname)) {
                //
                //The header must be available
                if (!isset($table->header)) {
                    throw new \Exception("Table '$tname' has no header");
                }
                //
                //Check that the named column exists in the header
                if (!isset($table->header[$cname]))
                    return new myerror("Column $cname is not found in table $tname");
                //
                $position = $table->header[$cname];
            }
            //When the column is given as a position
            elseif (is_int($cname)) {
                //
                //The column name is specified in terms of the position
                $position = $cname;
            }
            //The columns data type is not known
            else {
                throw new \Exception("Data type for column $cname in table $tname is not expexted");
            }
            //
            //Get the current row (a.k.a., barrel) being processed
            $barrel = barrel::$current;
            //
            //Ensure that the barrel is associated with this table
            if ($barrel->tname !== $tname)
                throw new \Exception(
                        "The curent barrel is not assciated with table '$tname'"
                );
            //
            //The current barrel has the data we are looking for. It must
            //have been set by the loop that visited each row of the milk table
            if (!isset($barrel->Ibarrel[$position]))
                new \null_();
            //
            //Get the basic value at the requested position
            $value = $barrel->Ibarrel[$position];
            //
            //If the value is null convert it to a null expression
            if (is_null($value))
                return new \null_();
            //
            //if it is a scalar reurn a scalar
            if (is_scalar($value))
                return new scalar($value);
            //
            //This must be an unknown data type
            return new myerror("Invalid data type at current row for $tname.$cname");
        }

        //A lookup can pass or fail integrity checks
        function pass_integrity(&$error): bool {
            //
            //Get the table and column names for the lookup
            $tname = $this->tname;
            $cname = $this->cname;
            //
            //Get the string version of this expression
            $lookupstr = "lookup($tname, $cname)";
            //
            //The named table must exist in the current questionnaire
            if (!isset(\questionnaire::$current->tables[$tname])) {
                $error = new myerror(
                        "Table name '$tname' in $lookupstr is not known"
                );
                return false;
            }
            //
            //Get the actual milk table
            $table = \questionnaire::$current->tables[$tname];
            //
            //The named column must be exist in the table
            if (!isset($table->header[$cname])) {
                $error = new myerror(
                        "Column '$cname' not found in table '$tname'"
                );
                return false;
            }
            //
            //Integrity test is passed
            return true;
        }

    }

//Modelling the concatenation function
    class concat extends expression {

        //
        //The contantenation arguments
        public array $args;

        //
        function __construct(...$args) {
            //
            //Convert the arguments to expressions
            $this->args = array_map(fn($arg) => expression::create($arg), $args);
            parent::__construct();
        }

        //When a function is simplified, we get a literal or 
        //undefined
        function simplify(): ans {
            //
            //Simplify all the arguments
            $args = array_map(fn($arg) => $arg->simplify(), $this->args);
            //
            //The concat function result is undefined if any of its 
            //simplified arguments is not a scalar
            $undefineds = array_filter($args, fn($arg) => !$arg instanceof scalar);

            //
            //Test for undefined arguments and return the erroneous arguements if 
            //any
            if (count($undefineds) > 0) {
                //
                //Compile the undefineds to a friendly message
                $strs = array_map(fn($undef) => "$undef", $undefineds);
                //
                //Join the strings, sepated by a comma
                $msg = implode(", ", $strs);
                //
                //Simplification is not possible
                return new myerror("Error in concat arguements: $msg");
            }
            //
            //Perform the concat operation
            //
            //Get the bits to concatenate
            $strs = array_map(fn($arg) => $arg->value, $args);
            //
            //Do the concatentation and return the simple scalar
            return new scalar(implode("", $strs));
        }

        //
        //The concat expression yields as many tables as its arguments
        function collect_tname(array &$tnames): void {
            //
            foreach ($this->args as $arg) {
                //
                $arg->collect_tname($tnames);
            }
        }

        //A concat expression passes the integrity test if all its arguments
        //pass the test
        function pass_integrity(&$error): bool {
            //
            //Test all the arguments
            //
            //Start with an empty list of error collection
            $errors = [];
            //
            //Loop through all the arguements and test for integrity
            foreach ($this->args as $arg) {
                //
                //Add the resulting error if this argument fails the test
                if (!$arg->pass_integrity($error))
                    $errors[] = $error;
            }
            //
            //Concan fails the integrity test if at least one of its arguments
            //fails the test
            if (count($errors) > 0) {
                //
                //Collect the error message
                $error = "Concat fails integrity test because of " . json_encode($errors);
                //
                return false;
            }
            //
            return true;
        }

    }

//For reporting errors in the capture namespace. It has very different 
//requirements to those of the root namespace version.
    class myerror extends expression implements ans {
        //
        //Requirements for supporting the get_position() method
        public /*[row_index, col_index?]*/$position = null;
        //
        //This functin is important for transferring expression position data 
        //between expressions. E.g., 
        //$col->ans->position = $col->exp->get_postion()
        function get_position(){return $this->position; }
        //
        //The error message
        public string $msg;
        //
        function __construct(string $msg) {
            $this->msg = $msg;
            parent::__construct();
        }

        //
        //An error message cannot be simplified
        function simplify(): ans {
            return $this;
        }

        //
        //An error does not yield any tname
        function collect_tname(array &$tnames): void {
            
        }

        //
        //An error has no checks for integrity. So, it always pass the integrity 
        //tests
        function pass_integrity(&$error): bool {
            return true;
        }

        //The string representation of an error
        function __toString(): string {
            return "Error. $this->msg";
        }

    }

//Modelling prepared and ordinary sql staments for capturing data
    abstract class statement {

        //
        //Types of statements: ormal or prepared
        const normal = "normal";
        const prepared = "prepared";

        //
        //The base of this statement    
        public artefact $artefact;
        //
        //Simplify access to the pdo
        public \PDO $pdo;
        //
        //The pdo statement handle
        public \PDOStatement $handle;
        //
        //The columns of this statement that must be bound
        //before this statement is executed
        public array /* <column> */$columns;

        //Construct an sql statement base on the aliased entity, i.e., artefact. 
        function __construct(artefact $artefact) {
            //
            //Save the input artefact for this statement.
            $this->artefact = $artefact;
            //
            //Set this statement's columns that need to be bound. These are
            //somewhat aliase columns. They are not the originals
            $this->columns = $this->get_columns();
            //
            //Set the pdo of the statement as the same as that of the 
            //underlying database;
            $this->pdo = $this->artefact->get_dbase()->pdo;
            //
            //Access to the pdo under these data writing circumstances require
            //that statements should execute even if they are erroneous. Error
            //trapping using try/catch blocks does not seem to work; so we will
            //use the approach of executing the statements silently then testing
            //the results for errors. 
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
            //
            //Prepare the statement and set the handle
            $this->handle = $this->prepare_statement();
            //
            //Use the handle to bind parameters
            $this->bind_parameters();
        }

        //Prepare this statement and return te PD version
        function prepare_statement(): \PDOStatement {
            //
            //Get the sql text fit for preparing this statement. Yes, a prepared,
            //rather than ordinary statement is rquired.
            $this->stmt = $this->sql($this->columns, true);
            //
            //Prepare its pdo sttaement
            $handle = $this->pdo->prepare($this->stmt);
            //
            return $handle;
        }

        //The prepared or normal sql text is driven by the given columns
        abstract function sql(array $columns, bool $prepared): string;

        //Returns the columns of this statement that need to be bound 
        function get_columns(): array/* <column> */ {
            //
            return array_filter(
                //
                //Select from the underlying artefact all those columns that...
                $this->artefact->columns,
                //
                //...that are fit to participate in a bound statement. Yes, 
                //this is the case if the column...
                function($col) {
                  //
                  $yes = 
                    //...is not a primary key, as this does not take part in data 
                    //export
                    !($col->source instanceof \primary)
                    //
                    //...and must participate in the current questionnaire. 
                    && (
                        //An sttribute participates if its expression is not  
                        //erroneous 
                        (isset($col->exp) && !($col->exp instanceof myerror))
                        //
                        //A foreigner particiates if it points to a valid artefact
                        || (isset($col->nearest) && $col->nearest instanceof artefact)
                    )
                    //
                    //...and must satisfy the statement specific condition.
                    // E.g., a cross_member column is valid only for cross 
                    // member update
                    && $this->is_valid($col);
                    //
                    return $yes;
                }
            );
        }

        //The statement specific condition for a column to take part in the
        //binding process. E.g., an update query for non-cross members should
        //target non-cross member columns only
        abstract function is_valid(column $column): bool;

        //Bind the all the parameters of this statement
        function bind_parameters() {
            //
            //Bind the column selection parameters to their scalar values
            foreach ($this->columns as $column) {
                //
                //Bind the named column 
                $this->bind_parameter(":{$column->source->name}", $column->scalar);
            }
        }

        //Execute a statement using a prepared or normal statement to return an 
        //expression as the result. The prepared version is valid
        //only when all the columns bound to parameters of this statement are
        //valid. Typically the result is a primary key value, error, or \null
        function execute(): ans {
            //
            //Collect the result for saving all the columns bound to this 
            //statement that were truely saved.
            $cols = array_filter(
                    $this->columns,
                    fn($col) => $col->answer instanceof scalar
            );
            //
            //Count the valid cases
            $all_valids = count($cols);
            //
            //Prepare for successful or failed execute -- depending on
            //how many columns were validly saved
            //
            //No bound column of this statement is valid
            if ($all_valids == 0) {
                //
                //Handle the case of empty inputs. Only for update is this
                //not an error
                return $this->handle_empties();
            }
            //
            //The bound columns are fewer than expected; handle this situation 
            //depending the the statement type. Fore select,this is definitely
            //an error. For update and insert, use a non prpeared statement  to 
            //proceed
            if ($all_valids < count($this->columns)) return $this->handle_fewer ();
            //
            //Execute the prepared statement and return ok=true when successful;
            //otherwise set ok to false
            $ok = $this->handle->execute();
            //
            //Check the results and proceed accordingly
            if ($ok) {
                //Return the answer to a successful execute. The select statement
                //returns either a primary key or null; the insert returns the 
                //last inserted id and the update returns the primary key of
                //the underlying artefact
                $answer = $this->handle_success($this->handle);
            }
            //Otherwise compile the error message
            else {
                //
                //Get the error message from the statement handle
                $msg = $this->handle->errorInfo()[2];
                //
                //Return the exception error as the answer
                $answer = new myerror($msg);
            }
            //
            return $answer;
        }

        //Handle the cases when there are no valid columns bound to 
        //parameters of this statement.
        abstract function handle_empties(): ans;

        //Handle the case of a successful execute. Generally, this returns the
        //answer associated with the primary key, but for insert, its the last 
        //inserted id, as an expression.
        //The sttaement parameter is important, because it may be from the
        //that the prepared or normal statement(when theer is not enough data
        //for all the parameters)
        abstract function handle_success(\PDOStatement $stmt): ans;

        //Handle the cases where the number of bound columns is fewer than
        //the available data (expressions). Generally, this executes a non prepared
        //statement. Throwing of an exception is specific to the select statement
        function handle_fewer(): ans {
            //
            //Collect columns of this statement that have valid data 
            $columns = array_filter(
                    $this->columns,
                    fn($col) => $col->answer instanceof scalar
            );
            //
            //Use the columns to formulate an ordinary (not prepared) statement .
            //(true is for prepared statements)
            $sql = $this->sql($columns, false);
            //
            //Query the associated database, returning a temporary pdo statement
            $result = $this->pdo->query($sql);
            //
            //The result is a pdo statement if succesful; otherwise its an error
            if (!$result) {
                //
                //Get the error message
                $msg = $this->pdo->errorInfo()[2];
                //
                //Return the error
                return new myerror($msg);
            }    
            //
            return $this->handle_success($result);
        }

        //
        //Bind the given parameter to the given variable
        function bind_parameter(string $parameter, &$variable): void {
            //
            if (!$this->handle->bindParam(
                //    
                $parameter,
                //    
                $variable
                //
                //All mysql paremeters are assumed to be of type string
                //int $data_type = PDO::PARAM_STR, 
                //
                //What is the importamce of these?
                //int $length = ? , 
                //mixed $driver_options = ? 
            )) {
                throw new \Exception("Unable to bind parameter $parameter in {$this->sql()}");
            }
        }

        //
        //How to export data using a statement
        function export(): expression {
            //
            //Decide which form of execute you want -- prepaed or normal 
            //query
            try {
                if ($prepared) {
                    $this->handle->execute();
                } else {
                    $pdo->query($sql);
                }
                //
                $result = $this->get_result();
            } catch (Exception $ex) {
                $result = new myerror($msg);
            }

            //Return the result
            return $result;
        }

    }

    //Modelling the prepared and normal insert statements as a function of all
    //data capture columns that are not cross members
    class insert extends statement {
        //
        function __construct(artefact $artefact) {
            parent::__construct($artefact);
        }
        //
        //Define what column is valid for insert.
        //A column is considered valid for insert if it is not a cross member
        function is_valid(column $col): bool {
            return !$col->source->is_cross_member();
        }
        
        //Return the insert statement which looks like...
        //
        //INSERT INTO $ename ($cname, ...) VALUES($string, ...)
        //
        //The string values may be either acutal or paremeter markers -- depending
        //on whenter we want a prapered statement or not
        function sql(array $columns, bool $prepared): string {
            //
            //Get the array indexing column names
            $columns_names = array_keys($columns);
            //
            //Map the column names to a backtick-enclosed, comma separated list.
            $column_str = implode(
                    //
                    //Use the comma as a separator    
                    ',',
                    //
                    //Enclose names with backticks    
                    array_map(fn($cname) => "`$cname`", $columns_names)
            );
            //
            //Collect all the values as a comma separated string. Pay stecial
            //attention to prepared statements
            $values_str = implode(
                    //
                    //Use the comma separator    
                    ',',
                    //
                    //The inserts may be either parameters or actiual values
                    array_map(
                            fn($col) => $prepared ? ":$col->name" : "'{$col->scalar}'",
                            $columns
                    )
            );
            //
            //3. Compile and return the insert sql statement
            return "INSERT \n"
                    //
                    //The string version provides the fully qualified name of
                    //the table into which teh values will be inserted 
                    . "INTO  $this->artefact\n"
                    //
                    //The column names to insert
                    . "($column_str)\n"
                    //
                    //The values that match the column names
                    . "VALUES ($values_str)\n";
        }

        //
        //Handle the case of a succesful insert where we return the last inserted
        //id as an expression
        function handle_success(\PDOStatement $stmt): ans {
            //
            //The primary key is the last insert id, on the pdo
            $pk = $this->pdo->lastInsertId();
            //
            //Formulate it as a literal expression and flag it as an
            //insert. This is used later to figure out if the parimary
            //key was derived via an insert or an update
            return new scalar($pk, "insert");
        }

        //It is a sign of a problem if there is nothing to insert
        public function handle_empties(): ans {
            throw new \Exception("An empty insert statement is not expected");
        }

    }

//The prepared and normal select statement is a function of all
//columns of the associated index
    class select extends statement {

        //
        //The index that characterises this select 
        public index $index;

        //
        function __construct(artefact $artefact, index $index) {
            //
            $this->index = $index;
            parent::__construct($artefact);
        }

        //The columns of a select statement are those of its associated index
        //Note that this method overrides the generalized version
        function get_columns(): array/* <schema\columns> */ {
            //
            return $this->index->columns;
        }

        //
        //Implement the required (abstract) method. Its a sign of a problem
        //if you ever call this method. Why? Because get_columns is implemeneted
        //directly for the select case
        function is_valid(column $col): bool {
            throw new Exeption("A call to this method 'is_valid' for column '$col->name'is not expected");
        }

        //The sql text of a select statement in the context of data 
        //capture tries to retrieve a primary key for a know set of
        //indexing column values, i.e.,
        //SELECT $p FROM $ename WHERE $indexers
        function sql(array $columns, bool $prepared): string {
            //
            //The where condition is an array of "anded" facts based columns 
            //of this statement's index.
            //
            //Starting with an empty list of "ands"...
            $condition = [];
            //
            //...build the where conditions.
            foreach ($columns as $col) {
                //
                //Compile an ordinary or parametrized value
                $value = $prepared ? ":$col->name" : "'{$col->scalar}'";
                //
                //Create a where clause in the form, e.g., "`cname`= '1'";
                $condition = "`$col->name`=$value";
                //
                //push the new "where" into the array
                $conditions[] = $condition;
            }
            //
            //Stringify the where array in order to formulate a complete where 
            //clause string 
            $where = implode(' and ', $conditions);
            //
            //Formuate tehsql statement to test for existence of abscence of a record
            return "SELECT\n"
                    //
                    //Ensure this is teh primary key
                    . "\t$this->artefact.`{$this->artefact->source->name}`\n"
                    . "FROM\n"
                    . "\t$this->artefact\n"
                    . "WHERE\n"
                    . "$where";
        }

        //A select statement used by an index for retriving an identified record
        //cannot be empty
        function handle_empties(): ans {
            return new myerror("Select statement for index {$this->partial_name->name} has empty columns");
        }

        //Handle the cases where the number of bound columns is fewer than
        //the available data (expressions). Generally, this executes a non prepared
        //statement, but for select, this should throw the incomplete index error
        function handle_fewer(): ans {
            //
            //Collect all the columns of this statement that are erroneous
            $columns = array_filter(
                    $this->columns,
                    fn($col) => !($col->answer instanceof scalar)
            );
            //Get the column names
            $names = array_map(fn($col) => $col->name, $columns);
            //
            //Convet the name array text
            $strs = json_encode($names);
            //
            return new myerror(
                    "Incomplete index. Data for columns, $strs, is missing or erroneous"
            );
        }

        //For a successful select, return the result ot the select. Its ether the
        //primary key scalar or a null if no record selected
        function handle_success(\PDOStatement $stmt): ans {
            //
            //Use the handle of this statement to fetch the only record, indexed
            //by numbers (rather than names)
            $result = $stmt->fetchAll(\PDO::FETCH_NUM);
            //
            //Return a null expresssion, if the result is empty
            if (count($result) == 0)
                return new \null_();
            //
            //It is an error to return more than one value
            if (count($result) > 1)
                throw new \Exception("Multiple values in the select statement:<br/>{$this->handle->queryString}");
            //
            //Convert the only result to a scalar and return 
            return new scalar($result[0][0]);
        }

    }

//The prepared and normal update statement are driven by all
//capture columns that are either:-
//(a) cross members only or
//(b) not-cross members.
    class update extends statement {
        //
        //Is this a prepared statemet or not?. 
        /*
          A prepared statement uses all columns of an artefact's as 
          entity as parameters?????
          {type:'prepared'}

          A normal statement has no parameters to bind, hence the columns are
          provided directly
          |{type:'normal', columns:array<column>}

          //If no columns are available to update, then the statement is marked
          //as such
          |{type:'none'}
         */
        public array $preparedness;
        //
        //Indicates if this update is for cross members or not
        public bool $is_cross_member;
        //
        function __construct(artefact $artefact, bool $is_cross_member) {
            //
            $this->is_cross_member = $is_cross_member;
            //
            parent::__construct($artefact);
        }

        //Override the binding of parameters to add the one for building the 
        //where clause
        function bind_parameters() {
            //
            //Bind the column selection parameters to their scalar values, as usual
            parent::bind_parameters();
            //
            //Then...
            //
            //..bind the entity limiting/filtering parameter in the where clause
            //
            //Get the primary key of this statement's artefact
            $pk = $this->artefact->pk();
            //
            //Bind the primary key scalar, under the matching parameter name
            $this->bind_parameter(":$pk->name", $this->artefact->pk()->scalar);
        }

        //A column is considered valid for binding in an update statement 
        //depending on the request, i.e., whether cross member or not
        function is_valid(column $col): bool {
            // 
            //A column should not be considered for update if it is defaulted as
            //it will likely overwrite exiting data with a default value. A 
            //column is defaulted if ...
            $defaulted = fn($col) =>
                // 
                //...there is no user supplied data associated with it....
                !isset($col->exp)
                //
                //... and it is a attribute... 
                && $col->source instanceof \attribute
                //
                //...with a predefined default value.
                & !is_null($col->source->default);
            //
            //A column is valid for update if...
            return
                //...its cross member status match the current request...
                $col->source->is_cross_member() == $this->is_cross_member
                // 
                //..and it is not defaulted (See above for definition of defaulted)
                & !$defaulted($col);
        }
        
        //The general stement process does not execute effectively for the
        //update operation, so, we are overriding it here to avoid the prepared
        //statemente approach. It needs to be stidied further. For now, assume
        //that the actual bound columns are fwer than expected, hence, us the 
        //normal update stetement
        function execute_temp(): ans {
            //
            //Collect the result for saving all the columns bound to this 
            //statement that were truely saved.
            $cols = array_filter(
                    $this->columns,
                    fn($col) => $col->answer instanceof scalar
            );
            //
            //Count the valid cases
            $all_valids = count($cols);
            //
            //Prepare for successful or failed execute -- depending on
            //how many columns were validly saved
            //
            //No bound column of this statement is valid
            if ($all_valids == 0) {
                //
                //Handle the case of empty inputs. Only for update is this
                //not an error
                return $this->handle_empties();
            }
            //
            //ASSUME: The bound columns are fewer than expected; handle this situation 
            //depending the the statement type. Fore select,this is definitely
            //an error. For update and insert, use a non prpeared statement  to 
            //proceed
            return $this->handle_fewer();
        }
        //
        //Returns the normal or prepared sql update statement:
        //UPDATE $table SET $column_values WHERE $condition
        function sql(array $incolumns, bool $prepared): string {
            //
            //1. Formulate the SET clause
            //
            //Select the columns that participates in the sql; they are either
            //all the columns of this statement, if we are preparing the 
            //statement, or only the selected ones.
            $columns = $prepared ? $this->columns : $incolumns;
            //
            //Begin with an empty set clause
            $set = [];
            //
            //Loop through the select columns pairing their names with their 
            //respective values, e.g., `name`=:name for prepared cases,
            //otherwise `name`='kamau' 
            foreach ($columns as $cname => $column) {
                //
                //Depending on the type, get the colum's value
                $value = $prepared
                    //The column's value is a colon prefixed name for prepared 
                    //statement...    
                    ? ":$cname"
                    //
                    //..or the actual quoted scalar value for a normal statement     
                    : "'{$column->scalar}'";
                //
                //Populate the set clause with the value/pair
                array_push($set, "`$cname` = $value");
            }
            //
            //Convert the set array to a comma separated text as required
            //for the clause
            $str_set = implode(',', $set);
            //
            //2. Formulate the WHERE value
            //
            //The where's condition is either bound to a parameter or set 
            //to scalr value of this statement's artefact's primary key value
            // -- depending on its preparedness
            $pk = $prepared
                //    
                //The primary key is either the name of the bound parameter, 
                //e.g., :client
                ? ":{$this->artefact->name}"
                //
                //...or the actual value 
                : "{$this->artefact->pk()->scalar}";
            //
            //This is an update statement 
            $text = "UPDATE \n"
                //
                //Update this entity using the fully sql qualifield name
                //e.g. `mutall_user`.`developer`    
                . "\t{$this->artefact} \n"
                . "SET \n"
                //
                //The update values as a set of anded key-value pairs 
                . "\t$str_set \n"
                //
                //The joins, if any;for now there is none
                //
                //The where condition
                . "WHERE\n"
                . "\t$this->artefact.{$this->artefact->name}= $pk\n";
            //
            //Return the sql text
            return $text;
        }

        //It is not an issue, if there is nothing to update. Just return 
        //the primary key of the underlying artefact
        function handle_empties(): ans {
            return $this->artefact->pk()->answer;
        }

        //For a successful update, return the primary answer
        function handle_success(\PDOStatement $stmt): ans {
            //
            //Get the primary key as a scalar answer
            return $this->artefact->pk()->answer;
        }

    }

//
//Models the index of an entity (needed for unique identification of database 
//entries) as a schema object. That means that it is capable of writing to a 
//database
    class index extends \schema {
        //
        //The index name
        public string $name;
        //
        //The artefact that is the base for this index
        public artefact $artefact;
        //
        //The columns of this index
        public array /* <capture\column> */$columns;

        function __construct(
                //
                //Name of ths index
                string $name,
                //
                //The parent artefact    
                artefact $artefact
        ) {
            $this->name = $name;
            $this->artefact = $artefact;
            //
            //Compile the partial name of this index
            $partial_name = "{$artefact->source->name}.$name";
            parent::__construct($partial_name);
            //
            //Map the incoming cnames to columns of this artefact
            $this->columns = array_map(
                    fn($cname) => $artefact->columns[$cname],
                    $artefact->source->indices[$name]
            );
        }

        //Returns the string version of this index ???? 
        function to_str(): string {
            return "`$this->ename`";
        }

        //Returns the ename of this index???????
        function get_ename(): string {
            return "`$this->ename`";
        }

        //Define the entities of this index as a function. This cannot be defined
        //as a propertu because of recurssion during serialization.
        function entity() {
            //
            //Open the database of the this index
            $dbase = $this->open_dbase($this->dbname);
            //
            //Retrive the entity mathing this indx
            $entity = $dbase->entities[$this->ename];
            //
            //Return it.
            return $entity;
        }

        //
        //Save the current record using this index. 
        // 
        //If any column of this index is erroneous, then the index cannot be used 
        //for saving the artefact; otherwise we use the columns to either insert a 
        //new record or update an existing one.
        function write(/* row|null */$row): ans {
            //
            //Collect all the invalid scalars (a.k.a., capture/myerrors or /myerror) of this 
            //index
            $invalids = array_filter(
                $this->columns,
                function($col) {
                    //
                    //The test was originally specified as 
                    //  $x instance of myerror
                    //which failed becase the correct test should have been
                    //  $x instance of myerror || $x instance of \myerror
                    //which was not obvious...AND \myerror does not extemd captire\myerror!!
                    //
                    //Before I reaslises what is happendimg, I had already 
                    //settled for... 
                    $x = !($col->answer instanceof scalar);
                    return $x;
                }
            );
            //
            //Test if this index is valid to save the current record. An index is
            //invalid if at least one of its columns is erroneous
            if (count($invalids) > 0) {
                //
                //Prepare a legible list of the erroneous columns
                $col_str = implode(
                    ',',
                    //
                    //Get the invalid column names    
                    array_map(fn($col) => $col->name, $invalids)
                );
                //
                //At least one indexing column is erronoeus. The index is unusable.
                return new myerror(
                        "Unusable index: These columns, $col_str, are invalid"
                );
            }
            //Execute the select statement for this index; the resulting expression
            //takes one of 3 forms:
            // - \null_, if the statement suggests the record does not exist
            // - scalar, if the record exists and therefore has a primary key
            // - myerror, if the statement failed to execute for whatever reason
            $result = $this->select->execute();
            //
            //If a record was retrieved or there was an error then we return the 
            //result...
            if (!($result instanceof \null_))
                return $result;
            //
            //...otherwise we execute the insert statement. NB: the resulting scalar 
            //will be marked with the 'insert'type,just in case we need to know
            //how a primary key was obtained.
            $ans = $this->artefact->insert->execute();
            //
            return $ans;
        }

    }

}