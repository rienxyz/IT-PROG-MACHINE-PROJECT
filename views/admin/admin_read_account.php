<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
require_once '../db.php';

$result = mysqli_query($connection, "SELECT account_id, first_name, last_name, e_mail, role, activity_status, verification_status FROM accounts ORDER BY account_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLS · Account Management</title>
</head>
<body>
    <h1>Account Management (Admin)</h1>
    <p><a href="admin_dashboard.php">← Back to Dashboard</a> | <a href="admin_add_account.php">+ Add New Account</a></p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Account ID</th>
                <th>Name</th>
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
                    <tr>
                        <td><?= $user['account_id'] ?></td>
                        <td><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars($user['e_mail']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($user['role'] ?? 'Unassigned')) ?></td>
                        <td><?= htmlspecialchars(ucfirst($user['activity_status'] ?? 'inactive')) ?></td>
                        <td><?= htmlspecialchars(ucfirst($user['verification_status'] ?? 'unverified')) ?></td>
                        <td>
                            <a href="admin_set_account_status.php?account_id=<?= $user['account_id'] ?>&current_status=<?= $user['activity_status'] ?>">
                                <?= ($user['activity_status'] === 'active') ? 'Suspend' : 'Activate' ?>
                            </a> · 
                            <a href="admin_change_pw.php?account_id=<?= $user['account_id'] ?>">Reset Password</a> · 
                            <a href="admin_delete_account.php?account_id=<?= $user['account_id'] ?>&role=<?= $user['role'] ?>" onclick="return confirm('Delete this account permanently?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No accounts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>