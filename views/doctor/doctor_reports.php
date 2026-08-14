<?php
require_once '../../data/connection.php';

// Single doctor context - doctor_id passed via GET (no sessions used)
$doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 1;

// Appointment counts grouped by status
$stmt = $connection->prepare(
    "SELECT status, COUNT(*) AS total
     FROM appointments
     WHERE doctor_id = ?
     GROUP BY status"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$status_counts = [];
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status'] ?? 'unspecified'] = $row['total'];
}
$stmt->close();

// Total unique patients seen
$stmt2 = $connection->prepare(
    "SELECT COUNT(DISTINCT patient_id) AS total FROM appointments WHERE doctor_id = ?"
);
$stmt2->bind_param("i", $doctor_id);
$stmt2->execute();
$unique_patients = $stmt2->get_result()->fetch_assoc()['total'];
$stmt2->close();

// Recent logs tied to this doctor's appointments
$stmt3 = $connection->prepare(
    "SELECT l.log_id, l.content, l.timestamp, l.appointment_id
     FROM logs l
     JOIN appointments ap ON l.appointment_id = ap.appointment_id
     WHERE ap.doctor_id = ?
     ORDER BY l.timestamp DESC
     LIMIT 20"
);
$stmt3->bind_param("i", $doctor_id);
$stmt3->execute();
$logs_result = $stmt3->get_result();

$logs = [];
while ($row = $logs_result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt3->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLS · Doctor Reports</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>

<body>
    <h1>Doctor Reports</h1>

    <h3>Appointments by status</h3>
    <?php if (count($status_counts) === 0): ?>
        <p>No appointment data available.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($status_counts as $status => $count): ?>
                <li><?= htmlspecialchars(ucfirst($status)) ?>: <?= (int) $count ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Unique patients seen</h3>
    <p><?= (int) $unique_patients ?> distinct patient(s)</p>

    <h3>Recent activity logs</h3>
    <?php if (count($logs) === 0): ?>
        <p>No logs available.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($logs as $log): ?>
                <li>
                    [<?= htmlspecialchars($log['timestamp']) ?>] Appointment #<?= (int) $log['appointment_id'] ?>:
                    <?= htmlspecialchars($log['content']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="doctor_dashboard.php?doctor_id=<?= (int) $doctor_id ?>">Back to Dashboard</a></p>
</body>

</html>
