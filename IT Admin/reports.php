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
   HELPER
==================================================== */

function getCount($pdo, $sql)
{
    try {

        $stmt = $pdo->query($sql);

        return (int)$stmt->fetchColumn();

    } catch (PDOException $e) {

        return 0;
    }
}


/* ====================================================
   GENERAL STATISTICS
==================================================== */

$totalUsers = getCount(
    $pdo,
    "SELECT COUNT(*) FROM users"
);

$totalStudents = getCount(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE role = 'student'"
);

$totalTechnicians = getCount(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE role = 'technician'"
);

$totalAdmins = getCount(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
);


/* ====================================================
   RESOURCE STATISTICS
==================================================== */

$totalResources = getCount(
    $pdo,
    "SELECT COUNT(*) FROM resource"
);

$availableResources = getCount(
    $pdo,
    "SELECT COUNT(*) FROM resource WHERE status = 'available'"
);

$donationResources = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM resource 
     WHERE availability_type = 'donation'"
);

$exchangeResources = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM resource 
     WHERE availability_type = 'exchange'"
);


/* ====================================================
   DONATION STATISTICS
==================================================== */

$totalDonations = getCount(
    $pdo,
    "SELECT COUNT(*) FROM donation"
);

$pendingDonations = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM donation 
     WHERE status = 'requested'"
);

$approvedDonations = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM donation 
     WHERE status = 'approved'"
);

$completedDonations = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM donation 
     WHERE status = 'completed'"
);


/* ====================================================
   EXCHANGE STATISTICS
==================================================== */

$totalExchanges = getCount(
    $pdo,
    "SELECT COUNT(*) FROM exchange"
);

$pendingExchanges = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM exchange 
     WHERE status = 'pending'"
);

$matchedExchanges = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM exchange 
     WHERE status = 'matched'"
);

$completedExchanges = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM exchange 
     WHERE status = 'completed'"
);


/* ====================================================
   UPCYCLING STATISTICS
==================================================== */

$totalUpcycling = getCount(
    $pdo,
    "SELECT COUNT(*) FROM upcycling_idea"
);

$pendingUpcycling = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM upcycling_idea 
     WHERE status = 'pending'"
);

$approvedUpcycling = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM upcycling_idea 
     WHERE status = 'approved'"
);


/* ====================================================
   COMPUTER STATISTICS
==================================================== */

$totalComputers = getCount(
    $pdo,
    "SELECT COUNT(*) FROM computer"
);

$workingComputers = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM computer 
     WHERE status = 'working'"
);

$repairComputers = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM computer 
     WHERE status = 'repair'"
);


/* ====================================================
   COMPONENT STATISTICS
==================================================== */

$totalComponents = getCount(
    $pdo,
    "SELECT COUNT(*) FROM component"
);

$availableComponents = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM component 
     WHERE status = 'available'"
);

$usedComponents = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM component 
     WHERE status = 'used'"
);


/* ====================================================
   REPAIR STATISTICS
==================================================== */

$totalRepairs = getCount(
    $pdo,
    "SELECT COUNT(*) FROM repair_requests"
);

$diagnosisRepairs = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM repair_requests 
     WHERE status = 'Needs Diagnosis'"
);

$completedRepairs = getCount(
    $pdo,
    "SELECT COUNT(*) 
     FROM repair_requests 
     WHERE status = 'Completed'"
);


/* ====================================================
   RECENT DONATIONS
==================================================== */

$recentDonations = [];

try {

    $stmt = $pdo->query("
        SELECT
            d.donation_id,
            d.resource_id,
            d.status,
            d.requested_at,
            donor.full_name AS donor_name,
            requester.full_name AS requester_name,
            r.name AS resource_name

        FROM donation d

        LEFT JOIN users donor
            ON d.donor_id = donor.user_id

        LEFT JOIN users requester
            ON d.requester_id = requester.user_id

        LEFT JOIN resource r
            ON d.resource_id = r.resource_id

        ORDER BY d.requested_at DESC

        LIMIT 8
    ");

    $recentDonations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $recentDonations = [];
}


/* ====================================================
   RECENT EXCHANGES
==================================================== */

$recentExchanges = [];

try {

    $stmt = $pdo->query("
        SELECT

            e.exchange_id,
            e.status,
            e.created_at,

            ua.full_name AS student_a,
            ub.full_name AS student_b

        FROM exchange e

        LEFT JOIN users ua
            ON e.student_a_id = ua.user_id

        LEFT JOIN users ub
            ON e.student_b_id = ub.user_id

        ORDER BY e.created_at DESC

        LIMIT 8
    ");

    $recentExchanges = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $recentExchanges = [];
}


/* ====================================================
   RECENT REPAIRS
==================================================== */

$recentRepairs = [];

try {

    $stmt = $pdo->query("
        SELECT

            rr.repair_id,
            rr.computer_id,
            rr.problem_description,
            rr.priority,
            rr.status,
            rr.created_at

        FROM repair_requests rr

        ORDER BY rr.created_at DESC

        LIMIT 8
    ");

    $recentRepairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $recentRepairs = [];
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
    Reports - UniShare
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

    margin-bottom: 30px;
}

.page-header h1 {

    margin: 0 0 8px;

    font-size: 32px;
}

.page-header p {

    margin: 0;

    color: #6b7280;

    font-size: 15px;
}


/* ====================================================
   STAT GRID
==================================================== */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 30px;
}


/* ====================================================
   STAT CARD
==================================================== */

.stat-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 22px;

    transition: 0.2s;
}

.stat-card:hover {

    transform: translateY(-3px);

    box-shadow:
        0 8px 20px rgba(0,0,0,0.06);
}

.stat-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 15px;
}

.stat-title {

    color: #6b7280;

    font-size: 13px;

    font-weight: 600;
}

.stat-icon {

    width: 42px;

    height: 42px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    background: #eaf6ee;
}

.stat-number {

    font-size: 30px;

    font-weight: 700;

    margin-bottom: 5px;
}

.stat-description {

    font-size: 12px;

    color: #9ca3af;
}


/* ====================================================
   SECTION
==================================================== */

.section {

    margin-bottom: 30px;
}

.section-title {

    margin-bottom: 15px;
}

.section-title h2 {

    margin: 0;

    font-size: 21px;
}

.section-title p {

    margin: 5px 0 0;

    color: #6b7280;

    font-size: 13px;
}


/* ====================================================
   CATEGORY GRID
==================================================== */

.category-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;
}

.category-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 22px;
}

.category-card h3 {

    margin: 0 0 18px;

    font-size: 18px;
}

.category-row {

    display: flex;

    justify-content: space-between;

    padding: 10px 0;

    border-bottom: 1px solid #f0f0f0;

    font-size: 13px;
}

.category-row:last-child {

    border-bottom: none;
}

.category-label {

    color: #6b7280;
}

.category-value {

    font-weight: 700;
}


/* ====================================================
   TABLE CARD
==================================================== */

.table-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    overflow: hidden;
}

.table-wrapper {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 800px;
}

thead {

    background: #f3f6f4;
}

th {

    padding: 14px;

    text-align: left;

    font-size: 12px;

    color: #374151;

    border-bottom: 1px solid #e5e7eb;
}

td {

    padding: 14px;

    font-size: 13px;

    border-bottom: 1px solid #f0f0f0;
}

tbody tr:hover {

    background: #fafafa;
}


/* ====================================================
   STATUS
==================================================== */

.status {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 600;

    text-transform: capitalize;
}

.status-requested {

    background: #fef3c7;

    color: #92400e;
}

.status-approved {

    background: #dbeafe;

    color: #1d4ed8;
}

.status-completed {

    background: #dcfce7;

    color: #166534;
}

.status-pending {

    background: #fef3c7;

    color: #92400e;
}

.status-matched {

    background: #dbeafe;

    color: #1d4ed8;
}

.status-rejected {

    background: #fee2e2;

    color: #b91c1c;
}

.status-transferred {

    background: #ede9fe;

    color: #6d28d9;
}

.status-needs-diagnosis {

    background: #fef3c7;

    color: #92400e;
}


/* ====================================================
   TWO COLUMN
==================================================== */

.two-column {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 25px;
}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 1100px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .category-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}

@media (max-width: 800px) {

    .main-content {

        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }

    .two-column {

        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {

    .stats-grid {

        grid-template-columns: 1fr;
    }

    .category-grid {

        grid-template-columns: 1fr;
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
         MAIN
    ==================================================== -->

    <main class="main-content">


        <!-- HEADER -->

        <div class="page-header">

            <h1>
                Reports & Analytics 📊
            </h1>

            <p>
                Overview of the UniShare platform activity and performance.
            </p>

        </div>


        <!-- ====================================================
             MAIN STATISTICS
        ==================================================== -->

        <div class="stats-grid">


            <!-- USERS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Total Users
                    </span>

                    <div class="stat-icon">
                        👥
                    </div>

                </div>

                <div class="stat-number">
                    <?= $totalUsers ?>
                </div>

                <div class="stat-description">
                    All registered users
                </div>

            </div>


            <!-- RESOURCES -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Total Resources
                    </span>

                    <div class="stat-icon">
                        📦
                    </div>

                </div>

                <div class="stat-number">
                    <?= $totalResources ?>
                </div>

                <div class="stat-description">
                    Shared resources
                </div>

            </div>


            <!-- DONATIONS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Donations
                    </span>

                    <div class="stat-icon">
                        🎁
                    </div>

                </div>

                <div class="stat-number">
                    <?= $totalDonations ?>
                </div>

                <div class="stat-description">
                    Donation activities
                </div>

            </div>


            <!-- EXCHANGE -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Exchanges
                    </span>

                    <div class="stat-icon">
                        🔄
                    </div>

                </div>

                <div class="stat-number">
                    <?= $totalExchanges ?>
                </div>

                <div class="stat-description">
                    Resource exchanges
                </div>

            </div>


            <!-- COMPUTERS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Computers
                    </span>

                    <div class="stat-icon">
                        💻
                    </div>

                </div>

                <div class="stat-number">
                    <?= $totalComputers ?>
                </div>

                <div class="stat-description">
                    Registered computers
                </div>

            </div>


            <!-- COMPONENTS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Components
                    </span>

                    <div class="stat-icon">
                        🔧
                    </div>

                </div>

                <div class="stat-number">
                    <?= $totalComponents ?>
                </div>

                <div class="stat-description">
                    Computer components
                </div>

            </div>


            <!-- REPAIRS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Repair Requests
                    </span>

                    <div class="stat-icon">
                        🛠️
                    </div>

                </div>

                <div class="stat-number">
                    <?= $totalRepairs ?>
                </div>

                <div class="stat-description">
                    Computer repair requests
                </div>

            </div>


            <!-- UPCYCLING -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Upcycling Ideas
                    </span>

                    <div class="stat-icon">
                        ♻️
                    </div>

                </div>

                <div class="stat-number">
                    <?= $totalUpcycling ?>
                </div>

                <div class="stat-description">
                    Submitted ideas
                </div>

            </div>


        </div>


        <!-- ====================================================
             PLATFORM BREAKDOWN
        ==================================================== -->

        <section class="section">

            <div class="section-title">

                <h2>
                    Platform Overview
                </h2>

                <p>
                    Detailed breakdown of the platform data.
                </p>

            </div>


            <div class="category-grid">


                <!-- USERS -->

                <div class="category-card">

                    <h3>
                        👥 Users
                    </h3>

                    <div class="category-row">

                        <span class="category-label">
                            Students
                        </span>

                        <span class="category-value">
                            <?= $totalStudents ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Technicians
                        </span>

                        <span class="category-value">
                            <?= $totalTechnicians ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Administrators
                        </span>

                        <span class="category-value">
                            <?= $totalAdmins ?>
                        </span>

                    </div>

                </div>


                <!-- RESOURCES -->

                <div class="category-card">

                    <h3>
                        📦 Resources
                    </h3>

                    <div class="category-row">

                        <span class="category-label">
                            Total Resources
                        </span>

                        <span class="category-value">
                            <?= $totalResources ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Available
                        </span>

                        <span class="category-value">
                            <?= $availableResources ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            For Donation
                        </span>

                        <span class="category-value">
                            <?= $donationResources ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            For Exchange
                        </span>

                        <span class="category-value">
                            <?= $exchangeResources ?>
                        </span>

                    </div>

                </div>


                <!-- DONATIONS -->

                <div class="category-card">

                    <h3>
                        🎁 Donations
                    </h3>

                    <div class="category-row">

                        <span class="category-label">
                            Total
                        </span>

                        <span class="category-value">
                            <?= $totalDonations ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Requested
                        </span>

                        <span class="category-value">
                            <?= $pendingDonations ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Approved
                        </span>

                        <span class="category-value">
                            <?= $approvedDonations ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Completed
                        </span>

                        <span class="category-value">
                            <?= $completedDonations ?>
                        </span>

                    </div>

                </div>


                <!-- EXCHANGE -->

                <div class="category-card">

                    <h3>
                        🔄 Exchange
                    </h3>

                    <div class="category-row">

                        <span class="category-label">
                            Total
                        </span>

                        <span class="category-value">
                            <?= $totalExchanges ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Pending
                        </span>

                        <span class="category-value">
                            <?= $pendingExchanges ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Matched
                        </span>

                        <span class="category-value">
                            <?= $matchedExchanges ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Completed
                        </span>

                        <span class="category-value">
                            <?= $completedExchanges ?>
                        </span>

                    </div>

                </div>


                <!-- REPAIR -->

                <div class="category-card">

                    <h3>
                        🛠️ Repairs
                    </h3>

                    <div class="category-row">

                        <span class="category-label">
                            Total Requests
                        </span>

                        <span class="category-value">
                            <?= $totalRepairs ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Needs Diagnosis
                        </span>

                        <span class="category-value">
                            <?= $diagnosisRepairs ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Completed
                        </span>

                        <span class="category-value">
                            <?= $completedRepairs ?>
                        </span>

                    </div>

                </div>


                <!-- COMPUTER -->

                <div class="category-card">

                    <h3>
                        💻 Computers & Components
                    </h3>

                    <div class="category-row">

                        <span class="category-label">
                            Computers
                        </span>

                        <span class="category-value">
                            <?= $totalComputers ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Working
                        </span>

                        <span class="category-value">
                            <?= $workingComputers ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Components
                        </span>

                        <span class="category-value">
                            <?= $totalComponents ?>
                        </span>

                    </div>

                    <div class="category-row">

                        <span class="category-label">
                            Available Components
                        </span>

                        <span class="category-value">
                            <?= $availableComponents ?>
                        </span>

                    </div>

                </div>


            </div>

        </section>


        <!-- ====================================================
             RECENT ACTIVITY
        ==================================================== -->

        <div class="two-column">


            <!-- =================================================
                 RECENT DONATIONS
            ================================================= -->

            <section class="section">

                <div class="section-title">

                    <h2>
                        Recent Donations
                    </h2>

                    <p>
                        Latest donation activities.
                    </p>

                </div>


                <div class="table-card">

                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Resource
                                    </th>

                                    <th>
                                        Donor
                                    </th>

                                    <th>
                                        Requester
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php if (empty($recentDonations)): ?>

                                <tr>

                                    <td
                                        colspan="4"
                                        style="text-align:center;"
                                    >
                                        No donation activity.
                                    </td>

                                </tr>

                            <?php else: ?>


                                <?php foreach (
                                    $recentDonations
                                    as $donation
                                ): ?>

                                    <?php

                                    $status =
                                        strtolower(
                                            trim(
                                                $donation["status"]
                                                ?? ""
                                            )
                                        );

                                    $statusClass =
                                        "status-" .
                                        str_replace(
                                            " ",
                                            "-",
                                            $status
                                        );

                                    ?>


                                    <tr>

                                        <td>

                                            <?= htmlspecialchars(
                                                $donation["resource_name"]
                                                ?: "Resource #" .
                                                $donation["resource_id"]
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $donation["donor_name"]
                                                ?: "Unknown"
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $donation["requester_name"]
                                                ?: "No requester"
                                            ) ?>

                                        </td>

                                        <td>

                                            <span
                                                class="status <?= htmlspecialchars($statusClass) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $donation["status"]
                                                    ?: "Unknown"
                                                ) ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 RECENT EXCHANGES
            ================================================= -->

            <section class="section">

                <div class="section-title">

                    <h2>
                        Recent Exchanges
                    </h2>

                    <p>
                        Latest resource exchange activities.
                    </p>

                </div>


                <div class="table-card">

                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        Student A
                                    </th>

                                    <th>
                                        Student B
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (empty($recentExchanges)): ?>

                                <tr>

                                    <td
                                        colspan="4"
                                        style="text-align:center;"
                                    >
                                        No exchange activity.
                                    </td>

                                </tr>

                            <?php else: ?>


                                <?php foreach (
                                    $recentExchanges
                                    as $exchange
                                ): ?>

                                    <?php

                                    $status =
                                        strtolower(
                                            trim(
                                                $exchange["status"]
                                                ?? ""
                                            )
                                        );

                                    $statusClass =
                                        "status-" .
                                        str_replace(
                                            " ",
                                            "-",
                                            $status
                                        );

                                    ?>


                                    <tr>

                                        <td>

                                            #<?= (int)
                                                $exchange["exchange_id"] ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $exchange["student_a"]
                                                ?: "Unknown"
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $exchange["student_b"]
                                                ?: "Unknown"
                                            ) ?>

                                        </td>

                                        <td>

                                            <span
                                                class="status <?= htmlspecialchars($statusClass) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $exchange["status"]
                                                    ?: "Unknown"
                                                ) ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                            <?php endif; ?>


                            </tbody>

                        </table>

                    </div>

                </div>

            </section>


        </div>


        <!-- ====================================================
             RECENT REPAIRS
        ==================================================== -->

        <section class="section">

            <div class="section-title">

                <h2>
                    Recent Repair Requests
                </h2>

                <p>
                    Latest computer repair requests.
                </p>

            </div>


            <div class="table-card">

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Repair ID
                                </th>

                                <th>
                                    Computer
                                </th>

                                <th>
                                    Problem
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created At
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($recentRepairs)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    style="text-align:center;"
                                >
                                    No repair requests.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach (
                                $recentRepairs
                                as $repair
                            ): ?>

                                <?php

                                $status =
                                    strtolower(
                                        trim(
                                            $repair["status"]
                                            ?? ""
                                        )
                                    );

                                $statusClass =
                                    "status-" .
                                    str_replace(
                                        " ",
                                        "-",
                                        $status
                                    );

                                ?>


                                <tr>

                                    <td>

                                        #<?= (int)
                                            $repair["repair_id"] ?>

                                    </td>

                                    <td>

                                        Computer #

                                        <?= (int)
                                            $repair["computer_id"] ?>

                                    </td>

                                    <td>

                                        <?php

                                        $problem =
                                            $repair[
                                                "problem_description"
                                            ] ?? "";

                                        if (
                                            strlen($problem) > 60
                                        ) {

                                            echo htmlspecialchars(
                                                substr(
                                                    $problem,
                                                    0,
                                                    60
                                                )
                                            ) . "...";

                                        } else {

                                            echo htmlspecialchars(
                                                $problem
                                            );
                                        }

                                        ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $repair["priority"]
                                            ?: "Medium"
                                        ) ?>

                                    </td>

                                    <td>

                                        <span
                                            class="status <?= htmlspecialchars($statusClass) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $repair["status"]
                                                ?: "Unknown"
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $repair["created_at"]
                                            ?: "N/A"
                                        ) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </section>


    </main>


</div>


</body>

</html>