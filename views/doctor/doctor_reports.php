<?php
require_once __DIR__ . '/../../data/connection.php'; 

session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php");
    exit();
}

$doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 1;

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

$stmt2 = $connection->prepare(
    "SELECT COUNT(DISTINCT patient_id) AS total FROM appointments WHERE doctor_id = ?"
);
$stmt2->bind_param("i", $doctor_id);
$stmt2->execute();
$unique_patients = $stmt2->get_result()->fetch_assoc()['total'];
$stmt2->close();

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
    <p>Volume and activity summary</p>

    <h3>Navigate</h3>
    <ul>
        <li><a href="doctor_dashboard.php?doctor_id=<?= (int) $doctor_id ?>">Dashboard</a></li>
        <li><a href="doctor_appointment.php?doctor_id=<?= (int) $doctor_id ?>">My Appointments</a></li>
        <li><a href="doctor_scheduler.php?doctor_id=<?= (int) $doctor_id ?>">Scheduler</a></li>
        <li>Reports (current)</li>
        <li><a href="../../sign_out.php">Sign Out</a></li>
    </ul>

    <h2>Summary</h2>
    <table>
        <tbody>
            <tr>
                <th scope="row">Distinct patients seen</th>
                <td><?= (int) $unique_patients ?></td>
            </tr>
            <tr>
                <th scope="row">Total appointments</th>
                <td><?= array_sum($status_counts) ?></td>
            </tr>
        </tbody>
    </table>

    <h2>Appointments by status</h2>
    <?php if (count($status_counts) === 0): ?>
        <p>No appointment data available.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($status_counts as $status => $count): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucfirst($status)) ?></td>
                        <td><?= (int) $count ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Recent activity logs</h2>
    <?php if (count($logs) === 0): ?>
        <p>No logs available.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Appointment</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                        <td>#<?= (int) $log['appointment_id'] ?></td>
                        <td><?= htmlspecialchars($log['content']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>
