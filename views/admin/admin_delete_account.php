<?php
require_once 'check_session.php';
require_once '../db.php';

if (isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];

    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
}


header("Location: read_user.php?msg=deleted");
exit();
?>