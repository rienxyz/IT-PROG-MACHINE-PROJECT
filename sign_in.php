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

    <title> MLS · Sign In </title>
</head>

<body>
    <h2> Sign In </h2>

    <div>
        <?php echo $error ?: '' ?>
    </div>

    <form method="POST">
        <div>
            <label for="email"> E-Mail </label>
            <input name="email" id="email" type="email" placeholder="email@domain.com" required>
        </div>

        <div>
            <label for="password"> Password </label>
            <input name="password" id="password" type="password" placeholder="********" required>
        </div>

        <div>
            <span> Don"t have an account? <a href="sign_up.php"> Sign-up instead! </a></span>
        </div>

        <div>
            <button type="submit"> Sign In </button>
        </div>
    </form>
</body>

</html>