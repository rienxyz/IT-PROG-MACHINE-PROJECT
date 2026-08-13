<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../data/connection.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Disposition: attachment; filename="appointment_db_export_' . date('Y-m-d_H-i-s') . '.json"');

$tables = ['accounts', 'patients', 'doctors', 'secretaries', 'assignments', 'appointments', 'logs'];
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
