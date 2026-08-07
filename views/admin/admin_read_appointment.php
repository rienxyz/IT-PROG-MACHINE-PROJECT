```php
<?php

/*
|--------------------------------------------------------------------------
| ADMIN APPOINTMENT MANAGER
|--------------------------------------------------------------------------
|
| This single file handles:
|
| - Viewing appointments
| - Searching appointments
| - Filtering by status
| - Editing appointment details
| - Changing appointment status
| - Changing doctor
| - Changing room
| - Changing date/time
| - Deleting appointments
|
| Functionality only.
| No CSS/UI framework.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/admin_auth.php';


/*
|--------------------------------------------------------------------------
| ALLOWED STATUSES
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'pending',
    'confirmed',
    'rescheduled',
    'completed',
    'cancelled'
];


/*
|--------------------------------------------------------------------------
| MESSAGE VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';
$error = '';


/*
|--------------------------------------------------------------------------
| GET ACTION
|--------------------------------------------------------------------------
*/

$action = $_GET['action'] ?? '';


/*
|--------------------------------------------------------------------------
| GET APPOINTMENT ID
|--------------------------------------------------------------------------
*/

$appointmentId = isset($_GET['appointment_id'])
    ? (int) $_GET['appointment_id']
    : 0;


/*
|--------------------------------------------------------------------------
| DELETE APPOINTMENT
|--------------------------------------------------------------------------
|
| Deletion is handled here instead of a separate file.
|
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_action'] ?? '') === 'delete'
) {

    $deleteId = (int) ($_POST['appointment_id'] ?? 0);


    if ($deleteId <= 0) {

        $error = 'Invalid appointment ID.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK APPOINTMENT EXISTS
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $connection,
            "
            SELECT appointment_id
            FROM appointments
            WHERE appointment_id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {

            $error =
                'Failed to prepare appointment lookup.';

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $deleteId
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);


            if (!mysqli_fetch_assoc($result)) {

                $error =
                    'Appointment was not found.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | DELETE
                |--------------------------------------------------------------------------
                */

                $delete = mysqli_prepare(
                    $connection,
                    "
                    DELETE FROM appointments
                    WHERE appointment_id = ?
                    "
                );


                if (!$delete) {

                    $error =
                        'Failed to prepare appointment deletion.';

                } else {

                    mysqli_stmt_bind_param(
                        $delete,
                        "i",
                        $deleteId
                    );


                    if (
                        !mysqli_stmt_execute($delete)
                    ) {

                        $error =
                            'Appointment could not be deleted: ' .
                            mysqli_error($connection);

                    } elseif (
                        mysqli_stmt_affected_rows($delete) !== 1
                    ) {

                        $error =
                            'No appointment was deleted.';

                    } else {

                        header(
                            "Location: admin_read_appointment.php?msg=deleted"
                        );

                        exit();
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE APPOINTMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_action'] ?? '') === 'update'
) {

    $updateId =
        (int) ($_POST['appointment_id'] ?? 0);

    $patientId =
        (int) ($_POST['patient_id'] ?? 0);

    $doctorId =
        (int) ($_POST['doctor_id'] ?? 0);

    $insurance =
        trim($_POST['insurance'] ?? '');

    $roomNumber =
        trim($_POST['room_number'] ?? '');

    $date =
        trim($_POST['date'] ?? '');

    $time =
        trim($_POST['time'] ?? '');

    $newStatus =
        trim($_POST['status'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($updateId <= 0) {

        $error =
            'Invalid appointment ID.';

    } elseif ($patientId <= 0) {

        $error =
            'A valid patient is required.';

    } elseif ($doctorId <= 0) {

        $error =
            'A valid doctor is required.';

    } elseif ($insurance === '') {

        $error =
            'Insurance is required.';

    } elseif ($roomNumber === '') {

        $error =
            'Room number is required.';

    } elseif ($date === '') {

        $error =
            'Appointment date is required.';

    } elseif ($time === '') {

        $error =
            'Appointment time is required.';

    } elseif (
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $error =
            'Invalid appointment status.';
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE DATE
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $dateObject =
            DateTime::createFromFormat(
                'Y-m-d',
                $date
            );


        if (
            !$dateObject ||
            $dateObject->format('Y-m-d') !== $date
        ) {

            $error =
                'Invalid appointment date.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE TIME
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $timeObject =
            DateTime::createFromFormat(
                'H:i',
                $time
            );


        if (
            !$timeObject &&
            !DateTime::createFromFormat(
                'H:i:s',
                $time
            )
        ) {

            $error =
                'Invalid appointment time.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PATIENT EXISTS
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $connection,
            "
            SELECT patient_id
            FROM patients
            WHERE patient_id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {

            $error =
                'Failed to validate patient.';

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $patientId
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);


            if (!mysqli_fetch_assoc($result)) {

                $error =
                    'Selected patient does not exist.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DOCTOR EXISTS
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $connection,
            "
            SELECT doctor_id
            FROM doctors
            WHERE doctor_id = ?
            LIMIT 1
            "
        );


        if (!$stmt) {

            $error =
                'Failed to validate doctor.';

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $doctorId
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);


            if (!mysqli_fetch_assoc($result)) {

                $error =
                    'Selected doctor does not exist.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DOCTOR SCHEDULE CONFLICT
    |--------------------------------------------------------------------------
    |
    | Do not allow the same doctor to have another appointment
    | at the exact same date and time.
    |
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $connection,
            "
            SELECT appointment_id
            FROM appointments
            WHERE doctor_id = ?
            AND date = ?
            AND time = ?
            AND appointment_id <> ?
            LIMIT 1
            "
        );


        if (!$stmt) {

            $error =
                'Failed to check doctor schedule.';

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "issi",
                $doctorId,
                $date,
                $time,
                $updateId
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);


            if (mysqli_fetch_assoc($result)) {

                $error =
                    'The selected doctor already has an appointment at this date and time.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ROOM CONFLICT
    |--------------------------------------------------------------------------
    |
    | Do not allow two appointments to use the same room
    | at the exact same date and time.
    |
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $connection,
            "
            SELECT appointment_id
            FROM appointments
            WHERE room_number = ?
            AND date = ?
            AND time = ?
            AND appointment_id <> ?
            LIMIT 1
            "
        );


        if (!$stmt) {

            $error =
                'Failed to check room schedule.';

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $roomNumber,
                $date,
                $time,
                $updateId
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);


            if (mysqli_fetch_assoc($result)) {

                $error =
                    'The selected room is already occupied at this date and time.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $update = mysqli_prepare(
            $connection,
            "
            UPDATE appointments

            SET
                patient_id = ?,
                doctor_id = ?,
                insurance = ?,
                room_number = ?,
                date = ?,
                time = ?,
                status = ?

            WHERE appointment_id = ?
            "
        );


        if (!$update) {

            $error =
                'Failed to prepare appointment update.';

        } else {

            mysqli_stmt_bind_param(
                $update,
                "iisssssi",
                $patientId,
                $doctorId,
                $insurance,
                $roomNumber,
                $date,
                $time,
                $newStatus,
                $updateId
            );


            if (
                !mysqli_stmt_execute($update)
            ) {

                $error =
                    'Failed to update appointment: ' .
                    mysqli_error($connection);

            } else {

                header(
                    "Location: admin_read_appointment.php?msg=updated"
                );

                exit();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | IF UPDATE FAILED
    |--------------------------------------------------------------------------
    |
    | Stay on edit page and reload the appointment below.
    |
    |--------------------------------------------------------------------------
    */

    $appointmentId =
        $updateId;

    $action =
        'edit';
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGES
|--------------------------------------------------------------------------
*/

if (isset($_GET['msg'])) {

    if ($_GET['msg'] === 'updated') {

        $message =
            'Appointment updated successfully.';

    } elseif ($_GET['msg'] === 'deleted') {

        $message =
            'Appointment deleted successfully.';
    }
}


/*
|--------------------------------------------------------------------------
| LOAD PATIENTS FOR EDITING
|--------------------------------------------------------------------------
*/

$patients = [];

$patientResult = mysqli_query(
    $connection,
    "
    SELECT
        p.patient_id,
        a.first_name,
        a.last_name,
        a.e_mail

    FROM patients p

    INNER JOIN accounts a
        ON a.account_id = p.account_id

    ORDER BY
        a.last_name ASC,
        a.first_name ASC
    "
);


if ($patientResult) {

    while (
        $patient = mysqli_fetch_assoc(
            $patientResult
        )
    ) {

        $patients[] =
            $patient;
    }
}


/*
|--------------------------------------------------------------------------
| LOAD DOCTORS FOR EDITING
|--------------------------------------------------------------------------
*/

$doctors = [];

$doctorResult = mysqli_query(
    $connection,
    "
    SELECT
        d.doctor_id,
        a.first_name,
        a.last_name,
        a.e_mail

    FROM doctors d

    INNER JOIN accounts a
        ON a.account_id = d.account_id

    ORDER BY
        a.last_name ASC,
        a.first_name ASC
    "
);


if ($doctorResult) {

    while (
        $doctor = mysqli_fetch_assoc(
            $doctorResult
        )
    ) {

        $doctors[] =
            $doctor;
    }
}


/*
|--------------------------------------------------------------------------
| LOAD APPOINTMENT FOR EDITING
|--------------------------------------------------------------------------
*/

$editAppointment = null;


if (
    $action === 'edit' &&
    $appointmentId > 0
) {

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

        LIMIT 1
        "
    );


    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $appointmentId
        );

        mysqli_stmt_execute($stmt);

        $result =
            mysqli_stmt_get_result($stmt);

        $editAppointment =
            mysqli_fetch_assoc($result);


        if (!$editAppointment) {

            $error =
                'Appointment was not found.';

            $action = '';
        }
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH AND FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$statusFilter =
    trim($_GET['status'] ?? '');


/*
|--------------------------------------------------------------------------
| BUILD APPOINTMENT LIST QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        ap.appointment_id,
        ap.patient_id,
        ap.doctor_id,
        ap.insurance,
        ap.room_number,
        ap.date,
        ap.time,
        ap.status,

        patient_account.first_name
            AS patient_first_name,

        patient_account.last_name
            AS patient_last_name,

        patient_account.e_mail
            AS patient_email,

        doctor_account.first_name
            AS doctor_first_name,

        doctor_account.last_name
            AS doctor_last_name,

        doctor_account.e_mail
            AS doctor_email

    FROM appointments ap

    INNER JOIN patients p
        ON p.patient_id = ap.patient_id

    INNER JOIN accounts patient_account
        ON patient_account.account_id = p.account_id

    INNER JOIN doctors d
        ON d.doctor_id = ap.doctor_id

    INNER JOIN accounts doctor_account
        ON doctor_account.account_id = d.account_id

    WHERE 1 = 1
";


$params = [];

$types = "";


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            patient_account.first_name LIKE ?
            OR patient_account.last_name LIKE ?
            OR patient_account.e_mail LIKE ?

            OR doctor_account.first_name LIKE ?
            OR doctor_account.last_name LIKE ?
            OR doctor_account.e_mail LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';


    for ($i = 0; $i < 6; $i++) {

        $params[] =
            $searchValue;
    }


    $types .=
        "ssssss";
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    $statusFilter !== '' &&
    in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $sql .= "
        AND ap.status = ?
    ";

    $params[] =
        $statusFilter;

    $types .=
        "s";
}


/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        ap.date ASC,
        ap.time ASC,
        ap.appointment_id ASC
";


/*
|--------------------------------------------------------------------------
| PREPARE LIST QUERY
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $connection,
    $sql
);


if (!$stmt) {

    die(
        "Failed to prepare appointment query: " .
        mysqli_error($connection)
    );
}


/*
|--------------------------------------------------------------------------
| BIND LIST PARAMETERS
|--------------------------------------------------------------------------
*/

if (count($params) > 0) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


/*
|--------------------------------------------------------------------------
| EXECUTE LIST QUERY
|--------------------------------------------------------------------------
*/

if (!mysqli_stmt_execute($stmt)) {

    die(
        "Failed to retrieve appointments: " .
        mysqli_error($connection)
    );
}


$appointmentResult =
    mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Admin - Appointments</title>

</head>

<body>


<h1>Appointment Management</h1>


<p>

    <a href="admin_dashboard.php">
        Back to Admin Dashboard
    </a>

</p>


<?php if ($message !== ''): ?>

    <p>

        <strong>
            <?= htmlspecialchars($message) ?>
        </strong>

    </p>

<?php endif; ?>


<?php if ($error !== ''): ?>

    <p>

        <strong>
            Error:
        </strong>

        <?= htmlspecialchars($error) ?>

    </p>

<?php endif; ?>


<?php if ($action === 'edit' && $editAppointment): ?>

    <hr>

    <h2>Edit Appointment</h2>


    <form
        method="POST"
        action="admin_read_appointment.php"
    >

        <input
            type="hidden"
            name="form_action"
            value="update"
        >


        <input
            type="hidden"
            name="appointment_id"
            value="<?= (int) $editAppointment['appointment_id'] ?>"
        >


        <p>

            <label for="patient_id">
                Patient:
            </label>

            <select
                id="patient_id"
                name="patient_id"
                required
            >

                <option value="">
                    Select patient
                </option>

                <?php foreach ($patients as $patient): ?>

                    <option
                        value="<?= (int) $patient['patient_id'] ?>"
                        <?= (
                            (int) $editAppointment['patient_id'] ===
                            (int) $patient['patient_id']
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars(
                            trim(
                                $patient['first_name'] .
                                ' ' .
                                $patient['last_name']
                            )
                        ) ?>

                        -
                        <?= htmlspecialchars(
                            $patient['e_mail']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </p>


        <p>

            <label for="doctor_id">
                Doctor:
            </label>

            <select
                id="doctor_id"
                name="doctor_id"
                required
            >

                <option value="">
                    Select doctor
                </option>

                <?php foreach ($doctors as $doctor): ?>

                    <option
                        value="<?= (int) $doctor['doctor_id'] ?>"
                        <?= (
                            (int) $editAppointment['doctor_id'] ===
                            (int) $doctor['doctor_id']
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars(
                            trim(
                                $doctor['first_name'] .
                                ' ' .
                                $doctor['last_name']
                            )
                        ) ?>

                        -
                        <?= htmlspecialchars(
                            $doctor['e_mail']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </p>


        <p>

            <label for="insurance">
                Insurance:
            </label>

            <input
                id="insurance"
                type="text"
                name="insurance"
                value="<?= htmlspecialchars(
                    $editAppointment['insurance']
                ) ?>"
                required
            >

        </p>


        <p>

            <label for="room_number">
                Room:
            </label>

            <input
                id="room_number"
                type="text"
                name="room_number"
                value="<?= htmlspecialchars(
                    $editAppointment['room_number']
                ) ?>"
                required
            >

        </p>


        <p>

            <label for="date">
                Date:
            </label>

            <input
                id="date"
                type="date"
                name="date"
                value="<?= htmlspecialchars(
                    $editAppointment['date']
                ) ?>"
                required
            >

        </p>


        <p>

            <label for="time">
                Time:
            </label>

            <input
                id="time"
                type="time"
                name="time"
                value="<?= htmlspecialchars(
                    substr(
                        $editAppointment['time'],
                        0,
                        5
                    )
                ) ?>"
                required
            >

        </p>


        <p>

            <label for="status">
                Status:
            </label>

            <select
                id="status"
                name="status"
                required
            >

                <?php foreach ($allowedStatuses as $option): ?>

                    <option
                        value="<?= htmlspecialchars($option) ?>"
                        <?= (
                            $editAppointment['status'] ===
                            $option
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($option) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </p>


        <button type="submit">
            Save Appointment
        </button>


        <a href="admin_read_appointment.php">
            Cancel
        </a>

    </form>


    <hr>

<?php endif; ?>


<h2>Search Appointments</h2>


<form
    method="GET"
    action="admin_read_appointment.php"
>

    <p>

        <label for="search">
            Search:
        </label>

        <input
            id="search"
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Patient or doctor"
        >

    </p>


    <p>

        <label for="status_filter">
            Status:
        </label>

        <select
            id="status_filter"
            name="status"
        >

            <option value="">
                All statuses
            </option>

            <?php foreach ($allowedStatuses as $option): ?>

                <option
                    value="<?= htmlspecialchars($option) ?>"
                    <?= (
                        $statusFilter === $option
                    ) ? 'selected' : '' ?>
                >

                    <?= htmlspecialchars($option) ?>

                </option>

            <?php endforeach; ?>

        </select>

    </p>


    <button type="submit">
        Search
    </button>


    <a href="admin_read_appointment.php">
        Clear
    </a>

</form>


<hr>


<h2>Appointments</h2>


<?php if (mysqli_num_rows($appointmentResult) === 0): ?>

    <p>
        No appointments found.
    </p>

<?php else: ?>

    <table
        border="1"
        cellpadding="8"
    >

        <thead>

            <tr>

                <th>ID</th>

                <th>Patient</th>

                <th>Doctor</th>

                <th>Insurance</th>

                <th>Room</th>

                <th>Date</th>

                <th>Time</th>

                <th>Status</th>

                <th>Actions</th>

            </tr>

        </thead>


        <tbody>

        <?php while (
            $appointment =
                mysqli_fetch_assoc(
                    $appointmentResult
                )
        ): ?>

            <tr>

                <td>

                    <?= (int) $appointment[
                        'appointment_id'
                    ] ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        trim(
                            $appointment[
                                'patient_first_name'
                            ] .
                            ' ' .
                            $appointment[
                                'patient_last_name'
                            ]
                        )
                    ) ?>

                    <br>

                    <?= htmlspecialchars(
                        $appointment[
                            'patient_email'
                        ]
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        trim(
                            $appointment[
                                'doctor_first_name'
                            ] .
                            ' ' .
                            $appointment[
                                'doctor_last_name'
                            ]
                        )
                    ) ?>

                    <br>

                    <?= htmlspecialchars(
                        $appointment[
                            'doctor_email'
                        ]
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $appointment['insurance']
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $appointment['room_number']
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $appointment['date']
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $appointment['time']
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $appointment['status']
                    ) ?>

                </td>


                <td>

                    <a
                        href="admin_read_appointment.php?action=edit&appointment_id=<?= (int) $appointment['appointment_id'] ?>"
                    >
                        Edit
                    </a>


                    <form
                        method="POST"
                        action="admin_read_appointment.php"
                        style="display:inline;"
                    >

                        <input
                            type="hidden"
                            name="form_action"
                            value="delete"
                        >

                        <input
                            type="hidden"
                            name="appointment_id"
                            value="<?= (int) $appointment['appointment_id'] ?>"
                        >

                        <button
                            type="submit"
                            onclick="return confirm('Delete this appointment permanently?');"
                        >
                            Delete
                        </button>

                    </form>

                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>

    </table>

<?php endif; ?>


</body>

</html>
```
