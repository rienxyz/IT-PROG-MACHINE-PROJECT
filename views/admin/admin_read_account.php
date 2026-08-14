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
| SEARCH AND FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
$activityStatus = $_GET['activity_status'] ?? '';
$verificationStatus = $_GET['verification_status'] ?? '';


/*
|--------------------------------------------------------------------------
| BUILD ACCOUNT QUERY
|--------------------------------------------------------------------------
*/

$sql = "
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
    WHERE 1 = 1
";

$types = '';
$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            first_name LIKE ?
            OR last_name LIKE ?
            OR e_mail LIKE ?
            OR CAST(account_id AS CHAR) LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $types .= 'ssss';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| ROLE FILTER
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'patient',
    'secretary',
    'doctor',
    'admin'
];

if (in_array($role, $allowedRoles, true)) {

    $sql .= " AND role = ?";

    $types .= 's';

    $params[] = $role;
}


/*
|--------------------------------------------------------------------------
| ACTIVITY STATUS FILTER
|--------------------------------------------------------------------------
*/

$allowedActivityStatuses = [
    'active',
    'inactive'
];

if (in_array($activityStatus, $allowedActivityStatuses, true)) {

    $sql .= " AND activity_status = ?";

    $types .= 's';

    $params[] = $activityStatus;
}


/*
|--------------------------------------------------------------------------
| VERIFICATION STATUS FILTER
|--------------------------------------------------------------------------
*/

$allowedVerificationStatuses = [
    'verified',
    'unverified',
    'verifying'
];

if (in_array($verificationStatus, $allowedVerificationStatuses, true)) {

    $sql .= " AND verification_status = ?";

    $types .= 's';

    $params[] = $verificationStatus;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= " ORDER BY account_id DESC";


/*
|--------------------------------------------------------------------------
| PREPARE QUERY
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($connection, $sql);

if (!$stmt) {
    die("Failed to prepare account query.");
}


/*
|--------------------------------------------------------------------------
| BIND PARAMETERS
|--------------------------------------------------------------------------
*/

if ($types !== '') {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

if (!mysqli_stmt_execute($stmt)) {
    die("Failed to retrieve accounts.");
}

$result = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| MESSAGE HANDLING
|--------------------------------------------------------------------------
*/

$message = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Account Management</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>

</head>

<body>

<h1>Account Management</h1>

<p>
    <a href="admin_dashboard.php">
        Back to Dashboard
    </a>
    |
    <a href="admin_add_account.php">
        Add New Account
    </a>
</p>


<?php if ($message === 'account_created'): ?>

    <p>
        Account successfully created.
    </p>

<?php elseif ($message === 'account_updated'): ?>

    <p>
        Account successfully updated.
    </p>

<?php elseif ($message === 'password_reset'): ?>

    <p>
        Password successfully reset.
    </p>

<?php elseif ($message === 'status_updated'): ?>

    <p>
        Account status successfully updated.
    </p>

<?php elseif ($message === 'verification_updated'): ?>

    <p>
        Verification status successfully updated.
    </p>

<?php elseif ($message === 'deleted'): ?>

    <p>
        Account successfully deleted.
    </p>

<?php endif; ?>


<?php if ($error === 'cannot_delete'): ?>

    <p>
        The account could not be deleted. It may still be referenced
        by existing records.
    </p>

<?php elseif ($error === 'cannot_modify_self'): ?>

    <p>
        You cannot modify your own administrator account from this page.
    </p>

<?php elseif ($error === 'not_found'): ?>

    <p>
        The requested account was not found.
    </p>

<?php endif; ?>


<hr>


<h2>Search and Filter Accounts</h2>

<form method="GET" action="admin_read_account.php">

    <p>

        <label>
            Search:

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Name, email, or account ID"
            >

        </label>

    </p>


    <p>

        <label>
            Role:

            <select name="role">

                <option value="">
                    All Roles
                </option>

                <option
                    value="patient"
                    <?= $role === 'patient' ? 'selected' : '' ?>
                >
                    Patient
                </option>

                <option
                    value="secretary"
                    <?= $role === 'secretary' ? 'selected' : '' ?>
                >
                    Secretary
                </option>

                <option
                    value="doctor"
                    <?= $role === 'doctor' ? 'selected' : '' ?>
                >
                    Doctor
                </option>

                <option
                    value="admin"
                    <?= $role === 'admin' ? 'selected' : '' ?>
                >
                    Admin
                </option>

            </select>

        </label>

    </p>


    <p>

        <label>
            Activity Status:

            <select name="activity_status">

                <option value="">
                    All Statuses
                </option>

                <option
                    value="active"
                    <?= $activityStatus === 'active' ? 'selected' : '' ?>
                >
                    Active
                </option>

                <option
                    value="inactive"
                    <?= $activityStatus === 'inactive' ? 'selected' : '' ?>
                >
                    Inactive
                </option>

            </select>

        </label>

    </p>


    <p>

        <label>
            Verification:

            <select name="verification_status">

                <option value="">
                    All Verification Statuses
                </option>

                <option
                    value="verified"
                    <?= $verificationStatus === 'verified' ? 'selected' : '' ?>
                >
                    Verified
                </option>

                <option
                    value="unverified"
                    <?= $verificationStatus === 'unverified' ? 'selected' : '' ?>
                >
                    Unverified
                </option>

                <option
                    value="verifying"
                    <?= $verificationStatus === 'verifying' ? 'selected' : '' ?>
                >
                    Verifying
                </option>

            </select>

        </label>

    </p>


    <button type="submit">
        Search / Filter
    </button>

    <a href="admin_read_account.php">
        Clear Filters
    </a>

</form>


<hr>


<h2>Accounts</h2>

<table border="1" cellpadding="8">

    <thead>

        <tr>

            <th>Account ID</th>

            <th>Name</th>

            <th>Phone</th>

            <th>Email</th>

            <th>Role</th>

            <th>Activity Status</th>

            <th>Verification</th>

            <th>Actions</th>

        </tr>

    </thead>


    <tbody>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>

        <?php while ($user = mysqli_fetch_assoc($result)): ?>

            <?php

            $accountId = (int) $user['account_id'];

            $isCurrentAdmin =
                isset($_SESSION['account_id']) &&
                $accountId === (int) $_SESSION['account_id'];

            ?>

            <tr>

                <td>
                    <?= $accountId ?>
                </td>


                <td>

                    <?= htmlspecialchars(
                        trim(
                            ($user['first_name'] ?? '') .
                            ' ' .
                            ($user['last_name'] ?? '')
                        )
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $user['phone_number'] ?? ''
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $user['e_mail']
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        ucfirst($user['role'] ?? '')
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        ucfirst(
                            $user['activity_status'] ?? ''
                        )
                    ) ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        ucfirst(
                            $user['verification_status'] ?? ''
                        )
                    ) ?>

                </td>


                <td>

                    <a
                        href="admin_edit_account.php?account_id=<?= $accountId ?>"
                    >
                        Edit
                    </a>

                    |

                    <a
                        href="admin_change_pw.php?account_id=<?= $accountId ?>"
                    >
                        Reset Password
                    </a>

                    |

                    <?php if (!$isCurrentAdmin): ?>

                        <a
                            href="admin_set_account_status.php?account_id=<?= $accountId ?>&current_status=<?= urlencode($user['activity_status']) ?>"
                        >
                            <?=
                            $user['activity_status'] === 'active'
                                ? 'Deactivate'
                                : 'Activate'
                            ?>
                        </a>

                        |

                        <a
                            href="admin_set_verification.php?account_id=<?= $accountId ?>&current_status=<?= urlencode($user['verification_status']) ?>"
                        >
                            Update Verification
                        </a>

                        |

                        <a
                            href="admin_delete_account.php?account_id=<?= $accountId ?>"
                            onclick="return confirm('Delete this account permanently?');"
                        >
                            Delete
                        </a>

                    <?php else: ?>

                        <strong>
                            Current Admin
                        </strong>

                    <?php endif; ?>

                </td>

            </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr>

            <td colspan="8">
                No accounts found.
            </td>

        </tr>

    <?php endif; ?>

    </tbody>

</table>

</body>

</html>
