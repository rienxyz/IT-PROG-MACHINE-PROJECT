<?php
require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

require_once __DIR__ . '/secretary_auth.php';

$patients = [];
$patientQuery = "
    SELECT
        p.patient_id,
        a.first_name,
        a.last_name,
        a.e_mail,
        a.phone_number
    FROM patients p
    INNER JOIN accounts a ON a.account_id = p.account_id
    WHERE a.activity_status = 'active'
    ORDER BY a.last_name ASC
";

$patientResult = mysqli_query($connection, $patientQuery);
if ($patientResult) {
    while ($patient = mysqli_fetch_assoc($patientResult)) {
        $patients[] = $patient;
    }
}

$doctors = [];
$doctorQuery = "
    SELECT
        d.doctor_id,
        d.specialty,
        a.first_name,
        a.last_name,
        a.e_mail
    FROM doctors d
    INNER JOIN accounts a ON a.account_id = d.account_id
    WHERE a.activity_status = 'active'
    ORDER BY a.last_name ASC
";

$doctorResult = mysqli_query($connection, $doctorQuery);
if ($doctorResult) {
    while ($doctor = mysqli_fetch_assoc($doctorResult)) {
        $doctors[] = $doctor;
    }
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
    $status = trim($_POST['status'] ?? 'pending');

    if ($patientId <= 0) {
        $error = "Please select a patient.";
    } elseif ($doctorId <= 0) {
        $error = "Please select a doctor.";
    } elseif ($insurance === '') {
        $error = "Insurance information is required.";
    } elseif ($roomNumber === '') {
        $error = "Room number is required.";
    } elseif ($date === '') {
        $error = "Appointment date is required.";
    } elseif ($time === '') {
        $error = "Appointment time is required.";
    } elseif (!in_array($status, ['pending', 'confirmed'], true)) {
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
            AND status NOT IN ('cancelled')
            LIMIT 1
            "
        );

        mysqli_stmt_bind_param($checkStmt, "iss", $doctorId, $date, $time);
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
            AND status NOT IN ('cancelled')
            LIMIT 1
            "
        );

        mysqli_stmt_bind_param($checkStmt, "sss", $roomNumber, $date, $time);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($checkResult)) {
            $error = "The selected room is already occupied for this date and time.";
        }
    }

    if ($error === '') {
        $insert = mysqli_prepare(
            $connection,
            "
            INSERT INTO appointments
            (patient_id, doctor_id, insurance, room_number, date, time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            "
        );

        mysqli_stmt_bind_param(
            $insert,
            "iisssss",
            $patientId,
            $doctorId,
            $insurance,
            $roomNumber,
            $date,
            $time,
            $status
        );

        if (mysqli_stmt_execute($insert)) {
            $success = "Appointment scheduled successfully!";
            $_POST = [];
        } else {
            $error = "Failed to schedule appointment: " . mysqli_error($connection);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secretary · Schedule Appointment</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>
<body>

<h1>Schedule New Appointment</h1>

<p>
    <a href="secretary_dashboard.php">← Back to Dashboard</a>
    |
    <a href="secretary_read_appointment.php">View All Appointments</a>
</p>

<?php if ($success !== ''): ?>
    <p style="color: green;"><strong><?= htmlspecialchars($success) ?></strong></p>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <p style="color: red;"><strong>Error: <?= htmlspecialchars($error) ?></strong></p>
<?php endif; ?>

<hr>

<form method="POST" action="secretary_add_appointment.php">

    <p>
        <label>Patient:
            <select name="patient_id" required>
                <option value="">-- Select Patient --</option>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?= $patient['patient_id'] ?>"
                        <?= ($_POST['patient_id'] ?? 0) == $patient['patient_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) ?>
                        (<?= htmlspecialchars($patient['e_mail']) ?>)
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
                        <?= ($_POST['doctor_id'] ?? 0) == $doctor['doctor_id'] ? 'selected' : '' ?>>
                        Dr. <?= htmlspecialchars($doctor['last_name'] . ', ' . $doctor['first_name']) ?>
                        (<?= htmlspecialchars($doctor['specialty'] ?? 'General') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <p>
        <label>Insurance:
            <input type="text" name="insurance" value="<?= htmlspecialchars($_POST['insurance'] ?? '') ?>" required>
        </label>
    </p>

    <p>
        <label>Room Number:
            <input type="text" name="room_number" value="<?= htmlspecialchars($_POST['room_number'] ?? '') ?>" required
                   placeholder="e.g., Room 101">
        </label>
    </p>

    <p>
        <label>Date:
            <input type="date" name="date" value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>" required>
        </label>
    </p>

    <p>
        <label>Time:
            <input type="time" name="time" value="<?= htmlspecialchars($_POST['time'] ?? '09:00') ?>" required>
        </label>
    </p>

    <p>
        <label>Status:
            <select name="status">
                <option value="pending" <?= ($_POST['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>
                    Pending
                </option>
                <option value="confirmed" <?= ($_POST['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>
                    Confirmed
                </option>
            </select>
        </label>
    </p>

    <button type="submit">Schedule Appointment</button>
    <button type="reset">Reset</button>

</form>

</body>
</html>