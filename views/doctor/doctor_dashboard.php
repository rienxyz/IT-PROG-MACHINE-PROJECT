<?php
require_once __DIR__ . './../../data/connection.php'; 

session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php");
    exit();
}

$doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 1;

$stmt = $connection->prepare(
    "SELECT d.doctor_id, d.specialty, a.first_name, a.last_name, a.e_mail, a.phone_number
    FROM doctors d
    JOIN accounts a ON d.account_id = a.account_id
    WHERE d.doctor_id = ?"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor_result = $stmt->get_result();
$doctor = $doctor_result->fetch_assoc();
$stmt->close();

if (!$doctor) {
    die("Doctor not found.");
}

$stmt = $connection->prepare(
    "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ? AND date = CURDATE()"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$today_total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $connection->prepare(
    "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ? AND status = 'confirmed'"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$pending_total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $connection->prepare(
    "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ?"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$all_time_total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLS · Doctor Dashboard</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>

<body>
    <h1>Doctor Dashboard</h1>
    <p>Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> &middot; <?= htmlspecialchars($doctor['specialty'] ?? 'General') ?></p>

    <h3>Navigate</h3>
    <ul>
        <li>Dashboard (current)</li>
        <li><a href="doctor_appointment.php?doctor_id=<?= (int) $doctor_id ?>">My Appointments</a></li>
        <li><a href="doctor_scheduler.php?doctor_id=<?= (int) $doctor_id ?>">Scheduler</a></li>
        <li><a href="doctor_reports.php?doctor_id=<?= (int) $doctor_id ?>">Reports</a></li>
    </ul>

    <h2>Profile</h2>
    <table>
        <tbody>
            <tr>
                <th scope="row">Name</th>
                <td>Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?></td>
            </tr>
            <tr>
                <th scope="row">Specialty</th>
                <td><?= htmlspecialchars($doctor['specialty'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th scope="row">Email</th>
                <td><?= htmlspecialchars($doctor['e_mail']) ?></td>
            </tr>
            <tr>
                <th scope="row">Phone</th>
                <td><?= htmlspecialchars($doctor['phone_number'] ?? 'N/A') ?></td>
            </tr>
        </tbody>
    </table>

    <h2>Patient volume</h2>
    <table>
        <tbody>
            <tr>
                <th scope="row">Appointments today</th>
                <td><?= (int) $today_total ?></td>
            </tr>
            <tr>
                <th scope="row">Confirmed &amp; pending</th>
                <td><?= (int) $pending_total ?></td>
            </tr>
            <tr>
                <th scope="row">All-time total</th>
                <td><?= (int) $all_time_total ?></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
