<?php

/*
|--------------------------------------------------------------------------
| ADMIN PASSWORD RESET
|--------------------------------------------------------------------------
|
| Admin-only development functionality.
| Authentication is handled by the application's normal login flow.
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
    : (int) ($_POST['account_id'] ?? 0);


if ($accountId <= 0) {

    header(
        "Location: admin_read_account.php?error=not_found"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| PREVENT ADMIN FROM RESETTING THEIR OWN PASSWORD
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
        e_mail
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
| PROCESS PASSWORD RESET
|--------------------------------------------------------------------------
*/

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($newPassword === '') {

        $error =
            "New password is required.";

    } elseif (strlen($newPassword) < 8) {

        $error =
            "Password must be at least 8 characters long.";

    } elseif ($newPassword !== $confirmPassword) {

        $error =
            "Passwords do not match.";
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $passwordHash =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );


        if ($passwordHash === false) {

            $error =
                "Failed to securely hash the password.";

        } else {

            $update = mysqli_prepare(
                $connection,
                "
                UPDATE accounts
                SET password = ?
                WHERE account_id = ?
                "
            );


            if (!$update) {

                $error =
                    "Failed to prepare password update.";

            } else {

                mysqli_stmt_bind_param(
                    $update,
                    "si",
                    $passwordHash,
                    $accountId
                );


                if (
                    !mysqli_stmt_execute($update)
                ) {

                    $error =
                        "Failed to update password: " .
                        mysqli_error($connection);

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    header(
                        "Location: admin_read_account.php?msg=password_reset"
                    );

                    exit();
                }
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Reset Password</title>

</head>

<body>

<h1>Reset Account Password</h1>


<p>

    <a href="admin_read_account.php">
        Back to Account Management
    </a>

</p>


<h2>Account</h2>

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


<hr>


<?php if ($error !== ''): ?>

    <p>

        <strong>
            Error:
        </strong>

        <?= htmlspecialchars($error) ?>

    </p>

<?php endif; ?>


<form
    method="POST"
    action="admin_change_pw.php?account_id=<?= (int) $account['account_id'] ?>"
>


    <input
        type="hidden"
        name="account_id"
        value="<?= (int) $account['account_id'] ?>"
    >


    <p>

        <label>

            New Password:

            <input
                type="password"
                name="new_password"
                minlength="8"
                required
            >

        </label>

    </p>


    <p>

        <label>

            Confirm New Password:

            <input
                type="password"
                name="confirm_password"
                minlength="8"
                required
            >

        </label>

    </p>


    <button type="submit">
        Reset Password
    </button>

</form>

</body>

</html>
