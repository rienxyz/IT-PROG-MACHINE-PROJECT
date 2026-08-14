<?php
session_start();
session_destroy();

header("location: ./views/patient/patient_find_doctor.php");
exit();
?>