<?php
require_once '../../data/connection.php';

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
</head>

<body>
    <h1>Doctor Scheduler</h1>
    <p><strong>Plan allocated time per consultation</strong></p>

    <form method="get" action="doctor_scheduler.php">
        <input type="hidden" name="doctor_id" value="<?= (int) $doctor_id ?>">
        <label>Date: <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>"></label>
        <button type="submit">View schedule</button>
    </form>

    <h3>Schedule for <?= htmlspecialchars($selected_date) ?></h3>
    <?php if (count($slots) === 0): ?>
        <p>No appointments scheduled for this date.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($slots as $i => $s): ?>
                <li>
                    <?= htmlspecialchars(substr($s['time'], 0, 5)) ?> &middot;
                    Patient: <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?> &middot;
                    Room: <?= htmlspecialchars($s['room_number'] ?? 'N/A') ?> &middot;
                    Status: <?= htmlspecialchars($s['status'] ?? 'N/A') ?>
                    <?php if (isset($gaps[$i])): ?>
                        <br><em>Gap before this slot: <?= (int) $gaps[$i] ?> minute(s)</em>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Summary</h3>
    <p>Total appointments on this date: <?= count($slots) ?></p>

    <p><a href="doctor_dashboard.php?doctor_id=<?= (int) $doctor_id ?>">Back to Dashboard</a></p>
</body>

</html>
