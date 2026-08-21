<?php

session_start();

/* ====================================================
   AUTHENTICATION
==================================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION["role"] !== "technician") {
    header("Location: ../index.php");
    exit();
}


/* ====================================================
   DATABASE
==================================================== */

require_once __DIR__ . "/../db.php";

$technician_id = $_SESSION["user_id"];


/* ====================================================
   GET REPAIR REQUESTS
==================================================== */

$repair_requests = [];
$error = "";

try {

    $stmt = $pdo->prepare("
        SELECT
            rr.repair_id,
            rr.problem_description,
            rr.diagnosis,
            rr.repair_action,
            rr.priority,
            rr.status,
            rr.created_at,
            rr.completed_at,

            c.brand,
            c.model,
            c.serial_number,
            c.location

        FROM repair_requests rr

        INNER JOIN computer c
            ON rr.computer_id = c.computer_id

        WHERE rr.technician_id = ?

        ORDER BY rr.repair_id DESC
    ");

    $stmt->execute([
        $technician_id
    ]);

    $repair_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $error = "Unable to load repair requests.";
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
    Repair Requests - UniShare
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

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;
}

.page-header h1 {

    margin: 0 0 7px;

    font-size: 30px;
}

.page-header p {

    margin: 0;

    color: #6b7280;

    font-size: 15px;
}


/* ====================================================
   BACK BUTTON
==================================================== */

.back-btn {

    text-decoration: none;

    background: #eaf6ee;

    color: #16803c;

    padding: 10px 16px;

    border-radius: 8px;

    font-size: 13px;

    font-weight: 600;
}

.back-btn:hover {

    background: #16803c;

    color: white;
}


/* ====================================================
   ERROR
==================================================== */

.error {

    background: #fee2e2;

    color: #b91c1c;

    padding: 14px 18px;

    border-radius: 8px;

    margin-bottom: 20px;
}


/* ====================================================
   CARD
==================================================== */

.requests-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 25px;

    box-shadow:
        0 2px 8px
        rgba(0, 0, 0, 0.03);
}


/* ====================================================
   TABLE
==================================================== */

.table-container {

    width: 100%;

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 900px;
}

thead {

    background: #f8faf9;
}

th {

    text-align: left;

    padding: 14px;

    font-size: 13px;

    color: #6b7280;

    font-weight: 600;

    border-bottom:
        1px solid #e5e7eb;
}

td {

    padding: 16px 14px;

    font-size: 14px;

    border-bottom:
        1px solid #f0f0f0;

    color: #374151;
}

tbody tr:hover {

    background: #fafdfb;
}


/* ====================================================
   COMPUTER
==================================================== */

.computer-name {

    font-weight: 600;

    color: #111827;
}

.serial {

    display: block;

    margin-top: 4px;

    font-size: 12px;

    color: #9ca3af;
}


/* ====================================================
   PROBLEM
==================================================== */

.problem {

    max-width: 250px;

    line-height: 1.5;
}


/* ====================================================
   PRIORITY
==================================================== */

.priority {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;
}

.priority.high {

    background: #fee2e2;

    color: #b91c1c;
}

.priority.medium {

    background: #fef3c7;

    color: #92400e;
}

.priority.low {

    background: #dcfce7;

    color: #166534;
}


/* ====================================================
   STATUS
==================================================== */

.status {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;
}

.status.pending {

    background: #fff7ed;

    color: #c2410c;
}

.status.repair {

    background: #eff6ff;

    color: #1d4ed8;
}

.status.completed {

    background: #dcfce7;

    color: #15803d;
}


/* ====================================================
   ACTION
==================================================== */

.action-btn {

    display: inline-block;

    padding: 8px 13px;

    background: #eaf6ee;

    color: #16803c;

    border-radius: 7px;

    text-decoration: none;

    font-size: 12px;

    font-weight: 600;

    transition: 0.2s;
}

.action-btn:hover {

    background: #16803c;

    color: white;
}


/* ====================================================
   EMPTY
==================================================== */

.empty-state {

    text-align: center;

    padding: 60px 20px;

    color: #6b7280;
}

.empty-icon {

    font-size: 55px;

    margin-bottom: 10px;
}

.empty-state h2 {

    color: #374151;

    margin-bottom: 8px;
}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 700px) {

    .main-content {

        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }

    .page-header {

        align-items: flex-start;

        gap: 15px;

        flex-direction: column;
    }
}

</style>

</head>


<body>


<div class="dashboard-container">


    <!-- ====================================================
         SIDEBAR
    ==================================================== -->

    <?php include "technician_sidebar.php"; ?>


    <!-- ====================================================
         MAIN CONTENT
    ==================================================== -->

    <main class="main-content">


        <!-- HEADER -->

        <div class="page-header">

            <div>

                <h1>
                    Repair Requests 🔧
                </h1>

                <p>
                    View and manage your assigned repair requests.
                </p>

            </div>


            <a
                href="technician_dashboard.php"
                class="back-btn"
            >
                ← Dashboard
            </a>

        </div>


        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- ====================================================
             REQUESTS
        ==================================================== -->

        <section class="requests-card">


            <div class="table-container">

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
                                Priority
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($repair_requests)): ?>


                        <tr>

                            <td
                                colspan="6"
                                style="
                                    text-align:center;
                                    padding:50px;
                                "
                            >

                                <div class="empty-state">

                                    <div class="empty-icon">
                                        🔧
                                    </div>

                                    <h2>
                                        No Repair Requests
                                    </h2>

                                    <p>
                                        You currently have no repair requests assigned to you.
                                    </p>

                                </div>

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($repair_requests as $request): ?>


                            <?php

                            $priority =
                                strtolower(
                                    $request["priority"] ?? "Medium"
                                );

                            $status =
                                $request["status"] ?? "Needs Diagnosis";


                            ?>


                            <tr>


                                <!-- COMPUTER -->

                                <td>

                                    <span class="computer-name">

                                        <?= htmlspecialchars(
                                            ($request["brand"] ?? "")
                                            . " "
                                            . ($request["model"] ?? "")
                                        ) ?>

                                    </span>


                                    <span class="serial">

                                        Serial:
                                        <?= htmlspecialchars(
                                            $request["serial_number"]
                                            ?: "N/A"
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PROBLEM -->

                                <td>

                                    <div class="problem">

                                        <?= htmlspecialchars(
                                            $request["problem_description"]
                                        ) ?>

                                    </div>

                                </td>


                                <!-- PRIORITY -->

                                <td>

                                    <span
                                        class="priority
                                        <?= htmlspecialchars($priority) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $request["priority"]
                                            ?: "Medium"
                                        ) ?>

                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $status === "Needs Diagnosis"
                                    ): ?>

                                        <span class="status pending">

                                            Needs Diagnosis

                                        </span>


                                    <?php elseif (
                                        $status === "Under Repair"
                                    ): ?>

                                        <span class="status repair">

                                            Under Repair

                                        </span>


                                    <?php elseif (
                                        $status === "Completed"
                                    ): ?>

                                        <span class="status completed">

                                            Completed

                                        </span>


                                    <?php else: ?>

                                        <span class="status pending">

                                            <?= htmlspecialchars($status) ?>

                                        </span>

                                    <?php endif; ?>


                                </td>


                                <!-- CREATED -->

                                <td>

                                    <?= htmlspecialchars(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $request["created_at"]
                                            )
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="diagnose.php?id=<?= (int)$request["repair_id"] ?>"
                                        class="action-btn"
                                    >


                                        <?php if (
                                            $status === "Needs Diagnosis"
                                        ): ?>

                                            Diagnose

                                        <?php elseif (
                                            $status === "Under Repair"
                                        ): ?>

                                            Continue

                                        <?php else: ?>

                                            View

                                        <?php endif; ?>


                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>


                </table>

            </div>


        </section>


    </main>


</div>


</body>

</html>