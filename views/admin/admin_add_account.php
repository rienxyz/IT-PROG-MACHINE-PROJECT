<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['e_mail']);
    $phone = trim($_POST['phone_number']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; // 'patient', 'secretary', 'doctor', 'admin'[cite: 6]
    
    mysqli_begin_transaction($connection);
    try {
        $stmtAccount = mysqli_prepare($connection, "INSERT INTO accounts (first_name, last_name, phone_number, e_mail, password, role, activity_status, verification_status) VALUES (?, ?, ?, ?, ?, ?, 'active', 'verified')");
        mysqli_stmt_bind_param($stmtAccount, "ssssss", $firstName, $lastName, $phone, $email, $password, $role);
        mysqli_stmt_execute($stmtAccount);
        
        $newAccountId = mysqli_insert_id($connection);

        if ($role === 'doctor') {
            $specialty = trim($_POST['specialty'] ?? '');
            $stmtDoc = mysqli_prepare($connection, "INSERT INTO doctors (account_id, specialty) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmtDoc, "is", $newAccountId, $specialty);
            mysqli_stmt_execute($stmtDoc);
        } elseif ($role === 'secretary') {
            $department = trim($_POST['department'] ?? '');
            $stmtSec = mysqli_prepare($connection, "INSERT INTO secretaries (account_id, department) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmtSec, "is", $newAccountId, $department);
            mysqli_stmt_execute($stmtSec);
        } elseif ($role === 'patient') {
            $stmtPat = mysqli_prepare($connection, "INSERT INTO patients (account_id) VALUES (?)");
            mysqli_stmt_bind_param($stmtPat, "i", $newAccountId);
            mysqli_stmt_execute($stmtPat);
        }

        mysqli_commit($connection);
        header("Location: admin_read_account.php?msg=account_created");
    } catch (Exception $e) {
        mysqli_rollback($connection);
        header("Location: admin_read_account.php?error=creation_failed");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLS · Add Account</title>
</head>
<body>
    <h1>Create New Account</h1>
    <form method="POST" action="admin_add_account.php">
        <label>First Name: <input type="text" name="first_name" required></label><br><br>
        <label>Last Name: <input type="text" name="last_name" required></label><br><br>
        <label>Email: <input type="email" name="e_mail" required></label><br><br>
        <label>Phone Number: <input type="text" name="phone_number"></label><br><br>
        <label>Password: <input type="password" name="password" required></label><br><br>
        <label>Role: 
            <select name="role" required>
                <option value="patient">Patient</option>
                <option value="doctor">Doctor</option>
                <option value="secretary">Secretary</option>
                <option value="admin">Admin</option>
            </select>
        </label><br><br>
        <label>Specialty (Doctor only): <input type="text" name="specialty"></label><br><br>
        <label>Department (Secretary only): <input type="text" name="department"></label><br><br>
        <button type="submit">Create Account</button>
    </form>
</body>
</html>