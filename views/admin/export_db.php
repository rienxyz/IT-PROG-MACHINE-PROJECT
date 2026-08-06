<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
require_once '../db.php';

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="appointment_db_export_' . date('Y-m-d_H-i') . '.json"');

$tables = ['accounts', 'patients', 'doctors', 'secretaries', 'assignments', 'appointments', 'logs'];[cite: 6]
$backup = [];

foreach ($tables as $table) {
    $result = mysqli_query($connection, "SELECT * FROM `$table`");
    if ($result) {
        $backup[$table] = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        $backup[$table] = [];
    }
}

echo json_encode($backup, JSON_PRETTY_PRINT);
exit();
?>