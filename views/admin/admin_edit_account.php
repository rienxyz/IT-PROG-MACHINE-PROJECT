<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}


/*
|--------------------------------------------------------------------------
| GET ACCOUNT ID
|--------------------------------------------------------------------------
*/

$accountId = isset($_GET['account_id'])
    ? (int) $_GET['account_id']
    : (int) ($_POST['account_id'] ?? 0);


if ($accountId <= 0) {
    header("Location: admin_read_account.php?error=not_found");
    exit();
}


/*
|--------------------------------------------------------------------------
| PREVENT ADMIN FROM EDITING THEMSELVES
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['account_id']) &&
    $accountId === (int) $_SESSION['account_id']
) {
    header("Location: admin_read_account.php?error=cannot_modify_self");
    exit();
}


/*
|--------------------------------------------------------------------------
| UPDATE ACCOUNT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['e_mail'] ?? '');
    $role = $_POST['role'] ?? '';
    $activityStatus = $_POST['activity_status'] ?? '';
    $verificationStatus = $_POST['verification_status'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    $allowedRoles = [
        'patient',
        'secretary',
        'doctor',
        'admin'
    ];

    $allowedActivityStatuses = [
        'active',
        'inactive'
    ];

    $allowedVerificationStatuses = [
        'verified',
        'unverified',
        'verifying'
    ];


    if (
        $firstName === '' ||
        $lastName === '' ||
        $email === ''
    ) {
        die("First name, last name, and email are required.");
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }


    if (!in_array($role, $allowedRoles, true)) {
        die("Invalid account role.");
    }


    if (
        !in_array(
            $activityStatus,
            $allowedActivityStatuses,
            true
        )
    ) {
        die("Invalid activity status.");
    }


    if (
        !in_array(
            $verificationStatus,
            $allowedVerificationStatuses,
            true
        )
    ) {
        die("Invalid verification status.");
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EMAIL
    |--------------------------------------------------------------------------
    */

    $emailCheck = mysqli_prepare(
        $connection,
        "
        SELECT account_id
        FROM accounts
        WHERE e_mail = ?
        AND account_id != ?
        "
    );

    mysqli_stmt_bind_param(
        $emailCheck,
        "si",
        $email,
        $accountId
    );

    mysqli_stmt_execute($emailCheck);

    $emailResult = mysqli_stmt_get_result($emailCheck);

    if (mysqli_num_rows($emailResult) > 0) {
        die("That email address is already being used.");
    }


    /*
    |--------------------------------------------------------------------------
    | GET CURRENT ROLE
    |--------------------------------------------------------------------------
    */

    $roleCheck = mysqli_prepare(
        $connection,
        "
        SELECT role
        FROM accounts
        WHERE account_id = ?
        "
    );

    mysqli_stmt_bind_param(
        $roleCheck,
        "i",
        $accountId
    );

    mysqli_stmt_execute($roleCheck);

    $roleResult = mysqli_stmt_get_result($roleCheck);

    $currentAccount = mysqli_fetch_assoc($roleResult);

    if (!$currentAccount) {
        header("Location: admin_read_account.php?error=not_found");
        exit();
    }

    $oldRole = $currentAccount['role'];


    /*
    |--------------------------------------------------------------------------
    | TRANSACTION
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction($connection);

    try {

        /*
        |--------------------------------------------------------------------------
        | UPDATE MAIN ACCOUNT
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $connection,
            "
            UPDATE accounts
            SET
                first_name = ?,
                last_name = ?,
                phone_number = ?,
                e_mail = ?,
                role = ?,
                activity_status = ?,
                verification_status = ?
            WHERE account_id = ?
            "
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sssssssi",
            $firstName,
            $lastName,
            $phoneNumber,
            $email,
            $role,
            $activityStatus,
            $verificationStatus,
            $accountId
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update account.");
        }


        /*
        |--------------------------------------------------------------------------
        | ROLE CHANGED
        |--------------------------------------------------------------------------
        |
        | Remove old role-specific record when necessary.
        |
        */

        if ($oldRole !== $role) {

            if ($oldRole === 'doctor') {

                $stmt = mysqli_prepare(
                    $connection,
                    "
                    SELECT doctor_id
                    FROM doctors
                    WHERE account_id = ?
                    "
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $accountId
                );

                mysqli_stmt_execute($stmt);

                $doctorResult = mysqli_stmt_get_result($stmt);

                if ($doctor = mysqli_fetch_assoc($doctorResult)) {

                    $doctorId = (int) $doctor['doctor_id'];

                    /*
                    |--------------------------------------------------------------------------
                    | Remove doctor assignments
                    |--------------------------------------------------------------------------
                    */

                    $deleteAssignments = mysqli_prepare(
                        $connection,
                        "
                        DELETE FROM assignments
                        WHERE doctor_id = ?
                        "
                    );

                    mysqli_stmt_bind_param(
                        $deleteAssignments,
                        "i",
                        $doctorId
                    );

                    mysqli_stmt_execute(
                        $deleteAssignments
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Remove doctor record
                    |--------------------------------------------------------------------------
                    */

                    $deleteDoctor = mysqli_prepare(
                        $connection,
                        "
                        DELETE FROM doctors
                        WHERE doctor_id = ?
                        "
                    );

                    mysqli_stmt_bind_param(
                        $deleteDoctor,
                        "i",
                        $doctorId
                    );

                    mysqli_stmt_execute(
                        $deleteDoctor
                    );
                }
            }


            if ($oldRole === 'secretary') {

                $stmt = mysqli_prepare(
                    $connection,
                    "
                    SELECT secretary_id
                    FROM secretaries
                    WHERE account_id = ?
                    "
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $accountId
                );

                mysqli_stmt_execute($stmt);

                $secretaryResult =
                    mysqli_stmt_get_result($stmt);

                if ($secretary =
                    mysqli_fetch_assoc($secretaryResult)
                ) {

                    $secretaryId =
                        (int) $secretary['secretary_id'];


                    /*
                    |--------------------------------------------------------------------------
                    | Remove secretary assignments
                    |--------------------------------------------------------------------------
                    */

                    $deleteAssignments = mysqli_prepare(
                        $connection,
                        "
                        DELETE FROM assignments
                        WHERE secretary_id = ?
                        "
                    );

                    mysqli_stmt_bind_param(
                        $deleteAssignments,
                        "i",
                        $secretaryId
                    );

                    mysqli_stmt_execute(
                        $deleteAssignments
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Remove secretary record
                    |--------------------------------------------------------------------------
                    */

                    $deleteSecretary = mysqli_prepare(
                        $connection,
                        "
                        DELETE FROM secretaries
                        WHERE secretary_id = ?
                        "
                    );

                    mysqli_stmt_bind_param(
                        $deleteSecretary,
                        "i",
                        $secretaryId
                    );

                    mysqli_stmt_execute(
                        $deleteSecretary
                    );
                }
            }


            if ($oldRole === 'patient') {

                /*
                |--------------------------------------------------------------------------
                | Do not automatically delete patient records
                |--------------------------------------------------------------------------
                |
                | Existing appointments may reference the patient.
                |
                */

                $stmt = mysqli_prepare(
                    $connection,
                    "
                    DELETE FROM patients
                    WHERE account_id = ?
                    "
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $accountId
                );

                /*
                |--------------------------------------------------------------------------
                | If the patient has appointments, this may fail.
                | In that case the transaction will be rolled back.
                |--------------------------------------------------------------------------
                */

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception(
                        "Patient record could not be removed."
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | CREATE NEW ROLE RECORD
            |--------------------------------------------------------------------------
            */

            if ($role === 'doctor') {

                $specialty =
                    trim($_POST['specialty'] ?? '');

                $stmt = mysqli_prepare(
                    $connection,
                    "
                    INSERT INTO doctors
                    (account_id, specialty)
                    VALUES (?, ?)
                    "
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "is",
                    $accountId,
                    $specialty
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception(
                        "Failed to create doctor record."
                    );
                }
            }


            if ($role === 'secretary') {

                $department =
                    trim($_POST['department'] ?? '');

                $stmt = mysqli_prepare(
                    $connection,
                    "
                    INSERT INTO secretaries
                    (account_id, department)
                    VALUES (?, ?)
                    "
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "is",
                    $accountId,
                    $department
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception(
                        "Failed to create secretary record."
                    );
                }
            }


            if ($role === 'patient') {

                $stmt = mysqli_prepare(
                    $connection,
                    "
                    INSERT INTO patients
                    (account_id)
                    VALUES (?)
                    "
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $accountId
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception(
                        "Failed to create patient record."
                    );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE ROLE-SPECIFIC INFORMATION
        |--------------------------------------------------------------------------
        */

        if ($role === 'doctor') {

            $specialty =
                trim($_POST['specialty'] ?? '');

            $stmt = mysqli_prepare(
                $connection,
                "
                UPDATE doctors
                SET specialty = ?
                WHERE account_id = ?
                "
            );

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $specialty,
                $accountId
            );

            mysqli_stmt_execute($stmt);
        }


        if ($role === 'secretary') {

            $department =
                trim($_POST['department'] ?? '');

            $stmt = mysqli_prepare(
                $connection,
                "
                UPDATE secretaries
                SET department = ?
                WHERE account_id = ?
                "
            );

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $department,
                $accountId
            );

            mysqli_stmt_execute($stmt);
        }


        mysqli_commit($connection);

        header(
            "Location: admin_read_account.php?msg=account_updated"
        );

        exit();

    } catch (Exception $e) {

        mysqli_rollback($connection);

        die(
            "Account update failed: " .
            htmlspecialchars($e->getMessage())
        );
    }
}


/*
|--------------------------------------------------------------------------
| LOAD ACCOUNT
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $connection,
    "
    SELECT
        account_id,
        first_name,
        last_name,
        phone_number,
        e_mail,
        role,
        activity_status,
        verification_status
    FROM accounts
    WHERE account_id = ?
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $accountId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$account = mysqli_fetch_assoc($result);

if (!$account) {
    header("Location: admin_read_account.php?error=not_found");
    exit();
}


/*
|--------------------------------------------------------------------------
| LOAD DOCTOR DATA
|--------------------------------------------------------------------------
*/

$doctor = null;

if ($account['role'] === 'doctor') {

    $stmt = mysqli_prepare(
        $connection,
        "
        SELECT specialty
        FROM doctors
        WHERE account_id = ?
        "
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $accountId
    );

    mysqli_stmt_execute($stmt);

    $doctorResult = mysqli_stmt_get_result($stmt);

    $doctor = mysqli_fetch_assoc($doctorResult);
}


/*
|--------------------------------------------------------------------------
| LOAD SECRETARY DATA
|--------------------------------------------------------------------------
*/

$secretary = null;

if ($account['role'] === 'secretary') {

    $stmt = mysqli_prepare(
        $connection,
        "
        SELECT department
        FROM secretaries
        WHERE account_id = ?
        "
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $accountId
    );

    mysqli_stmt_execute($stmt);

    $secretaryResult =
        mysqli_stmt_get_result($stmt);

    $secretary =
        mysqli_fetch_assoc($secretaryResult);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/style.css">

    <title>Edit Account</title>

</head>

<body class="admin-page">

<h1>Edit Account</h1>

<p>
    <a href="admin_read_account.php">
        Back to Account Management
    </a>
</p>

<form method="POST" action="admin_edit_account.php">

    <input
        type="hidden"
        name="account_id"
        value="<?= (int) $account['account_id'] ?>"
    >


    <p>

        <label>
            First Name:

            <input
                type="text"
                name="first_name"
                value="<?= htmlspecialchars($account['first_name'] ?? '') ?>"
                required
            >

        </label>

    </p>


    <p>

        <label>
            Last Name:

            <input
                type="text"
                name="last_name"
                value="<?= htmlspecialchars($account['last_name'] ?? '') ?>"
                required
            >

        </label>

    </p>


    <p>

        <label>
            Phone Number:

            <input
                type="text"
                name="phone_number"
                value="<?= htmlspecialchars($account['phone_number'] ?? '') ?>"
            >

        </label>

    </p>


    <p>

        <label>
            Email:

            <input
                type="email"
                name="e_mail"
                value="<?= htmlspecialchars($account['e_mail']) ?>"
                required
            >

        </label>

    </p>


    <p>

        <label>
            Role:

            <select name="role" required>

                <?php

                $roles = [
                    'patient',
                    'secretary',
                    'doctor',
                    'admin'
                ];

                foreach ($roles as $role):

                ?>

                    <option
                        value="<?= $role ?>"
                        <?= $account['role'] === $role
                            ? 'selected'
                            : ''
                        ?>
                    >
                        <?= ucfirst($role) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </label>

    </p>


    <p>

        <label>
            Activity Status:

            <select name="activity_status" required>

                <option
                    value="active"
                    <?= $account['activity_status'] === 'active'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Active
                </option>

                <option
                    value="inactive"
                    <?= $account['activity_status'] === 'inactive'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Inactive
                </option>

            </select>

        </label>

    </p>


    <p>

        <label>
            Verification Status:

            <select
                name="verification_status"
                required
            >

                <option
                    value="verified"
                    <?= $account['verification_status'] === 'verified'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Verified
                </option>

                <option
                    value="unverified"
                    <?= $account['verification_status'] === 'unverified'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Unverified
                </option>

                <option
                    value="verifying"
                    <?= $account['verification_status'] === 'verifying'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Verifying
                </option>

            </select>

        </label>

    </p>


    <?php if ($account['role'] === 'doctor'): ?>

        <p>

            <label>
                Specialty:

                <input
                    type="text"
                    name="specialty"
                    value="<?= htmlspecialchars(
                        $doctor['specialty'] ?? ''
                    ) ?>"
                >

            </label>

        </p>

    <?php endif; ?>


    <?php if ($account['role'] === 'secretary'): ?>

        <p>

            <label>
                Department:

                <input
                    type="text"
                    name="department"
                    value="<?= htmlspecialchars(
                        $secretary['department'] ?? ''
                    ) ?>"
                >

            </label>

        </p>

    <?php endif; ?>


    <button type="submit">
        Save Changes
    </button>

</form>

</body>

</html>
