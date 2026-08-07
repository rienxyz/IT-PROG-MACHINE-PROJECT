```php
<?php

/*
|--------------------------------------------------------------------------
| ADMIN APPOINTMENT TEST DATA SEEDER
|--------------------------------------------------------------------------
|
| DEVELOPMENT / TESTING ONLY
|
| Creates several appointments using the existing test:
| - Doctor
| - Patient
|
| It does NOT create new accounts.
|
| Delete this file after running it.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/admin_auth.php';


/*
|--------------------------------------------------------------------------
| FIND TEST DOCTOR
|--------------------------------------------------------------------------
*/

$doctorEmail = 'doctor.test@example.com';

$stmt = mysqli_prepare(
    $connection,
    "
    SELECT
        d.doctor_id,
        d.account_id,
        a.first_name,
        a.last_name
    FROM doctors d
    INNER JOIN accounts a
        ON a.account_id = d.account_id
    WHERE a.e_mail = ?
    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $doctorEmail
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$doctor = mysqli_fetch_assoc($result);

if (!$doctor) {
    die(
        "Test doctor not found. Run admin_seed_test_data.php first."
    );
}

$doctorId = (int) $doctor['doctor_id'];


/*
|--------------------------------------------------------------------------
| FIND TEST PATIENT
|--------------------------------------------------------------------------
*/

$patientEmail = 'patient.test@example.com';

$stmt = mysqli_prepare(
    $connection,
    "
    SELECT
        p.patient_id,
        p.account_id,
        a.first_name,
        a.last_name
    FROM patients p
    INNER JOIN accounts a
        ON a.account_id = p.account_id
    WHERE a.e_mail = ?
    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $patientEmail
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$patient = mysqli_fetch_assoc($result);

if (!$patient) {
    die(
        "Test patient not found. Run admin_seed_test_data.php first."
    );
}

$patientId = (int) $patient['patient_id'];


/*
|--------------------------------------------------------------------------
| CHECK WHETHER TEST APPOINTMENTS ALREADY EXIST
|--------------------------------------------------------------------------
*/

$check = mysqli_query(
    $connection,
    "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE patient_id = $patientId
    AND doctor_id = $doctorId
    AND insurance = 'Test Insurance'
    "
);

if (!$check) {
    die(
        "Failed to check existing test appointments: " .
        mysqli_error($connection)
    );
}

$row = mysqli_fetch_assoc($check);

$existingAppointments = (int) $row['total'];


/*
|--------------------------------------------------------------------------
| CREATE TEST APPOINTMENTS
|--------------------------------------------------------------------------
*/

if ($existingAppointments === 0) {

    $appointments = [

        [
            'insurance' => 'Test Insurance',
            'room_number' => 'Room 1',
            'date' => date('Y-m-d'),
            'time' => '09:00:00',
            'status' => 'confirmed'
        ],

        [
            'insurance' => 'Test Insurance',
            'room_number' => 'Room 2',
            'date' => date(
                'Y-m-d',
                strtotime('+1 day')
            ),
            'time' => '10:00:00',
            'status' => 'confirmed'
        ],

        [
            'insurance' => 'Test Insurance',
            'room_number' => 'Room 3',
            'date' => date(
                'Y-m-d',
                strtotime('+2 days')
            ),
            'time' => '11:00:00',
            'status' => 'rescheduled'
        ],

        [
            'insurance' => 'Test Insurance',
            'room_number' => 'Room 4',
            'date' => date(
                'Y-m-d',
                strtotime('-1 day')
            ),
            'time' => '13:00:00',
            'status' => 'completed'
        ],

        [
            'insurance' => 'Test Insurance',
            'room_number' => 'Room 5',
            'date' => date(
                'Y-m-d',
                strtotime('-2 days')
            ),
            'time' => '14:00:00',
            'status' => 'cancelled'
        ]

    ];


    mysqli_begin_transaction($connection);


    try {

        $stmt = mysqli_prepare(
            $connection,
            "
            INSERT INTO appointments
            (
                patient_id,
                doctor_id,
                insurance,
                room_number,
                date,
                time,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
            "
        );


        if (!$stmt) {
            throw new Exception(
                "Failed to prepare appointment insert."
            );
        }


        foreach ($appointments as $appointment) {

            mysqli_stmt_bind_param(
                $stmt,
                "iisssss",
                $patientId,
                $doctorId,
                $appointment['insurance'],
                $appointment['room_number'],
                $appointment['date'],
                $appointment['time'],
                $appointment['status']
            );


            if (!mysqli_stmt_execute($stmt)) {

                throw new Exception(
                    "Failed to create test appointment: " .
                    mysqli_error($connection)
                );
            }
        }


        mysqli_commit($connection);


        $created = count($appointments);

    } catch (Exception $e) {

        mysqli_rollback($connection);

        die(
            "Appointment seed failed: " .
            htmlspecialchars($e->getMessage())
        );
    }

} else {

    $created = 0;
}


/*
|--------------------------------------------------------------------------
| OUTPUT
|--------------------------------------------------------------------------
*/

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Appointment Test Data</title>

</head>

<body>

<h1>Appointment Test Data</h1>


<?php if ($created > 0): ?>

    <p>
        Successfully created
        <strong><?= $created ?></strong>
        test appointments.
    </p>

<?php else: ?>

    <p>
        Test appointments already exist.
        No duplicate appointments were created.
    </p>

<?php endif; ?>


<h2>Test Doctor</h2>

<p>

    <?= htmlspecialchars(
        $doctor['first_name'] . ' ' .
        $doctor['last_name']
    ) ?>

    —

    Doctor ID:

    <?= $doctorId ?>

</p>


<h2>Test Patient</h2>

<p>

    <?= htmlspecialchars(
        $patient['first_name'] . ' ' .
        $patient['last_name']
    ) ?>

    —

    Patient ID:

    <?= $patientId ?>

</p>


<h2>Created Test Cases</h2>

<table border="1" cellpadding="8">

    <tr>

        <th>Date</th>

        <th>Time</th>

        <th>Room</th>

        <th>Status</th>

    </tr>


    <tr>

        <td>
            Today
        </td>

        <td>
            09:00
        </td>

        <td>
            Room 1
        </td>

        <td>
            confirmed
        </td>

    </tr>


    <tr>

        <td>
            Tomorrow
        </td>

        <td>
            10:00
        </td>

        <td>
            Room 2
        </td>

        <td>
            confirmed
        </td>

    </tr>


    <tr>

        <td>
            +2 Days
        </td>

        <td>
            11:00
        </td>

        <td>
            Room 3
        </td>

        <td>
            rescheduled
        </td>

    </tr>


    <tr>

        <td>
            Yesterday
        </td>

        <td>
            13:00
        </td>

        <td>
            Room 4
        </td>

        <td>
            completed
        </td>

    </tr>


    <tr>

        <td>
            -2 Days
        </td>

        <td>
            14:00
        </td>

        <td>
            Room 5
        </td>

        <td>
            cancelled
        </td>

    </tr>

</table>


<hr>


<p>

    <strong>
        Delete admin_seed_appointments.php after running it.
    </strong>

</p>


<p>

    <a href="admin_dashboard.php">
        Back to Admin Dashboard
    </a>

</p>

</body>

</html>
```
