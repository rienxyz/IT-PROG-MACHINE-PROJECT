<?php
require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

require_once __DIR__ . '/secretary_auth.php';

$todayQuery = "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE date = CURDATE()
";

$todayResult = mysqli_query($connection, $todayQuery);
$todayAppointments = mysqli_fetch_assoc($todayResult)['total'] ?? 0;

$upcomingQuery = "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE date >= CURDATE()
    AND status NOT IN ('completed', 'cancelled')
";

$upcomingResult = mysqli_query($connection, $upcomingQuery);
$upcomingAppointments = mysqli_fetch_assoc($upcomingResult)['total'] ?? 0;

$pendingQuery = "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'pending'
";

$pendingResult = mysqli_query($connection, $pendingQuery);
$pendingAppointments = mysqli_fetch_assoc($pendingResult)['total'] ?? 0;

$patientQuery = "
    SELECT COUNT(*) AS total
    FROM patients
";

$patientResult = mysqli_query($connection, $patientQuery);
$totalPatients = mysqli_fetch_assoc($patientResult)['total'] ?? 0;

$doctorQuery = "
    SELECT COUNT(*) AS total
    FROM doctors
";

$doctorResult = mysqli_query($connection, $doctorQuery);
$totalDoctors = mysqli_fetch_assoc($doctorResult)['total'] ?? 0;

$todayAppointmentsList = [];

$todayListQuery = "
    SELECT
        ap.appointment_id,
        ap.insurance,
        ap.room_number,
        ap.time,
        ap.status,
        p.patient_id,
        pa.first_name AS patient_first_name,
        pa.last_name AS patient_last_name,
        d.doctor_id,
        da.first_name AS doctor_first_name,
        da.last_name AS doctor_last_name
    FROM appointments ap
    INNER JOIN patients p ON p.patient_id = ap.patient_id
    INNER JOIN accounts pa ON pa.account_id = p.account_id
    INNER JOIN doctors d ON d.doctor_id = ap.doctor_id
    INNER JOIN accounts da ON da.account_id = d.account_id
    WHERE ap.date = CURDATE()
    ORDER BY ap.time ASC
";

$todayListResult = mysqli_query($connection, $todayListQuery);

if ($todayListResult) {
    while ($row = mysqli_fetch_assoc($todayListResult)) {
        $todayAppointmentsList[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>
<body>

<h1>Secretary Dashboard</h1>

<p>
    Welcome, <?= htmlspecialchars($_SESSION['first_name'] ?? '') ?>
    <?php if ($_SESSION['department']): ?>
        (Department: <?= htmlspecialchars($_SESSION['department']) ?>)
    <?php endif; ?>
</p>

<hr>

<h2>Overview</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Today's Appointments</th>
        <td><?= $todayAppointments ?></td>
    </tr>
    <tr>
        <th>Upcoming Appointments</th>
        <td><?= $upcomingAppointments ?></td>
    </tr>
    <tr>
        <th>Pending Appointments</th>
        <td><?= $pendingAppointments ?></td>
    </tr>
    <tr>
        <th>Total Patients</th>
        <td><?= $totalPatients ?></td>
    </tr>
    <tr>
        <th>Total Doctors</th>
        <td><?= $totalDoctors ?></td>
    </tr>
</table>

<hr>

<h2>Today's Appointments</h2>

<?php if (count($todayAppointmentsList) > 0): ?>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Time</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Room</th>
                <th>Insurance</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($todayAppointmentsList as $appointment): ?>
                <tr>
                    <td><?= htmlspecialchars(substr($appointment['time'], 0, 5)) ?></td>
                    <td>
                        <?= htmlspecialchars(
                            $appointment['patient_first_name'] . ' ' .
                            $appointment['patient_last_name']
                        ) ?>
                    </td>
                    <td>
                        Dr. <?= htmlspecialchars(
                            $appointment['doctor_first_name'] . ' ' .
                            $appointment['doctor_last_name']
                        ) ?>
                    </td>
                    <td><?= htmlspecialchars($appointment['room_number'] ?? 'Not assigned') ?></td>
                    <td><?= htmlspecialchars($appointment['insurance']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($appointment['status'])) ?></td>
                    <td>
                        <a href="secretary_edit_appointment.php?appointment_id=<?= $appointment['appointment_id'] ?>">
                            Edit
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No appointments scheduled for today.</p>
<?php endif; ?>

<hr>

<h2>Secretary Functions</h2>

<ul>
    <li><a href="secretary_read_appointment.php">View All Appointments</a></li>
    <li><a href="secretary_add_appointment.php">Schedule New Appointment</a></li>
    <li><a href="secretary_read_patient.php">View Patients</a></li>
    <li><a href="secretary_read_doctor.php">View Doctors</a></li>
    <li><a href="secretary_profile.php">My Profile</a></li>
    <li><a href="../../sign_out.php">Sign Out</a></li>
</ul>

</body>
</html>