<?php
require_once '../../data/connection.php';

// Single doctor context - doctor_id passed via GET (no sessions used)
$doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 1;

// Fetch doctor info
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

// Today's appointment count
$stmt = $connection->prepare(
    "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ? AND date = CURDATE()"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$today_total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Pending (confirmed) appointments count
$stmt = $connection->prepare(
    "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ? AND status = 'confirmed'"
);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$pending_total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total patient volume (all appointments ever assigned to this doctor)
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
</head>

<body>
    <h1>Doctor Dashboard</h1>

    <h3>Profile</h3>
    <p>
        Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?><br>
        Specialty: <?= htmlspecialchars($doctor['specialty'] ?? 'N/A') ?><br>
        Email: <?= htmlspecialchars($doctor['e_mail']) ?><br>
        Phone: <?= htmlspecialchars($doctor['phone_number'] ?? 'N/A') ?>
    </p>

    <h3>Today's volume</h3>
    <p><?= (int) $today_total ?> appointment(s) today &middot; <?= (int) $pending_total ?> confirmed pending</p>

    <h3>All-time patient volume</h3>
    <p><?= (int) $all_time_total ?> total appointments handled</p>

    <h3>Navigate</h3>
    <ul>
        <li><a href="doctor_appointment.php?doctor_id=<?= (int) $doctor_id ?>">My Appointments</a></li>
        <li><a href="doctor_scheduler.php?doctor_id=<?= (int) $doctor_id ?>">Scheduler</a></li>
        <li><a href="doctor_reports.php?doctor_id=<?= (int) $doctor_id ?>">Reports</a></li>
    </ul>
</body>

</html>
