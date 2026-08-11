<?php
session_start();
require_once '../../data/connection.php';

// if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'doctor') {
//     die("Access denied. Please log in as a doctor.");
// }
// $account_id = $_SESSION['account_id'];
$account_id = 1;

$stmt = $connection->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctor) {
    die("Doctor profile not found for this account.");
}
$doctor_id = $doctor['doctor_id'];

$message = "";

// handle the reschedule form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule'])) {
    $appointment_id = (int) $_POST['appointment_id'];
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    $new_room = trim($_POST['new_room']);

    // check if the doctor already has something booked at that time
    $stmt = $connection->prepare("SELECT appointment_id FROM appointments
         WHERE doctor_id = ? AND date = ? AND time = ? AND appointment_id != ?
           AND status NOT IN ('cancelled', 'declined')");
    $stmt->bind_param("issi", $doctor_id, $new_date, $new_time, $appointment_id);
    $stmt->execute();
    $conflict = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($conflict) {
        $message = "That time slot on $new_date is already booked. Please choose a different time.";
    } else {
        $stmt = $connection->prepare("UPDATE appointments
            SET date = ?, time = ?, room_number = ?, status = 'rescheduled'
            WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->bind_param("sssii", $new_date, $new_time, $new_room, $appointment_id, $doctor_id);
        $stmt->execute();
        $stmt->close();

        // simple log entry
        $log_content = "Rescheduled to $new_date $new_time, room $new_room by doctor (account_id $account_id)";
        $stmt = $connection->prepare("INSERT INTO logs (appointment_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $appointment_id, $log_content);
        $stmt->execute();
        $stmt->close();

        $message = "Appointment #$appointment_id rescheduled to $new_date at $new_time.";
    }
}

// upcoming appointments that can still be rescheduled
$stmt = $connection->prepare("SELECT a.appointment_id, a.date, a.time, a.room_number, a.status,
            acc.first_name, acc.last_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN accounts acc ON p.account_id = acc.account_id
    WHERE a.doctor_id = ? AND a.date >= CURDATE()
        AND a.status NOT IN ('cancelled', 'declined')
    ORDER BY a.date ASC, a.time ASC");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> MLS · Doctor Scheduler </title>
</head>

<body>
    <h1> Doctor View </h1>
    <p><strong> Scheduler · reschedule appointments and allocate time/rooms </strong></p>

    <?php if ($message): ?>
        <p><em><?php echo $message; ?></em></p>
    <?php endif; ?>

    <h3> Upcoming appointments </h3>

    <?php if (count($appointments) === 0): ?>
        <p>No upcoming appointments.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Patient</th>
                <th>Room</th>
                <th>Status</th>
                <th>Reschedule</th>
            </tr>
            <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?php echo $appt['date']; ?></td>
                    <td><?php echo $appt['time']; ?></td>
                    <td><?php echo $appt['first_name'] . ' ' . $appt['last_name']; ?></td>
                    <td><?php echo $appt['room_number'] ?? '—'; ?></td>
                    <td><?php echo $appt['status'] ?? 'pending'; ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="appointment_id" value="<?php echo (int) $appt['appointment_id']; ?>">
                            <input type="date" name="new_date" value="<?php echo $appt['date']; ?>" required>
                            <input type="time" name="new_time" value="<?php echo $appt['time']; ?>" required>
                            <input type="text" name="new_room" placeholder="Room #" value="<?php echo $appt['room_number'] ?? ''; ?>">
                            <button type="submit" name="reschedule" value="1">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p>
        <a href="doctor_appointment.php">Back to appointments</a> |
        <a href="doctor_reports.php">View reports</a>
    </p>
</body>

</html>
