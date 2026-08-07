<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['account_id']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../../sign_in.php?error=unauthorized");
    exit();
}

require_once __DIR__ . "/../../data/connection.php";

$message = "";
$error = "";

if (isset($_GET['msg'])) {

    switch ($_GET['msg']) {

        case 'room_assigned':
            $message = "Room assigned successfully.";
            break;

        case 'updated':
            $message = "Room updated successfully.";
            break;

        case 'deleted':
            $message = "Room removed successfully.";
            break;
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$sql = "

SELECT

    room_number,

    COUNT(*) AS total_appointments,

    MIN(date) AS first_appointment,

    MAX(date) AS latest_appointment

FROM appointments

WHERE
    room_number IS NOT NULL
    AND room_number <> ''

";

$params = [];
$types = "";

if ($search !== '') {

    $sql .= "
        AND room_number LIKE ?
    ";

    $params[] = "%{$search}%";
    $types .= "s";
}

$sql .= "

GROUP BY room_number

ORDER BY room_number ASC

";

$stmt = mysqli_prepare($connection, $sql);

if (!$stmt) {

    die(
        "Failed to prepare query: " .
        mysqli_error($connection)
    );
}

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Room Overview</title>
</head>

<body>

<h1>Room Overview</h1>

<p>
    <a href="admin_dashboard.php">Back to Dashboard</a> |
    <a href="admin_add_room.php">Assign Room</a>
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
    <strong>Error:</strong>
    <?= htmlspecialchars($error) ?>
</p>

<?php endif; ?>

<hr>

<h2>Search Room</h2>

<form method="GET">

<p>

<label>

Room Number

<input
    type="text"
    name="search"
    value="<?= htmlspecialchars($search) ?>"
    placeholder="Search room">

</label>

<button type="submit">

Search

</button>

<a href="admin_read_room.php">

Clear

</a>

</p>

</form>

<hr>

<h2>Assigned Rooms</h2>

<?php if (mysqli_num_rows($result) === 0): ?>

<p>

No rooms found.

</p>

<?php else: ?>

<table
border="1"
cellpadding="8">

<thead>

<tr>

<th>Room Number</th>

<th>Total Appointments</th>

<th>First Appointment</th>

<th>Latest Appointment</th>

</tr>

</thead>

<tbody>

<?php while ($room = mysqli_fetch_assoc($result)): ?>

<tr>

<td>

<?= htmlspecialchars($room['room_number']) ?>

</td>

<td>

<?= (int)$room['total_appointments'] ?>

</td>

<td>

<?=
$room['first_appointment']
? htmlspecialchars($room['first_appointment'])
: '-'
?>

</td>

<td>

<?=
$room['latest_appointment']
? htmlspecialchars($room['latest_appointment'])
: '-'
?>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php endif; ?>

<hr>

<h3>Room Summary</h3>

<p>

This page displays every room currently assigned to appointments.

</p>

<ul>

<li>
Search rooms using the search box above.
</li>

<li>
Each room appears only once.
</li>

<li>
The total appointments column shows how many appointments use that room.
</li>

<li>
The first and latest appointment dates help identify room usage over time.
</li>

</ul>

</body>

</html>