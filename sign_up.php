<?php
session_start();
require __DIR__ . "/data/connection.php";
$error = $success = "";

$query = 'SELECT DISTINCT specialty FROM doctors ORDER BY specialty ASC';
$result = mysqli_query($connection, $query);
$array = array();

while ($row = mysqli_fetch_assoc($result)) {
    $array[] = $row; 
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = mysqli_real_escape_string($connection, trim($_POST["first-name"]));
    $lastName = mysqli_real_escape_string($connection, trim($_POST["last-name"]));
    $email = mysqli_real_escape_string($connection, trim($_POST["email"]));
    $password = mysqli_real_escape_string($connection, trim($_POST["password"]));
    $phone = mysqli_real_escape_string($connection, trim($_POST["phone"]));
    $hmo = mysqli_real_escape_string($connection, trim($_POST["hmo"] ?? ""));
    $specialty = mysqli_real_escape_string($connection, trim($_POST["specialty"] ?? ""));

    $query = "SELECT * FROM accounts WHERE e_mail = '$email'";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        $error = "An account already has these credentials!";
        $success = "";
    }

    if (!$error) {
        $query = "INSERT INTO accounts (first_name, last_name, e_mail, password, phone_number, role, activity_status) " .
            "VALUES ('$firstName', '$lastName', '$email', '$password', '$phone', 'patient', 'active')";

        if ($result = mysqli_query($connection, $query)) {
            $account_id = mysqli_insert_id($connection);
            $query = "INSERT INTO patients (account_id, insurance, preferred_specialty) " .
                "VALUES ($account_id, '$hmo', '$specialty')";

            mysqli_query($connection, $query);

            $success = 'Sign up successful! <a class="text-white" href="./sign_in.php"> Sign In Here! </a>';
            $error = "";
        } else {
            $error = "Sign up failed, please try again!";
            $success = "";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous" defer></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="./styles/style.css">
    <title> MLS · Sign Up </title>
</head>

<body class="d-flex flex-column min-vh-100 bg-light user-select-none">
    <div class="p-5 my-auto gap-3 card mx-auto shadow bg-info bg-opacity-25 border-info-subtle">
        <h2 class="m-0 p-0 mx-auto text-primary fw-bolder"> Sign Up </h2>

        <div id="badge" class="m-0 p-0 <?php echo empty($error ?: $success) ? 'd-none' : 'd-flex'; ?>">
            <span class="p-2 badge text-bg-<?php echo $error ? 'danger' : 'success' ?> bg-opacity-100 mx-auto"> <?php echo $error ?: $success ?> </span>
        </div>

        <form class="d-flex flex-column gap-3" method="POST">
            <div class="row">
                <div class="col">
                    <div class="input-group input-group-sm">
                        <span class="p-0 input-group-text bg-info bg-opacity-75 border-info">
                            <i class="p-2 px-3 fs-4 text-primary-emphasis bi bi-person-badge-fill"></i>
                        </span>

                        <div class="form-floating">
                            <input name="first-name" id="first-name" type="text" class="form-control border-info border-2" placeholder="" required>
                            <label for="first-name"> First Name </label>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="input-group input-group-sm">
                        <span class="p-0 input-group-text bg-info bg-opacity-75 border-info">
                            <i class="p-2 px-3 fs-4 text-primary-emphasis bi bi-person-vcard-fill"></i>
                        </span>

                        <div class="form-floating">
                            <input name="last-name" id="last-name" type="text" class="form-control border-info border-2" placeholder="" required>
                            <label for="last-name"> Last Name </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group input-group-sm">
                <span class="p-0 input-group-text bg-info bg-opacity-75 border-info">
                    <i class="p-2 px-3 fs-4 text-primary-emphasis bi bi-envelope-at-fill"></i>
                </span>

                <div class="form-floating">
                    <input name="email" id="email" type="email" class="form-control border-info border-2" placeholder="" required>
                    <label for="email"> E-Mail </label>
                </div>
            </div>

            <div class="input-group input-group-sm">
                <span class="p-0 input-group-text bg-info bg-opacity-75 border-info">
                    <i class="p-2 px-3 fs-4 text-primary-emphasis bi bi-shield-lock-fill"></i>
                </span>

                <div class="form-floating">
                    <input name="password" id="password" type="password" class="form-control border-info border-2" placeholder="" required>
                    <label for="password"> Password </label>
                </div>
            </div>

            <div class="input-group input-group-sm">
                <span class="p-0 input-group-text bg-info bg-opacity-75 border-info">
                    <i class="p-2 px-3 fs-4 text-primary-emphasis bi bi-telephone-fill"></i>
                </span>

                <div class="form-floating">
                    <input name="phone" id="phone" type="tel" class="form-control border-info border-2" placeholder="" pattern="\+63 [0-9]{3}-[0-9]{3}-[0-9]{4}" required>
                    <label for="phone"> Phone (+63 ###-###-####) </label>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="input-group input-group-sm">
                        <span class="p-0 input-group-text bg-info bg-opacity-75 border-info">
                            <i class="p-2 px-3 fs-4 text-primary-emphasis bi bi-person-badge-fill"></i>
                        </span>

                        <div class="form-floating">
                            <select name="hmo" id="hmo" class="form-select border-info border-2" required>
                                <option selected> None </option>
                                <option> iCare </option>
                                <option> Intellicare </option>
                                <option> Maxicare </option>
                                <option> MediCard </option>
                                <option> Philcare </option>
                            </select>

                            <label for="hmo"> HMO or Insurance </label>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="input-group input-group-sm">
                        <span class="p-0 input-group-text bg-info bg-opacity-75 border-info">
                            <i class="p-2 px-3 fs-4 text-primary-emphasis bi bi-award-fill"></i>
                        </span>

                        <div class="form-floating">
                            <select name="specialty" id="specialty" class="form-select border-info border-2" required>
                                <option selected> Any Specialty </option>

                                <?php
                                foreach ($array as $row) {
                                    echo "<option>" . $row['specialty'] . "</option>";
                                }
                                ?>
                            </select>

                            <label for="specialty"> Preferred Specialty </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mx-auto">
                <span> Already have an account?
                    <a class="text-decoration-none text-secondary" href="sign_in.php"> Sign-in instead! </a>
                </span>
            </div>

            <div class="p-0 m-0 mx-auto">
                <input class="btn btn-sn btn-outline-primary border-2 fw-bold" type="submit" value="Sign Up">
            </div>
        </form>
    </div>
</body>

</html>