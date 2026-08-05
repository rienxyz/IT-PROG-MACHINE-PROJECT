<?php
require_once 'check_session.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title> MLS · Administrator Panel </title>
</head>

<body>
    <h1> Administrator Panel </h1>
    <p><strong> Technical & operational maintenance · system integrity </strong></p>

    <h3> User Management </h3>
    <ul>
        <li><a href="read_user.php"> View & Manage Accounts </a></li>
        <li><a href="add_user.php"> Create Accounts (doctors, secretaries, etc.) </a></li>
        <li> Assign roles: patient, secretary, doctor, admin </li>
    </ul>

    <h3> Resource Management </h3>
    <p> Attach rooms · real-time capacity · prevent double-booking </p>

    <h3> Data Management </h3>
    <p><a href="export_db.php"> Secure export · backup database </a></p>

    <h3> Financial Management </h3>
    <p> Auto-generate invoices · insurance verification · claims </p>
</body>

</html>