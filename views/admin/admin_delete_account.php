<?php

/*
|--------------------------------------------------------------------------
| ADMIN SAFE ACCOUNT DELETION
|--------------------------------------------------------------------------
|
| This page uses the application's normal login flow.
|
| IMPORTANT:
| This script intentionally refuses to delete accounts that have
| related records. This prevents accidental loss of appointment,
| doctor, secretary, patient, or assignment data.
|
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../data/connection.php';


/*
|--------------------------------------------------------------------------
| GET ACCOUNT ID
|--------------------------------------------------------------------------
*/

$accountId = isset($_GET['account_id'])
    ? (int) $_GET['account_id']
    : 0;


if ($accountId <= 0) {

    header(
        "Location: admin_read_account.php?error=not_found"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| PREVENT SELF-DELETION
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['account_id']) &&
    $accountId === (int) $_SESSION['account_id']
) {

    header(
        "Location: admin_read_account.php?error=cannot_modify_self"
    );

    exit();
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
        e_mail,
        role
    FROM accounts
    WHERE account_id = ?
    LIMIT 1
    "
);


if (!$stmt) {
    die("Failed to prepare account query.");
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $accountId
);


if (!mysqli_stmt_execute($stmt)) {
    die("Failed to retrieve account.");
}


$result = mysqli_stmt_get_result($stmt);

$account = mysqli_fetch_assoc($result);


if (!$account) {

    header(
        "Location: admin_read_account.php?error=not_found"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| CHECK FOR RELATED RECORDS
|--------------------------------------------------------------------------
|
| We check the known role-specific tables first.
|
|--------------------------------------------------------------------------
*/

$relatedRecords = [];


/*
|--------------------------------------------------------------------------
| DOCTOR RECORD
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $connection,
    "
    SELECT COUNT(*) AS total
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

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

if ((int) $row['total'] > 0) {

    $relatedRecords[] =
        "Doctor record";
}


/*
|--------------------------------------------------------------------------
| SECRETARY RECORD
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $connection,
    "
    SELECT COUNT(*) AS total
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

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

if ((int) $row['total'] > 0) {

    $relatedRecords[] =
        "Secretary record";
}


/*
|--------------------------------------------------------------------------
| PATIENT RECORD
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $connection,
    "
    SELECT COUNT(*) AS total
    FROM patients
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

$row = mysqli_fetch_assoc($result);

if ((int) $row['total'] > 0) {

    $relatedRecords[] =
        "Patient record";
}


/*
|--------------------------------------------------------------------------
| IF RELATED RECORDS EXIST, STOP
|--------------------------------------------------------------------------
*/

if (count($relatedRecords) > 0) {

    $message =
        "This account cannot be safely deleted because it has " .
        "related records.";

    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <title>Account Cannot Be Deleted</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>

    </head>

    <body>

    <h1>Account Cannot Be Deleted</h1>

    <p>

        The following related records exist:

    </p>

    <ul>

        <?php foreach ($relatedRecords as $record): ?>

            <li>
                <?= htmlspecialchars($record) ?>
            </li>

        <?php endforeach; ?>

    </ul>

    <p>
        Deleting this account automatically would risk breaking
        relationships in the database.
    </p>

    <p>

        The account should be deactivated instead.

    </p>

    <p>

        <a href="admin_read_account.php">
            Back to Account Management
        </a>

    </p>

    </body>

    </html>

    <?php

    exit();
}


/*
|--------------------------------------------------------------------------
| FINAL CONFIRMATION
|--------------------------------------------------------------------------
|
| The actual deletion happens only when:
|
| ?confirm=yes
|
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['confirm']) ||
    $_GET['confirm'] !== 'yes'
) {

    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <title>Confirm Account Deletion</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>

    </head>

    <body>

    <h1>Confirm Account Deletion</h1>

    <p>

        You are about to permanently delete:

    </p>

    <p>

        Account ID:

        <strong>
            <?= (int) $account['account_id'] ?>
        </strong>

    </p>

    <p>

        Name:

        <strong>

            <?= htmlspecialchars(
                trim(
                    ($account['first_name'] ?? '') .
                    ' ' .
                    ($account['last_name'] ?? '')
                )
            ) ?>

        </strong>

    </p>

    <p>

        Email:

        <strong>

            <?= htmlspecialchars(
                $account['e_mail']
            ) ?>

        </strong>

    </p>

    <p>

        Role:

        <strong>

            <?= htmlspecialchars(
                $account['role']
            ) ?>

        </strong>

    </p>

    <p>
        This action cannot be undone.
    </p>


    <p>

        <a
            href="admin_delete_account.php?account_id=<?= (int) $accountId ?>&confirm=yes"
        >
            Confirm Permanent Deletion
        </a>

    </p>


    <p>

        <a href="admin_read_account.php">
            Cancel
        </a>

    </p>

    </body>

    </html>

    <?php

    exit();
}


/*
|--------------------------------------------------------------------------
| DELETE ACCOUNT
|--------------------------------------------------------------------------
|
| At this point:
|
| - The account exists.
| - It is not the current admin.
| - No doctor record exists.
| - No secretary record exists.
| - No patient record exists.
|
| Therefore deleting the account itself is safe from those known
| relationships.
|
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($connection);


try {

    $delete = mysqli_prepare(
        $connection,
        "
        DELETE FROM accounts
        WHERE account_id = ?
        "
    );


    if (!$delete) {
        throw new Exception(
            "Failed to prepare account deletion."
        );
    }


    mysqli_stmt_bind_param(
        $delete,
        "i",
        $accountId
    );


    if (!mysqli_stmt_execute($delete)) {

        throw new Exception(
            "The database refused to delete this account. " .
            "It may still have related records."
        );
    }


    if (mysqli_stmt_affected_rows($delete) !== 1) {

        throw new Exception(
            "No account was deleted."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    mysqli_commit($connection);


    /*
    |--------------------------------------------------------------------------
    | RETURN TO ACCOUNT LIST
    |--------------------------------------------------------------------------
    */

    header(
        "Location: admin_read_account.php?msg=deleted"
    );

    exit();

} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    mysqli_rollback($connection);


    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <title>Account Deletion Failed</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>

    </head>

    <body>

    <h1>Account Was Not Deleted</h1>

    <p>

        <?= htmlspecialchars(
            $e->getMessage()
        ) ?>

    </p>

    <p>

        The database was left unchanged.

    </p>

    <p>

        <a href="admin_read_account.php">
            Back to Account Management
        </a>

    </p>

    </body>

    </html>

    <?php

}

?>
