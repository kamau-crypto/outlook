<?php
//
//View the records of some table in a desired style
//
//The page_ecords class used to drive this page is defiend in the extended 
//library that supporta a broda set of useres o interact with this page 
require_once "library.php";

//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = querystring::create(INPUT_GET);
//
//Create an instance of this page of records and assume that all the 
//required inputs have been supplied via a query string. This allows us to 
//incorporate this page in a website
$page_records= new page_records($qstring);
//
?>
<html>

    <head>
        <title>Review <?php echo $page_records->tname; ?></title>

        <link id="mutallcss" rel="stylesheet" type="text/css" href="mutall.css">
          <meta name="viewport" content="width=device-width, initial-scale=0.8">

        <!-- Include the Services Javascript library and all her ancestors-->
        <script id='library.js' src="library.js"></script>
        
        <!--Script for defining the objects needed for interacting with this page-->
        <script id='page'>
            //
            //Create an active js page of records around which the related methods 
            //will be organized.
            var page_records = new page_records(<?php echo $page_records; ?>);
        </script>

    </head>
    <body onload="page_records.onload()">

        <!-- The header section -->
        <header>
            <!-- The data for composing the where of an sql-->
            <div id='where'>
                <label for ="criteria">Search Criteria</label>
                <input 
                    type ="text" 
                    id="criteria" 
                    size="80" 
                    <?php echo $page_records->get_qstring_value('criteria'); ?>
                    onchange='page_records.onchange(this)'/>
                
                <label for ="order_by">Order by</label>
                <input 
                    type ='text' 
                    id='order_by'
                    <?php echo $page_records->get_qstring_value('order_by'); ?>
                    onchange='page_records.onchange(this)'/>
            
                <!--Hiding undesired fields -->
                <label for ="hidden_fields">Hidden Fields</label>
                <input 
                    type ="text" 
                    id="hidden_fields"
                    <?php echo $page_records->get_qstring_value('hidden_fields'); ?>
                    onchange='page_records.onchange(this)'/>
            </div>
            
            <!-- Layout group--> 
            <div id='glayout'>
                <label for='layout'>Show Label Layout</label>
                <input 
                    type ='checkbox' 
                    id='layout' 
                    <?php echo $page_records->get_qstring_value("layout", true);?> 
                    onclick='page_records.onchange(this)' />
            </div>
            
            <!-- Display Mode group-->
            <div id='gmode'>
                <label for='mode'>Show Input Mode</label>
                <input 
                    type ='checkbox' 
                    id='mode' 
                    <?php echo $page_records->get_qstring_value("mode", true);?>
                    onclick='page_records.onchange(this)' />
            </div>
            
            <!-- Selector group-->
            <div id="gselector">
                <label for='selector'>Show Multi-Record Selector</label>
                <input 
                    type='checkbox' 
                    id='selector' 
                    <?php echo $page_records->get_qstring_value("selector", true);?>
                    onchange='page_records.onchange(this)'/>
            </div>
            <!-- This tag is needed for reporting mutall errors. On clicking
            clear the error--> 
            <p id='error' onclick='this.innerHTML=""'></p>


        </header>

        <!-- Capture  the onscroll event (vertical) for this articles node -->
        <article onscroll = "page_records.vscroll(this)">

            <?php
            //
            //Display this page using the local settings, i.e.,layout and mode, 
            // defined during construction
            $page_records->display_page();
            ?>
        </article>

        <!-- The footer section -->
        <footer>
            <!-- View a detailed version of the selected record -->
            <input 
                id=view_record 
                type="button" 
                value="View record" 
                onclick='page_records.view_record()'>

            <!-- Edit the current field selection-->
            <input 
                id=edit_field 
                type="button" 
                value="Edit Current field" 
                onclick='page_records.edit_field()'>

            <!-- Add a new record-->
            <input 
                id=add_record 
                type="button" 
                value="Add New record" 
                onclick='page_records.add_record()'>

            <!-- Modify the current record-->
            <input 
                id=edit_record 
                type="button" 
                value="Modify record" 
                onclick='page_records.edit_record()'>

            <!-- Save the selected record -->
            <input 
                id=save_current_record 
                type="button" 
                value="Save record" 
                onclick='page_records.save_current_record()'>

            <input 
                id=delete_record 
                type="button" 
                value="Delete record" 
                onclick="page_records.delete_record()">

            <!-- Cancel the record/field edit operation-->
            <input 
                id=cancel_edit 
                type="button" 
                value="Cancel Edit" 
                onclick='page_records.cancel_edit()'>
            <!--
            Execute the a search on hitting the enter key. The searched 
            value will be read off the criteria input field -->
            <input 
                type ="button" 
                id="search" 
                value='Refresh' 
                onclick="page_records.search_criteria()" />

        </footer>

    </body>

</html>
