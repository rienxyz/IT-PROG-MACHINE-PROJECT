<?php
$localhost = 'localhost:3308';
$database = 'appointment_db';
$username = 'root';
$password = '';

$connection = mysqli_connect($localhost, $username, $password, $database);

if (!$connection) {
    die("Connection Failed: " . mysqli_connect_error() . "!");
}
?>