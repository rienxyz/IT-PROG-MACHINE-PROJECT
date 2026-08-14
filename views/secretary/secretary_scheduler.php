<?php
require_once __DIR__ . '/../../data/connection.php';

$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

if ($month < 1) {
    $month = 1;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth);

$monthName = date('F Y', $firstDayOfMonth);

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$appointmentsByDay = [];

$dateStart = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
$dateEnd = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $daysInMonth;

$sql = "
    SELECT
        ap.appointment_id,
        ap.date,
        ap.time,
        ap.status,
        ap.room_number,
        pa.first_name AS patient_first_name,
        pa.last_name AS patient_last_name,
        da.first_name AS doctor_first_name,
        da.last_name AS doctor_last_name
    FROM appointments ap
    INNER JOIN patients p ON p.patient_id = ap.patient_id
    INNER JOIN accounts pa ON pa.account_id = p.account_id
    INNER JOIN doctors d ON d.doctor_id = ap.doctor_id
    INNER JOIN accounts da ON da.account_id = d.account_id
    WHERE ap.date BETWEEN ? AND ?
    AND ap.status NOT IN ('cancelled')
    ORDER BY ap.date ASC, ap.time ASC
";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "ss", $dateStart, $dateEnd);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $day = (int) substr($row['date'], 8, 2);
    if (!isset($appointmentsByDay[$day])) {
        $appointmentsByDay[$day] = [];
    }
    $appointmentsByDay[$day][] = $row;
}

$today = (int) date('j');
$currentMonth = (int) date('m');
$currentYear = (int) date('Y');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary · Appointment Scheduler</title>
    <link rel="stylesheet" href="../../styles/style.css"></link>
    <style>
        table.calendar {
            border-collapse: collapse;
            width: 100%;
            max-width: 1200px;
        }
        table.calendar th, table.calendar td {
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
            width: 14.28%;
            height: 100px;
        }
        table.calendar th {
            background-color: #f0f0f0;
            text-align: center;
        }
        table.calendar td {
            background-color: #fff;
        }
        table.calendar td.empty {
            background-color: #f9f9f9;
        }
        table.calendar td.today {
            background-color: #e8f4fd;
            border-color: #2196F3;
        }
        .day-number {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
        }
        .appointment-item {
            font-size: 11px;
            padding: 2px 4px;
            margin-bottom: 2px;
            background-color: #e3f2fd;
            border-radius: 3px;
            border-left: 3px solid #2196F3;
            cursor: pointer;
        }
        .appointment-item:hover {
            background-color: #bbdefb;
        }
        .appointment-item.pending {
            border-left-color: #ff9800;
            background-color: #fff3e0;
        }
        .appointment-item.confirmed {
            border-left-color: #4caf50;
            background-color: #e8f5e9;
        }
        .appointment-item.completed {
            border-left-color: #9e9e9e;
            background-color: #f5f5f5;
        }
        .appointment-item.rescheduled {
            border-left-color: #2196f3;
            background-color: #e3f2fd;
        }
        .appointment-time {
            font-weight: bold;
        }
        .nav-links {
            margin: 15px 0;
        }
        .nav-links a {
            padding: 8px 16px;
            text-decoration: none;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin: 0 5px;
        }
        .nav-links a:hover {
            background-color: #ddd;
        }
        .month-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }
        .legend {
            margin: 15px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .legend span {
            display: inline-block;
            padding: 4px 12px;
            margin-right: 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .legend .pending { background-color: #fff3e0; border-left: 3px solid #ff9800; }
        .legend .confirmed { background-color: #e8f5e9; border-left: 3px solid #4caf50; }
        .legend .completed { background-color: #f5f5f5; border-left: 3px solid #9e9e9e; }
        .legend .rescheduled { background-color: #e3f2fd; border-left: 3px solid #2196f3; }
    </style>
</head>
<body>

<h1>Appointment Scheduler</h1>

<p>
    <a href="secretary_dashboard.php">← Back to Dashboard</a>
    |
    <a href="secretary_add_appointment.php">+ Schedule New Appointment</a>
    |
    <a href="secretary_read_appointment.php">View All Appointments</a>
</p>

<div class="nav-links">
    <a href="secretary_scheduler.php?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">&laquo; Previous</a>
    <span class="month-title"><?= $monthName ?></span>
    <a href="secretary_scheduler.php?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">Next &raquo;</a>
    <a href="secretary_scheduler.php" style="background-color:#2196F3;color:white;">Today</a>
</div>

<div class="legend">
    <strong>Status Legend:</strong>
    <span class="pending">Pending</span>
    <span class="confirmed">Confirmed</span>
    <span class="rescheduled">Rescheduled</span>
    <span class="completed">Completed</span>
</div>

<table class="calendar">
    <thead>
        <tr>
            <th>Sunday</th>
            <th>Monday</th>
            <th>Tuesday</th>
            <th>Wednesday</th>
            <th>Thursday</th>
            <th>Friday</th>
            <th>Saturday</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <?php
            $dayCount = 1;
            for ($i = 0; $i < $firstDayOfWeek; $i++) {
                echo '<td class="empty"></td>';
            }

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $isToday = ($day == $today && $month == $currentMonth && $year == $currentYear);
                $dayClass = $isToday ? 'today' : '';
                $hasAppointments = isset($appointmentsByDay[$day]) && count($appointmentsByDay[$day]) > 0;

                echo '<td class="' . $dayClass . '">';
                echo '<div class="day-number">' . $day . '</div>';

                if ($hasAppointments) {
                    $dayAppointments = $appointmentsByDay[$day];
                    $maxDisplay = 4;
                    $count = count($dayAppointments);
                    $displayed = 0;

                    usort($dayAppointments, function($a, $b) {
                        return strcmp($a['time'], $b['time']);
                    });

                    foreach ($dayAppointments as $appointment) {
                        if ($displayed >= $maxDisplay) {
                            $remaining = $count - $maxDisplay;
                            if ($remaining > 0) {
                                echo '<div class="appointment-item" style="font-size:10px;color:#666;">+' . $remaining . ' more...</div>';
                            }
                            break;
                        }
                        $statusClass = strtolower($appointment['status']);
                        echo '<div class="appointment-item ' . $statusClass . '" 
                             onclick="location.href=\'secretary_edit_appointment.php?appointment_id=' . $appointment['appointment_id'] . '\'">';
                        echo '<span class="appointment-time">' . substr($appointment['time'], 0, 5) . '</span> ';
                        echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']);
                        echo ' → Dr. ' . htmlspecialchars($appointment['doctor_last_name']);
                        if ($appointment['room_number']) {
                            echo ' [' . htmlspecialchars($appointment['room_number']) . ']';
                        }
                        echo '</div>';
                        $displayed++;
                    }
                }

                echo '</td>';

                $dayCount++;
                if (($day + $firstDayOfWeek) % 7 == 0 && $day < $daysInMonth) {
                    echo '</tr><tr>';
                }
            }

            $remainingCells = (7 - (($daysInMonth + $firstDayOfWeek) % 7)) % 7;
            for ($i = 0; $i < $remainingCells; $i++) {
                echo '<td class="empty"></td>';
            }
            ?>
        </tr>
    </tbody>
</table>

<hr>

<h2>Quick Actions</h2>
<ul>
    <li><a href="secretary_add_appointment.php">Schedule New Appointment</a></li>
    <li><a href="secretary_read_appointment.php?date=<?= date('Y-m-d') ?>">View Today's Appointments</a></li>
    <li><a href="secretary_read_appointment.php">View All Appointments</a></li>
</ul>

</body>
</html>