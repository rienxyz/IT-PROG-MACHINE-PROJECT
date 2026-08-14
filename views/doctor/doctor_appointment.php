<?php
require_once '../../data/connection.php';

$doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 1;

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

$sql = "SELECT ap.appointment_id, ap.date, ap.time, ap.status, ap.room_number,
               acc.first_name, acc.last_name, acc.phone_number, p.insurance
        FROM appointments ap
        JOIN patients p ON ap.patient_id = p.patient_id
        JOIN accounts acc ON p.account_id = acc.account_id
        WHERE ap.doctor_id = ?";

$types = "i";
$params = [$doctor_id];

if ($status_filter !== '') {
    $sql .= " AND ap.status = ?";
    $types .= "s";
    $params[] = $status_filter;
}

if ($date_filter !== '') {
    $sql .= " AND ap.date = ?";
    $types .= "s";
    $params[] = $date_filter;
}

$sql .= " ORDER BY ap.date ASC, ap.time ASC";

$stmt = $connection->prepare($sql);
$stmt->bind_param($types, ...$params);
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
</head>

<body>
    <h1>Doctor View</h1>
    <p><strong>Service providers &middot; track patient volume &middot; allocated time</strong></p>

    <h3>Filter</h3>
    <form method="get" action="doctor_appointment.php">
        <input type="hidden" name="doctor_id" value="<?= (int) $doctor_id ?>">
        <label>Status:
            <select name="status">
                <option value="">All</option>
                <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                <option value="no show" <?= $status_filter === 'no show' ? 'selected' : '' ?>>No show</option>
                <option value="rescheduled" <?= $status_filter === 'rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                <option value="declined" <?= $status_filter === 'declined' ? 'selected' : '' ?>>Declined</option>
                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </label>
        <label>Date: <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>"></label>
        <button type="submit">Apply</button>
    </form>

    <h3>My appointments</h3>
    <?php if (count($appointments) === 0): ?>
        <p>No appointments found.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($appointments as $a): ?>
                <li>
                    <?= htmlspecialchars($a['date']) ?> &middot; <?= htmlspecialchars(substr($a['time'], 0, 5)) ?>
                    &middot; Patient: <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>
                    &middot; Room: <?= htmlspecialchars($a['room_number'] ?? 'N/A') ?>
                    &middot; Status: <?= htmlspecialchars($a['status'] ?? 'N/A') ?>
                    &middot; Insurance: <?= htmlspecialchars($a['insurance'] ?? 'N/A') ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Today's volume</h3>
    <p><?= (int) $today_total ?> consultation(s) &middot; <?= (int) $pending_today ?> confirmed pending</p>

    <p><a href="doctor_dashboard.php?doctor_id=<?= (int) $doctor_id ?>">Back to Dashboard</a></p>
</body>

</html>
