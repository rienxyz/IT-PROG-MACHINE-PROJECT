<?php
require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

require_once __DIR__ . '/secretary_auth.php';

$allowedStatuses = [
    'pending',
    'confirmed',
    'rescheduled',
    'completed',
    'cancelled'
];

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');
$doctorFilter = (int) ($_GET['doctor_id'] ?? 0);

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
        pa.first_name AS patient_first_name,
        pa.last_name AS patient_last_name,
        pa.e_mail AS patient_email,
        da.first_name AS doctor_first_name,
        da.last_name AS doctor_last_name,
        da.e_mail AS doctor_email
    FROM appointments ap
    INNER JOIN patients p ON p.patient_id = ap.patient_id
    INNER JOIN accounts pa ON pa.account_id = p.account_id
    INNER JOIN doctors d ON d.doctor_id = ap.doctor_id
    INNER JOIN accounts da ON da.account_id = d.account_id
    WHERE 1 = 1
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= "
        AND (
            pa.first_name LIKE ?
            OR pa.last_name LIKE ?
            OR pa.e_mail LIKE ?
            OR da.first_name LIKE ?
            OR da.last_name LIKE ?
            OR da.e_mail LIKE ?
            OR ap.insurance LIKE ?
        )
    ";
    $searchValue = '%' . $search . '%';
    for ($i = 0; $i < 7; $i++) {
        $params[] = $searchValue;
    }
    $types .= "sssssss";
}

if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= " AND ap.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($dateFilter !== '') {
    $sql .= " AND ap.date = ?";
    $params[] = $dateFilter;
    $types .= "s";
}

if ($doctorFilter > 0) {
    $sql .= " AND ap.doctor_id = ?";
    $params[] = $doctorFilter;
    $types .= "i";
}

$sql .= " ORDER BY ap.date DESC, ap.time ASC";

$stmt = mysqli_prepare($connection, $sql);

if (!$stmt) {
    die("Failed to prepare appointment query: " . mysqli_error($connection));
}

if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

if (!mysqli_stmt_execute($stmt)) {
    die("Failed to retrieve appointments: " . mysqli_error($connection));
}

$appointmentResult = mysqli_stmt_get_result($stmt);

$doctors = [];
$doctorQuery = "
    SELECT
        d.doctor_id,
        a.first_name,
        a.last_name
    FROM doctors d
    INNER JOIN accounts a ON a.account_id = d.account_id
    ORDER BY a.last_name ASC
";

$doctorResult = mysqli_query($connection, $doctorQuery);
if ($doctorResult) {
    while ($doctor = mysqli_fetch_assoc($doctorResult)) {
        $doctors[] = $doctor;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secretary · Appointments</title>
</head>
<body>

<h1>Appointment Management</h1>

<p>
    <a href="secretary_dashboard.php">← Back to Dashboard</a>
    |
    <a href="secretary_add_appointment.php">+ Schedule New Appointment</a>
</p>

<hr>

<h2>Search & Filter Appointments</h2>

<form method="GET" action="secretary_read_appointment.php">
    <p>
        <label>Search:
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Patient, doctor, or insurance">
        </label>
    </p>

    <p>
        <label>Status:
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach ($allowedStatuses as $status): ?>
                    <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                        <?= ucfirst($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <p>
        <label>Date:
            <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>">
        </label>
    </p>

    <p>
        <label>Doctor:
            <select name="doctor_id">
                <option value="0">All Doctors</option>
                <?php foreach ($doctors as $doctor): ?>
                    <option value="<?= $doctor['doctor_id'] ?>" <?= $doctorFilter === (int) $doctor['doctor_id'] ? 'selected' : '' ?>>
                        Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <button type="submit">Search</button>
    <a href="secretary_read_appointment.php">Clear Filters</a>
</form>

<hr>

<h2>Appointments</h2>

<?php if (mysqli_num_rows($appointmentResult) === 0): ?>
    <p>No appointments found.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
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
            <?php while ($appointment = mysqli_fetch_assoc($appointmentResult)): ?>
                <tr>
                    <td><?= (int) $appointment['appointment_id'] ?></td>
                    <td>
                        <?= htmlspecialchars(
                            $appointment['patient_first_name'] . ' ' .
                            $appointment['patient_last_name']
                        ) ?>
                    </td>
                    <td>
                        Dr. <?= htmlspecialchars(
                            $appointment['doctor_first_name'] . ' ' .
                            $appointment['doctor_last_name']
                        ) ?>
                    </td>
                    <td><?= htmlspecialchars($appointment['insurance']) ?></td>
                    <td><?= htmlspecialchars($appointment['room_number'] ?? '') ?></td>
                    <td><?= htmlspecialchars($appointment['date']) ?></td>
                    <td><?= htmlspecialchars(substr($appointment['time'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars(ucfirst($appointment['status'])) ?></td>
                    <td>
                        <a href="secretary_edit_appointment.php?appointment_id=<?= $appointment['appointment_id'] ?>">
                            Edit
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>