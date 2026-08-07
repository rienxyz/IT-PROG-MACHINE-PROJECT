<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../sign_in.php?error=unauthorized");
    exit();
}
require_once '../db.php';

if (isset($_GET['account_id']) && isset($_GET['current_status'])) {
    $accountId = (int)$_GET['account_id'];
    $newStatus = ($_GET['current_status'] === 'active') ? 'inactive' : 'active';

    $stmt = mysqli_prepare($connection, "UPDATE accounts SET activity_status = ? WHERE account_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $accountId);
    mysqli_stmt_execute($stmt);
}

header("Location: admin_read_account.php");
exit();
?>