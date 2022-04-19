<?php
?>
<html lang="en">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Sending sms's</title>
</head>

<body>
    <form method="$_POST" action="send_sms.php" class="message">
        <h1>Send Text Message</h1>
        <label>To: <input name="to" type="tel" placeholder="Receiver's phone number" required pattern="\d+|\W\d+">
            </input>
        </label>
        <label for="msg">Message: </label>
        <textarea id="msg" name="message" rows="7" cols="35" autocomplete="on" autocapitalize="sentences" autocorrect="on" placeholder="Compose a message">
        </textarea>
        <input class="send" type="submit" value="send" />
    </form>
</body>

</html>