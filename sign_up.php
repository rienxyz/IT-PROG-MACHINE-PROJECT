<?php
session_start();
require __DIR__ . '/connection.php';
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = mysqli_real_escape_string($connection, trim($_POST['first-name']));
    $lastName = mysqli_real_escape_string($connection, trim($_POST['last-name']));
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $password = mysqli_real_escape_string($connection, trim($_POST['password']));

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = 'All fields are requireds!';
        $success = '';
    }

    if (!$error) {
        $query = "SELECT * FROM users WHERE e_mail = $email";
        $statement = mysqli_prepare($connection, $query);
        $result = mysqli_stmt_get_result($statement);

        if (mysqli_num_rows($result) > 0) {
            $error = 'An account already has these credentials!';
            $success = '';
        }
    }

    if (!$error) {
        $query = 'INSERT INTO users (first_name, last_name, e_mail, password) VALUES ($firstName, $lastName, $email, $password)';
        $statement = mysqli_prepare($connection, $query);

        if ($result = mysqli_stmt_get_result($statement)) {
            $success = 'Sign up successful! <a href="./sign_in.php"> Sign In Here! </a>';
            $error = '';
        } else {
            $error = 'Sign up failed, please try again!';
            $success = '';
        }
    }
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
    <h2> Sign Up </h2>

    <div>
        <?php if (!empty($error)) {
            echo $error;
        } ?>

        <?php if (!empty($success)) {
            echo $success;
        } ?>
    </div>

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