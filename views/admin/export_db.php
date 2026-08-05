<?php
require_once 'check_session.php';
require_once '../db.php';

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="appointment_db_export_' . date('Y-m-d_H-i') . '.json"');


$tables = ['users', 'doctors', 'appointments', 'logs'];
$backup = [];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $backup[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $backup[$table] = [];
    }
}

echo json_encode($backup, JSON_PRETTY_PRINT);
exit();
?>