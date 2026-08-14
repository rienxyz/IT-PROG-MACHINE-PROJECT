<?php
require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

require_once __DIR__ . '/secretary_auth.php';

$search = trim($_GET['search'] ?? '');
$specialtyFilter = trim($_GET['specialty'] ?? '');

$specialties = [];
$specQuery = "SELECT DISTINCT specialty FROM doctors WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty";
$specResult = mysqli_query($connection, $specQuery);
while ($spec = mysqli_fetch_assoc($specResult)) {
    $specialties[] = $spec['specialty'];
}

$sql = "
    SELECT
        d.doctor_id,
        d.specialty,
        a.account_id,
        a.first_name,
        a.last_name,
        a.e_mail,
        a.phone_number,
        a.activity_status,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.doctor_id) AS appointment_count,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.doctor_id AND date >= CURDATE()) AS upcoming_count
    FROM doctors d
    INNER JOIN accounts a ON a.account_id = d.account_id
    WHERE a.role = 'doctor'
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.e_mail LIKE ?)";
    $searchValue = '%' . $search . '%';
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $types .= "sss";
}

if ($specialtyFilter !== '') {
    $sql .= " AND d.specialty = ?";
    $params[] = $specialtyFilter;
    $types .= "s";
}

$sql .= " ORDER BY a.last_name ASC, a.first_name ASC";

$stmt = mysqli_prepare($connection, $sql);
if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$doctorResult = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secretary · Doctors</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>
<body>

<h1>Doctor Records</h1>

<p>
    <a href="secretary_dashboard.php">← Back to Dashboard</a>
</p>

<h2>Search & Filter</h2>
<form method="GET" action="secretary_read_doctor.php">
    <p>
        <label>Search:
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Name or email">
        </label>
    </p>

    <p>
        <label>Specialty:
            <select name="specialty">
                <option value="">All Specialties</option>
                <?php foreach ($specialties as $spec): ?>
                    <option value="<?= htmlspecialchars($spec) ?>" <?= $specialtyFilter === $spec ? 'selected' : '' ?>>
                        <?= htmlspecialchars($spec) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <button type="submit">Search</button>
    <a href="secretary_read_doctor.php">Clear</a>
</form>

<hr>

<h2>Doctors</h2>

<?php if (mysqli_num_rows($doctorResult) === 0): ?>
    <p>No doctors found.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Specialty</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Total Apps</th>
                <th>Upcoming</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($doctor = mysqli_fetch_assoc($doctorResult)): ?>
                <tr>
                    <td><?= $doctor['doctor_id'] ?></td>
                    <td>
                        Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($doctor['specialty'] ?? 'General') ?></td>
                    <td><?= htmlspecialchars($doctor['e_mail']) ?></td>
                    <td><?= htmlspecialchars($doctor['phone_number'] ?? '') ?></td>
                    <td><?= htmlspecialchars(ucfirst($doctor['activity_status'])) ?></td>
                    <td><?= $doctor['appointment_count'] ?></td>
                    <td><?= $doctor['upcoming_count'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>