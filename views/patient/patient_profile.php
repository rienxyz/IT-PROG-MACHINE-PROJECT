<?php
session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php");
    exit();
}

require_once __DIR__ . './../../data/connection.php'; 

$account_id = $_SESSION['account_id'];
$message = "";

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $e_mail = trim($_POST['e_mail']);
    $insurance = trim($_POST['insurance']);
    $preferred_specialty = trim($_POST['preferred_specialty']);
    
    // Update accounts table
    $acc_query = "UPDATE accounts SET first_name = ?, last_name = ?, phone_number = ?, e_mail = ? WHERE account_id = ?";
    if ($stmt = mysqli_prepare($connection, $acc_query)) {
        mysqli_stmt_bind_param($stmt, "ssssi", $first_name, $last_name, $phone_number, $e_mail, $account_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Update patients table
    $pat_query = "UPDATE patients SET insurance = ?, preferred_specialty = ? WHERE account_id = ?";
    if ($stmt = mysqli_prepare($connection, $pat_query)) {
        mysqli_stmt_bind_param($stmt, "ssi", $insurance, $preferred_specialty, $account_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Profile updated successfully.";
        } else {
            $message = "Error updating patient profile: " . mysqli_error($connection);
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch user data across accounts and patients tables
$user_data = [];
$fetch_query = "SELECT acc.first_name, acc.last_name, acc.phone_number, acc.e_mail, p.insurance, p.preferred_specialty 
               FROM accounts acc 
               LEFT JOIN patients p ON acc.account_id = p.account_id 
               WHERE acc.account_id = ?";

if ($stmt = mysqli_prepare($connection, $fetch_query)) {
    mysqli_stmt_bind_param($stmt, "i", $account_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $user_data = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="../../styles/style.css">
</head>
<body class="patient-page">
    <nav>
        <a href="patient_dashboard.php">Dashboard</a>
        <a href="patient_find_doctor.php">Find Doctor</a>
        <a href="../../sign_out.php">Sign Out</a>
    </nav>

    <main>
        <h2>My Profile</h2>
        
        <?php if ($message): ?>
            <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
        <?php endif; ?>

        <form action="patient_profile.php" method="POST">
            <div>
                <label for="first_name">First Name:</label><br>
                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
            </div>
            <br>

            <div>
                <label for="last_name">Last Name:</label><br>
                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
            </div>
            <br>

            <div>
                <label for="phone_number">Phone Number:</label><br>
                <input type="text" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>">
            </div>
            <br>

            <div>
                <label for="e_mail">Email Address:</label><br>
                <input type="email" name="e_mail" id="e_mail" value="<?php echo htmlspecialchars($user_data['e_mail'] ?? ''); ?>" required>
            </div>
            <br>

            <div>
                <label for="insurance">Insurance Provider:</label><br>
                <input type="text" name="insurance" id="insurance" value="<?php echo htmlspecialchars($user_data['insurance'] ?? ''); ?>">
            </div>
            <br>

            <div>
                <label for="preferred_specialty">Preferred Specialty:</label><br>
                <input type="text" name="preferred_specialty" id="preferred_specialty" value="<?php echo htmlspecialchars($user_data['preferred_specialty'] ?? ''); ?>">
            </div>
            <br>

            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </main>
</body>
</html>
