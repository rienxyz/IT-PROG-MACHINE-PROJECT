<?php
 
session_start();
require_once __DIR__ . '/patient_auth.php';
require_once __DIR__ . '/../../data/connection.php';
 
/*
|--------------------------------------------------------------------------
| PATIENT DASHBOARD
|--------------------------------------------------------------------------
| Functionality only.
| No CSS/UI framework is being used at this stage.
|--------------------------------------------------------------------------
*/
 
// Check database connection
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}
 
 
/*
|--------------------------------------------------------------------------
| CURRENT PATIENT
|--------------------------------------------------------------------------
*/
 
$accountId = (int) $_SESSION['account_id'];
$patientId = (int) $_SESSION['patient_id'];
 
 
/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/
 
function getCount($connection, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($connection, $sql);
 
    if (!$stmt) {
        return 0;
    }
 
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
 
    if (!mysqli_stmt_execute($stmt)) {
        return 0;
    }
 
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
 
    return (int) ($row['total'] ?? 0);
}
 
 
/*
|--------------------------------------------------------------------------
| PATIENT PROFILE SUMMARY
|--------------------------------------------------------------------------
*/
 
$profileSql = "
    SELECT
        accounts.first_name,
        accounts.last_name,
        accounts.e_mail,
        accounts.phone_number,
        accounts.verification_status,
        patients.insurance,
        patients.insurance_status,
        patients.preferred_specialty
    FROM patients
    INNER JOIN accounts
        ON accounts.account_id = patients.account_id
    WHERE patients.patient_id = ?
    LIMIT 1
";
 
$stmt = mysqli_prepare($connection, $profileSql);
 
if (!$stmt) {
    die("Failed to prepare profile query.");
}
 
mysqli_stmt_bind_param($stmt, 'i', $patientId);
 
if (!mysqli_stmt_execute($stmt)) {
    die("Failed to retrieve patient profile.");
}
 
$profileResult = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($profileResult);
 
 
/*
|--------------------------------------------------------------------------
| APPOINTMENT STATUS COUNTS
|--------------------------------------------------------------------------
*/
 
$appointmentStatuses = [
    'confirmed',
    'completed',
    'cancelled',
    'rescheduled',
    'declined',
    'no show',
];
 
$statusCounts = [];
 
foreach ($appointmentStatuses as $status) {
 
    $statusCounts[$status] = getCount(
        $connection,
        "SELECT COUNT(*) AS total
         FROM appointments
         WHERE patient_id = ?
         AND status = ?",
        'is',
        [$patientId, $status]
    );
}
 
$totalAppointments = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM appointments
     WHERE patient_id = ?",
    'i',
    [$patientId]
);
 
 
/*
|--------------------------------------------------------------------------
| NEXT UPCOMING APPOINTMENT
|--------------------------------------------------------------------------
*/
 
$nextAppointmentSql = "
    SELECT
        appointments.appointment_id,
        appointments.date,
        appointments.time,
        appointments.room_number,
        appointments.status,
        doctors.specialty,
        accounts.first_name AS doctor_first_name,
        accounts.last_name AS doctor_last_name
    FROM appointments
    INNER JOIN doctors
        ON doctors.doctor_id = appointments.doctor_id
    INNER JOIN accounts
        ON accounts.account_id = doctors.account_id
    WHERE appointments.patient_id = ?
    AND appointments.status = 'confirmed'
    AND appointments.date >= CURDATE()
    ORDER BY appointments.date ASC, appointments.time ASC
    LIMIT 1
";
 
$stmt = mysqli_prepare($connection, $nextAppointmentSql);
 
if (!$stmt) {
    die("Failed to prepare next appointment query.");
}
 
mysqli_stmt_bind_param($stmt, 'i', $patientId);
 
if (!mysqli_stmt_execute($stmt)) {
    die("Failed to retrieve next appointment.");
}
 
$nextAppointmentResult = mysqli_stmt_get_result($stmt);
$nextAppointment = mysqli_fetch_assoc($nextAppointmentResult);
 
 
/*
|--------------------------------------------------------------------------
| RECENT APPOINTMENTS
|--------------------------------------------------------------------------
*/
 
$recentSql = "
    SELECT
        appointments.appointment_id,
        appointments.date,
        appointments.time,
        appointments.status,
        doctors.specialty,
        accounts.first_name AS doctor_first_name,
        accounts.last_name AS doctor_last_name
    FROM appointments
    INNER JOIN doctors
        ON doctors.doctor_id = appointments.doctor_id
    INNER JOIN accounts
        ON accounts.account_id = doctors.account_id
    WHERE appointments.patient_id = ?
    ORDER BY appointments.date DESC, appointments.time DESC
    LIMIT 5
";
 
$stmt = mysqli_prepare($connection, $recentSql);
 
if (!$stmt) {
    die("Failed to prepare recent appointments query.");
}
 
mysqli_stmt_bind_param($stmt, 'i', $patientId);
 
if (!mysqli_stmt_execute($stmt)) {
    die("Failed to retrieve recent appointments.");
}
 
$recentResult = mysqli_stmt_get_result($stmt);
 
 
/*
|--------------------------------------------------------------------------
| MESSAGE HANDLING
|--------------------------------------------------------------------------
*/
 
$message = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
 
?>
<!DOCTYPE html>
<html lang="en">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
 
    <title> MLS · Patient Dashboard </title>
</head>
 
<body>
 
    <h1>
        Patient Dashboard
    </h1>
 
    <p>
        Welcome,
        <?= htmlspecialchars(
            trim(
                ($profile['first_name'] ?? '') .
                ' ' .
                ($profile['last_name'] ?? '')
            )
        ) ?>
    </p>
 
    <p>
        <a href="patient_find_doctor.php">
            Find a Doctor
        </a>
        |
        <a href="patient_appointment.php">
            Book / Manage Appointments
        </a>
        |
        <a href="patient_profile.php">
            My Profile
        </a>
        |
        <a href="../../sign_out.php">
            Sign Out
        </a>
    </p>
 
 
    <?php if ($message === 'appointment_booked'): ?>
 
        <p>
            Appointment successfully requested.
        </p>
 
    <?php elseif ($message === 'appointment_cancelled'): ?>
 
        <p>
            Appointment successfully cancelled.
        </p>
 
    <?php elseif ($message === 'appointment_rescheduled'): ?>
 
        <p>
            Appointment successfully rescheduled.
        </p>
 
    <?php elseif ($message === 'profile_updated'): ?>
 
        <p>
            Profile successfully updated.
        </p>
 
    <?php endif; ?>
 
 
    <?php if ($error === 'not_found'): ?>
 
        <p>
            The requested appointment was not found.
        </p>
 
    <?php elseif ($error === 'cannot_modify'): ?>
 
        <p>
            That appointment can no longer be modified.
        </p>
 
    <?php endif; ?>
 
 
    <hr>
 
 
    <h2>
        My Profile
    </h2>
 
    <?php if ($profile): ?>
 
        <table border="1" cellpadding="8">
 
            <tr>
                <th> Email </th>
                <td> <?= htmlspecialchars($profile['e_mail'] ?? '') ?> </td>
            </tr>
 
            <tr>
                <th> Phone </th>
                <td> <?= htmlspecialchars($profile['phone_number'] ?? '—') ?> </td>
            </tr>
 
            <tr>
                <th> Insurance / HMO </th>
                <td> <?= htmlspecialchars($profile['insurance'] ?? '—') ?> </td>
            </tr>
 
            <tr>
                <th> Insurance Status </th>
                <td>
                    <?= htmlspecialchars(
                        ucfirst($profile['insurance_status'] ?? 'not verified')
                    ) ?>
                </td>
            </tr>
 
            <tr>
                <th> Preferred Specialty </th>
                <td> <?= htmlspecialchars($profile['preferred_specialty'] ?? '—') ?> </td>
            </tr>
 
            <tr>
                <th> Account Verification </th>
                <td>
                    <?= htmlspecialchars(
                        ucfirst($profile['verification_status'] ?? '')
                    ) ?>
                </td>
            </tr>
 
        </table>
 
    <?php else: ?>
 
        <p>
            Profile information is unavailable.
        </p>
 
    <?php endif; ?>
 
 
    <hr>
 
 
    <h2>
        Next Appointment
    </h2>
 
    <?php if ($nextAppointment): ?>
 
        <table border="1" cellpadding="8">
 
            <tr>
                <th> Doctor </th>
                <td>
                    Dr.
                    <?= htmlspecialchars(
                        trim(
                            ($nextAppointment['doctor_first_name'] ?? '') .
                            ' ' .
                            ($nextAppointment['doctor_last_name'] ?? '')
                        )
                    ) ?>
                </td>
            </tr>
 
            <tr>
                <th> Specialty </th>
                <td> <?= htmlspecialchars($nextAppointment['specialty'] ?? '—') ?> </td>
            </tr>
 
            <tr>
                <th> Date </th>
                <td> <?= htmlspecialchars($nextAppointment['date'] ?? '—') ?> </td>
            </tr>
 
            <tr>
                <th> Time </th>
                <td> <?= htmlspecialchars($nextAppointment['time'] ?? '—') ?> </td>
            </tr>
 
            <tr>
                <th> Room </th>
                <td> <?= htmlspecialchars($nextAppointment['room_number'] ?? 'TBA') ?> </td>
            </tr>
 
            <tr>
                <th> Status </th>
                <td> <?= htmlspecialchars(ucfirst($nextAppointment['status'] ?? '')) ?> </td>
            </tr>
 
        </table>
 
        <p>
            <a href="patient_appointment.php?appointment_id=<?= (int) $nextAppointment['appointment_id'] ?>">
                View / Manage this appointment
            </a>
        </p>
 
    <?php else: ?>
 
        <p>
            You have no upcoming confirmed appointments.
        </p>
 
        <p>
            <a href="patient_find_doctor.php">
                Find a doctor to book one
            </a>
        </p>
 
    <?php endif; ?>
 
 
    <hr>
 
 
    <h2>
        Appointment Summary
    </h2>
 
    <table border="1" cellpadding="8">
 
        <thead>
            <tr>
                <th> Total </th>
                <th> Confirmed </th>
                <th> Completed </th>
                <th> Rescheduled </th>
                <th> Cancelled </th>
                <th> Declined </th>
                <th> No Show </th>
            </tr>
        </thead>
 
        <tbody>
            <tr>
                <td> <?= $totalAppointments ?> </td>
                <td> <?= $statusCounts['confirmed'] ?> </td>
                <td> <?= $statusCounts['completed'] ?> </td>
                <td> <?= $statusCounts['rescheduled'] ?> </td>
                <td> <?= $statusCounts['cancelled'] ?> </td>
                <td> <?= $statusCounts['declined'] ?> </td>
                <td> <?= $statusCounts['no show'] ?> </td>
            </tr>
        </tbody>
 
    </table>
 
 
    <hr>
 
 
    <h2>
        Recent Appointments
    </h2>
 
    <table border="1" cellpadding="8">
 
        <thead>
            <tr>
                <th> Doctor </th>
                <th> Specialty </th>
                <th> Date </th>
                <th> Time </th>
                <th> Status </th>
                <th> Actions </th>
            </tr>
        </thead>
 
        <tbody>
 
        <?php if ($recentResult && mysqli_num_rows($recentResult) > 0): ?>
 
            <?php while ($appointment = mysqli_fetch_assoc($recentResult)): ?>
 
                <tr>
 
                    <td>
                        Dr.
                        <?= htmlspecialchars(
                            trim(
                                ($appointment['doctor_first_name'] ?? '') .
                                ' ' .
                                ($appointment['doctor_last_name'] ?? '')
                            )
                        ) ?>
                    </td>
 
                    <td>
                        <?= htmlspecialchars($appointment['specialty'] ?? '—') ?>
                    </td>
 
                    <td>
                        <?= htmlspecialchars($appointment['date'] ?? '—') ?>
                    </td>
 
                    <td>
                        <?= htmlspecialchars($appointment['time'] ?? '—') ?>
                    </td>
 
                    <td>
                        <?= htmlspecialchars(ucfirst($appointment['status'] ?? '')) ?>
                    </td>
 
                    <td>
                        <a href="patient_appointment.php?appointment_id=<?= (int) $appointment['appointment_id'] ?>">
                            View
                        </a>
                    </td>
 
                </tr>
 
            <?php endwhile; ?>
 
        <?php else: ?>
 
            <tr>
                <td colspan="6">
                    No appointment history yet.
                </td>
            </tr>
 
        <?php endif; ?>
 
        </tbody>
 
    </table>
 
    <p><em>
        Basic consultations only (no lab / surgery).
    </em></p>
 
</body>
 
</html>
