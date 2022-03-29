<?php
//A selector page allows us to select records of a foreign key field when
//doing data entry. Only the output component of the primary key field of the 
//foreign table is shown. This compnent is derived from all the identification 
//and friendly fields of the table. 
//
//We include the extesion to the Mutall library where page_selector class is 
//defined
require_once 'library.php';
//
//Create a instance of a a page_selector.
//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = new querystring();
//
//Now create the selector page instance
$page_selector= new page_selector($qstring);
?>

<html>
    <head>
        <title>Select <?php echo $page_selector->tname; ?></title>
        
        <!-- make this page responsive to mobile platforms-->
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        
        <link rel="stylesheet" type="text/css" href="mutall.css">

        <!-- Script for resolving references to the javascript page_selector
        and other related "classes"-->
        <script id='mutall' src="library.js"></script>
        
        <!--Script for creating the js object, viz., page_selector,  needed for 
        supporting interactions with this page-->
        <script id='page'>
            //
            var page_selector = new page_selector(<?php echo $page_selector; ?>);
        </script>
            
        <style>
            header{
                height:10%
            }
            
            article{
                height:90%
            }
        </style>


    </head>
    
    <!-- Once the body is loaded, show the output subfield in the last selected
    field, for ease of reference-->
    <body onload="page_selector.onload()">

        <!-- The header section -->
        <header>
             <!--Do a search on the primary key field using the hinted value
            -->
            <div>
                <label for ="criteria">Filter Client
                
                <input 
                    type ="text" 
                    id="criteria" 
                    onkeyup="page_selector.search_hint(this.value)"/>
                </label>
            </div>
            
            <!-- The menus -->
            <div>
                <!-- Return the selected record to the caller-->
                <input id=return_field type="button" value="Return Selected Record" onclick='page_selector.return_field()'>

                <!-- Create a new record of the type that matches the selector request-->
                <input id='add_record' type="button" value="Create New Record" onclick='page_selector.create_record()'>

                <!-- Abort the selection, by simply closing the window -->
                <input id=cancel type=button value="Cancel" onclick="window.close()"/>
            </div>
            
        </header>

        <article>
            
            <?php
            //
            //Output the table name from which records are cteated
            echo "Select the $page_selector->tname"; 
            //
            //Display the the record selector ung he local settings
            $page_selector->display_page();
            ?>
        </article>

    </body>

</html>


