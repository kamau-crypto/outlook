<?php
//
//View the records of some table in a desired style
//
//The page_ecords class used to drive this page is defiend in the extended 
//library that supporta a broda set of useres o interact with this page 
require_once "../library/library.php";

//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = querystring::create(INPUT_GET);
//
//Create an instance of this page of table and assume that all the 
//required inputs have been supplied via a query string. This allows us to 
//incorporate this page in a website
$page_record= new page_record($qstring);
//
?>
<html>

    <head>
        <title>Create <?php echo $page_record->tname; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=0.8">

        <link id="mutallcss" rel="stylesheet" type="text/css" href="../library/mutall.css">
        
        <!-- Include the Services Javascript library and all her ancestors-->
        <script id='library.js' src="../library/library.js"></script>
        
        <!--Script for defining the objects needed for interacting with this page-->
        <script id='page'>
            //
            //Create an active js page table around which the related methods 
            //will be organized.  
            var page_record = new page_record(<?php echo $page_record; ?>);
        </script>
        
        <style>
            /*
            Reduce the header size from the defaault*/
            header {
                height:10%
            }
            /*
            Buttons in the header should be spaced out and stretched out a bit*/
            header input{
                margin-right: 2%;
            }

            /*
            Show label names*/
            span.normal {
                display:inline;
                /*
                Separate the field name from the data*/
                margin-right:1%; 
            }

            /*
            The width of teh record is 100%
            */
            record {
                width:100%;
                cursor:pointer;
            }

            /*
            Put spacing between fields*/
            label{
              margin-top: 1%;
            }

        </style>

    </head>
    
    <body>

        <!-- The header section -->
        <header>
            
            <!--Save record to database and show the results. In a single 
            record case the saving the record saves the captured data to the
            current window and closes the window. In contrast, the page_records
            overrides this by inserting the saved record in the page-->
            <input 
                id="save_current_record" 
                type="button" 
                value="Save record" 
                onclick='page_record.save_current_record()'/>

            <!-- Go back to the previous page-->
            <input 
                id="cancel_edit" 
                type="button" 
                value="Go Back" 
                onclick='window.close()'/>
            
            <p id='error' onclick='this.innerHTML=""'/>
            
        </header>

        <article>
            <!--The default page layout for creating new records is that of 
            label, rather than tabular-->
            <records>
               <?php
               //
               //Show the record code
               $page_record->add_record('label');
               ?>
            </records>    
        </article>

    </body>

</html>
