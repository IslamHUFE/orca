<?php

session_start();

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // Check empty fields
    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        // Get user from database
        $sql = "
            SELECT
                user_id,
                full_name,
                email,
                password_hash,
                role,
                university_id,
                is_verified
            FROM users
            WHERE email = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists
        if ($user) {

            // Compare password with hashed password
            if (password_verify($password, $user["password_hash"])) {

                // Check account verification
                if ($user["is_verified"] != 1) {

                    $error = "Your account has not been verified yet.";

                } else {

                    // Store user information in session
                    $_SESSION["user_id"] = $user["user_id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["university_id"] = $user["university_id"];

                    // Redirect according to role

                    if ($user["role"] === "admin") {

                        header("Location: IT Admin/admin_dashboard.php");
                        exit();

                    } elseif ($user["role"] === "technician") {

                        header("Location: Technician/technician_dashboard.php");
                        exit();

                    } elseif ($user["role"] === "student") {

                        header("Location: student/dashboard.php");
                        exit();

                    } else {

                        $error = "Invalid user role.";
                    }
                }

            } else {

                $error = "Invalid email or password.";
            }

        } else {

            $error = "Invalid email or password.";
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

    <title>UniShare - Login</title>

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

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 16px;

            box-shadow:
                0 10px 35px rgba(0, 0, 0, 0.08);
        }

        .logo {
            color: #16803c;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
        }

        .login-card h1 {
            text-align: center;
            color: #111827;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 13px 15px;

            border: 1px solid #d1d5db;
            border-radius: 8px;

            outline: none;
            font-size: 15px;
        }

        .form-group input:focus {
            border-color: #16803c;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-options a {
            color: #16803c;
            text-decoration: none;
        }

        .login-btn {
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

        .login-btn:hover {
            background: #126b32;
        }

        .register-text {
            text-align: center;
            margin-top: 25px;
            color: #6b7280;
        }

        .register-text a {
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

    </style>

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                ♻️ UniShare
            </div>

            <h1>
                Welcome Back
            </h1>

            <p class="subtitle">
                Login to continue sharing resources.
            </p>


            <?php if (!empty($error)): ?>

                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="">

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


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>


                <div class="form-options">

                    <label>

                        <input
                            type="checkbox"
                            name="remember"
                        >

                        Remember me

                    </label>


                   <a href="forgot_password.php">
    Forgot Password?
</a>

                </div>


                <button
                    type="submit"
                    class="login-btn"
                >
                    Login
                </button>

            </form>


            <p class="register-text">

                Don't have an account?

                <a href="register.php">
                    Create an account
                </a>

            </p>

        </div>

    </div>

</body>

</html>