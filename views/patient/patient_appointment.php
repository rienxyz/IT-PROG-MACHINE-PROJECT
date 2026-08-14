<?php
session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../../sign_in.php");
    exit();
}

require_once __DIR__ . './../../data/connection.php'; 

$account_id = $_SESSION['account_id'];
$message = "";
$selected_doctor = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

$patient_id = 0;
$p_query = "SELECT patient_id, insurance FROM patients WHERE account_id = ?";
if ($p_stmt = mysqli_prepare($connection, $p_query)) {
    mysqli_stmt_bind_param($p_stmt, "i", $account_id);
    mysqli_stmt_execute($p_stmt);
    $p_res = mysqli_stmt_get_result($p_stmt);
    if ($p_row = mysqli_fetch_assoc($p_res)) {
        $patient_id = $p_row['patient_id'];
        $default_insurance = $p_row['insurance'];
    }
    mysqli_stmt_close($p_stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $insurance = trim($_POST['insurance']);
    $room_number = trim($_POST['room_number']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $status = 'confirmed';

    $insert_query = "INSERT INTO appointments (patient_id, doctor_id, insurance, room_number, date, time, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($connection, $insert_query)) {
        mysqli_stmt_bind_param($stmt, "iisssss", $patient_id, $doctor_id, $insurance, $room_number, $date, $time, $status);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Appointment successfully booked!";
        } else {
            $message = "Error booking appointment: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

$doctors = [];
$doc_query = "SELECT d.doctor_id, acc.first_name, acc.last_name, d.specialty 
              FROM doctors d 
              JOIN accounts acc ON d.account_id = acc.account_id";
$doc_result = mysqli_query($connection, $doc_query);
if ($doc_result) {
    while ($row = mysqli_fetch_assoc($doc_result)) {
        $doctors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="../../styles/placeholder.css">
</head>
<body>
    <nav>
        <a href="patient_dashboard.php">Dashboard</a>
        <a href="../../sign_out.php">Sign Out</a>
    </nav>

    <main>
        <h2>Book a New Appointment</h2>
        
        <?php if ($message): ?>
            <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
        <?php endif; ?>

        <form action="patient_appointment.php" method="POST">
            <div>
                <label for="doctor_id">Select Doctor:</label><br>
                <select name="doctor_id" id="doctor_id" required>
                    <option value="">-- Choose a Doctor --</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?php echo $doc['doctor_id']; ?>" <?php echo ($selected_doctor === (int)$doc['doctor_id']) ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name'] . ' (' . $doc['specialty'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <br>

            <div>
                <label for="insurance">Insurance Provider:</label><br>
                <input type="text" name="insurance" id="insurance" value="<?php echo htmlspecialchars($default_insurance ?? ''); ?>" required>
            </div>
            <br>

            <div>
                <label for="room_number">Room Number:</label><br>
                <input type="text" name="room_number" id="room_number" placeholder="e.g. 101" required>
            </div>
            <br>

            <div>
                <label for="date">Date:</label><br>
                <input type="date" name="date" id="date" required>
            </div>
            <br>

            <div>
                <label for="time">Time:</label><br>
                <input type="time" name="time" id="time" required>
            </div>
            <br>

            <button type="submit" name="book_appointment">Book Now</button>
        </form>
    </main>
</body>
</html>
