<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../db.php";


/*
====================================================
GET IDEA ID
====================================================
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: upcycling.php");
    exit();
}

$idea_id = (int) $_GET["id"];


/*
====================================================
HANDLE APPROVE / REJECT
====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "approve") {

        $sql = "
            UPDATE upcycling_idea
            SET status = 'approved'
            WHERE idea_id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idea_id]);

        header("Location: upcycling_details.php?id=" . $idea_id);
        exit();

    }

    if ($action === "reject") {

        $sql = "
            UPDATE upcycling_idea
            SET status = 'rejected'
            WHERE idea_id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idea_id]);

        header("Location: upcycling_details.php?id=" . $idea_id);
        exit();
    }
}


/*
====================================================
GET IDEA
====================================================
*/

$sql = "
    SELECT
        idea_id,
        material_type,
        title,
        materials,
        steps,
        difficulty,
        estimated_cost,
        image_path,
        resource_name,
        status
    FROM upcycling_idea
    WHERE idea_id = ?
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$idea_id]);

$idea = $stmt->fetch(PDO::FETCH_ASSOC);


/*
====================================================
NOT FOUND
====================================================
*/

if (!$idea) {

    echo "Upcycling idea not found.";
    exit();

}


/*
====================================================
NORMALIZE STATUS
====================================================
*/

$status = strtolower(trim($idea["status"] ?? "pending"));


/*
====================================================
FIX OLD VALUE
====================================================
*/

if ($status === "approve") {
    $status = "approved";
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
    <?= htmlspecialchars($idea["title"]) ?> - IT Admin
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

.page-container {

    margin-left: 240px;

    min-height: 100vh;

    padding: 40px;

}

.page-wrapper {

    max-width: 950px;

    margin: auto;

}


/* ====================================================
   BACK
==================================================== */

.back-link {

    display: inline-block;

    margin-bottom: 20px;

    color: #15803d;

    text-decoration: none;

    font-size: 14px;

    font-weight: 700;

}

.back-link:hover {

    text-decoration: underline;

}


/* ====================================================
   CARD
==================================================== */

.details-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    overflow: hidden;

    box-shadow: 0 6px 25px rgba(0,0,0,0.05);

}


/* ====================================================
   IMAGE
==================================================== */

.idea-image {

    width: 100%;

    height: 320px;

    object-fit: cover;

    display: block;

}

.no-image {

    height: 320px;

    background: #ecfdf5;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 85px;

}


/* ====================================================
   CONTENT
==================================================== */

.details-content {

    padding: 32px;

}


/* ====================================================
   HEADER
==================================================== */

.material-badge {

    display: inline-block;

    background: #dcfce7;

    color: #15803d;

    padding: 6px 12px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

    margin-bottom: 12px;

}


.title {

    margin: 0 0 10px;

    font-size: 30px;

}


.resource {

    color: #6b7280;

    font-size: 14px;

    margin-bottom: 25px;

}


/* ====================================================
   STATUS
==================================================== */

.status {

    display: inline-block;

    padding: 7px 13px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 700;

    margin-bottom: 25px;

}


.status.pending {

    background: #fef3c7;

    color: #92400e;

}


.status.approved {

    background: #dcfce7;

    color: #166534;

}


.status.rejected {

    background: #fee2e2;

    color: #991b1b;

}


/* ====================================================
   INFO
==================================================== */

.info-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 15px;

    margin-bottom: 28px;

}


.info-box {

    background: #f9fafb;

    border-radius: 10px;

    padding: 15px;

}


.info-label {

    display: block;

    font-size: 10px;

    text-transform: uppercase;

    color: #9ca3af;

    margin-bottom: 5px;

}


.info-value {

    font-size: 14px;

    font-weight: 700;

    color: #374151;

}


/* ====================================================
   SECTIONS
==================================================== */

.section {

    margin-top: 25px;

}


.section h3 {

    margin: 0 0 10px;

    color: #166534;

    font-size: 17px;

}


.section-content {

    background: #f8faf9;

    border-radius: 10px;

    padding: 17px;

    color: #4b5563;

    font-size: 14px;

    line-height: 1.8;

}


/* ====================================================
   ACTIONS
==================================================== */

.actions {

    display: flex;

    gap: 10px;

    margin-top: 30px;

    padding-top: 25px;

    border-top: 1px solid #eeeeee;

}


.action-btn {

    border: none;

    cursor: pointer;

    padding: 12px 20px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 700;

}


.approve {

    background: #16a34a;

    color: white;

}


.approve:hover {

    background: #15803d;

}


.reject {

    background: #dc2626;

    color: white;

}


.reject:hover {

    background: #b91c1c;

}


/* ====================================================
   CURRENT STATUS MESSAGE
==================================================== */

.current-status {

    margin-top: 25px;

    padding: 15px;

    border-radius: 10px;

    font-size: 14px;

    font-weight: 600;

}


.current-status.approved {

    background: #ecfdf5;

    color: #166534;

}


.current-status.rejected {

    background: #fef2f2;

    color: #991b1b;

}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 700px) {

    .page-container {

        margin-left: 0;

        padding: 20px;

    }

    .info-grid {

        grid-template-columns: 1fr;

    }

    .details-content {

        padding: 22px;

    }

    .title {

        font-size: 24px;

    }

    .actions {

        flex-direction: column;

    }

}

</style>

</head>


<body>


<?php include "includes/sidebar.php"; ?>


<div class="page-container">

<div class="page-wrapper">


<a
    href="upcycling.php"
    class="back-link"
>
    ← Back to Upcycling Ideas
</a>


<div class="details-card">


<!-- ====================================================
     IMAGE
==================================================== -->

<?php if (!empty($idea["image_path"])): ?>

    <img
        src="../<?= htmlspecialchars($idea["image_path"]) ?>"
        class="idea-image"
        alt="<?= htmlspecialchars($idea["title"]) ?>"
    >

<?php else: ?>

    <div class="no-image">
        ♻️
    </div>

<?php endif; ?>


<div class="details-content">


<!-- MATERIAL -->

<span class="material-badge">

    <?= htmlspecialchars(
        $idea["material_type"] ?? "Material"
    ) ?>

</span>


<!-- TITLE -->

<h1 class="title">

    <?= htmlspecialchars(
        $idea["title"]
    ) ?>

</h1>


<!-- RESOURCE -->

<div class="resource">

    Based on:

    <strong>

        <?= htmlspecialchars(
            $idea["resource_name"] ?? "Material"
        ) ?>

    </strong>

</div>


<!-- STATUS -->

<?php if ($status === "approved"): ?>

    <span class="status approved">
        🟢 Approved
    </span>

<?php elseif ($status === "rejected"): ?>

    <span class="status rejected">
        🔴 Rejected
    </span>

<?php else: ?>

    <span class="status pending">
        🟡 Pending
    </span>

<?php endif; ?>


<!-- INFO -->

<div class="info-grid">


<div class="info-box">

    <span class="info-label">
        Difficulty
    </span>

    <span class="info-value">

        <?= htmlspecialchars(
            $idea["difficulty"] ?? "-"
        ) ?>

    </span>

</div>


<div class="info-box">

    <span class="info-label">
        Estimated Cost
    </span>

    <span class="info-value">

        <?php if (
            $idea["estimated_cost"] !== null &&
            $idea["estimated_cost"] !== ""
        ): ?>

            <?= number_format(
                (float)$idea["estimated_cost"],
                2
            ) ?>

            EGP

        <?php else: ?>

            Free

        <?php endif; ?>

    </span>

</div>


</div>


<!-- MATERIALS -->

<div class="section">

    <h3>
        🧰 Materials Needed
    </h3>

    <div class="section-content">

        <?= nl2br(
            htmlspecialchars(
                $idea["materials"] ?? "Not specified"
            )
        ) ?>

    </div>

</div>


<!-- STEPS -->

<div class="section">

    <h3>
        🛠️ How to Make It
    </h3>

    <div class="section-content">

        <?= nl2br(
            htmlspecialchars(
                $idea["steps"] ?? "Not specified"
            )
        ) ?>

    </div>

</div>


<!-- ====================================================
     ACTIONS
==================================================== -->

<?php if ($status === "pending"): ?>

<div class="actions">


<form method="POST">

    <input
        type="hidden"
        name="action"
        value="approve"
    >

    <button
        type="submit"
        class="action-btn approve"
        onclick="return confirm('Are you sure you want to approve this idea?');"
    >

        ✓ Approve Idea

    </button>

</form>


<form method="POST">

    <input
        type="hidden"
        name="action"
        value="reject"
    >

    <button
        type="submit"
        class="action-btn reject"
        onclick="return confirm('Are you sure you want to reject this idea?');"
    >

        ✕ Reject Idea

    </button>

</form>


</div>

<?php elseif ($status === "approved"): ?>

<div class="current-status approved">

    🟢 This idea has already been approved.

</div>

<?php elseif ($status === "rejected"): ?>

<div class="current-status rejected">

    🔴 This idea has been rejected.

</div>

<?php endif; ?>


</div>

</div>


</div>

</div>


</body>

</html>