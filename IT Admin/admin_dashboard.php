<?php

session_start();

/* =========================================
   CHECK LOGIN
========================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}


/* =========================================
   DATABASE
========================================= */

require_once "../db.php";


/* =========================================
   ADMIN NAME
========================================= */

$admin_name = $_SESSION["full_name"] ?? "Admin";


/* =========================================
   COMPUTER STATISTICS
========================================= */

$total_computers = 0;
$working_computers = 0;
$repair_computers = 0;
$not_repairable_computers = 0;

try {

    // Total Computers
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM computer
    ");

    $total_computers = (int)$stmt->fetchColumn();


    // Working Computers
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM computer
        WHERE LOWER(status) = 'working'
    ");

    $working_computers = (int)$stmt->fetchColumn();


    // Computers Needing Repair
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM computer
        WHERE LOWER(status) = 'needs_repair'
    ");

    $repair_computers = (int)$stmt->fetchColumn();


    // Not Repairable Computers
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM computer
        WHERE LOWER(status) = 'not_repairable'
    ");

    $not_repairable_computers = (int)$stmt->fetchColumn();

} catch (PDOException $e) {

    $total_computers = 0;
    $working_computers = 0;
    $repair_computers = 0;
    $not_repairable_computers = 0;
}


/* =========================================
   STUDENTS
========================================= */

$total_students = 0;

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'student'
    ");

    $total_students = (int)$stmt->fetchColumn();

} catch (PDOException $e) {

    $total_students = 0;
}


/* =========================================
   TOTAL USERS
========================================= */

$total_users = 0;

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM users
    ");

    $total_users = (int)$stmt->fetchColumn();

} catch (PDOException $e) {

    $total_users = 0;
}


/* =========================================
   RESOURCES
========================================= */

$total_resources = 0;
$available_resources = 0;

try {

    // Total Resources
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM resource
    ");

    $total_resources = (int)$stmt->fetchColumn();


    // Available Resources
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM resource
        WHERE status = 'available'
    ");

    $available_resources = (int)$stmt->fetchColumn();

} catch (PDOException $e) {

    $total_resources = 0;
    $available_resources = 0;
}


/* =========================================
   RECENT COMPUTERS
========================================= */

$recent_computers = [];

try {

    $stmt = $pdo->query("
        SELECT
            computer_id,
            brand,
            model,
            serial_number,
            status,
            location
        FROM computer
        ORDER BY computer_id DESC
        LIMIT 5
    ");

    $recent_computers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $recent_computers = [];
}


/* =========================================
   COMPUTERS NEEDING REPAIR
========================================= */

$repair_list = [];

try {

    $stmt = $pdo->query("
        SELECT
            computer_id,
            brand,
            model,
            serial_number,
            location,
            status
        FROM computer
        WHERE LOWER(status) = 'needs_repair'
        ORDER BY computer_id DESC
        LIMIT 5
    ");

    $repair_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $repair_list = [];
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard - UniShare</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f8faf9;
    color: #111827;
}

.dashboard-container {
    display: flex;
    min-height: 100vh;
}


/* =========================================
   MAIN CONTENT
========================================= */

.main-content {
    margin-left: 240px;
    width: calc(100% - 240px);
    min-height: 100vh;
    padding: 40px;
}

.dashboard-wrapper {
    max-width: 1200px;
    margin: auto;
}


/* =========================================
   HEADER
========================================= */

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 32px;
    margin-bottom: 8px;
}

.page-header p {
    color: #6b7280;
    font-size: 15px;
}


/* =========================================
   STATISTICS
========================================= */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px;
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-title {
    color: #6b7280;
    font-size: 13px;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    margin-top: 7px;
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: #eaf6ee;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.stat-description {
    margin-top: 12px;
    color: #9ca3af;
    font-size: 12px;
}


/* =========================================
   PANELS
========================================= */

.panel {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
}

.section-title {
    font-size: 21px;
    margin-bottom: 20px;
}


/* =========================================
   COMPUTER OVERVIEW
========================================= */

.status-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.status-box {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
}

.status-box-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-working {
    background: #dcfce7;
}

.status-working .status-dot {
    background: #16a34a;
}

.status-repair {
    background: #fff7ed;
}

.status-repair .status-dot {
    background: #f97316;
}

.status-broken {
    background: #fee2e2;
}

.status-broken .status-dot {
    background: #dc2626;
}

.status-box h3 {
    font-size: 14px;
}

.status-number {
    font-size: 28px;
    font-weight: 700;
}


/* =========================================
   QUICK ACTIONS
========================================= */

.actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.action-link {
    text-decoration: none;
    color: #111827;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    transition: 0.2s;
}

.action-link:hover {
    border-color: #16803c;
    background: #f5faf6;
    transform: translateY(-2px);
}

.action-icon {
    font-size: 25px;
    margin-bottom: 10px;
}

.action-link strong {
    display: block;
    margin-bottom: 5px;
}

.action-link span {
    color: #6b7280;
    font-size: 12px;
}


/* =========================================
   TWO COLUMNS
========================================= */

.dashboard-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.panel-header h2 {
    font-size: 18px;
}

.view-all {
    color: #16803c;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}


/* =========================================
   COMPUTER LIST
========================================= */

.computer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f0f0f0;
}

.computer-item:last-child {
    border-bottom: none;
}

.computer-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.computer-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #eaf6ee;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.computer-info strong {
    display: block;
    font-size: 14px;
}

.computer-info span {
    color: #9ca3af;
    font-size: 11px;
}


/* =========================================
   STATUS BADGES
========================================= */

.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-working {
    background: #dcfce7;
    color: #15803d;
}

.badge-repair {
    background: #ffedd5;
    color: #c2410c;
}

.badge-broken {
    background: #fee2e2;
    color: #b91c1c;
}


/* =========================================
   EMPTY STATE
========================================= */

.empty-state {
    text-align: center;
    padding: 30px 10px;
    color: #6b7280;
}

.empty-icon {
    font-size: 40px;
    margin-bottom: 10px;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 1000px) {

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .status-grid {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-columns {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 700px) {

    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>

<div class="dashboard-container">


    <!-- =========================================
         SIDEBAR
    ========================================== -->

    <?php include "includes/sidebar.php"; ?>


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->

    <main class="main-content">

        <div class="dashboard-wrapper">


            <!-- =========================================
                 HEADER
            ========================================== -->

            <div class="page-header">

                <h1>
                    Admin Dashboard
                </h1>

                <p>
                    Welcome back,
                    <?= htmlspecialchars($admin_name) ?>.
                    Manage university resources, computers and users.
                </p>

            </div>


            <!-- =========================================
                 STATISTICS
            ========================================== -->

            <div class="stats-grid">


                <!-- TOTAL COMPUTERS -->

                <div class="stat-card">

                    <div class="stat-top">

                        <div>

                            <div class="stat-title">
                                Total Computers
                            </div>

                            <div class="stat-number">
                                <?= $total_computers ?>
                            </div>

                        </div>

                        <div class="stat-icon">
                            🖥️
                        </div>

                    </div>

                    <div class="stat-description">
                        All university computers
                    </div>

                </div>


                <!-- WORKING COMPUTERS -->

                <div class="stat-card">

                    <div class="stat-top">

                        <div>

                            <div class="stat-title">
                                Working Computers
                            </div>

                            <div class="stat-number">
                                <?= $working_computers ?>
                            </div>

                        </div>

                        <div class="stat-icon">
                            ✅
                        </div>

                    </div>

                    <div class="stat-description">
                        Currently operational
                    </div>

                </div>


                <!-- AVAILABLE RESOURCES -->

                <div class="stat-card">

                    <div class="stat-top">

                        <div>

                            <div class="stat-title">
                                Available Resources
                            </div>

                            <div class="stat-number">
                                <?= $available_resources ?>
                            </div>

                        </div>

                        <div class="stat-icon">
                            📦
                        </div>

                    </div>

                    <div class="stat-description">
                        Resources available for sharing
                    </div>

                </div>


                <!-- STUDENTS -->

                <div class="stat-card">

                    <div class="stat-top">

                        <div>

                            <div class="stat-title">
                                Students
                            </div>

                            <div class="stat-number">
                                <?= $total_students ?>
                            </div>

                        </div>

                        <div class="stat-icon">
                            👨‍🎓
                        </div>

                    </div>

                    <div class="stat-description">
                        Registered students
                    </div>

                </div>


            </div>


            <!-- =========================================
                 COMPUTER OVERVIEW
            ========================================== -->

            <div class="panel">

                <h2 class="section-title">
                    Computer Overview
                </h2>


                <div class="status-grid">


                    <!-- WORKING -->

                    <div class="status-box status-working">

                        <div class="status-box-header">

                            <span class="status-dot"></span>

                            <h3>
                                Working
                            </h3>

                        </div>

                        <div class="status-number">
                            <?= $working_computers ?>
                        </div>

                    </div>


                    <!-- NEEDS REPAIR -->

                    <div class="status-box status-repair">

                        <div class="status-box-header">

                            <span class="status-dot"></span>

                            <h3>
                                Needs Repair
                            </h3>

                        </div>

                        <div class="status-number">
                            <?= $repair_computers ?>
                        </div>

                    </div>


                    <!-- NOT REPAIRABLE -->

                    <div class="status-box status-broken">

                        <div class="status-box-header">

                            <span class="status-dot"></span>

                            <h3>
                                Not Repairable
                            </h3>

                        </div>

                        <div class="status-number">
                            <?= $not_repairable_computers ?>
                        </div>

                    </div>


                </div>

            </div>


            <!-- =========================================
                 QUICK ACTIONS
            ========================================== -->

            <div class="panel">

                <h2 class="section-title">
                    Quick Actions
                </h2>


                <div class="actions-grid">


                    <a href="computers.php" class="action-link">

                        <div class="action-icon">
                            🖥️
                        </div>

                        <strong>
                            Manage Computers
                        </strong>

                        <span>
                            View and update computer status
                        </span>

                    </a>


                    <a href="resources.php" class="action-link">

                        <div class="action-icon">
                            📦
                        </div>

                        <strong>
                            Manage Resources
                        </strong>

                        <span>
                            Review university resources
                        </span>

                    </a>


                    <a href="users.php" class="action-link">

                        <div class="action-icon">
                            👥
                        </div>

                        <strong>
                            Manage Users
                        </strong>

                        <span>
                            View students and staff
                        </span>

                    </a>


                </div>

            </div>


            <!-- =========================================
                 RECENT COMPUTERS + NEEDS REPAIR
            ========================================== -->

            <div class="dashboard-columns">


                <!-- RECENT COMPUTERS -->

                <div class="panel">

                    <div class="panel-header">

                        <h2>
                            Recent Computers
                        </h2>

                        <a href="computers.php" class="view-all">
                            View All
                        </a>

                    </div>


                    <?php if (empty($recent_computers)): ?>

                        <div class="empty-state">

                            <div class="empty-icon">
                                🖥️
                            </div>

                            No computers added yet.

                        </div>

                    <?php else: ?>


                        <?php foreach ($recent_computers as $computer): ?>

                            <div class="computer-item">


                                <div class="computer-info">

                                    <div class="computer-icon">
                                        🖥️
                                    </div>

                                    <div>

                                        <strong>

                                            <?= htmlspecialchars(
                                                trim(
                                                    ($computer["brand"] ?? "") .
                                                    " " .
                                                    ($computer["model"] ?? "")
                                                )
                                            ) ?>

                                        </strong>

                                        <span>

                                            <?= htmlspecialchars(
                                                $computer["location"] ?? "No location"
                                            ) ?>

                                        </span>

                                    </div>

                                </div>


                                <?php

                                $computer_status =
                                    strtolower(trim($computer["status"] ?? ""));

                                ?>


                                <?php if ($computer_status === "working"): ?>

                                    <span class="status-badge badge-working">
                                        Working
                                    </span>


                                <?php elseif ($computer_status === "needs_repair"): ?>

                                    <span class="status-badge badge-repair">
                                        Needs Repair
                                    </span>


                                <?php elseif ($computer_status === "not_repairable"): ?>

                                    <span class="status-badge badge-broken">
                                        Not Repairable
                                    </span>


                                <?php else: ?>

                                    <span class="status-badge">
                                        <?= htmlspecialchars(
                                            $computer["status"] ?? "Unknown"
                                        ) ?>
                                    </span>

                                <?php endif; ?>


                            </div>

                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


                <!-- =========================================
                     NEEDS REPAIR
                ========================================== -->

                <div class="panel">

                    <div class="panel-header">

                        <h2>
                            Needs Repair
                        </h2>

                        <a href="computers.php" class="view-all">
                            View All
                        </a>

                    </div>


                    <?php if (empty($repair_list)): ?>

                        <div class="empty-state">

                            <div class="empty-icon">
                                ✅
                            </div>

                            No computers currently need repair.

                        </div>

                    <?php else: ?>


                        <?php foreach ($repair_list as $computer): ?>

                            <div class="computer-item">


                                <div class="computer-info">

                                    <div class="computer-icon">
                                        🔧
                                    </div>

                                    <div>

                                        <strong>

                                            <?= htmlspecialchars(
                                                trim(
                                                    ($computer["brand"] ?? "") .
                                                    " " .
                                                    ($computer["model"] ?? "")
                                                )
                                            ) ?>

                                        </strong>

                                        <span>

                                            <?= htmlspecialchars(
                                                $computer["location"] ?? "No location"
                                            ) ?>

                                        </span>

                                    </div>

                                </div>


                                <span class="status-badge badge-repair">

                                    Needs Repair

                                </span>


                            </div>

                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


            </div>


            <!-- =========================================
                 PLATFORM SUMMARY
            ========================================== -->

            <div class="panel">

                <div class="panel-header">

                    <h2>
                        Platform Summary
                    </h2>

                </div>


                <div class="status-grid">


                    <!-- RESOURCES -->

                    <div class="status-box">

                        <div class="status-box-header">

                            <span>📦</span>

                            <h3>
                                Resources
                            </h3>

                        </div>

                        <div class="status-number">
                            <?= $total_resources ?>
                        </div>

                    </div>


                    <!-- USERS -->

                    <div class="status-box">

                        <div class="status-box-header">

                            <span>👥</span>

                            <h3>
                                Users
                            </h3>

                        </div>

                        <div class="status-number">
                            <?= $total_users ?>
                        </div>

                    </div>


                    <!-- COMPUTERS -->

                    <div class="status-box">

                        <div class="status-box-header">

                            <span>🖥️</span>

                            <h3>
                                Computers
                            </h3>

                        </div>

                        <div class="status-number">
                            <?= $total_computers ?>
                        </div>

                    </div>


                </div>

            </div>


        </div>

    </main>

</div>

</body>

</html>