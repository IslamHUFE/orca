<?php

session_start();

require_once "db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    /*
    =========================================
    VALIDATION
    =========================================
    */

    if (
        empty($full_name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        /*
        =========================================
        CHECK EMAIL
        =========================================
        */

        $sql = "
            SELECT user_id
            FROM users
            WHERE email = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $email
        ]);

        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($existing_user) {

            $error = "An account with this email already exists.";

        } else {

            /*
            =========================================
            CREATE ACCOUNT
            =========================================
            */

            /*
            Students register themselves.
            Admin and technician accounts
            can be created later by the admin.
            */

            $role = "student";


            /*
            =========================================
            ACCOUNT VERIFICATION
            =========================================
            */

            $is_verified = 1;


            /*
            =========================================
            PASSWORD HASH
            =========================================
            */

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /*
            =========================================
            UNIVERSITY ID
            =========================================

            Students do not enter university_id
            during registration.

            We use NULL instead of "N/A"
            because university_id is UNIQUE.
            Multiple NULL values are allowed.
            */

            $university_id = null;


            /*
            =========================================
            INSERT USER
            =========================================
            */

            $sql = "
                INSERT INTO users
                (
                    full_name,
                    email,
                    password_hash,
                    role,
                    university_id,
                    is_verified
                )
                VALUES
                (?, ?, ?, ?, ?, ?)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $full_name,
                $email,
                $password_hash,
                $role,
                $university_id,
                $is_verified
            ]);


            /*
            =========================================
            SUCCESS
            =========================================
            */

            $success = "Account created successfully. You can now login.";
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

<title>UniShare - Create Account</title>


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


.register-container {

    width: 100%;

    max-width: 480px;

    padding: 20px;

}


.register-card {

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


.register-card h1 {

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

    box-shadow:
        0 0 0 3px
        rgba(22, 128, 60, 0.08);

}


.register-btn {

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


.register-btn:hover {

    background: #126b32;

}


.login-text {

    text-align: center;

    margin-top: 25px;

    color: #6b7280;

}


.login-text a {

    color: #16803c;

    font-weight: bold;

    text-decoration: none;

}


.login-text a:hover {

    text-decoration: underline;

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

}


@media (max-width: 500px) {

    .register-card {

        padding: 30px 20px;

    }

}

</style>

</head>


<body>


<div class="register-container">


    <div class="register-card">


        <div class="logo">

            ♻️ UniShare

        </div>


        <h1>

            Create Account

        </h1>


        <p class="subtitle">

            Join UniShare and start sharing resources.

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

        <?php endif; ?>



        <form method="POST" action="">


            <div class="form-group">

                <label for="full_name">

                    Full Name

                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($_POST["full_name"] ?? "") ?>"
                    required
                >

            </div>



            <div class="form-group">

                <label for="email">

                    University Email

                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your university email"
                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
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



            <div class="form-group">

                <label for="confirm_password">

                    Confirm Password

                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    required
                >

            </div>



            <button
                type="submit"
                class="register-btn"
            >

                Create Account

            </button>


        </form>



        <p class="login-text">

            Already have an account?

            <a href="index.php">

                Login

            </a>

        </p>


    </div>


</div>


</body>

</html>