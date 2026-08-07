<?php
session_start();
require __DIR__ . '/connection.php';
$error = ''; $success = '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">

    <title> MLS · Sign In </title>
</head>

<body>
    <h2> Sign In </h2>
    
    <form action="">
        <label for="email"> E-Mail </label>
        <input id="email" name="email" type="email">

        <label for="password"> Password </label>
        <input id="password" name="password" type="password">

        <button type="submit"> Sign In </button>
    </form>
</body>

</html>