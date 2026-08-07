<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../sign_in.php?error=unauthorized");
    exit();
}
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = (int)$_POST['appointment_id'];
    $roomNumber = trim($_POST['room_number']);

    $stmt = mysqli_prepare($connection, "UPDATE appointments SET room_number = ? WHERE appointment_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $roomNumber, $appointmentId);
    mysqli_stmt_execute($stmt);

    header("Location: admin_read_room.php?msg=room_assigned");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLS · Assign Room</title>
</head>
<body>
    <h1>Assign Room to Appointment</h1>
    <form method="POST" action="admin_add_room.php">
        <label>Appointment ID: <input type="number" name="appointment_id" required></label><br><br>
        <label>Room Designation: <input type="text" name="room_number" placeholder="e.g., Room 3" required></label><br><br>
        <button type="submit">Assign Room</button>
    </form>
</body>
</html>