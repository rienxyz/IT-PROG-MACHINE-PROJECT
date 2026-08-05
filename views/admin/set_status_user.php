<?php
require_once 'check_session.php';
require_once '../db.php';

if (isset($_GET['user_id']) && isset($_GET['current_status'])) {
    $userId = (int)$_GET['user_id'];
    
    
    $newStatus = ($_GET['current_status'] === 'active') ? 'inactive' : 'active';

    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE user_id = ?");
    $stmt->execute([$newStatus, $userId]);
}


header("Location: read_user.php");
exit();
?>