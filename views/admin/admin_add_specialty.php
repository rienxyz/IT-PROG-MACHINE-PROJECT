<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../data/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = (int)$_POST['doctor_id'];
    $specialty = trim($_POST['specialty']);

    $stmt = mysqli_prepare($connection, "UPDATE doctors SET specialty = ? WHERE doctor_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $specialty, $doctorId);
    mysqli_stmt_execute($stmt);

    header("Location: admin_dashboard.php?msg=specialty_updated");
    exit();
}

$query = "SELECT d.doctor_id, a.first_name, a.last_name, d.specialty FROM doctors d JOIN accounts a ON d.account_id = a.account_id";
$doctorsRes = mysqli_query($connection, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLS · Add Specialty</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
</head>
<body>
    <h1>Set Doctor Specialty</h1>
    <form method="POST" action="admin_add_specialty.php">
        <label>Select Doctor: 
            <select name="doctor_id" required>
                <?php if ($doctorsRes && mysqli_num_rows($doctorsRes) > 0): ?>
                    <?php while ($doc = mysqli_fetch_assoc($doctorsRes)): ?>
                        <option value="<?= $doc['doctor_id'] ?>">
                            Dr. <?= htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']) ?> (Current: <?= htmlspecialchars($doc['specialty'] ?? 'None') ?>)
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </label><br><br>
        <label>New Specialty: <input type="text" name="specialty" required></label><br><br>
        <button type="submit">Update Specialty</button>
    </form>
</body>
</html>
