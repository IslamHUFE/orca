<?php

session_start();

require_once "db.php";

$error = "";
$success = "";

$user_id = $_SESSION["reset_user_id"] ?? null;
$reset_code = $_SESSION["reset_code"] ?? null;


/*
=========================================
CHECK RESET SESSION
=========================================
*/

if (!$user_id || !$reset_code) {

    $error =
        "Invalid or expired password reset request.";
}


/*
=========================================
RESET PASSWORD
=========================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && $user_id
    && $reset_code
) {

    $code = trim($_POST["code"] ?? "");

    $password = $_POST["password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    /*
    =========================================
    CHECK CODE
    =========================================
    */

    if (empty($code)) {

        $error = "Please enter the reset code.";

    } elseif ($code !== $reset_code) {

        $error = "Invalid reset code.";

    } elseif (
        empty($password)
        || empty($confirm_password)
    ) {

        $error = "Please fill in all fields.";

    } elseif (strlen($password) < 6) {

        $error =
            "Password must be at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error =
            "Passwords do not match.";

    } else {

        /*
        =========================================
        HASH NEW PASSWORD
        =========================================
        */

        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        /*
        =========================================
        UPDATE PASSWORD
        =========================================
        */

        $sql = "
            UPDATE users
            SET password_hash = ?
            WHERE user_id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $password_hash,
            $user_id
        ]);


        /*
        =========================================
        CLEAR RESET SESSION
        =========================================
        */

        unset($_SESSION["reset_user_id"]);
        unset($_SESSION["reset_email"]);
        unset($_SESSION["reset_code"]);


        $success =
            "Your password has been reset successfully.";
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>UniShare - Reset Password</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family: Arial, sans-serif;

    background: #f5faf6;

    min-height: 100vh;

    display: flex;

    justify-content: center;

    align-items: center;
}

.container {

    width: 100%;

    max-width: 450px;

    padding: 20px;
}

.card {

    background: white;

    padding: 40px;

    border-radius: 16px;

    box-shadow:
        0 10px 35px
        rgba(0, 0, 0, 0.08);
}

.logo {

    color: #16803c;

    font-size: 24px;

    font-weight: bold;

    text-align: center;

    margin-bottom: 25px;
}

h1 {

    text-align: center;

    color: #111827;

    margin-bottom: 25px;
}

.form-group {

    margin-bottom: 20px;
}

label {

    display: block;

    margin-bottom: 8px;

    color: #374151;

    font-weight: 600;
}

input {

    width: 100%;

    padding: 13px 15px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    outline: none;

    font-size: 15px;
}

input:focus {

    border-color: #16803c;
}

button {

    width: 100%;

    padding: 14px;

    background: #16803c;

    color: white;

    border: none;

    border-radius: 8px;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}

button:hover {

    background: #126b32;
}

.message {

    background: #eaf6ee;

    color: #16803c;

    padding: 15px;

    border-radius: 8px;

    text-align: center;

    margin-bottom: 20px;

    line-height: 1.5;
}

.error-message {

    background: #fee2e2;

    color: #b91c1c;

    padding: 15px;

    border-radius: 8px;

    text-align: center;

    margin-bottom: 20px;
}

.login-link {

    text-align: center;

    margin-top: 25px;
}

.login-link a {

    color: #16803c;

    font-weight: bold;

    text-decoration: none;
}

</style>

</head>

<body>

<div class="container">

    <div class="card">

        <div class="logo">
            ♻️ UniShare
        </div>


        <?php if (!empty($success)): ?>

            <h1>
                Password Reset
            </h1>

            <div class="message">

                <?= htmlspecialchars($success) ?>

            </div>

            <div class="login-link">

                <a href="index.php">
                    Go to Login
                </a>

            </div>


        <?php elseif (!empty($error) && !$user_id): ?>

            <h1>
                Reset Password
            </h1>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

            <div class="login-link">

                <a href="forgot_password.php">
                    Request a New Code
                </a>

            </div>


        <?php elseif (!empty($error)): ?>

            <h1>
                Reset Password
            </h1>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

            <form method="POST">

                <div class="form-group">

                    <label for="code">
                        Reset Code
                    </label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        placeholder="Enter the 6-digit code"
                        maxlength="6"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your new password"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your new password"
                        required
                    >

                </div>

                <button type="submit">
                    Reset Password
                </button>

            </form>


        <?php else: ?>

            <h1>
                Reset Password
            </h1>

            <form method="POST">

                <div class="form-group">

                    <label for="code">
                        Reset Code
                    </label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        placeholder="Enter the 6-digit code"
                        maxlength="6"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your new password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your new password"
                        required
                    >

                </div>


                <button type="submit">

                    Reset Password

                </button>

            </form>

        <?php endif; ?>

    </div>

</div>

</body>

</html>