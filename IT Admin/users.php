```php
<?php

session_start();

/* ====================================================
   AUTHENTICATION
==================================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}


/* ====================================================
   DATABASE
==================================================== */

require_once "../db.php";


/* ====================================================
   VARIABLES
==================================================== */

$message = "";
$error = "";


/* ====================================================
   DELETE USER
==================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $user_id = (int)($_POST["user_id"] ?? 0);

    if ($action === "delete_user") {

        /* Prevent admin from deleting himself */

        if ($user_id === (int)$_SESSION["user_id"]) {

            $error = "You cannot delete your own account.";

        } elseif ($user_id <= 0) {

            $error = "Invalid user.";

        } else {

            try {

                /*
                Check if user exists
                */

                $stmt = $pdo->prepare("
                    SELECT user_id, full_name, role
                    FROM users
                    WHERE user_id = ?
                ");

                $stmt->execute([$user_id]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);


                if (!$user) {

                    $error = "User not found.";

                } else {

                    /*
                    Delete user
                    */

                    $stmt = $pdo->prepare("
                        DELETE FROM users
                        WHERE user_id = ?
                    ");

                    $stmt->execute([$user_id]);

                    $message = "User deleted successfully.";
                }

            } catch (PDOException $e) {

                /*
                This can happen if the user is connected
                to other tables through foreign keys.
                */

                $error = "Unable to delete this user because they are linked to other records.";
            }
        }
    }
}


/* ====================================================
   SEARCH & FILTER
==================================================== */

$search = trim($_GET["search"] ?? "");
$role_filter = trim($_GET["role"] ?? "");


/* ====================================================
   GET USERS
==================================================== */

$users = [];

try {

    $sql = "
        SELECT
            user_id,
            full_name,
            email,
            role,
            university_id,
            is_verified,
            created_at
        FROM users
        WHERE 1=1
    ";

    $params = [];


    /* SEARCH */

    if ($search !== "") {

        $sql .= "
            AND (
                full_name LIKE ?
                OR email LIKE ?
                OR university_id LIKE ?
            )
        ";

        $search_value = "%" . $search . "%";

        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
    }


    /* ROLE FILTER */

    if (
        $role_filter !== ""
        &&
        in_array(
            $role_filter,
            ["admin", "technician", "student"],
            true
        )
    ) {

        $sql .= " AND role = ? ";

        $params[] = $role_filter;
    }


    /*
    Newest users first
    */

    $sql .= "
        ORDER BY created_at DESC
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $error = "Unable to load users.";
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

<title>
    Users Management - UniShare
</title>


<style>

/* ====================================================
   GENERAL
==================================================== */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #f8faf9;

    color: #111827;
}


/* ====================================================
   MAIN CONTENT
==================================================== */

.main-content {

    margin-left: 240px;

    width: calc(100% - 240px);

    min-height: 100vh;

    padding: 40px;
}


/* ====================================================
   HEADER
==================================================== */

.page-header {

    margin-bottom: 25px;
}

.page-header h1 {

    margin: 0 0 8px;

    font-size: 30px;
}

.page-header p {

    margin: 0;

    color: #6b7280;

    font-size: 15px;
}


/* ====================================================
   MESSAGES
==================================================== */

.message {

    padding: 13px 16px;

    margin-bottom: 20px;

    border-radius: 8px;

    background: #dcfce7;

    color: #166534;

    border: 1px solid #bbf7d0;
}

.error {

    padding: 13px 16px;

    margin-bottom: 20px;

    border-radius: 8px;

    background: #fee2e2;

    color: #b91c1c;

    border: 1px solid #fecaca;
}


/* ====================================================
   FILTER CARD
==================================================== */

.filter-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 20px;

    margin-bottom: 25px;
}

.filter-form {

    display: flex;

    gap: 12px;

    align-items: center;

    flex-wrap: wrap;
}

.search-box {

    flex: 1;

    min-width: 250px;
}

.search-box input,
.role-filter select {

    width: 100%;

    padding: 11px 13px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    font-size: 14px;

    outline: none;
}

.search-box input:focus,
.role-filter select:focus {

    border-color: #16803c;
}


/* ====================================================
   FILTER BUTTON
==================================================== */

.filter-btn {

    padding: 11px 20px;

    border: none;

    border-radius: 8px;

    background: #16803c;

    color: white;

    font-size: 14px;

    font-weight: 600;

    cursor: pointer;
}

.filter-btn:hover {

    background: #126b32;
}


/* ====================================================
   RESET BUTTON
==================================================== */

.reset-btn {

    padding: 10px 18px;

    border-radius: 8px;

    background: #f3f4f6;

    color: #374151;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;
}

.reset-btn:hover {

    background: #e5e7eb;
}


/* ====================================================
   TABLE CARD
==================================================== */

.table-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    overflow: hidden;

    box-shadow:
        0 2px 8px rgba(0, 0, 0, 0.03);
}


/* ====================================================
   TABLE WRAPPER
==================================================== */

.table-wrapper {

    width: 100%;

    overflow-x: auto;
}


/* ====================================================
   TABLE
==================================================== */

table {

    width: 100%;

    min-width: 950px;

    border-collapse: collapse;
}

thead {

    background: #f3f6f4;
}

th {

    padding: 16px;

    text-align: left;

    font-size: 13px;

    color: #374151;

    border-bottom: 1px solid #e5e7eb;

    white-space: nowrap;
}

td {

    padding: 16px;

    font-size: 14px;

    border-bottom: 1px solid #f0f0f0;

    vertical-align: middle;
}

tbody tr:hover {

    background: #fafafa;
}


/* ====================================================
   USER
==================================================== */

.user-name {

    font-weight: 600;

    color: #111827;

    margin-bottom: 4px;
}

.user-email {

    font-size: 12px;

    color: #6b7280;
}


/* ====================================================
   ROLE
==================================================== */

.role {

    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;

    text-transform: capitalize;
}

.role-admin {

    background: #ede9fe;

    color: #6d28d9;
}

.role-technician {

    background: #dbeafe;

    color: #1d4ed8;
}

.role-student {

    background: #dcfce7;

    color: #166534;
}


/* ====================================================
   VERIFIED
==================================================== */

.verified {

    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;
}

.verified-yes {

    background: #dcfce7;

    color: #166534;
}

.verified-no {

    background: #fee2e2;

    color: #b91c1c;
}


/* ====================================================
   DELETE BUTTON
==================================================== */

.delete-btn {

    border: none;

    border-radius: 7px;

    padding: 8px 13px;

    background: #fee2e2;

    color: #b91c1c;

    font-size: 12px;

    font-weight: 600;

    cursor: pointer;
}

.delete-btn:hover {

    background: #fecaca;
}


/* ====================================================
   CURRENT USER
==================================================== */

.current-user {

    color: #9ca3af;

    font-size: 12px;

    font-weight: 600;
}


/* ====================================================
   EMPTY STATE
==================================================== */

.empty-state {

    padding: 70px 20px;

    text-align: center;

    color: #6b7280;
}

.empty-icon {

    font-size: 50px;

    margin-bottom: 15px;
}

.empty-state h3 {

    margin: 0 0 8px;

    color: #374151;

    font-size: 20px;
}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 800px) {

    .main-content {

        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }
}

@media (max-width: 600px) {

    .main-content {

        margin-left: 0;

        width: 100%;

        padding: 20px;
    }

    .filter-form {

        flex-direction: column;

        align-items: stretch;
    }

    .search-box {

        min-width: 100%;
    }

}

</style>

</head>


<body>


<div class="dashboard-container">


    <!-- ====================================================
         ADMIN SIDEBAR
    ==================================================== -->

    <?php

    include __DIR__ . "/includes/sidebar.php";

    ?>


    <!-- ====================================================
         MAIN CONTENT
    ==================================================== -->

    <main class="main-content">


        <!-- HEADER -->

        <div class="page-header">

            <h1>
                Users Management 👥
            </h1>

            <p>
                Manage students, technicians, and administrators.
            </p>

        </div>


        <!-- SUCCESS MESSAGE -->

        <?php if ($message !== ""): ?>

            <div class="message">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if ($error !== ""): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- ====================================================
             SEARCH & FILTER
        ==================================================== -->

        <div class="filter-card">

            <form
                method="GET"
                class="filter-form"
            >


                <!-- SEARCH -->

                <div class="search-box">

                    <input
                        type="text"
                        name="search"
                        placeholder="Search by name, email or university ID..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>


                <!-- ROLE -->

                <div class="role-filter">

                    <select name="role">

                        <option value="">
                            All Roles
                        </option>

                        <option
                            value="admin"
                            <?= $role_filter === "admin" ? "selected" : "" ?>
                        >
                            Admin
                        </option>

                        <option
                            value="technician"
                            <?= $role_filter === "technician" ? "selected" : "" ?>
                        >
                            Technician
                        </option>

                        <option
                            value="student"
                            <?= $role_filter === "student" ? "selected" : "" ?>
                        >
                            Student
                        </option>

                    </select>

                </div>


                <!-- FILTER -->

                <button
                    type="submit"
                    class="filter-btn"
                >
                    Search
                </button>


                <!-- RESET -->

                <a
                    href="users.php"
                    class="reset-btn"
                >
                    Reset
                </a>


            </form>

        </div>


        <!-- ====================================================
             USERS TABLE
        ==================================================== -->

        <div class="table-card">


            <?php if (empty($users)): ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        👥
                    </div>

                    <h3>
                        No Users Found
                    </h3>

                    <p>
                        No users match your search or filter.
                    </p>

                </div>


            <?php else: ?>


                <div class="table-wrapper">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    User
                                </th>

                                <th>
                                    University ID
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Verification
                                </th>

                                <th>
                                    Created At
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($users as $user): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <strong>
                                        #<?= (int)$user["user_id"] ?>
                                    </strong>

                                </td>


                                <!-- USER -->

                                <td>

                                    <div class="user-name">

                                        <?= htmlspecialchars(
                                            $user["full_name"]
                                        ) ?>

                                    </div>

                                    <div class="user-email">

                                        <?= htmlspecialchars(
                                            $user["email"]
                                        ) ?>

                                    </div>

                                </td>


                                <!-- UNIVERSITY ID -->

                                <td>

                                    <?= htmlspecialchars(
                                        $user["university_id"]
                                    ) ?>

                                </td>


                                <!-- ROLE -->

                                <td>

                                    <?php

                                    $role = strtolower(
                                        $user["role"]
                                    );

                                    ?>

                                    <span
                                        class="role role-<?= htmlspecialchars($role) ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars($role)
                                        ) ?>

                                    </span>

                                </td>


                                <!-- VERIFICATION -->

                                <td>

                                    <?php if ((int)$user["is_verified"] === 1): ?>

                                        <span class="verified verified-yes">
                                            ✓ Verified
                                        </span>

                                    <?php else: ?>

                                        <span class="verified verified-no">
                                            ✕ Not Verified
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <?= htmlspecialchars(
                                        $user["created_at"]
                                    ) ?>

                                </td>


                                <!-- ACTION -->

                                <td>


                                    <?php if (
                                        (int)$user["user_id"]
                                        ===
                                        (int)$_SESSION["user_id"]
                                    ): ?>


                                        <span class="current-user">
                                            Current Account
                                        </span>


                                    <?php else: ?>


                                        <form
                                            method="POST"
                                            onsubmit="return confirm(
                                                'Are you sure you want to delete this user?'
                                            );"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete_user"
                                            >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= (int)$user["user_id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="delete-btn"
                                            >
                                                Delete
                                            </button>

                                        </form>


                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>
```
