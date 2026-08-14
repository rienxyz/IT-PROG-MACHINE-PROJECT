<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../data/connection.php';

/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
| Functionality only.
| No CSS/UI framework is being used at this stage.
|--------------------------------------------------------------------------
*/

// Check database connection
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function getCount($connection, $query)
{
    $result = mysqli_query($connection, $query);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int) ($row['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| ACCOUNT STATISTICS
|--------------------------------------------------------------------------
*/

$totalAccounts = getCount(
    $connection,
    "SELECT COUNT(*) AS total FROM accounts"
);

$totalDoctors = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE role = 'doctor'"
);

$totalSecretaries = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE role = 'secretary'"
);

$totalPatients = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE role = 'patient'"
);

$totalAdmins = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE role = 'admin'"
);


/*
|--------------------------------------------------------------------------
| ACCOUNT STATUS
|--------------------------------------------------------------------------
*/

$activeAccounts = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE activity_status = 'active'"
);

$inactiveAccounts = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE activity_status = 'inactive'"
);


/*
|--------------------------------------------------------------------------
| VERIFICATION STATUS
|--------------------------------------------------------------------------
*/

$verifiedAccounts = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE verification_status = 'verified'"
);

$unverifiedAccounts = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE verification_status = 'unverified'"
);

$verifyingAccounts = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM accounts
     WHERE verification_status = 'verifying'"
);


/*
|--------------------------------------------------------------------------
| APPOINTMENT STATISTICS
|--------------------------------------------------------------------------
*/

$totalAppointments = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM appointments"
);

$completedAppointments = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM appointments
     WHERE status = 'completed'"
);

$cancelledAppointments = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM appointments
     WHERE status = 'cancelled'"
);


/*
|--------------------------------------------------------------------------
| UPCOMING APPOINTMENTS
|--------------------------------------------------------------------------
*/

$upcomingAppointments = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM appointments
     WHERE date >= CURDATE()
     AND status NOT IN ('completed', 'cancelled')"
);


/*
|--------------------------------------------------------------------------
| TODAY'S APPOINTMENTS
|--------------------------------------------------------------------------
*/

$todayAppointments = getCount(
    $connection,
    "SELECT COUNT(*) AS total
     FROM appointments
     WHERE date = CURDATE()"
);


/*
|--------------------------------------------------------------------------
| RECENT ACCOUNTS
|--------------------------------------------------------------------------
*/

$recentAccounts = [];

$query = "
    SELECT
        account_id,
        first_name,
        last_name,
        e_mail,
        role,
        activity_status,
        verification_status
    FROM accounts
    ORDER BY account_id DESC
    LIMIT 5
";

$result = mysqli_query($connection, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $recentAccounts[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| RECENT APPOINTMENTS
|--------------------------------------------------------------------------
*/

$recentAppointments = [];

$query = "
    SELECT *
    FROM appointments
    ORDER BY appointment_id DESC
    LIMIT 5
";

$result = mysqli_query($connection, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $recentAppointments[] = $row;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/style.css">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

</head>

<body class="admin-page">

<h1>Administrator Dashboard</h1>

<hr>


<!-- =========================================================
     ACCOUNT STATISTICS
========================================================= -->

<h2>Account Statistics</h2>

<p>
    Total Accounts:
    <strong><?= $totalAccounts ?></strong>
</p>

<p>
    Administrators:
    <strong><?= $totalAdmins ?></strong>
</p>

<p>
    Doctors:
    <strong><?= $totalDoctors ?></strong>
</p>

<p>
    Secretaries:
    <strong><?= $totalSecretaries ?></strong>
</p>

<p>
    Patients:
    <strong><?= $totalPatients ?></strong>
</p>


<!-- =========================================================
     ACCOUNT STATUS
========================================================= -->

<h2>Account Status</h2>

<p>
    Active Accounts:
    <strong><?= $activeAccounts ?></strong>
</p>

<p>
    Inactive Accounts:
    <strong><?= $inactiveAccounts ?></strong>
</p>


<!-- =========================================================
     VERIFICATION
========================================================= -->

<h2>Verification Status</h2>

<p>
    Verified:
    <strong><?= $verifiedAccounts ?></strong>
</p>

<p>
    Unverified:
    <strong><?= $unverifiedAccounts ?></strong>
</p>

<p>
    Currently Verifying:
    <strong><?= $verifyingAccounts ?></strong>
</p>


<!-- =========================================================
     APPOINTMENT STATISTICS
========================================================= -->

<h2>Appointment Statistics</h2>

<p>
    Total Appointments:
    <strong><?= $totalAppointments ?></strong>
</p>

<p>
    Today's Appointments:
    <strong><?= $todayAppointments ?></strong>
</p>

<p>
    Upcoming Appointments:
    <strong><?= $upcomingAppointments ?></strong>
</p>

<p>
    Completed Appointments:
    <strong><?= $completedAppointments ?></strong>
</p>

<p>
    Cancelled Appointments:
    <strong><?= $cancelledAppointments ?></strong>
</p>


<hr>


<!-- =========================================================
     RECENT ACCOUNTS
========================================================= -->

<h2>Recent Accounts</h2>

<?php if (count($recentAccounts) > 0): ?>

<table border="1" cellpadding="5">

    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Verification</th>
    </tr>

    <?php foreach ($recentAccounts as $account): ?>

        <tr>

            <td>
                <?= htmlspecialchars($account['account_id']) ?>
            </td>

            <td>
                <?= htmlspecialchars(
                    trim(
                        $account['first_name'] . ' ' .
                        $account['last_name']
                    )
                ) ?>
            </td>

            <td>
                <?= htmlspecialchars($account['e_mail']) ?>
            </td>

            <td>
                <?= htmlspecialchars($account['role']) ?>
            </td>

            <td>
                <?= htmlspecialchars($account['activity_status']) ?>
            </td>

            <td>
                <?= htmlspecialchars(
                    $account['verification_status']
                ) ?>
            </td>

        </tr>

    <?php endforeach; ?>

</table>

<?php else: ?>

<p>
    No accounts found.
</p>

<?php endif; ?>


<hr>


<!-- =========================================================
     RECENT APPOINTMENTS
========================================================= -->

<h2>Recent Appointments</h2>

<?php if (count($recentAppointments) > 0): ?>

<table border="1" cellpadding="5">

    <tr>

        <th>ID</th>

        <?php if (isset($recentAppointments[0]['patient_id'])): ?>
            <th>Patient ID</th>
        <?php endif; ?>

        <?php if (isset($recentAppointments[0]['doctor_id'])): ?>
            <th>Doctor ID</th>
        <?php endif; ?>

        <?php if (isset($recentAppointments[0]['date'])): ?>
            <th>Date</th>
        <?php endif; ?>

        <?php if (isset($recentAppointments[0]['time'])): ?>
            <th>Time</th>
        <?php endif; ?>

        <?php if (isset($recentAppointments[0]['status'])): ?>
            <th>Status</th>
        <?php endif; ?>

    </tr>


    <?php foreach ($recentAppointments as $appointment): ?>

        <tr>

            <td>
                <?= htmlspecialchars(
                    $appointment['appointment_id'] ?? ''
                ) ?>
            </td>


            <?php if (isset($recentAppointments[0]['patient_id'])): ?>

                <td>
                    <?= htmlspecialchars(
                        $appointment['patient_id'] ?? ''
                    ) ?>
                </td>

            <?php endif; ?>


            <?php if (isset($recentAppointments[0]['doctor_id'])): ?>

                <td>
                    <?= htmlspecialchars(
                        $appointment['doctor_id'] ?? ''
                    ) ?>
                </td>

            <?php endif; ?>


            <?php if (isset($recentAppointments[0]['date'])): ?>

                <td>
                    <?= htmlspecialchars(
                        $appointment['date'] ?? ''
                    ) ?>
                </td>

            <?php endif; ?>


            <?php if (isset($recentAppointments[0]['time'])): ?>

                <td>
                    <?= htmlspecialchars(
                        $appointment['time'] ?? ''
                    ) ?>
                </td>

            <?php endif; ?>


            <?php if (isset($recentAppointments[0]['status'])): ?>

                <td>
                    <?= htmlspecialchars(
                        $appointment['status'] ?? ''
                    ) ?>
                </td>

            <?php endif; ?>

        </tr>

    <?php endforeach; ?>

</table>

<?php else: ?>

<p>
    No appointments found.
</p>

<?php endif; ?>


<hr>


<!-- =========================================================
     ADMIN FUNCTIONS
========================================================= -->

<h2>Admin Functions</h2>

<ul>

    <li>
        <a href="admin_read_account.php">
            Manage Accounts
        </a>
    </li>

    <li>
        <a href="admin_add_account.php">
            Add Account
        </a>
    </li>

    <li>
        <a href="admin_read_room.php">
            View Rooms
        </a>
    </li>

    <li>
        <a href="admin_add_room.php">
            Assign Room
        </a>
    </li>

    <li>
        <a href="admin_add_specialty.php">
            Manage Doctor Specialty
        </a>
    </li>

    <li>
        <a href="change_password.php">
            Change Password
        </a>
    </li>

    <li>
        <a href="export_db.php">
            Export Database
        </a>
    </li>

    <li>
        <a href="../../sign_out.php">
            Sign Out
        </a>
    </li>
</ul>

</body>

</html>
