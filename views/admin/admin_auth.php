```php
<?php

/*
|--------------------------------------------------------------------------
| ADMIN DEVELOPMENT AUTHENTICATION BYPASS
|--------------------------------------------------------------------------
|
| DEVELOPMENT ONLY
|
| This file temporarily bypasses the unfinished login system so that
| we can work on and test the admin functionality.
|
| IMPORTANT:
| Remove/disable this bypass when the real authentication system
| is implemented.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die(
        "Database connection failed: " .
        mysqli_connect_error()
    );
}


/*
|--------------------------------------------------------------------------
| FIND A REAL ADMIN ACCOUNT
|--------------------------------------------------------------------------
|
| We do NOT assume that the admin account has account_id = 1.
| Instead, find an actual admin account from the database.
|
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        account_id,
        first_name,
        last_name,
        e_mail,
        role,
        activity_status,
        verification_status
    FROM accounts
    WHERE role = 'admin'
    AND activity_status = 'active'
    LIMIT 1
";


$result = mysqli_query(
    $connection,
    $sql
);


if (!$result) {

    die(
        "Failed to locate an administrator account: " .
        mysqli_error($connection)
    );
}


/*
|--------------------------------------------------------------------------
| CHECK FOR ADMIN ACCOUNT
|--------------------------------------------------------------------------
*/

$admin = mysqli_fetch_assoc($result);


if (!$admin) {

    die(
        "No active administrator account was found. " .
        "Please create an admin account in the database first."
    );
}


/*
|--------------------------------------------------------------------------
| CREATE DEVELOPMENT ADMIN SESSION
|--------------------------------------------------------------------------
*/

$_SESSION['account_id'] =
    (int) $admin['account_id'];

$_SESSION['role'] =
    $admin['role'];

$_SESSION['email'] =
    $admin['e_mail'];

$_SESSION['first_name'] =
    $admin['first_name'];

$_SESSION['last_name'] =
    $admin['last_name'];


/*
|--------------------------------------------------------------------------
| DEVELOPMENT FLAG
|--------------------------------------------------------------------------
|
| This lets us distinguish the temporary bypass session from a future
| real authentication session.
|
|--------------------------------------------------------------------------
*/

$_SESSION['admin_dev_bypass'] = true;


/*
|--------------------------------------------------------------------------
| CONFIRM ADMIN ROLE
|--------------------------------------------------------------------------
*/

if ($_SESSION['role'] !== 'admin') {

    session_unset();
    session_destroy();

    die(
        "Access denied. The selected account is not an administrator."
    );
}


/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION COMPLETE
|--------------------------------------------------------------------------
|
| The page that included this file can now continue normally.
|
|--------------------------------------------------------------------------
*/

?>
```
