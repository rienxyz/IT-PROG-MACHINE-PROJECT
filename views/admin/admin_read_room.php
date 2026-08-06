<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
require_once '../db.php';

$query = "SELECT DISTINCT room_number, COUNT(appointment_id) as total_appointments FROM appointments WHERE room_number IS NOT NULL AND room_number != '' GROUP BY room_number";
$result = mysqli_query($connection, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLS · Room Overview</title>
</head>
<body>
    <h1>Room Overview</h1>
    <p><a href="admin_dashboard.php">← Back to Dashboard</a> | <a href="admin_add_room.php">+ Assign Room</a></p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Room Number</th>
                <th>Total Appointments Assigned</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($room = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($room['room_number']) ?></td>
                        <td><?= $room['total_appointments'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2">No active rooms found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>