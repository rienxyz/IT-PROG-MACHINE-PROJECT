<?php
require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

require_once __DIR__ . '/secretary_auth.php';

$appointmentId = (int) ($_GET['appointment_id'] ?? 0);

if ($appointmentId <= 0) {
    header("Location: secretary_read_appointment.php?error=invalid_id");
    exit();
}

$allowedStatuses = ['pending', 'confirmed', 'rescheduled', 'completed', 'cancelled'];

$appointment = null;
$stmt = mysqli_prepare(
    $connection,
    "
    SELECT
        appointment_id,
        patient_id,
        doctor_id,
        insurance,
        room_number,
        date,
        time,
        status
    FROM appointments
    WHERE appointment_id = ?
    "
);

mysqli_stmt_bind_param($stmt, "i", $appointmentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    header("Location: secretary_read_appointment.php?error=not_found");
    exit();
}

$patients = [];
$patientQuery = "
    SELECT p.patient_id, a.first_name, a.last_name, a.e_mail
    FROM patients p
    INNER JOIN accounts a ON a.account_id = p.account_id
    WHERE a.activity_status = 'active'
    ORDER BY a.last_name ASC
";
$patientResult = mysqli_query($connection, $patientQuery);
while ($patient = mysqli_fetch_assoc($patientResult)) {
    $patients[] = $patient;
}

$doctors = [];
$doctorQuery = "
    SELECT d.doctor_id, d.specialty, a.first_name, a.last_name, a.e_mail
    FROM doctors d
    INNER JOIN accounts a ON a.account_id = d.account_id
    WHERE a.activity_status = 'active'
    ORDER BY a.last_name ASC
";
$doctorResult = mysqli_query($connection, $doctorQuery);
while ($doctor = mysqli_fetch_assoc($doctorResult)) {
    $doctors[] = $doctor;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $insurance = trim($_POST['insurance'] ?? '');
    $roomNumber = trim($_POST['room_number'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if ($patientId <= 0) {
        $error = "Please select a patient.";
    } elseif ($doctorId <= 0) {
        $error = "Please select a doctor.";
    } elseif ($insurance === '') {
        $error = "Insurance information is required.";
    } elseif ($roomNumber === '') {
        $error = "Room number is required.";
    } elseif ($date === '') {
        $error = "Date is required.";
    } elseif ($time === '') {
        $error = "Time is required.";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Invalid status.";
    }

    if ($error === '') {
        $dateObject = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
            $error = "Invalid date format.";
        }
    }

    if ($error === '') {
        $timeObject = DateTime::createFromFormat('H:i', $time);
        if (!$timeObject) {
            $error = "Invalid time format.";
        }
    }

    if ($error === '') {
        $checkStmt = mysqli_prepare(
            $connection,
            "
            SELECT appointment_id
            FROM appointments
            WHERE doctor_id = ?
            AND date = ?
            AND time = ?
            AND appointment_id != ?
            AND status NOT IN ('cancelled')
            LIMIT 1
            "
        );

        mysqli_stmt_bind_param($checkStmt, "issi", $doctorId, $date, $time, $appointmentId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($checkResult)) {
            $error = "The selected doctor is already booked for this date and time.";
        }
    }

    if ($error === '') {
        $checkStmt = mysqli_prepare(
            $connection,
            "
            SELECT appointment_id
            FROM appointments
            WHERE room_number = ?
            AND date = ?
            AND time = ?
            AND appointment_id != ?
            AND status NOT IN ('cancelled')
            LIMIT 1
            "
        );

        mysqli_stmt_bind_param($checkStmt, "sssi", $roomNumber, $date, $time, $appointmentId);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($checkResult)) {
            $error = "The selected room is already occupied for this date and time.";
        }
    }

    if ($error === '') {
        $update = mysqli_prepare(
            $connection,
            "
            UPDATE appointments
            SET patient_id = ?,
                doctor_id = ?,
                insurance = ?,
                room_number = ?,
                date = ?,
                time = ?,
                status = ?
            WHERE appointment_id = ?
            "
        );

        mysqli_stmt_bind_param(
            $update,
            "iisssssi",
            $patientId,
            $doctorId,
            $insurance,
            $roomNumber,
            $date,
            $time,
            $status,
            $appointmentId
        );

        if (mysqli_stmt_execute($update)) {
            $success = "Appointment updated successfully!";
            $appointment = [
                'appointment_id' => $appointmentId,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'insurance' => $insurance,
                'room_number' => $roomNumber,
                'date' => $date,
                'time' => $time,
                'status' => $status
            ];
        } else {
            $error = "Failed to update appointment: " . mysqli_error($connection);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secretary · Edit Appointment</title>
</head>
<body>

<h1>Edit Appointment #<?= $appointmentId ?></h1>

<p>
    <a href="secretary_read_appointment.php">← Back to Appointments</a>
    |
    <a href="secretary_dashboard.php">Dashboard</a>
</p>

<?php if ($success !== ''): ?>
    <p style="color: green;"><strong><?= htmlspecialchars($success) ?></strong></p>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <p style="color: red;"><strong>Error: <?= htmlspecialchars($error) ?></strong></p>
<?php endif; ?>

<hr>

<form method="POST" action="secretary_edit_appointment.php?appointment_id=<?= $appointmentId ?>">

    <p>
        <label>Patient:
            <select name="patient_id" required>
                <option value="">-- Select Patient --</option>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?= $patient['patient_id'] ?>"
                        <?= $appointment['patient_id'] == $patient['patient_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <p>
        <label>Doctor:
            <select name="doctor_id" required>
                <option value="">-- Select Doctor --</option>
                <?php foreach ($doctors as $doctor): ?>
                    <option value="<?= $doctor['doctor_id'] ?>"
                        <?= $appointment['doctor_id'] == $doctor['doctor_id'] ? 'selected' : '' ?>>
                        Dr. <?= htmlspecialchars($doctor['last_name'] . ', ' . $doctor['first_name']) ?>
                        (<?= htmlspecialchars($doctor['specialty'] ?? 'General') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <p>
        <label>Insurance:
            <input type="text" name="insurance" value="<?= htmlspecialchars($appointment['insurance']) ?>" required>
        </label>
    </p>

    <p>
        <label>Room Number:
            <input type="text" name="room_number" value="<?= htmlspecialchars($appointment['room_number']) ?>" required>
        </label>
    </p>

    <p>
        <label>Date:
            <input type="date" name="date" value="<?= htmlspecialchars($appointment['date']) ?>" required>
        </label>
    </p>

    <p>
        <label>Time:
            <input type="time" name="time" value="<?= htmlspecialchars(substr($appointment['time'], 0, 5)) ?>" required>
        </label>
    </p>

    <p>
        <label>Status:
            <select name="status" required>
                <?php foreach ($allowedStatuses as $status): ?>
                    <option value="<?= $status ?>" <?= $appointment['status'] === $status ? 'selected' : '' ?>>
                        <?= ucfirst($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <button type="submit">Save Changes</button>
    <a href="secretary_read_appointment.php">Cancel</a>

</form>

</body>
</html>