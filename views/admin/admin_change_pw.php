<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../sign_in.php?error=unauthorized");
    exit();
}
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetAccountId = (int)$_POST['account_id'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($connection, "UPDATE accounts SET password = ? WHERE account_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $newPassword, $targetAccountId);
    mysqli_stmt_execute($stmt);

    header("Location: admin_read_account.php?msg=password_reset");
    exit();
}

$targetAccountId = $_GET['account_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLS · Reset Password</title>
</head>
<body>
    <h1>Reset User Password</h1>
    <form method="POST" action="admin_change_pw.php">
        <input type="hidden" name="account_id" value="<?= htmlspecialchars($targetAccountId) ?>">
        <label>New Password: <input type="password" name="new_password" required></label><br><br>
        <button type="submit">Update Password</button>
    </form>
</body>
</html>