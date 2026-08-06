<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
require_once '../db.php';

if (isset($_GET['account_id']) && isset($_GET['role'])) {
    $accountId = (int)$_GET['account_id'];
    $role = $_GET['role'];

    mysqli_begin_transaction($connection);
    try {
        if ($role === 'doctor') {
            $stmtGetDoc = mysqli_prepare($connection, "SELECT doctor_id FROM doctors WHERE account_id = ?");
            mysqli_stmt_bind_param($stmtGetDoc, "i", $accountId);
            mysqli_stmt_execute($stmtGetDoc);
            $res = mysqli_stmt_get_result($stmtGetDoc);
            if ($doc = mysqli_fetch_assoc($res)) {
                $docId = $doc['doctor_id'];
                $delAssign = mysqli_prepare($connection, "DELETE FROM assignments WHERE doctor_id = ?");
                mysqli_stmt_bind_param($delAssign, "i", $docId);
                mysqli_stmt_execute($delAssign);

                $delDoc = mysqli_prepare($connection, "DELETE FROM doctors WHERE doctor_id = ?");
                mysqli_stmt_bind_param($delDoc, "i", $docId);
                mysqli_stmt_execute($delDoc);
            }
        } elseif ($role === 'secretary') {
            $stmtGetSec = mysqli_prepare($connection, "SELECT secretary_id FROM secretaries WHERE account_id = ?");
            mysqli_stmt_bind_param($stmtGetSec, "i", $accountId);
            mysqli_stmt_execute($stmtGetSec);
            $res = mysqli_stmt_get_result($stmtGetSec);
            if ($sec = mysqli_fetch_assoc($res)) {
                $secId = $sec['secretary_id'];
                $delAssign = mysqli_prepare($connection, "DELETE FROM assignments WHERE secretary_id = ?");
                mysqli_stmt_bind_param($delAssign, "i", $secId);
                mysqli_stmt_execute($delAssign);

                $delSec = mysqli_prepare($connection, "DELETE FROM secretaries WHERE secretary_id = ?");
                mysqli_stmt_bind_param($delSec, "i", $secId);
                mysqli_stmt_execute($delSec);
            }
        } elseif ($role === 'patient') {
            $delPat = mysqli_prepare($connection, "DELETE FROM patients WHERE account_id = ?");
            mysqli_stmt_bind_param($delPat, "i", $accountId);
            mysqli_stmt_execute($delPat);
        }

        $delAcc = mysqli_prepare($connection, "DELETE FROM accounts WHERE account_id = ?");
        mysqli_stmt_bind_param($delAcc, "i", $accountId);
        mysqli_stmt_execute($delAcc);

        mysqli_commit($connection);
        header("Location: admin_read_account.php?msg=deleted");
    } catch (Exception $e) {
        mysqli_rollback($connection);
        header("Location: admin_read_account.php?error=cannot_delete");
    }
} else {
    header("Location: admin_read_account.php");
}
exit();
?>