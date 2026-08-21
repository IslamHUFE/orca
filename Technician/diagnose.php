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

$repair_id = (int)($_GET["id"] ?? 0);

$message = "";
$error = "";


if ($repair_id <= 0) {
    die("Invalid repair request.");
}


/* ====================================================
   GET REPAIR REQUEST
==================================================== */

try {

    $stmt = $pdo->prepare("

        SELECT

            rr.repair_id,
            rr.computer_id,
            rr.technician_id,
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
            c.image

        FROM repair_requests rr

        INNER JOIN computer c
            ON rr.computer_id = c.computer_id

        WHERE rr.repair_id = ?
        AND rr.technician_id = ?

        LIMIT 1

    ");

    $stmt->execute([
        $repair_id,
        $technician_id
    ]);

    $repair = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$repair) {
        die("Repair request not found.");
    }

} catch (PDOException $e) {

    die("Unable to load repair request.");

}


/* ====================================================
   GET COMPONENTS
==================================================== */

$components = [];

try {

    $stmt = $pdo->query("

        SELECT

            component_id,
            computer_id,
            type,
            model,
            serial_number,
            `condition`,
            status

        FROM component

        ORDER BY type ASC, model ASC

    ");

    $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $error = "Unable to load components.";

}


/* ====================================================
   SAVE DIAGNOSIS
==================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $diagnosis = trim(
        $_POST["diagnosis"] ?? ""
    );

    $faulty_component_id =
        (int)($_POST["faulty_component_id"] ?? 0);

    $repair_action = trim(
        $_POST["repair_action"] ?? ""
    );

    $priority =
        trim($_POST["priority"] ?? "Medium");

    $status =
        trim($_POST["status"] ?? "Under Repair");


    /* ====================================================
       VALIDATION
    ==================================================== */

    if (empty($diagnosis)) {

        $error = "Please enter the diagnosis.";

    } elseif (empty($repair_action)) {

        $error = "Please enter the repair action.";

    } else {

        try {

            /* ====================================================
               START TRANSACTION
            ==================================================== */

            $pdo->beginTransaction();


            /* ====================================================
               COMPLETED
            ==================================================== */

            if ($status === "Completed") {

                /* ====================================================
                   UPDATE REPAIR REQUEST
                ==================================================== */

                $stmt = $pdo->prepare("

                    UPDATE repair_requests

                    SET
                        diagnosis = ?,
                        faulty_component_id = ?,
                        repair_action = ?,
                        priority = ?,
                        status = ?,
                        completed_at = NOW()

                    WHERE repair_id = ?
                    AND technician_id = ?

                ");

                $stmt->execute([

                    $diagnosis,

                    $faulty_component_id > 0
                        ? $faulty_component_id
                        : null,

                    $repair_action,

                    $priority,

                    $status,

                    $repair_id,

                    $technician_id

                ]);


                /* ====================================================
                   UPDATE COMPUTER TO WORKING
                   
                   IMPORTANT:
                   computers.php uses:
                   working
                   needs_repair
                   not_repairable
                ==================================================== */

                $stmt = $pdo->prepare("

                    UPDATE computer

                    SET status = 'working'

                    WHERE computer_id = ?

                ");

                $stmt->execute([
                    $repair["computer_id"]
                ]);


                /* ====================================================
                   UPDATE FAULTY COMPONENT
                ==================================================== */

                if ($faulty_component_id > 0) {

                    $stmt = $pdo->prepare("

                        UPDATE component

                        SET
                            status = 'Faulty',
                            `condition` = 'Damaged'

                        WHERE component_id = ?

                    ");

                    $stmt->execute([
                        $faulty_component_id
                    ]);
                }


            } else {

                /* ====================================================
                   UPDATE REPAIR REQUEST
                   WITHOUT COMPLETING
                ==================================================== */

                $stmt = $pdo->prepare("

                    UPDATE repair_requests

                    SET
                        diagnosis = ?,
                        faulty_component_id = ?,
                        repair_action = ?,
                        priority = ?,
                        status = ?,
                        completed_at = NULL

                    WHERE repair_id = ?
                    AND technician_id = ?

                ");

                $stmt->execute([

                    $diagnosis,

                    $faulty_component_id > 0
                        ? $faulty_component_id
                        : null,

                    $repair_action,

                    $priority,

                    $status,

                    $repair_id,

                    $technician_id

                ]);


                /* ====================================================
                   KEEP COMPUTER UNDER REPAIR
                ==================================================== */

                $stmt = $pdo->prepare("

                    UPDATE computer

                    SET status = 'needs_repair'

                    WHERE computer_id = ?

                ");

                $stmt->execute([
                    $repair["computer_id"]
                ]);


                /* ====================================================
                   IF A FAULTY COMPONENT WAS SELECTED
                ==================================================== */

                if ($faulty_component_id > 0) {

                    $stmt = $pdo->prepare("

                        UPDATE component

                        SET
                            status = 'Faulty',
                            `condition` = 'Damaged'

                        WHERE component_id = ?

                    ");

                    $stmt->execute([
                        $faulty_component_id
                    ]);
                }

            }


            /* ====================================================
               COMMIT
            ==================================================== */

            $pdo->commit();


            $message =
                "Repair diagnosis saved successfully.";


            /* ====================================================
               RELOAD REPAIR DATA
            ==================================================== */

            $stmt = $pdo->prepare("

                SELECT

                    rr.repair_id,
                    rr.computer_id,
                    rr.technician_id,
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
                    c.image

                FROM repair_requests rr

                INNER JOIN computer c
                    ON rr.computer_id = c.computer_id

                WHERE rr.repair_id = ?
                AND rr.technician_id = ?

                LIMIT 1

            ");

            $stmt->execute([
                $repair_id,
                $technician_id
            ]);

            $repair =
                $stmt->fetch(PDO::FETCH_ASSOC);


        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                "Failed to save diagnosis: "
                . $e->getMessage();
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

<title>
    Repair Diagnosis - UniShare
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
   MAIN
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
}

.error {

    padding: 13px 16px;

    margin-bottom: 20px;

    border-radius: 8px;

    background: #fee2e2;

    color: #b91c1c;
}


/* ====================================================
   COMPUTER CARD
==================================================== */

.computer-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 25px;

    margin-bottom: 25px;

    display: flex;

    gap: 25px;

    align-items: center;
}

.computer-image {

    width: 150px;

    height: 120px;

    border-radius: 10px;

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

    font-size: 45px;

    color: #9ca3af;
}

.computer-info h2 {

    margin: 0 0 10px;

    font-size: 22px;
}

.computer-info p {

    margin: 6px 0;

    color: #6b7280;

    font-size: 14px;
}


/* ====================================================
   FORM CARD
==================================================== */

.form-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 30px;

    box-shadow:
        0 2px 8px rgba(0, 0, 0, 0.03);
}

.form-card h2 {

    margin: 0 0 25px;

    font-size: 21px;
}


/* ====================================================
   FORM
==================================================== */

.form-group {

    margin-bottom: 20px;
}

.form-group label {

    display: block;

    margin-bottom: 8px;

    font-size: 14px;

    font-weight: 600;

    color: #374151;
}

.form-group textarea,
.form-group select {

    width: 100%;

    padding: 12px 14px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    font-family: Arial, sans-serif;

    font-size: 14px;

    outline: none;

    background: white;
}

.form-group textarea {

    min-height: 110px;

    resize: vertical;
}

.form-group textarea:focus,
.form-group select:focus {

    border-color: #16803c;

    box-shadow:
        0 0 0 3px rgba(22,128,60,0.08);
}


/* ====================================================
   BUTTONS
==================================================== */

.form-actions {

    display: flex;

    gap: 12px;

    margin-top: 25px;
}

.submit-btn {

    padding: 12px 22px;

    border: none;

    border-radius: 8px;

    background: #16803c;

    color: white;

    font-weight: 600;

    cursor: pointer;
}

.submit-btn:hover {

    background: #126b32;
}

.back-btn {

    display: inline-flex;

    align-items: center;

    padding: 12px 22px;

    border-radius: 8px;

    background: #f3f4f6;

    color: #374151;

    text-decoration: none;

    font-weight: 600;
}

.back-btn:hover {

    background: #e5e7eb;
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

    .computer-card {

        flex-direction: column;

        align-items: flex-start;
    }

    .computer-image {

        width: 100%;

        height: 180px;
    }

    .form-actions {

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

            <h1>
                Repair Diagnosis 🔍
            </h1>

            <p>
                Diagnose the computer and record the repair details.
            </p>

        </div>


        <!-- MESSAGE -->

        <?php if (!empty($message)): ?>

            <div class="message">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- ====================================================
             COMPUTER INFORMATION
        ==================================================== -->

        <div class="computer-card">


            <div class="computer-image">

                <?php if (!empty($repair["image"])): ?>

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


            <div class="computer-info">

                <h2>

                    <?= htmlspecialchars(
                        $repair["brand"]
                        . " "
                        . $repair["model"]
                    ) ?>

                </h2>


                <p>

                    <strong>
                        Serial Number:
                    </strong>

                    <?= htmlspecialchars(
                        $repair["serial_number"]
                        ?: "N/A"
                    ) ?>

                </p>


                <p>

                    <strong>
                        Location:
                    </strong>

                    <?= htmlspecialchars(
                        $repair["location"]
                        ?: "N/A"
                    ) ?>

                </p>


                <p>

                    <strong>
                        Reported Problem:
                    </strong>

                    <?= htmlspecialchars(
                        $repair["problem_description"]
                    ) ?>

                </p>

            </div>

        </div>


        <!-- ====================================================
             FORM
        ==================================================== -->

        <div class="form-card">


            <h2>
                Repair Diagnosis 🔧
            </h2>


            <form method="POST">


                <!-- DIAGNOSIS -->

                <div class="form-group">

                    <label for="diagnosis">
                        Diagnosis *
                    </label>

                    <textarea
                        name="diagnosis"
                        id="diagnosis"
                        placeholder="Describe what you found..."
                        required
                    ><?= htmlspecialchars(
                        $repair["diagnosis"] ?? ""
                    ) ?></textarea>

                </div>


                <!-- FAULTY COMPONENT -->

                <div class="form-group">

                    <label for="faulty_component_id">
                        Faulty Component
                    </label>

                    <select
                        name="faulty_component_id"
                        id="faulty_component_id"
                    >

                        <option value="">
                            -- Select Component --
                        </option>


                        <?php foreach ($components as $component): ?>

                            <option
                                value="<?= $component["component_id"] ?>"

                                <?= (
                                    isset(
                                        $repair["faulty_component_id"]
                                    )
                                    &&
                                    $repair["faulty_component_id"]
                                    ==
                                    $component["component_id"]
                                )
                                ? "selected"
                                : ""
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $component["type"]
                                ) ?>


                                <?php if (
                                    !empty($component["model"])
                                ): ?>

                                    -
                                    <?= htmlspecialchars(
                                        $component["model"]
                                    ) ?>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $component["serial_number"]
                                    )
                                ): ?>

                                    (
                                    <?= htmlspecialchars(
                                        $component["serial_number"]
                                    ) ?>
                                    )

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- REPAIR ACTION -->

                <div class="form-group">

                    <label for="repair_action">
                        Repair Action *
                    </label>

                    <textarea
                        name="repair_action"
                        id="repair_action"
                        placeholder="Describe the repair action..."
                        required
                    ><?= htmlspecialchars(
                        $repair["repair_action"] ?? ""
                    ) ?></textarea>

                </div>


                <!-- PRIORITY -->

                <div class="form-group">

                    <label for="priority">
                        Priority
                    </label>

                    <select
                        name="priority"
                        id="priority"
                    >

                        <option
                            value="Low"
                            <?= (
                                ($repair["priority"] ?? "")
                                === "Low"
                            )
                            ? "selected"
                            : ""
                            ?>
                        >
                            Low
                        </option>

                        <option
                            value="Medium"
                            <?= (
                                ($repair["priority"] ?? "Medium")
                                === "Medium"
                            )
                            ? "selected"
                            : ""
                            ?>
                        >
                            Medium
                        </option>

                        <option
                            value="High"
                            <?= (
                                ($repair["priority"] ?? "")
                                === "High"
                            )
                            ? "selected"
                            : ""
                            ?>
                        >
                            High
                        </option>

                    </select>

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label for="status">
                        Repair Status
                    </label>

                    <select
                        name="status"
                        id="status"
                    >

                        <option
                            value="Needs Diagnosis"
                            <?= (
                                $repair["status"]
                                === "Needs Diagnosis"
                            )
                            ? "selected"
                            : ""
                            ?>
                        >
                            Needs Diagnosis
                        </option>

                        <option
                            value="Under Repair"
                            <?= (
                                $repair["status"]
                                === "Under Repair"
                            )
                            ? "selected"
                            : ""
                            ?>
                        >
                            Under Repair
                        </option>

                        <option
                            value="Completed"
                            <?= (
                                $repair["status"]
                                === "Completed"
                            )
                            ? "selected"
                            : ""
                            ?>
                        >
                            Completed
                        </option>

                    </select>

                </div>


                <!-- BUTTONS -->

                <div class="form-actions">

                    <a
                        href="technician_dashboard.php"
                        class="back-btn"
                    >
                        ← Back
                    </a>

                    <button
                        type="submit"
                        class="submit-btn"
                    >
                        Save Diagnosis
                    </button>

                </div>


            </form>


        </div>


    </main>


</div>


</body>

</html>