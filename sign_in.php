<?php
session_start();
require __DIR__ . "/data/connection.php";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = mysqli_real_escape_string($connection, trim($_POST["email"]));
    $password = mysqli_real_escape_string($connection, trim($_POST["password"]));

    $query = "SELECT * FROM accounts WHERE e_mail = '$email' AND password = '$password'";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) == 1) {
        $account = mysqli_fetch_assoc($result);
        $_SESSION["account_id"] = $account["account_id"];
        $_SESSION["full_name"] = "{$account["first_name"]}  {$account["last_name"]}";
        $_SESSION["role"] = $account["role"];

        $error = "";
        header("location: views/" . $_SESSION["role"] . "/" . $_SESSION["role"] . "_dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials, please try again!";
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
    <title> MLS · Sign In </title>
</head>

<body class="d-flex flex-column min-vh-100 bg-light user-select-none">
    <div class="p-5 my-auto gap-3 card mx-auto shadow bg-info bg-opacity-25 border-info-subtle">
        <h2 class="m-0 p-0 mx-auto text-primary fw-bolder"> Sign In </h2>

        <div id="badge" class="m-0 p-0 <?php echo empty($error) ? 'd-none' : 'd-flex'; ?>">
            <span class="p-2 badge text-bg-danger bg-opacity-100 mx-auto"> <?php echo $error ?: '' ?> </span>
        </div>

        <form class="d-flex flex-column gap-3" method="POST">
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

            <div class="mx-auto">
                <span> Don't have an account?
                    <a class="text-decoration-none text-secondary" href="sign_up.php"> Sign-up instead! </a>
                </span>
            </div>

            <div class="p-0 m-0 mx-auto">
                <input class="btn btn-sn btn-outline-primary border-2 fw-bold" type="submit" value="Sign In">
            </div>
        </form>
    </div>
</body>

</html>