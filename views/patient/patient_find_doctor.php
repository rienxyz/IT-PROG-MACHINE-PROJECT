<?php
session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php");
    exit();
}

require_once __DIR__ . './../../data/connection.php'; 

$doctors = [];
$search_term = "";
$error_message = "";

$query = "SELECT d.doctor_id, acc.first_name, acc.last_name, acc.e_mail, acc.phone_number, d.specialty 
          FROM doctors d
          JOIN accounts acc ON d.account_id = acc.account_id 
          WHERE acc.role = 'doctor'";

if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
    $query .= " AND (acc.first_name LIKE ? OR acc.last_name LIKE ? OR d.specialty LIKE ?)";
}

$query .= " ORDER BY acc.last_name ASC";

if ($stmt = mysqli_prepare($connection, $query)) {
    if (!empty($search_term)) {
        $like_term = "%" . $search_term . "%";
        mysqli_stmt_bind_param($stmt, "sss", $like_term, $like_term, $like_term);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $doctors[] = $row;
        }
    } else {
        $error_message = "Error fetching doctors: " . mysqli_error($connection);
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Find a Doctor</title>
    <link rel="stylesheet" href="../../styles/placeholder.css">
</head>
<body>
    <nav>
        <a href="patient_dashboard.php">Dashboard</a>
        <a href="patient_profile.php">Profile</a>
        <a href="patient_appointment.php">Book Appointment</a>
        <a href="../../sign_out.php">Sign Out</a>
    </nav>

    <main>
        <h2>Find a Doctor</h2>
        
        <form action="patient_find_doctor.php" method="GET" style="margin-bottom: 20px;">
            <input type="text" name="search" placeholder="Search by name or specialty..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit">Search</button>
            <a href="patient_find_doctor.php"><button type="button">Clear</button></a>
        </form>

        <?php if (!empty($error_message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif (count($doctors) > 0): ?>
            <table border="1">
                <tr>
                    <th>Doctor Name</th>
                    <th>Specialty</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($doctors as $doc): ?>
                    <tr>
                        <td>Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($doc['specialty']); ?></td>
                        <td><?php echo htmlspecialchars($doc['phone_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($doc['e_mail']); ?></td>
                        <td>
                            <a href="patient_appointment.php?doctor_id=<?php echo $doc['doctor_id']; ?>">Book</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No doctors found matching your search.</p>
        <?php endif; ?>
    </main>
</body>
</html>
