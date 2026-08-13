<<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../data/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['e_mail']);
    $phone = trim($_POST['phone_number']);
    $passwordPlain = $_POST['password'];
    $role = trim($_POST['role']);

    $specialty = trim($_POST['specialty'] ?? '');
    $department = trim($_POST['department'] ?? '');

    /* -----------------------------
       Basic Validation
    ------------------------------ */

    if (
        $firstName === '' ||
        $lastName === '' ||
        $email === '' ||
        $passwordPlain === '' ||
        $role === ''
    ) {
        die("All required fields must be filled.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }

    if (strlen($passwordPlain) < 8) {
        die("Password must be at least 8 characters long.");
    }

    if ($role === 'doctor' && $specialty === '') {
        die("Doctor accounts require a specialty.");
    }

    if ($role === 'secretary' && $department === '') {
        die("Secretary accounts require a department.");
    }

    /* -----------------------------
       Check Duplicate Email
    ------------------------------ */

    $check = mysqli_prepare($connection,
        "SELECT account_id
         FROM accounts
         WHERE e_mail = ?"
    );

    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        die("An account with this email already exists.");
    }

    mysqli_stmt_close($check);

    $password = password_hash($passwordPlain, PASSWORD_DEFAULT);

    mysqli_begin_transaction($connection);

    try {

        $stmt = mysqli_prepare(
            $connection,
            "INSERT INTO accounts
            (first_name,last_name,phone_number,e_mail,password,role,activity_status,verification_status)
            VALUES
            (?,?,?,?,?,?, 'active','verified')"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssss",
            $firstName,
            $lastName,
            $phone,
            $email,
            $password,
            $role
        );

        mysqli_stmt_execute($stmt);

        $accountId = mysqli_insert_id($connection);

        if ($role === 'doctor') {

            $doctor = mysqli_prepare(
                $connection,
                "INSERT INTO doctors(account_id,specialty)
                 VALUES (?,?)"
            );

            mysqli_stmt_bind_param(
                $doctor,
                "is",
                $accountId,
                $specialty
            );

            mysqli_stmt_execute($doctor);
        }

        if ($role === 'secretary') {

            $secretary = mysqli_prepare(
                $connection,
                "INSERT INTO secretaries(account_id,department)
                 VALUES (?,?)"
            );

            mysqli_stmt_bind_param(
                $secretary,
                "is",
                $accountId,
                $department
            );

            mysqli_stmt_execute($secretary);
        }

        if ($role === 'patient') {

            $patient = mysqli_prepare(
                $connection,
                "INSERT INTO patients(account_id)
                 VALUES (?)"
            );

            mysqli_stmt_bind_param(
                $patient,
                "i",
                $accountId
            );

            mysqli_stmt_execute($patient);
        }

        mysqli_commit($connection);

        header("Location: admin_read_account.php?msg=account_created");
        exit();

    } catch (Exception $e) {

        mysqli_rollback($connection);

        header("Location: admin_read_account.php?error=creation_failed");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLS · Add Account</title>
</head>
<body>
    <h1>Create New Account</h1>
    <form method="POST" action="admin_add_account.php">
        <label>First Name: <input type="text" name="first_name" required></label><br><br>
        <label>Last Name: <input type="text" name="last_name" required></label><br><br>
        <label>Email: <input type="email" name="e_mail" required></label><br><br>
        <label>Phone Number: <input type="text" name="phone_number"></label><br><br>
        <label>Password: <input type="password" name="password" required></label><br><br>
        <label>Role: 
            <select name="role" required>
                <option value="patient">Patient</option>
                <option value="doctor">Doctor</option>
                <option value="secretary">Secretary</option>
                <option value="admin">Admin</option>
            </select>
        </label><br><br>
        <label>Specialty (Doctor only): <input type="text" name="specialty"></label><br><br>
        <label>Department (Secretary only): <input type="text" name="department"></label><br><br>
        <button type="submit">Create Account</button>
    </form>
</body>
</html>
