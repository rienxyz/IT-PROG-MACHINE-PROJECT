```php
<?php

/*
|--------------------------------------------------------------------------
| ADMIN ACCOUNT STATUS
|--------------------------------------------------------------------------
|
| Development authentication is currently handled by admin_auth.php.
|
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/admin_auth.php';


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
| PREVENT ADMIN FROM MODIFYING THEMSELVES
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
| GET CURRENT ACCOUNT STATUS
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $connection,
    "
    SELECT
        account_id,
        activity_status
    FROM accounts
    WHERE account_id = ?
    LIMIT 1
    "
);


if (!$stmt) {

    die(
        "Failed to prepare account status query."
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $accountId
);


if (!mysqli_stmt_execute($stmt)) {

    die(
        "Failed to retrieve account status."
    );
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
| DETERMINE NEW STATUS
|--------------------------------------------------------------------------
*/

$currentStatus =
    $account['activity_status'];


if ($currentStatus === 'active') {

    $newStatus = 'inactive';

} elseif ($currentStatus === 'inactive') {

    $newStatus = 'active';

} else {

    die(
        "Invalid current account status."
    );
}


/*
|--------------------------------------------------------------------------
| UPDATE STATUS
|--------------------------------------------------------------------------
*/

$update = mysqli_prepare(
    $connection,
    "
    UPDATE accounts
    SET activity_status = ?
    WHERE account_id = ?
    "
);


if (!$update) {

    die(
        "Failed to prepare account status update."
    );
}


mysqli_stmt_bind_param(
    $update,
    "si",
    $newStatus,
    $accountId
);


if (!mysqli_stmt_execute($update)) {

    die(
        "Failed to update account status: " .
        mysqli_error($connection)
    );
}


/*
|--------------------------------------------------------------------------
| RETURN TO ACCOUNT LIST
|--------------------------------------------------------------------------
*/

header(
    "Location: admin_read_account.php?msg=status_updated"
);

exit();

?>
```
