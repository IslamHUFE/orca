```php
<?php 
 
session_start(); 
 
/* 
================================= 
Check Login 
================================= 
*/ 
 
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) { 
    header("Location: ../index.php"); 
    exit(); 
} 
 
 
/* 
================================= 
Get User Role 
================================= 
*/ 
 
$role = strtolower($_SESSION["role"]); 
 
 
/* 
================================= 
Allowed Roles 
================================= 
*/ 
 
$allowed_roles = [ 
    "student", 
    "admin", 
    "technician" 
]; 
 
if (!in_array($role, $allowed_roles)) { 
    header("Location: ../index.php"); 
    exit(); 
} 
 
 
/* 
================================= 
Database 
================================= 
*/ 
 
require_once "../db.php"; 
 
$user_id = $_SESSION["user_id"]; 
 
$message = ""; 
$error = ""; 
 
 
/* 
================================= 
Get Current User 
================================= 
*/ 
 
$sql = " 
    SELECT 
        user_id, 
        full_name, 
        email, 
        university_id, 
        role 
    FROM users 
    WHERE user_id = ? 
"; 
 
$stmt = $pdo->prepare($sql); 
$stmt->execute([$user_id]); 
 
$user = $stmt->fetch(); 
 
if (!$user) { 
 
    session_destroy(); 
 
    header("Location: ../index.php"); 
 
    exit(); 
} 
 
 
/* 
================================= 
Handle Forms 
================================= 
*/ 
 
if ($_SERVER["REQUEST_METHOD"] === "POST") { 
 
    $action = $_POST["action"] ?? ""; 
 
 
    /* 
    ================================= 
    Update Profile 
    ================================= 
    */ 
 
    if ($action === "update_profile") { 
 
        $full_name = trim($_POST["full_name"] ?? ""); 
        $email = trim($_POST["email"] ?? ""); 
 
 
        if ($full_name === "" || $email === "") { 
 
            $error = "Please fill in all required fields."; 
 
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
 
            $error = "Please enter a valid email address."; 
 
        } else { 
 
 
            /* 
            Check Email 
            */ 
 
            $sql = " 
                SELECT user_id 
                FROM users 
                WHERE email = ? 
                AND user_id != ? 
            "; 
 
            $stmt = $pdo->prepare($sql); 
 
            $stmt->execute([ 
                $email, 
                $user_id 
            ]); 
 
 
            if ($stmt->fetch()) { 
 
                $error = "This email is already being used."; 
 
            } else { 
 
 
                /* 
                Update User 
                */ 
 
                $sql = " 
                    UPDATE users 
                    SET 
                        full_name = ?, 
                        email = ? 
                    WHERE user_id = ? 
                "; 
 
                $stmt = $pdo->prepare($sql); 
 
                $stmt->execute([ 
                    $full_name, 
                    $email, 
                    $user_id 
                ]); 
 
 
                $message = 
                    "Your profile has been updated successfully."; 
 
 
                /* 
                Refresh User Data 
                */ 
 
                $sql = " 
                    SELECT 
                        user_id, 
                        full_name, 
                        email, 
                        university_id, 
                        role 
                    FROM users 
                    WHERE user_id = ? 
                "; 
 
                $stmt = $pdo->prepare($sql); 
 
                $stmt->execute([ 
                    $user_id 
                ]); 
 
                $user = $stmt->fetch(); 
            } 
        } 
    } 
 
 
 
    /* 
    ================================= 
    Change Password 
    ================================= 
    */ 
 
    if ($action === "change_password") { 
 
        $current_password = 
            $_POST["current_password"] ?? ""; 
 
        $new_password = 
            $_POST["new_password"] ?? ""; 
 
        $confirm_password = 
            $_POST["confirm_password"] ?? ""; 
 
 
        /* 
        Get Current Password Hash 
        */ 
 
        $sql = " 
            SELECT password_hash 
            FROM users 
            WHERE user_id = ? 
        "; 
 
        $stmt = $pdo->prepare($sql); 
 
        $stmt->execute([ 
            $user_id 
        ]); 
 
        $password_data = $stmt->fetch(); 
 
 
        if (!$password_data) { 
 
            $error = "User account was not found."; 
 
        } elseif ( 
            !password_verify( 
                $current_password, 
                $password_data["password_hash"] 
            ) 
        ) { 
 
            $error = "Current password is incorrect."; 
 
        } elseif ( 
            strlen($new_password) < 6 
        ) { 
 
            $error = 
                "New password must be at least 6 characters."; 
 
        } elseif ( 
            $new_password !== $confirm_password 
        ) { 
 
            $error = 
                "New passwords do not match."; 
 
        } else { 
 
 
            /* 
            Create New Password Hash 
            */ 
 
            $new_password_hash = 
                password_hash( 
                    $new_password, 
                    PASSWORD_DEFAULT 
                ); 
 
 
            /* 
            Update Password 
            */ 
 
            $sql = " 
                UPDATE users 
                SET password_hash = ? 
                WHERE user_id = ? 
            "; 
 
            $stmt = $pdo->prepare($sql); 
 
            $stmt->execute([ 
                $new_password_hash, 
                $user_id 
            ]); 
 
 
            $message = 
                "Your password has been changed successfully."; 
        } 
    } 
} 
 
 
/* 
================================= 
Dashboard URL 
================================= 
*/ 
 
if ($role === "student") { 
 
    $dashboard_url = "index.php"; 
 
} elseif ($role === "admin") { 
 
    $dashboard_url = "../IT Admin/index.php"; 
 
} elseif ($role === "technician") { 
 
    $dashboard_url = "../Technician/index.php"; 
 
} else { 
 
    $dashboard_url = "../index.php"; 
} 
 
 
/* 
================================= 
Sidebar 
================================= 
*/ 
 
$sidebar_file = ""; 
 
if ($role === "student") { 
 
    $sidebar_file = "includes/sidebar.php"; 
 
} elseif ($role === "admin") { 
 
    $sidebar_file = "../IT Admin/includes/sidebar.php"; 
 
} elseif ($role === "technician") { 
 
    $sidebar_file = "../Technician/includes/sidebar.php"; 
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
 
    <title>Settings - UniShare</title> 
 
 
    <link 
        rel="stylesheet" 
        href="../assets/css/dashboard.css" 
    > 
 
 
    <style> 
 
/* ========================= 
   General 
========================= */ 
 
* { 
    box-sizing: border-box; 
} 
 
body { 
 
    margin: 0; 
 
    font-family: Arial, sans-serif; 
 
    background: #f8faf9; 
 
    color: #111827; 
} 
 
 
/* ========================= 
   Dashboard 
========================= */ 
 
.dashboard-container { 
 
    display: flex; 
 
    min-height: 100vh; 
} 
 
 
/* ========================= 
   Main Content 
========================= */ 
 
.main-content { 
 
    margin-left: 240px; 
 
    width: calc(100% - 240px); 
 
    min-height: 100vh; 
 
    padding: 45px; 
} 
 
 
/* ========================= 
   Settings Container 
========================= */ 
 
.settings-container { 
 
    max-width: 900px; 
 
    margin: 0 auto; 
} 
 
 
/* ========================= 
   Back Button 
========================= */ 
 
.back-button-container { 
 
    margin-bottom: 20px; 
} 
 
.back-button { 
 
    border: none; 
 
    padding: 10px 18px; 
 
    background: #6b7280; 
 
    color: white; 
 
    border-radius: 8px; 
 
    font-size: 14px; 
 
    font-weight: 600; 
 
    cursor: pointer; 
 
    transition: 0.2s ease; 
} 
 
.back-button:hover { 
 
    background: #4b5563; 
} 
 
 
/* ========================= 
   Page Header 
========================= */ 
 
.page-header { 
 
    margin-bottom: 30px; 
} 
 
.page-header h1 { 
 
    margin: 0 0 8px; 
 
    font-size: 32px; 
 
    color: #111827; 
} 
 
.page-header p { 
 
    margin: 0; 
 
    color: #6b7280; 
 
    font-size: 15px; 
} 
 
 
/* ========================= 
   Messages 
========================= */ 
 
.success-message { 
 
    background: #eaf6ee; 
 
    color: #16803c; 
 
    border: 1px solid #cdebd8; 
 
    padding: 14px 16px; 
 
    border-radius: 8px; 
 
    margin-bottom: 20px; 
 
    font-size: 14px; 
} 
 
.error-message { 
 
    background: #fff1f2; 
 
    color: #be123c; 
 
    border: 1px solid #fecdd3; 
 
    padding: 14px 16px; 
 
    border-radius: 8px; 
 
    margin-bottom: 20px; 
 
    font-size: 14px; 
} 
 
 
/* ========================= 
   Settings Card 
========================= */ 
 
.settings-card { 
 
    background: white; 
 
    border: 1px solid #e5e7eb; 
 
    border-radius: 15px; 
 
    padding: 30px; 
 
    margin-bottom: 25px; 
} 
 
.settings-card h2 { 
 
    margin: 0 0 8px; 
 
    font-size: 21px; 
 
    color: #111827; 
} 
 
.settings-card-description { 
 
    margin: 0 0 25px; 
 
    color: #6b7280; 
 
    font-size: 14px; 
} 
 
 
/* ========================= 
   Form 
========================= */ 
 
.form-group { 
 
    margin-bottom: 20px; 
} 
 
.form-group label { 
 
    display: block; 
 
    margin-bottom: 8px; 
 
    color: #374151; 
 
    font-size: 14px; 
 
    font-weight: 600; 
} 
 
.form-group input { 
 
    width: 100%; 
 
    padding: 12px 13px; 
 
    border: 1px solid #d1d5db; 
 
    border-radius: 8px; 
 
    font-size: 14px; 
 
    outline: none; 
 
    transition: 0.2s ease; 
} 
 
.form-group input:focus { 
 
    border-color: #16803c; 
 
    box-shadow: 
        0 0 0 3px 
        rgba(22, 128, 60, 0.1); 
} 
 
 
/* ========================= 
   Save Button 
========================= */ 
 
.save-btn { 
 
    border: none; 
 
    padding: 12px 22px; 
 
    background: #16803c; 
 
    color: white; 
 
    border-radius: 8px; 
 
    font-size: 14px; 
 
    font-weight: 600; 
 
    cursor: pointer; 
 
    transition: 0.2s ease; 
} 
 
.save-btn:hover { 
 
    background: #126b32; 
} 
 
 
/* ========================= 
   Account Information 
========================= */ 
 
.account-info { 
 
    display: grid; 
 
    grid-template-columns: repeat(2, 1fr); 
 
    gap: 15px; 
 
    margin-top: 20px; 
} 
 
.account-item { 
 
    background: #f8faf9; 
 
    border-radius: 10px; 
 
    padding: 15px; 
} 
 
.account-label { 
 
    display: block; 
 
    color: #6b7280; 
 
    font-size: 12px; 
 
    margin-bottom: 6px; 
} 
 
.account-value { 
 
    color: #111827; 
 
    font-size: 14px; 
 
    font-weight: 600; 
} 
 
 
/* ========================= 
   Responsive 
========================= */ 
 
@media (max-width: 800px) { 
 
    .sidebar { 
 
        width: 200px; 
    } 
 
    .main-content { 
 
        margin-left: 200px; 
 
        width: calc(100% - 200px); 
 
        padding: 25px; 
    } 
} 
 
 
@media (max-width: 600px) { 
 
    .sidebar { 
 
        position: relative; 
 
        width: 100%; 
 
        height: auto; 
    } 
 
    .dashboard-container { 
 
        flex-direction: column; 
    } 
 
    .main-content { 
 
        margin-left: 0; 
 
        width: 100%; 
 
    } 
 
    .account-info { 
 
        grid-template-columns: 1fr; 
    } 
 
} 
 
    </style> 
 
</head> 
 
 
<body> 
 
 
<div class="dashboard-container"> 
 
 
    <!-- ========================= 
         Dynamic Sidebar 
    ========================= --> 
 
    <?php 
 
    if ($sidebar_file !== "" && file_exists($sidebar_file)) { 
 
        include $sidebar_file; 
 
    } 
 
    ?> 
 
 
    <!-- ========================= 
         Main Content 
    ========================= --> 
 
    <main class="main-content"> 
 
 
        <div class="settings-container"> 
 
 
            <!-- ========================= 
                 Back Button 
            ========================= --> 
 
            <div class="back-button-container"> 
 
                <button 
                    type="button" 
                    class="back-button" 
                    onclick="history.back()"
                > 
 
                    ← Back 
 
                </button> 
 
            </div> 
 
 
            <!-- ========================= 
                 Header 
            ========================= --> 
 
            <div class="page-header"> 
 
                <h1> 
                    Settings 
                </h1> 
 
                <p> 
                    Manage your account information and password. 
                </p> 
 
            </div> 
 
 
            <!-- ========================= 
                 Messages 
            ========================= --> 
 
            <?php if ($message !== ""): ?> 
 
                <div class="success-message"> 
 
                    <?php 
                    echo htmlspecialchars($message); 
                    ?> 
 
                </div> 
 
            <?php endif; ?> 
 
 
            <?php if ($error !== ""): ?> 
 
                <div class="error-message"> 
 
                    <?php 
                    echo htmlspecialchars($error); 
                    ?> 
 
                </div> 
 
            <?php endif; ?> 
 
 
            <!-- ========================= 
                 Profile Information 
            ========================= --> 
 
            <div class="settings-card"> 
 
                <h2> 
                    Profile Information 
                </h2> 
 
                <p class="settings-card-description"> 
                    Update your personal account information. 
                </p> 
 
 
                <form method="POST"> 
 
                    <input 
                        type="hidden" 
                        name="action" 
                        value="update_profile" 
                    > 
 
 
                    <div class="form-group"> 
 
                        <label> 
                            Full Name 
                        </label> 
 
                        <input 
                            type="text" 
                            name="full_name" 
                            value="<?php echo htmlspecialchars($user["full_name"]); ?>" 
                            required 
                        > 
 
                    </div> 
 
 
                    <div class="form-group"> 
 
                        <label> 
                            Email 
                        </label> 
 
                        <input 
                            type="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($user["email"]); ?>" 
                            required 
                        > 
 
                    </div> 
 
 
                    <button 
                        type="submit" 
                        class="save-btn" 
                    > 
                        Save Changes 
                    </button> 
 
                </form> 
 
            </div> 
 
 
            <!-- ========================= 
                 Account Information 
            ========================= --> 
 
            <div class="settings-card"> 
 
                <h2> 
                    Account Information 
                </h2> 
 
                <p class="settings-card-description"> 
                    Some account information cannot be changed. 
                </p> 
 
 
                <div class="account-info"> 
 
 
                    <div class="account-item"> 
 
                        <span class="account-label"> 
                            University ID 
                        </span> 
 
                        <span class="account-value"> 
 
                            <?php 
 
                            echo htmlspecialchars( 
                                $user["university_id"] ?? "Not available" 
                            ); 
 
                            ?> 
 
                        </span> 
 
                    </div> 
 
 
                    <div class="account-item"> 
 
                        <span class="account-label"> 
                            Account Type 
                        </span> 
 
                        <span class="account-value"> 
 
                            <?php 
 
                            echo ucfirst( 
                                htmlspecialchars( 
                                    $user["role"] 
                                ) 
                            ); 
 
                            ?> 
 
                        </span> 
 
                    </div> 
 
 
                </div> 
 
            </div> 
 
 
            <!-- ========================= 
                 Change Password 
            ========================= --> 
 
            <div class="settings-card"> 
 
                <h2> 
                    Change Password 
                </h2> 
 
                <p class="settings-card-description"> 
                    Make sure your new password is at least 6 characters. 
                </p> 
 
 
                <form method="POST"> 
 
                    <input 
                        type="hidden" 
                        name="action" 
                        value="change_password" 
                    > 
 
 
                    <div class="form-group"> 
 
                        <label> 
                            Current Password 
                        </label> 
 
                        <input 
                            type="password" 
                            name="current_password" 
                            required 
                        > 
 
                    </div> 
 
 
                    <div class="form-group"> 
 
                        <label> 
                            New Password 
                        </label> 
 
                        <input 
                            type="password" 
                            name="new_password" 
                            required 
                        > 
 
                    </div> 
 
 
                    <div class="form-group"> 
 
                        <label> 
                            Confirm New Password 
                        </label> 
 
                        <input 
                            type="password" 
                            name="confirm_password" 
                            required 
                        > 
 
                    </div> 
 
 
                    <button 
                        type="submit" 
                        class="save-btn" 
                    > 
                        Change Password 
                    </button> 
 
                </form> 
 
            </div> 
 
 
        </div> 
 
 
    </main> 
 
 
</div> 
 
 
</body> 
 
</html>
```
