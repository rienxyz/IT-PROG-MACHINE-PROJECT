<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../sign_in.php?error=unauthorized");
    exit();
}
require_once '../db.php';

$docRes = mysqli_query($connection, "SELECT COUNT(*) as count FROM accounts WHERE role = 'doctor'");
$doctorCount = mysqli_fetch_assoc($docRes)['count'];

$secRes = mysqli_query($connection, "SELECT COUNT(*) as count FROM accounts WHERE role = 'secretary'");
$secretaryCount = mysqli_fetch_assoc($secRes)['count'];

$patRes = mysqli_query($connection, "SELECT COUNT(*) as count FROM accounts WHERE role = 'patient'");
$patientCount = mysqli_fetch_assoc($patRes)['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLS · Administrator Panel</title>
</head>
<body>
    <h1>Administrator Panel</h1>
    <p><strong>Technical & operational maintenance · system integrity</strong></p>

    <div>
        <p><strong>Doctors:</strong> <?= $doctorCount ?> | <strong>Secretaries:</strong> <?= $secretaryCount ?> | <strong>Patients:</strong> <?= $patientCount ?></p>
    </div>

    <h3>User Management</h3>
    <ul>
        <li><a href="admin_read_account.php">View & Manage Accounts</a></li>
        <li><a href="admin_add_account.php">Create New Account</a></li>
    </ul>

    <h3>Resource & Specialty Management</h3>
    <ul>
        <li><a href="admin_read_room.php">View Room Assignments</a></li>
        <li><a href="admin_add_room.php">Assign/Add Room</a></li>
        <li><a href="admin_add_specialty.php">Add Doctor Specialty</a></li>
    </ul>

    <h3>Data Management</h3>
    <ul>
        <li><a href="export_db.php">Export Database Backup</a></li>
    </ul>
</body>
</html>