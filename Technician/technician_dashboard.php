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

require_once "../db.php";

$technician_id = $_SESSION["user_id"];

$message = "";
$error = "";


/* ====================================================
   CREATE REPAIR REQUEST
==================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $computer_id = (int)($_POST["computer_id"] ?? 0);

    $problem_description =
        trim($_POST["problem_description"] ?? "");

    $priority =
        trim($_POST["priority"] ?? "Medium");


    if (
        $computer_id <= 0 ||
        empty($problem_description)
    ) {

        $error = "Please enter the problem description.";

    } else {

        try {

            /* Check that computer needs repair */

            $stmt = $pdo->prepare("
                SELECT computer_id
                FROM computer
                WHERE computer_id = ?
                AND status = 'needs_repair'
            ");

            $stmt->execute([
                $computer_id
            ]);

            $computer =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$computer) {

                $error =
                    "This computer is not available for repair.";

            } else {

                /* Check existing active repair request */

                $stmt = $pdo->prepare("
                    SELECT repair_id
                    FROM repair_requests
                    WHERE computer_id = ?
                    AND status != 'Completed'
                    LIMIT 1
                ");

                $stmt->execute([
                    $computer_id
                ]);

                $existing =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                if ($existing) {

                    $error =
                        "A repair request already exists for this computer.";

                } else {

                    /* Create repair request */

                    $stmt = $pdo->prepare("
                        INSERT INTO repair_requests
                        (
                            computer_id,
                            technician_id,
                            problem_description,
                            priority,
                            status
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            'Needs Diagnosis'
                        )
                    ");

                    $stmt->execute([
                        $computer_id,
                        $technician_id,
                        $problem_description,
                        $priority
                    ]);

                    $message =
                        "Repair request created successfully.";
                }
            }

        } catch (PDOException $e) {

            $error =
                "Failed to create repair request.";
        }
    }
}


/* ====================================================
   GET STATISTICS
==================================================== */

$assigned_count = 0;
$diagnosis_count = 0;
$repair_count = 0;
$repaired_count = 0;


try {

    /* Assigned Computers */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM repair_requests
        WHERE technician_id = ?
        AND status != 'Completed'
    ");

    $stmt->execute([
        $technician_id
    ]);

    $assigned_count =
        (int)$stmt->fetchColumn();


    /* Needs Diagnosis */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM repair_requests
        WHERE technician_id = ?
        AND status = 'Needs Diagnosis'
    ");

    $stmt->execute([
        $technician_id
    ]);

    $diagnosis_count =
        (int)$stmt->fetchColumn();


    /* Under Repair */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM repair_requests
        WHERE technician_id = ?
        AND status = 'Under Repair'
    ");

    $stmt->execute([
        $technician_id
    ]);

    $repair_count =
        (int)$stmt->fetchColumn();


    /* Repaired */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM repair_requests
        WHERE technician_id = ?
        AND status = 'Completed'
    ");

    $stmt->execute([
        $technician_id
    ]);

    $repaired_count =
        (int)$stmt->fetchColumn();


} catch (PDOException $e) {

    $error =
        "Unable to load statistics.";
}


/* ====================================================
   GET RECENT REPAIR REQUESTS
==================================================== */

$repair_requests = [];


try {

    $stmt = $pdo->prepare("

        SELECT

            rr.repair_id,
            rr.problem_description,
            rr.priority,
            rr.status,
            rr.created_at,

            c.brand,
            c.model,
            c.serial_number

        FROM repair_requests rr

        INNER JOIN computer c
            ON rr.computer_id = c.computer_id

        WHERE rr.technician_id = ?

        ORDER BY rr.repair_id DESC

        LIMIT 5

    ");

    $stmt->execute([
        $technician_id
    ]);

    $repair_requests =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $error =
        "Unable to load repair requests.";
}


/* ====================================================
   GET COMPUTERS NEEDING REPAIR
==================================================== */

$computers = [];


try {

    $stmt = $pdo->query("

        SELECT

            computer_id,
            brand,
            model,
            serial_number,
            `condition`,
            status,
            location,
            description,
            image

        FROM computer

        WHERE status = 'needs_repair'

        ORDER BY computer_id DESC

    ");

    $computers =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $error =
        "Unable to load computers.";
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
    Technician Dashboard - UniShare
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

.dashboard-header {

    margin-bottom: 30px;
}

.dashboard-header h1 {

    margin: 0 0 8px;

    font-size: 30px;
}

.dashboard-header p {

    margin: 0;

    color: #6b7280;

    font-size: 15px;
}


/* ====================================================
   MESSAGES
==================================================== */

.message {

    padding: 13px 16px;

    border-radius: 8px;

    margin-bottom: 20px;

    background: #dcfce7;

    color: #166534;
}

.error {

    padding: 13px 16px;

    border-radius: 8px;

    margin-bottom: 20px;

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

    gap: 20px;

    margin-bottom: 35px;
}


.stat-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 22px;

    display: flex;

    align-items: center;

    gap: 16px;

    box-shadow:
        0 2px 8px
        rgba(0, 0, 0, 0.03);
}


.stat-icon {

    width: 48px;

    height: 48px;

    border-radius: 12px;

    background: #eaf6ee;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 24px;
}


.stat-title {

    color: #6b7280;

    font-size: 13px;
}


.stat-card h2 {

    margin: 5px 0 0;

    font-size: 25px;

    color: #16803c;
}


/* ====================================================
   SECTIONS
==================================================== */

.recent-section {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 25px;

    margin-bottom: 30px;

    box-shadow:
        0 2px 8px
        rgba(0, 0, 0, 0.03);
}


.section-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 20px;
}


.section-header h2 {

    margin: 0 0 6px;

    font-size: 20px;
}


.section-header p {

    margin: 0;

    color: #6b7280;

    font-size: 13px;
}


.view-all {

    text-decoration: none;

    color: #16803c;

    font-size: 14px;

    font-weight: 600;
}


.view-all:hover {

    text-decoration: underline;
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


.status.repaired {

    background: #dcfce7;

    color: #15803d;
}


/* ====================================================
   ACTION
==================================================== */

.action-btn {

    display: inline-block;

    padding: 7px 12px;

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
   COMPUTERS
==================================================== */

.computer-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;
}


.computer-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    overflow: hidden;
}


.computer-image {

    width: 100%;

    height: 180px;

    background: #f1f5f3;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;
}


.computer-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.no-image {

    font-size: 55px;

    color: #9ca3af;

    display: flex;

    align-items: center;

    justify-content: center;

    width: 100%;

    height: 100%;
}


.card-content {

    padding: 20px;
}


.card-title {

    margin: 0;

    font-size: 19px;
}


.card-model {

    margin-top: 5px;

    color: #6b7280;

    font-size: 13px;
}


.details {

    display: flex;

    flex-direction: column;

    gap: 9px;

    margin-top: 15px;
}


.detail {

    display: flex;

    gap: 8px;

    font-size: 13px;
}


.detail-label {

    color: #9ca3af;

    min-width: 70px;
}


.detail-value {

    color: #374151;

    font-weight: 500;
}


/* ====================================================
   REPAIR BUTTON
==================================================== */

.repair-btn {

    width: 100%;

    margin-top: 18px;

    padding: 11px;

    border: none;

    border-radius: 8px;

    background: #16803c;

    color: white;

    font-weight: bold;

    cursor: pointer;
}


.repair-btn:hover {

    background: #126b32;
}


/* ====================================================
   EMPTY
==================================================== */

.empty-state {

    text-align: center;

    padding: 45px 20px;

    color: #6b7280;
}


.empty-icon {

    font-size: 50px;

    margin-bottom: 10px;
}


/* ====================================================
   MODAL
==================================================== */

.modal {

    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(0, 0, 0, 0.45);

    align-items: center;

    justify-content: center;

    padding: 20px;

    z-index: 2000;
}


.modal-content {

    background: white;

    width: 100%;

    max-width: 550px;

    border-radius: 15px;

    padding: 30px;
}


.modal-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;
}


.modal-header h2 {

    margin: 0;
}


.close {

    border: none;

    background: none;

    font-size: 25px;

    cursor: pointer;

    color: #6b7280;
}


/* ====================================================
   FORM
==================================================== */

.form-group {

    margin-bottom: 17px;
}


.form-group label {

    display: block;

    margin-bottom: 7px;

    font-size: 13px;

    font-weight: 600;
}


.form-group input,
.form-group select,
.form-group textarea {

    width: 100%;

    padding: 11px 13px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    font-family: Arial;
}


.form-group textarea {

    min-height: 100px;

    resize: vertical;
}


.submit-btn {

    width: 100%;

    padding: 13px;

    background: #16803c;

    color: white;

    border: none;

    border-radius: 8px;

    font-weight: bold;

    cursor: pointer;
}


.submit-btn:hover {

    background: #126b32;
}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 1100px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .computer-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 700px) {

    .stats-grid {

        grid-template-columns: 1fr;
    }

    .computer-grid {

        grid-template-columns: 1fr;
    }

    .main-content {

        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
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

        <div class="dashboard-header">

            <h1>
                Technician Dashboard 🛠️
            </h1>

            <p>
                Manage repair requests and maintain university equipment.
            </p>

        </div>


        <!-- MESSAGES -->

        <?php if (!empty($message)): ?>

            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($error)): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- ====================================================
             STATISTICS
        ==================================================== -->

        <div class="stats-grid">


            <!-- ASSIGNED -->

            <div class="stat-card">

                <div class="stat-icon">
                    🖥️
                </div>

                <div>

                    <span class="stat-title">
                        Assigned Computers
                    </span>

                    <h2>
                        <?= $assigned_count ?>
                    </h2>

                </div>

            </div>


            <!-- DIAGNOSIS -->

            <div class="stat-card">

                <div class="stat-icon">
                    🔍
                </div>

                <div>

                    <span class="stat-title">
                        Needs Diagnosis
                    </span>

                    <h2>
                        <?= $diagnosis_count ?>
                    </h2>

                </div>

            </div>


            <!-- REPAIR -->

            <div class="stat-card">

                <div class="stat-icon">
                    🔧
                </div>

                <div>

                    <span class="stat-title">
                        Under Repair
                    </span>

                    <h2>
                        <?= $repair_count ?>
                    </h2>

                </div>

            </div>


            <!-- COMPLETED -->

            <div class="stat-card">

                <div class="stat-icon">
                    ✅
                </div>

                <div>

                    <span class="stat-title">
                        Repaired
                    </span>

                    <h2>
                        <?= $repaired_count ?>
                    </h2>

                </div>

            </div>


        </div>


        <!-- ====================================================
             RECENT REPAIR REQUESTS
        ==================================================== -->

        <section class="recent-section">


            <div class="section-header">

                <div>

                    <h2>
                        Recent Repair Requests
                    </h2>

                    <p>
                        Recently assigned computers that need attention.
                    </p>

                </div>


                <a
                    href="repair_requests.php"
                    class="view-all"
                >
                    View All
                </a>

            </div>


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
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($repair_requests)): ?>

                        <tr>

                            <td
                                colspan="5"
                                style="text-align:center; padding:35px;"
                            >

                                No repair requests yet.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($repair_requests as $request): ?>


                            <?php

                            $priority =
                                strtolower(
                                    $request["priority"]
                                );

                            ?>


                            <tr>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $request["brand"]
                                            . " "
                                            . $request["model"]
                                        ) ?>

                                    </strong>

                                    <br>

                                    <small>

                                        <?= htmlspecialchars(
                                            $request["serial_number"]
                                            ?: "N/A"
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $request["problem_description"]
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="priority
                                        <?= htmlspecialchars($priority) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $request["priority"]
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?php if (
                                        $request["status"]
                                        === "Needs Diagnosis"
                                    ): ?>

                                        <span
                                            class="status pending"
                                        >
                                            Needs Diagnosis
                                        </span>

                                    <?php elseif (
                                        $request["status"]
                                        === "Under Repair"
                                    ): ?>

                                        <span
                                            class="status repair"
                                        >
                                            Under Repair
                                        </span>

                                    <?php elseif (
                                        $request["status"]
                                        === "Completed"
                                    ): ?>

                                        <span
                                            class="status repaired"
                                        >
                                            Completed
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="status pending"
                                        >
                                            <?= htmlspecialchars(
                                                $request["status"]
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <a
                                        href="diagnose.php?id=<?= $request["repair_id"] ?>"
                                        class="action-btn"
                                    >

                                        <?php if (
                                            $request["status"]
                                            === "Needs Diagnosis"
                                        ): ?>

                                            Diagnose

                                        <?php elseif (
                                            $request["status"]
                                            === "Under Repair"
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


        <!-- ====================================================
             COMPUTERS NEEDING REPAIR
        ==================================================== -->

        <section class="recent-section">


            <div class="section-header">

                <div>

                    <h2>
                        Computers Needing Repair
                    </h2>

                    <p>
                        Create a repair request for a computer that needs attention.
                    </p>

                </div>

            </div>


            <?php if (empty($computers)): ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        🛠️
                    </div>

                    <h3>
                        No Computers Need Repair
                    </h3>

                    <p>
                        There are currently no computers waiting for repair.
                    </p>

                </div>


            <?php else: ?>


                <div class="computer-grid">


                    <?php foreach ($computers as $computer): ?>


                        <div class="computer-card">


                            <!-- ====================================================
                                 COMPUTER IMAGE
                            ==================================================== -->

                            <div class="computer-image">

                                <?php if (!empty($computer["image"])): ?>

                                    <img
                                        src="../IT Admin/uploads/computers/<?= htmlspecialchars($computer["image"]) ?>"
                                        alt="Computer Image"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >

                                    <div
                                        class="no-image"
                                        style="display:none;"
                                    >
                                        🖥️
                                    </div>

                                <?php else: ?>

                                    <div class="no-image">
                                        🖥️
                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- CONTENT -->

                            <div class="card-content">


                                <h2 class="card-title">

                                    <?= htmlspecialchars(
                                        $computer["brand"]
                                    ) ?>

                                </h2>


                                <div class="card-model">

                                    <?= htmlspecialchars(
                                        $computer["model"]
                                    ) ?>

                                </div>


                                <div class="details">


                                    <div class="detail">

                                        <span class="detail-label">
                                            Serial:
                                        </span>

                                        <span class="detail-value">

                                            <?= htmlspecialchars(
                                                $computer["serial_number"]
                                                ?: "N/A"
                                            ) ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span class="detail-label">
                                            Location:
                                        </span>

                                        <span class="detail-value">

                                            <?= htmlspecialchars(
                                                $computer["location"]
                                                ?: "N/A"
                                            ) ?>

                                        </span>

                                    </div>


                                </div>


                                <button
                                    type="button"
                                    class="repair-btn"
                                    onclick="openRepairModal(
                                        <?= $computer["computer_id"] ?>
                                    )"
                                >

                                    🔧 Create Repair Request

                                </button>


                            </div>

                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


<!-- ====================================================
     REPAIR MODALS
==================================================== -->

<?php foreach ($computers as $computer): ?>


<div
    class="modal"
    id="repairModal<?= $computer["computer_id"] ?>"
>


    <div class="modal-content">


        <div class="modal-header">

            <h2>
                Create Repair Request
            </h2>


            <button
                type="button"
                class="close"
                onclick="closeRepairModal(
                    <?= $computer["computer_id"] ?>
                )"
            >
                ×
            </button>

        </div>


        <form method="POST">


            <input
                type="hidden"
                name="computer_id"
                value="<?= $computer["computer_id"] ?>"
            >


            <div class="form-group">

                <label>
                    Computer
                </label>

                <input
                    type="text"
                    value="<?= htmlspecialchars(
                        $computer["brand"]
                        . " "
                        . $computer["model"]
                    ) ?>"
                    readonly
                >

            </div>


            <div class="form-group">

                <label>
                    Problem Description *
                </label>

                <textarea
                    name="problem_description"
                    placeholder="Describe the problem with this computer..."
                    required
                ></textarea>

            </div>


            <div class="form-group">

                <label>
                    Priority
                </label>

                <select name="priority">

                    <option value="Low">
                        Low
                    </option>

                    <option value="Medium" selected>
                        Medium
                    </option>

                    <option value="High">
                        High
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="submit-btn"
            >

                Create Repair Request

            </button>


        </form>


    </div>

</div>


<?php endforeach; ?>


<script>

/* ====================================================
   REPAIR MODAL
==================================================== */

function openRepairModal(id) {

    document.getElementById(
        "repairModal" + id
    ).style.display = "flex";

}


function closeRepairModal(id) {

    document.getElementById(
        "repairModal" + id
    ).style.display = "none";

}


window.onclick = function(event) {

    if (
        event.target.classList.contains("modal")
    ) {

        event.target.style.display = "none";

    }

};

</script>


</body>

</html>