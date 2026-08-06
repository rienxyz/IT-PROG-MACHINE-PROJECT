<?php
require_once 'check_session.php';
require_once '../db.php';


$stmt = $pdo->query("SELECT user_id, first_name, last_name, role, account_status FROM users ORDER BY user_id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title> MLS · User Management </title>
</head>

<body>
    <h1> User Management (Admin) </h1>
    <p> Create, remove, restrict, assign roles </p>
    
    <p><a href="admin_dashboard.php">← Back to Dashboard</a></p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th> User Name </th>
                <th> Role </th>
                <th> Status </th>
                <th> Action </th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars(ucfirst($user['role'] ?? 'Unassigned')) ?></td>
                        <td><?= htmlspecialchars(ucfirst($user['account_status'] ?? 'inactive')) ?></td>
                        <td>
                            <a href="set_status_user.php?user_id=<?= $user['user_id'] ?>&current_status=<?= $user['account_status'] ?>">
                                <?= ($user['account_status'] === 'active') ? 'Suspend' : 'Activate' ?>
                            </a> · 
                            <a href="del_user.php?user_id=<?= $user['user_id'] ?>" onclick="return confirm('Are you sure you want to delete this user completely?');"> Delete </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No users found in the system.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>