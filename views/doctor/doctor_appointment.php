<?php
session_start();
require_once '../../data/connection.php';

// if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'doctor') {
//     die("Access denied. Please log in as a doctor.");
// }
// $account_id = $_SESSION['account_id'];
$account_id = 1;

// get the doctor_id for this account
$stmt = $connection->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctor) {
    die("Doctor profile not found for this account.");
}
$doctor_id = $doctor['doctor_id'];

$allowed_statuses = ['confirmed', 'completed', 'cancelled', 'no show', 'rescheduled', 'declined'];
$update_message = "";

// handle status update form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appointment_id = (int) $_POST['appointment_id'];
    $new_status = $_POST['new_status'];

    if (!in_array($new_status, $allowed_statuses)) {
        $update_message = "Invalid status submitted.";
    } else {
        $stmt = $connection->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->bind_param("sii", $new_status, $appointment_id, $doctor_id);
        $stmt->execute();
        $stmt->close();

        // simple log entry
        $log_content = "Status changed to '$new_status' by doctor (account_id $account_id)";
        $stmt = $connection->prepare("INSERT INTO logs (appointment_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $appointment_id, $log_content);
        $stmt->execute();
        $stmt->close();

        $update_message = "Appointment #$appointment_id updated to '$new_status'.";
    }
}

// which date to show
$filter_date = $_GET['date'] ?? '';
if ($filter_date === '') {
    $filter_date = date('Y-m-d');
}

// get appointments for the selected date
$stmt = $connection->prepare("SELECT a.appointment_id, a.time, a.room_number, a.status, a.insurance,
        acc.first_name, acc.last_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN accounts acc ON p.account_id = acc.account_id
    WHERE a.doctor_id = ? AND a.date = ?
    ORDER BY a.time ASC");
$stmt->bind_param("is", $doctor_id, $filter_date);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// count totals for the little summary line
$total = count($appointments);
$pending_hmo = 0;
foreach ($appointments as $appt) {
    $is_settled = in_array($appt['status'], ['completed', 'cancelled', 'declined']);
    if ($appt['insurance'] && !$is_settled) {
        $pending_hmo++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> MLS · Doctor View </title>
</head>

<body>
    <h1> Doctor View </h1>
    <p><strong> Service providers · track patient volume · allocated time </strong></p>

    <?php if ($update_message): ?>
        <p><em><?php echo $update_message; ?></em></p>
    <?php endif; ?>

    <form method="get">
        <label for="date">View date:</label>
        <input type="date" id="date" name="date" value="<?php echo $filter_date; ?>">
        <button type="submit">Go</button>
    </form>

    <h3> My appointments — <?php echo $filter_date; ?> </h3>

    <?php if ($total === 0): ?>
        <p>No appointments scheduled for this date.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Time</th>
                <th>Patient</th>
                <th>Room</th>
                <th>Insurance</th>
                <th>Status</th>
                <th>Update</th>
            </tr>
            <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?php echo $appt['time']; ?></td>
                    <td><?php echo $appt['first_name'] . ' ' . $appt['last_name']; ?></td>
                    <td><?php echo $appt['room_number'] ?? '—'; ?></td>
                    <td><?php echo $appt['insurance'] ?? 'Self-pay'; ?></td>
                    <td><?php echo $appt['status'] ?? 'pending'; ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?php echo (int) $appt['appointment_id']; ?>">
                            <select name="new_status">
                                <?php foreach ($allowed_statuses as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($appt['status'] === $s) ? 'selected' : ''; ?>>
                                        <?php echo $s; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status" value="1">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h3> Today's volume </h3>
    <p><?php echo $total; ?> consultations · <?php echo $pending_hmo; ?> pending HMO </p>

    <p>
        <a href="doctor_scheduler.php">Manage schedule</a> |
        <a href="doctor_reports.php">View reports</a>
    </p>
</body>

</html>
