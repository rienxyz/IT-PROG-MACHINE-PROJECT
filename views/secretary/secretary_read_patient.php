<?php
require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

require_once __DIR__ . '/secretary_auth.php';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        p.patient_id,
        a.account_id,
        a.first_name,
        a.last_name,
        a.e_mail,
        a.phone_number,
        a.activity_status,
        a.verification_status,
        (SELECT COUNT(*) FROM appointments WHERE patient_id = p.patient_id) AS appointment_count
    FROM patients p
    INNER JOIN accounts a ON a.account_id = p.account_id
    WHERE a.role = 'patient'
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.e_mail LIKE ? OR a.phone_number LIKE ?)";
    $searchValue = '%' . $search . '%';
    for ($i = 0; $i < 4; $i++) {
        $params[] = $searchValue;
    }
    $types .= "ssss";
}

$sql .= " ORDER BY a.last_name ASC, a.first_name ASC";

$stmt = mysqli_prepare($connection, $sql);
if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$patientResult = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secretary · Patients</title>
</head>
<body>

<h1>Patient Records</h1>

<p>
    <a href="secretary_dashboard.php">← Back to Dashboard</a>
</p>

<h2>Search Patients</h2>
<form method="GET" action="secretary_read_patient.php">
    <p>
        <label>Search:
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Name, email, or phone">
        </label>
        <button type="submit">Search</button>
        <a href="secretary_read_patient.php">Clear</a>
    </p>
</form>

<hr>

<h2>Patients</h2>

<?php if (mysqli_num_rows($patientResult) === 0): ?>
    <p>No patients found.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Appointments</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($patient = mysqli_fetch_assoc($patientResult)): ?>
                <tr>
                    <td><?= $patient['patient_id'] ?></td>
                    <td>
                        <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($patient['e_mail']) ?></td>
                    <td><?= htmlspecialchars($patient['phone_number'] ?? '') ?></td>
                    <td><?= htmlspecialchars(ucfirst($patient['activity_status'])) ?></td>
                    <td><?= $patient['appointment_count'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>