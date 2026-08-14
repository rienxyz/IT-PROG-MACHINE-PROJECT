<?php
session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php");
    exit();
}

require_once __DIR__ . '/../../data/connection.php'; 

$account_id = $_SESSION['account_id'];
$appointments = [];
$error_message = "";

$query = "SELECT a.appointment_id, acc_doc.first_name AS doc_first, acc_doc.last_name AS doc_last, 
                 d.specialty, a.insurance, a.room_number, a.date, a.time, a.status 
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN doctors d ON a.doctor_id = d.doctor_id
          JOIN accounts acc_doc ON d.account_id = acc_doc.account_id
          WHERE p.account_id = ? 
          ORDER BY a.date ASC, a.time ASC";

if ($stmt = mysqli_prepare($connection, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $account_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Database error: " . mysqli_error($connection);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="../../styles/style.css">
</head>
<body class="patient-page">
    <nav>
        <a href="patient_dashboard.php">Dashboard</a>
        <a href="patient_profile.php">Profile</a>
        <a href="patient_find_doctor.php">Find Doctor</a>
        <a href="patient_appointment.php">Book Appointment</a>
        <a href="../../sign_out.php">Sign Out</a>
    </nav>

    <main>
        <h1>My Dashboard</h1>
        
        <section>
            <h3>Upcoming Appointments</h3>
            <?php if (!empty($error_message)): ?>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            <?php elseif (count($appointments) > 0): ?>
                <table border="1">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Doctor</th>
                        <th>Specialty</th>
                        <th>Insurance</th>
                        <th>Room</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appt['date']); ?></td>
                            <td><?php echo htmlspecialchars($appt['time']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($appt['doc_first'] . ' ' . $appt['doc_last']); ?></td>
                            <td><?php echo htmlspecialchars($appt['specialty']); ?></td>
                            <td><?php echo htmlspecialchars($appt['insurance']); ?></td>
                            <td><?php echo htmlspecialchars($appt['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($appt['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No appointments found.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
