<?php
session_start();
require_once '../../data/connection.php';

// if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'doctor') {
//     die("Access denied. Please log in as a doctor.");
// }
// $account_id = $_SESSION['account_id'];
$account_id = 1;

$stmt = $connection->prepare("SELECT doctor_id, specialty FROM doctors WHERE doctor_id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctor) {
    die("Doctor profile not found for this account.");
}
$doctor_id = $doctor['doctor_id'];

// default to the last 7 days if no dates were picked
$start_date = $_GET['start_date'] ?? '';
if ($start_date === '') {
    $start_date = date('Y-m-d', strtotime('-6 days'));
}
$end_date = $_GET['end_date'] ?? '';
if ($end_date === '') {
    $end_date = date('Y-m-d');
}

// totals for the whole date range
$stmt = $connection->prepare("SELECT COUNT(*) AS total_appointments,
        SUM(status = 'completed') AS completed,
        SUM(status = 'cancelled') AS cancelled,
        SUM(status = 'no show') AS no_show,
        SUM(status = 'rescheduled') AS rescheduled,
        SUM(status = 'declined') AS declined,
        SUM(status = 'confirmed' OR status IS NULL) AS upcoming_or_pending
    FROM appointments
    WHERE doctor_id = ? AND date BETWEEN ? AND ?");
$stmt->bind_param("iss", $doctor_id, $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// per-day breakdown
$stmt = $connection->prepare("SELECT date, COUNT(*) AS total,
        SUM(status = 'completed') AS completed,
        SUM(insurance IS NOT NULL) AS hmo_count
    FROM appointments
    WHERE doctor_id = ? AND date BETWEEN ? AND ?
    GROUP BY date
    ORDER BY date ASC");
$stmt->bind_param("iss", $doctor_id, $start_date, $end_date);
$stmt->execute();
$daily_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// breakdown by insurance type
$stmt = $connection->prepare("SELECT COALESCE(insurance, 'Self-pay') AS insurance_type, COUNT(*) AS total
    FROM appointments
    WHERE doctor_id = ? AND date BETWEEN ? AND ?
    GROUP BY insurance_type
    ORDER BY total DESC");
$stmt->bind_param("iss", $doctor_id, $start_date, $end_date);
$stmt->execute();
$insurance_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> MLS · Doctor Reports </title>
</head>

<body>
    <h1> Doctor View </h1>
    <p><strong> Reports · patient volume over time </strong></p>
    <p>Specialty: <?php echo $doctor['specialty'] ?? 'Not set'; ?></p>

    <form method="get">
        <label for="start_date">From:</label>
        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
        <label for="end_date">To:</label>
        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
        <button type="submit">Run report</button>
    </form>

    <h3> Summary (<?php echo $start_date; ?> to <?php echo $end_date; ?>) </h3>
    <ul>
        <li>Total appointments: <?php echo (int) $summary['total_appointments']; ?></li>
        <li>Completed: <?php echo (int) $summary['completed']; ?></li>
        <li>Upcoming / pending: <?php echo (int) $summary['upcoming_or_pending']; ?></li>
        <li>Cancelled: <?php echo (int) $summary['cancelled']; ?></li>
        <li>No shows: <?php echo (int) $summary['no_show']; ?></li>
        <li>Rescheduled: <?php echo (int) $summary['rescheduled']; ?></li>
        <li>Declined: <?php echo (int) $summary['declined']; ?></li>
    </ul>

    <h3> Daily volume </h3>
    <?php if (count($daily_rows) === 0): ?>
        <p>No appointments in this date range.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Date</th>
                <th>Total consultations</th>
                <th>Completed</th>
                <th>With insurance/HMO</th>
            </tr>
            <?php foreach ($daily_rows as $row): ?>
                <tr>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo (int) $row['total']; ?></td>
                    <td><?php echo (int) $row['completed']; ?></td>
                    <td><?php echo (int) $row['hmo_count']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h3> Volume by insurance type </h3>
    <?php if (count($insurance_rows) === 0): ?>
        <p>No data for this date range.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Insurance</th>
                <th>Appointments</th>
            </tr>
            <?php foreach ($insurance_rows as $row): ?>
                <tr>
                    <td><?php echo $row['insurance_type']; ?></td>
                    <td><?php echo (int) $row['total']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p>
        <a href="doctor_appointment.php">Back to appointments</a> |
        <a href="doctor_scheduler.php">Manage schedule</a>
    </p>
</body>

</html>
