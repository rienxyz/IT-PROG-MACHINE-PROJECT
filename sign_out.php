<?php
session_start();
session_destroy();

header("location: /views/patient_find_doctor.php");
exit();
?>