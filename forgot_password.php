<?php

session_start();

require_once "db.php";

$error = "";
$success = "";
$reset_code = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {

        $error = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $sql = "
            SELECT user_id, full_name, email
            FROM users
            WHERE email = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            /*
            =========================================
            GENERATE RESET CODE
            =========================================
            */

            $reset_code = str_pad(
                random_int(0, 999999),
                6,
                "0",
                STR_PAD_LEFT
            );

            /*
            =========================================
            SAVE RESET DATA IN SESSION
            =========================================
            */

            $_SESSION["reset_user_id"] = $user["user_id"];
            $_SESSION["reset_email"] = $user["email"];
            $_SESSION["reset_code"] = $reset_code;

            /*
            =========================================
            SHOW CODE FOR TRAINING
            =========================================
            */

            $success =
                "Your reset code is: " . $reset_code;

        } else {

            $error =
                "No account was found with this email address.";
        }
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

<title>UniShare - Forgot Password</title>

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

    margin-bottom: 10px;
}

.subtitle {

    text-align: center;

    color: #6b7280;

    margin-bottom: 30px;

    line-height: 1.5;
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

.back-login {

    text-align: center;

    margin-top: 25px;
}

.back-login a {

    color: #16803c;

    font-weight: bold;

    text-decoration: none;
}

.error-message {

    background: #fee2e2;

    color: #b91c1c;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 20px;

    text-align: center;
}

.success-message {

    background: #eaf6ee;

    color: #16803c;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 20px;

    text-align: center;

    line-height: 1.5;
}

.reset-link {

    display: block;

    text-align: center;

    margin-top: 15px;

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

        <h1>
            Forgot Password?
        </h1>

        <p class="subtitle">
            Enter your university email to receive a reset code.
        </p>

        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($success)): ?>

            <div class="success-message">

                <?= htmlspecialchars($success) ?>

            </div>

            <a
                href="reset_password.php"
                class="reset-link"
            >
                Continue to Reset Password →
            </a>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label for="email">
                    University Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your university email"
                    required
                >

            </div>


            <button type="submit">

                Get Reset Code

            </button>

        </form>


        <div class="back-login">

            <a href="index.php">

                ← Back to Login

            </a>

        </div>

    </div>

</div>

</body>

</html>