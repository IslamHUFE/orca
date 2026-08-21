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

$technician_id = $_SESSION["user_id"];

$status_filter = trim($_GET["status"] ?? "");
$search = trim($_GET["search"] ?? "");


/* ====================================================
   GET REPAIR HISTORY
==================================================== */

$repairs = [];

try {

    $sql = "

        SELECT

            rr.repair_id,
            rr.computer_id,
            rr.problem_description,
            rr.diagnosis,
            rr.faulty_component_id,
            rr.repair_action,
            rr.priority,
            rr.status,
            rr.created_at,
            rr.completed_at,

            c.brand,
            c.model,
            c.serial_number,
            c.location,
            c.image,

            comp.type AS component_type,
            comp.model AS component_model,
            comp.serial_number AS component_serial

        FROM repair_requests rr

        INNER JOIN computer c
            ON rr.computer_id = c.computer_id

        LEFT JOIN component comp
            ON rr.faulty_component_id = comp.component_id

        WHERE rr.technician_id = ?

    ";

    $params = [$technician_id];


    /* ====================================================
       STATUS FILTER
    ==================================================== */

    if (!empty($status_filter)) {

        $sql .= " AND rr.status = ? ";

        $params[] = $status_filter;
    }


    /* ====================================================
       SEARCH
    ==================================================== */

    if (!empty($search)) {

        $sql .= "

            AND (
                c.brand LIKE ?
                OR c.model LIKE ?
                OR c.serial_number LIKE ?
                OR rr.problem_description LIKE ?
                OR rr.diagnosis LIKE ?
            )

        ";

        $search_value = "%" . $search . "%";

        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
    }


    /* ====================================================
       ORDER
    ==================================================== */

    $sql .= "

        ORDER BY
            rr.created_at DESC

    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $error = "Unable to load repair history.";

}


/* ====================================================
   STATISTICS
==================================================== */

$total_repairs = 0;
$completed_repairs = 0;
$under_repair = 0;
$needs_diagnosis = 0;

try {

    $stmt = $pdo->prepare("

        SELECT

            COUNT(*) AS total_repairs,

            SUM(
                CASE
                    WHEN status = 'Completed'
                    THEN 1
                    ELSE 0
                END
            ) AS completed_repairs,

            SUM(
                CASE
                    WHEN status = 'Under Repair'
                    THEN 1
                    ELSE 0
                END
            ) AS under_repair,

            SUM(
                CASE
                    WHEN status = 'Needs Diagnosis'
                    THEN 1
                    ELSE 0
                END
            ) AS needs_diagnosis

        FROM repair_requests

        WHERE technician_id = ?

    ");

    $stmt->execute([$technician_id]);

    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats) {

        $total_repairs =
            (int)$stats["total_repairs"];

        $completed_repairs =
            (int)$stats["completed_repairs"];

        $under_repair =
            (int)$stats["under_repair"];

        $needs_diagnosis =
            (int)$stats["needs_diagnosis"];
    }

} catch (PDOException $e) {

    // Keep default statistics as zero.

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
    Repair History - UniShare
</title>


<style>

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
   ERROR
==================================================== */

.error {

    padding: 13px 16px;

    margin-bottom: 20px;

    border-radius: 8px;

    background: #fee2e2;

    color: #b91c1c;
}


/* ====================================================
   STATISTICS
==================================================== */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 28px;
}

.stat-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 20px;

    box-shadow:
        0 2px 8px rgba(0,0,0,0.03);
}

.stat-title {

    color: #6b7280;

    font-size: 13px;

    margin-bottom: 10px;
}

.stat-number {

    font-size: 28px;

    font-weight: 700;
}

.stat-card.total .stat-number {

    color: #16803c;
}

.stat-card.completed .stat-number {

    color: #15803d;
}

.stat-card.repair .stat-number {

    color: #d97706;
}

.stat-card.pending .stat-number {

    color: #dc2626;
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
}

.search-box {

    flex: 1;
}

.search-box input,
.filter-form select {

    width: 100%;

    padding: 11px 13px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    font-size: 14px;

    outline: none;

    background: white;
}

.search-box input:focus,
.filter-form select:focus {

    border-color: #16803c;

    box-shadow:
        0 0 0 3px rgba(22,128,60,0.08);
}

.filter-form select {

    width: 190px;
}

.filter-btn {

    padding: 11px 20px;

    border: none;

    border-radius: 8px;

    background: #16803c;

    color: white;

    font-weight: 600;

    cursor: pointer;
}

.filter-btn:hover {

    background: #126b32;
}

.clear-btn {

    padding: 11px 18px;

    border-radius: 8px;

    background: #f3f4f6;

    color: #374151;

    text-decoration: none;

    font-weight: 600;
}

.clear-btn:hover {

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
}

.table-header {

    padding: 20px 22px;

    border-bottom: 1px solid #e5e7eb;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.table-header h2 {

    margin: 0;

    font-size: 20px;
}

.results-count {

    color: #6b7280;

    font-size: 14px;
}


/* ====================================================
   TABLE
==================================================== */

.table-wrapper {

    width: 100%;

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;
}

thead {

    background: #f8faf9;
}

th {

    padding: 14px 16px;

    text-align: left;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: 0.4px;

    color: #6b7280;

    border-bottom: 1px solid #e5e7eb;
}

td {

    padding: 16px;

    border-bottom: 1px solid #f0f0f0;

    font-size: 14px;

    vertical-align: middle;
}

tbody tr:hover {

    background: #fafafa;
}


/* ====================================================
   COMPUTER
==================================================== */

.computer-cell {

    display: flex;

    align-items: center;

    gap: 12px;
}

.computer-image {

    width: 55px;

    height: 45px;

    border-radius: 8px;

    overflow: hidden;

    background: #f1f5f3;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;
}

.computer-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}

.no-image {

    font-size: 22px;

    color: #9ca3af;
}

.computer-name {

    font-weight: 600;

    color: #111827;
}

.serial {

    color: #6b7280;

    font-size: 12px;

    margin-top: 3px;
}


/* ====================================================
   PROBLEM / DIAGNOSIS
==================================================== */

.description {

    max-width: 250px;

    color: #4b5563;

    line-height: 1.5;
}


/* ====================================================
   COMPONENT
==================================================== */

.component-name {

    font-weight: 600;

    color: #374151;
}

.component-serial {

    color: #6b7280;

    font-size: 12px;

    margin-top: 3px;
}

.no-component {

    color: #9ca3af;
}


/* ====================================================
   BADGES
==================================================== */

.badge {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;

    white-space: nowrap;
}


/* STATUS */

.status-completed {

    background: #dcfce7;

    color: #166534;
}

.status-repair {

    background: #fef3c7;

    color: #92400e;
}

.status-diagnosis {

    background: #fee2e2;

    color: #991b1b;
}


/* PRIORITY */

.priority-high {

    background: #fee2e2;

    color: #991b1b;
}

.priority-medium {

    background: #fef3c7;

    color: #92400e;
}

.priority-low {

    background: #dcfce7;

    color: #166534;
}


/* ====================================================
   ACTION
==================================================== */

.view-btn {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 8px 12px;

    border-radius: 7px;

    background: #f0fdf4;

    color: #166534;

    text-decoration: none;

    font-size: 12px;

    font-weight: 600;
}

.view-btn:hover {

    background: #dcfce7;
}


/* ====================================================
   EMPTY STATE
==================================================== */

.empty-state {

    padding: 60px 20px;

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
}

.empty-state p {

    margin: 0;

    font-size: 14px;
}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 1000px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    .main-content {

        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }

    .stats-grid {

        grid-template-columns: 1fr;
    }

    .filter-form {

        flex-direction: column;

        align-items: stretch;
    }

    .filter-form select {

        width: 100%;
    }

}

</style>

</head>


<body>


<div class="dashboard-container">


    <!-- ====================================================
         SIDEBAR
    ==================================================== -->

    <!-- <?php include "technician_sidebar.php"; ?> -->
     <?PHP
     include __DIR__ . "/includes/sidebar.php";
?>

    <!-- ====================================================
         MAIN CONTENT
    ==================================================== -->

    <main class="main-content">


        <!-- HEADER -->

        <div class="page-header">

            <h1>
                Repair History 🔧
            </h1>

            <p>
                View and manage your assigned repair requests.
            </p>

        </div>


        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- ====================================================
             STATISTICS
        ==================================================== -->

        <div class="stats-grid">


            <div class="stat-card total">

                <div class="stat-title">
                    Total Repairs
                </div>

                <div class="stat-number">
                    <?= $total_repairs ?>
                </div>

            </div>


            <div class="stat-card completed">

                <div class="stat-title">
                    Completed
                </div>

                <div class="stat-number">
                    <?= $completed_repairs ?>
                </div>

            </div>


            <div class="stat-card repair">

                <div class="stat-title">
                    Under Repair
                </div>

                <div class="stat-number">
                    <?= $under_repair ?>
                </div>

            </div>


            <div class="stat-card pending">

                <div class="stat-title">
                    Needs Diagnosis
                </div>

                <div class="stat-number">
                    <?= $needs_diagnosis ?>
                </div>

            </div>


        </div>


        <!-- ====================================================
             FILTERS
        ==================================================== -->

        <div class="filter-card">

            <form
                method="GET"
                class="filter-form"
            >


                <div class="search-box">

                    <input
                        type="text"
                        name="search"
                        placeholder="Search by computer, serial number, or problem..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>


                <select name="status">

                    <option value="">
                        All Statuses
                    </option>

                    <option
                        value="Needs Diagnosis"
                        <?= $status_filter === "Needs Diagnosis"
                            ? "selected"
                            : "" ?>
                    >
                        Needs Diagnosis
                    </option>

                    <option
                        value="Under Repair"
                        <?= $status_filter === "Under Repair"
                            ? "selected"
                            : "" ?>
                    >
                        Under Repair
                    </option>

                    <option
                        value="Completed"
                        <?= $status_filter === "Completed"
                            ? "selected"
                            : "" ?>
                    >
                        Completed
                    </option>

                </select>


                <button
                    type="submit"
                    class="filter-btn"
                >
                    Search
                </button>


                <a
                    href="technician_repair_history.php"
                    class="clear-btn"
                >
                    Clear
                </a>


            </form>

        </div>


        <!-- ====================================================
             REPAIR TABLE
        ==================================================== -->

        <div class="table-card">


            <div class="table-header">

                <h2>
                    Repair Requests
                </h2>

                <span class="results-count">

                    <?= count($repairs) ?>
                    result(s)

                </span>

            </div>


            <?php if (!empty($repairs)): ?>


                <div class="table-wrapper">

                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Computer
                                </th>

                                <th>
                                    Problem
                                </th>

                                <th>
                                    Diagnosis
                                </th>

                                <th>
                                    Faulty Component
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($repairs as $repair): ?>


                                <tr>


                                    <!-- COMPUTER -->

                                    <td>

                                        <div class="computer-cell">


                                            <div class="computer-image">

                                                <?php if (
                                                    !empty(
                                                        $repair["image"]
                                                    )
                                                ): ?>

                                                    <img
                                                        src="../IT Admin/uploads/computers/<?= htmlspecialchars($repair["image"]) ?>"
                                                        alt="Computer"
                                                        onerror="this.style.display='none';"
                                                    >

                                                <?php else: ?>

                                                    <div class="no-image">
                                                        🖥️
                                                    </div>

                                                <?php endif; ?>

                                            </div>


                                            <div>

                                                <div class="computer-name">

                                                    <?= htmlspecialchars(
                                                        $repair["brand"]
                                                        . " "
                                                        . $repair["model"]
                                                    ) ?>

                                                </div>


                                                <div class="serial">

                                                    <?= htmlspecialchars(
                                                        $repair["serial_number"]
                                                        ?: "No Serial"
                                                    ) ?>

                                                </div>

                                            </div>


                                        </div>

                                    </td>


                                    <!-- PROBLEM -->

                                    <td>

                                        <div class="description">

                                            <?= htmlspecialchars(
                                                $repair["problem_description"]
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- DIAGNOSIS -->

                                    <td>

                                        <div class="description">

                                            <?= !empty(
                                                $repair["diagnosis"]
                                            )
                                                ? htmlspecialchars(
                                                    $repair["diagnosis"]
                                                )
                                                : '<span style="color:#9ca3af;">Not diagnosed</span>'
                                            ?>

                                        </div>

                                    </td>


                                    <!-- COMPONENT -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $repair["faulty_component_id"]
                                            )
                                        ): ?>


                                            <div class="component-name">

                                                <?= htmlspecialchars(
                                                    $repair["component_type"]
                                                    ?: "Component"
                                                ) ?>

                                                <?php if (
                                                    !empty(
                                                        $repair["component_model"]
                                                    )
                                                ): ?>

                                                    -
                                                    <?= htmlspecialchars(
                                                        $repair["component_model"]
                                                    ) ?>

                                                <?php endif; ?>

                                            </div>


                                            <?php if (
                                                !empty(
                                                    $repair["component_serial"]
                                                )
                                            ): ?>

                                                <div class="component-serial">

                                                    <?= htmlspecialchars(
                                                        $repair["component_serial"]
                                                    ) ?>

                                                </div>

                                            <?php endif; ?>


                                        <?php else: ?>

                                            <span class="no-component">
                                                None
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- PRIORITY -->

                                    <td>

                                        <?php

                                        $priority_class =
                                            "priority-medium";

                                        if (
                                            $repair["priority"]
                                            === "High"
                                        ) {

                                            $priority_class =
                                                "priority-high";

                                        } elseif (
                                            $repair["priority"]
                                            === "Low"
                                        ) {

                                            $priority_class =
                                                "priority-low";
                                        }

                                        ?>

                                        <span
                                            class="badge <?= $priority_class ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $repair["priority"]
                                                ?: "Medium"
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $status_class =
                                            "status-diagnosis";

                                        if (
                                            $repair["status"]
                                            === "Completed"
                                        ) {

                                            $status_class =
                                                "status-completed";

                                        } elseif (
                                            $repair["status"]
                                            === "Under Repair"
                                        ) {

                                            $status_class =
                                                "status-repair";
                                        }

                                        ?>

                                        <span
                                            class="badge <?= $status_class ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $repair["status"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            date(
                                                "M d, Y",
                                                strtotime(
                                                    $repair["created_at"]
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                       <a
    href="./diagnose.php?id=<?= (int)$repair["repair_id"] ?>"
    class="view-btn"
>
    🔍 View
</a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>

                </div>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        🔧
                    </div>

                    <h3>
                        No Repair Requests Found
                    </h3>

                    <p>
                        There are no repair requests matching your search.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>