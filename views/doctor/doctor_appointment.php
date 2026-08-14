<?php
require_once __DIR__ . '/../../data/connection.php'; 

session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php");
    exit();
}

$doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 1;

$sql = "SELECT ap.appointment_id, ap.date, ap.time, ap.status, ap.room_number,
        acc.first_name, acc.last_name, acc.phone_number, p.insurance
    FROM appointments ap
    JOIN patients p ON ap.patient_id = p.patient_id
    JOIN accounts acc ON p.account_id = acc.account_id
    WHERE ap.doctor_id = ?
    ORDER BY ap.date ASC, ap.time ASC";

$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt->close();

$stmt2 = $connection->prepare(
    "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ? AND date = CURDATE()"
);
$stmt2->bind_param("i", $doctor_id);
$stmt2->execute();
$today_total = $stmt2->get_result()->fetch_assoc()['total'];
$stmt2->close();

$stmt3 = $connection->prepare(
    "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ? AND date = CURDATE() AND status = 'confirmed'"
);
$stmt3->bind_param("i", $doctor_id);
$stmt3->execute();
$pending_today = $stmt3->get_result()->fetch_assoc()['total'];
$stmt3->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLS · Doctor View</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>

<body>
    <h1>My Appointments</h1>
    <p>Track patient volume and allocated time</p>

    <h3>Navigate</h3>
    <ul>
        <li><a href="doctor_dashboard.php?doctor_id=<?= (int) $doctor_id ?>">Dashboard</a></li>
        <li>My Appointments (current)</li>
        <li><a href="doctor_scheduler.php?doctor_id=<?= (int) $doctor_id ?>">Scheduler</a></li>
        <li><a href="doctor_reports.php?doctor_id=<?= (int) $doctor_id ?>">Reports</a></li>
        <li><a href="../../sign_out.php">Sign Out</a></li>
    </ul>

    <h2>Today's volume</h2>
    <table>
        <tbody>
            <tr>
                <th scope="row">Consultations today</th>
                <td><?= (int) $today_total ?></td>
            </tr>
            <tr>
                <th scope="row">Confirmed pending today</th>
                <td><?= (int) $pending_today ?></td>
            </tr>
        </tbody>
    </table>

    <h2>All appointments (<?= count($appointments) ?>)</h2>
    <?php if (count($appointments) === 0): ?>
        <p>No appointments found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Phone</th>
                    <th>Room</th>
                    <th>Insurance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['date']) ?></td>
                        <td><?= htmlspecialchars(substr($a['time'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                        <td><?= htmlspecialchars($a['phone_number'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($a['room_number'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($a['insurance'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($a['status'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>
