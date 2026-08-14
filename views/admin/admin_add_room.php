<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../data/connection.php";

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| ASSIGN ROOM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $roomNumber = trim($_POST['room_number'] ?? '');

    if ($appointmentId <= 0) {

        $error = "Please select an appointment.";

    } elseif ($roomNumber === '') {

        $error = "Please enter a room number.";

    } else {

        /*
        |--------------------------------------------------------------
        | VERIFY APPOINTMENT EXISTS
        |--------------------------------------------------------------
        */

        $check = mysqli_prepare(
            $connection,
            "SELECT appointment_id
             FROM appointments
             WHERE appointment_id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($check, "i", $appointmentId);
        mysqli_stmt_execute($check);

        $result = mysqli_stmt_get_result($check);

        if (!mysqli_fetch_assoc($result)) {

            $error = "Appointment not found.";

        } else {

            /*
            |----------------------------------------------------------
            | UPDATE ROOM
            |----------------------------------------------------------
            */

            $update = mysqli_prepare(
                $connection,
                "UPDATE appointments
                 SET room_number = ?
                 WHERE appointment_id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "si",
                $roomNumber,
                $appointmentId
            );

            if (mysqli_stmt_execute($update)) {

                header("Location: admin_read_room.php?msg=room_assigned");
                exit();

            } else {

                $error = "Failed to assign room.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD APPOINTMENTS
|--------------------------------------------------------------------------
*/

$query = "
SELECT

    ap.appointment_id,
    ap.date,
    ap.time,

    patient.first_name AS patient_first,
    patient.last_name  AS patient_last,

    doctor.first_name AS doctor_first,
    doctor.last_name  AS doctor_last,

    ap.room_number

FROM appointments ap

INNER JOIN patients p
ON ap.patient_id = p.patient_id

INNER JOIN accounts patient
ON p.account_id = patient.account_id

INNER JOIN doctors d
ON ap.doctor_id = d.doctor_id

INNER JOIN accounts doctor
ON d.account_id = doctor.account_id

ORDER BY
ap.date ASC,
ap.time ASC
";

$appointments = mysqli_query($connection, $query);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Assign Room</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>

<body>

<h1>Assign Room</h1>

<p>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</p>

<?php if ($message != ''): ?>

<p>
    <strong><?= htmlspecialchars($message) ?></strong>
</p>

<?php endif; ?>

<?php if ($error != ''): ?>

<p>
    <strong>Error:</strong>
    <?= htmlspecialchars($error) ?>
</p>

<?php endif; ?>

<form method="POST">

<p>

<label>

Appointment

<select name="appointment_id" required>

<option value="">Select Appointment</option>

<?php while ($row = mysqli_fetch_assoc($appointments)): ?>

<option value="<?= (int)$row['appointment_id'] ?>">

#<?= (int)$row['appointment_id'] ?>

-

<?= htmlspecialchars($row['patient_first'] . " " . $row['patient_last']) ?>

→

Dr.
<?= htmlspecialchars($row['doctor_first'] . " " . $row['doctor_last']) ?>

|

<?= htmlspecialchars($row['date']) ?>

<?= htmlspecialchars(substr($row['time'],0,5)) ?>

<?php
if (!empty($row['room_number'])) {
    echo " | Current Room: " .
    htmlspecialchars($row['room_number']);
}
?>

</option>

<?php endwhile; ?>

</select>

</label>

</p>

<p>

<label>

Room Number

<input
type="text"
name="room_number"
placeholder="Example: Room 101"
required>

</label>

</p>

<p>

<button type="submit">

Assign Room

</button>

</p>

</form>

</body>
</html>
