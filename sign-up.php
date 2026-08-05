<?php
session_start();
require __DIR__ . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = mysqli_real_escape_string($connection, trim($_POST['first-name']));
    $lastName = mysqli_real_escape_string($connection, trim($_POST['last-name']));
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $password = mysqli_real_escape_string($connection, trim($_POST['password']));

    $query = 'INSERT INTO users (first_name, last_name) VALUES ($firstName, $lastName)';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">

    <title> MLS · Sign Up </title>
</head>

<body>
    <h1> Sign Up For New Users </h1>
    <!-- Patients are automatically added into the users database -->
    <!-- The rest are manually added -->

    <form action="POST">
        <label for="first-name"> First Name </label>
        <input id="first-name" name="first-name" type="text">

        <label for="last-name"> Last Name </label>
        <input name="last-name" type="text" id="last-name">

        <label for="email"> E-Mail </label>
        <input id="email" name="email" type="email">

        <label for="password"> Password </label>
        <input id="password" name="password" type="password">

        <button type="submit"> Sign Up </button>
    </form>
</body>

</html>