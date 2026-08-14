<?php
require_once '../../data/connection.php';

session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php");
    exit();
}

$doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 1;

$selected_date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d');

$stmt = $connection->prepare(
    "SELECT ap.appointment_id, ap.time, ap.status, ap.room_number,
        acc.first_name, acc.last_name
    FROM appointments ap
    JOIN patients p ON ap.patient_id = p.patient_id
    JOIN accounts acc ON p.account_id = acc.account_id
    WHERE ap.doctor_id = ? AND ap.date = ?
    ORDER BY ap.time ASC"
);
$stmt->bind_param("is", $doctor_id, $selected_date);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}
$stmt->close();

$gaps = [];
for ($i = 1; $i < count($slots); $i++) {
    $prev = strtotime($slots[$i - 1]['time']);
    $curr = strtotime($slots[$i]['time']);
    $gaps[$i] = round(($curr - $prev) / 60);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLS · Doctor Scheduler</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>

<body>
    <h1>Doctor Scheduler</h1>
    <p>Plan allocated time per consultation</p>

    <h3>Navigate</h3>
    <ul>
        <li><a href="doctor_dashboard.php?doctor_id=<?= (int) $doctor_id ?>">Dashboard</a></li>
        <li><a href="doctor_appointment.php?doctor_id=<?= (int) $doctor_id ?>">My Appointments</a></li>
        <li>Scheduler (current)</li>
        <li><a href="doctor_reports.php?doctor_id=<?= (int) $doctor_id ?>">Reports</a></li>
    </ul>

    <form method="get" action="doctor_scheduler.php">
        <input type="hidden" name="doctor_id" value="<?= (int) $doctor_id ?>">
        <label>Date
            <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>">
        </label>
        <button type="submit">View schedule</button>
    </form>

    <h2>Schedule for <?= htmlspecialchars($selected_date) ?> (<?= count($slots) ?>)</h2>
    <?php if (count($slots) === 0): ?>
        <p>No appointments scheduled for this date.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Room</th>
                    <th>Status</th>
                    <th>Gap before</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slots as $i => $s): ?>
                    <tr>
                        <td><?= htmlspecialchars(substr($s['time'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                        <td><?= htmlspecialchars($s['room_number'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($s['status'] ?? 'N/A') ?></td>
                        <td><?= isset($gaps[$i]) ? (int) $gaps[$i] . ' min' : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>
