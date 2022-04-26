<?php
$filename= $_REQUEST["filename"];
?>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Rentize</title>
        <link rel="stylesheet" href="style.css" />
        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <script type="module">
            import run from "./script.js";
            //
            window.onload= ()=> run("./<?php echo $filename;?>");
        </script>
    </head>

    <body>
        <div id="header">
            <nav id="navigation" class="navigation">
            </nav>
        </div>
        <div id="content"></div>
    </body>

</html>
