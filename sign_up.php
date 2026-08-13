<?php
session_start();
require __DIR__ . "/data/connection.php";
$error = $success = "";

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

            $success = 'Sign up successful! <a href="./sign_in.php"> Sign In Here! </a>';
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
    <title> MLS · Sign Up </title>
</head>

<body>
    <h2> Sign Up </h2>

    <div>
        <?php echo $error ?: $success ?>
    </div>

    <form method="POST">
        <div>
            <label for="first-name"> First Name </label>
            <input name="first-name" id="first-name" type="text" placeholder="Enter Your First Name" required>
        </div>

        <div>
            <label for="last-name"> Last Name </label>
            <input name="last-name" id="last-name" type="text" placeholder="Enter Your Last Name" required>
        </div>

        <div>
            <label for="email"> Email </label>
            <input name="email" id="email" type="email" placeholder="email@domain.com" required>
        </div>

        <div>
            <label for="password"> Password </label>
            <input name="password" id="password" type="password" placeholder="********" required>
        </div>

        <div>
            <label for="phone"> Phone </label>
            <input name="phone" type="tel" id="phone" placeholder="+00 000-000-0000" pattern="\+[0-9]{2} [0-9]{3}-[0-9]{3}-[0-9]{4}" required>
        </div>

        <div>
            <label for="hmo"> HMO or Insurance </label>
            <select name="hmo" id="hmo" required>
                <option selected hidden disabled value=""> Select Your Primary HMO or Insurance </option>
                <option> iCare </option>
                <option> Intellicare </option>
                <option> Maxicare </option>
                <option> MediCard </option>
                <option> Philcare </option>
                <option> Other </option>
            </select>
        </div>

        <div>
            <label for="specialty"> Preferred Specialty </label>
            <select name="specialty" id="specialty" required>
                <option selected hidden disabled value=""> Select Your Preffered Doctor"s Specialty </option>
                <option> Dermatology </option>
                <option> Gastroenterology </option>
                <option> Internal Medicine </option>
                <option> Neurology </option>
                <option> Orthopedics </option>
                <option> Reproductive Health </option>
            </select>
        </div>

        <div>
            <span> Already have an account? <a href="sign_in.php"> Sign-in instead! </a></span>
        </div>

        <div>
            <button type="submit"> Sign Up </button>
        </div>
    </form>
</body>

</html>