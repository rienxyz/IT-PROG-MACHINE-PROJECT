<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php?error=unauthorized");
    exit();
}

$allowedRoles = ['secretary', 'admin'];

if (!in_array($_SESSION['role'], $allowedRoles, true)) {
    header("Location: ../../sign_in.php?error=unauthorized");
    exit();
}

require_once __DIR__ . '/../../data/connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

$accountId = (int) $_SESSION['account_id'];

$stmt = mysqli_prepare(
    $connection,
    "
    SELECT
        s.secretary_id,
        s.department,
        a.first_name,
        a.last_name,
        a.e_mail,
        a.phone_number
    FROM secretaries s
    INNER JOIN accounts a ON a.account_id = s.account_id
    WHERE s.account_id = ?
    "
);

mysqli_stmt_bind_param($stmt, "i", $accountId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$secretary = mysqli_fetch_assoc($result);

if (!$secretary && $_SESSION['role'] !== 'admin') {
    die("Secretary record not found for your account.");
}

$_SESSION['secretary_id'] = $secretary['secretary_id'] ?? null;
$_SESSION['department'] = $secretary['department'] ?? null;
?>