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
GET ALL UPCYCLING IDEAS
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
    ORDER BY idea_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$ideas = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
====================================================
COUNTS
====================================================
*/

$totalIdeas = count($ideas);

$pendingIdeas = 0;
$approvedIdeas = 0;
$rejectedIdeas = 0;

foreach ($ideas as $idea) {

    $status = strtolower($idea["status"] ?? "pending");

    if ($status === "pending") {

        $pendingIdeas++;

    } elseif ($status === "approved") {

        $approvedIdeas++;

    } elseif ($status === "rejected") {

        $rejectedIdeas++;
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

<title>Upcycling Ideas - IT Admin</title>


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

    padding: 45px;
}

.page-wrapper {

    max-width: 1200px;

    margin: auto;
}


/* ====================================================
   HEADER
==================================================== */

.page-header {

    margin-bottom: 30px;
}

.page-header h1 {

    margin: 0 0 8px;

    font-size: 30px;
}

.page-header p {

    margin: 0;

    color: #6b7280;

    font-size: 14px;
}


/* ====================================================
   STAT CARDS
==================================================== */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 30px;
}

.stat-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 20px;
}

.stat-label {

    color: #6b7280;

    font-size: 13px;

    margin-bottom: 8px;
}

.stat-number {

    font-size: 28px;

    font-weight: 700;
}

.total .stat-number {

    color: #111827;
}

.pending .stat-number {

    color: #d97706;
}

.approved .stat-number {

    color: #16a34a;
}

.rejected .stat-number {

    color: #dc2626;
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

    padding: 20px;

    border-bottom: 1px solid #e5e7eb;
}

.table-header h2 {

    margin: 0;

    font-size: 18px;
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
}

th {

    text-align: left;

    padding: 15px;

    background: #f9fafb;

    color: #6b7280;

    font-size: 11px;

    text-transform: uppercase;

    white-space: nowrap;
}

td {

    padding: 15px;

    border-top: 1px solid #f0f0f0;

    font-size: 13px;

    vertical-align: middle;
}

.idea-title {

    font-weight: 700;

    color: #111827;
}

.resource-name {

    color: #6b7280;

    font-size: 12px;

    margin-top: 4px;
}


/* ====================================================
   STATUS
==================================================== */

.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;
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
   ACTION
==================================================== */

.view-btn {

    display: inline-block;

    padding: 8px 13px;

    background: #ecfdf5;

    color: #15803d;

    border-radius: 7px;

    text-decoration: none;

    font-size: 12px;

    font-weight: 700;
}

.view-btn:hover {

    background: #dcfce7;
}


/* ====================================================
   EMPTY
==================================================== */

.empty-state {

    padding: 60px 20px;

    text-align: center;
}

.empty-state .icon {

    font-size: 50px;

    margin-bottom: 15px;
}

.empty-state h3 {

    margin: 0 0 8px;
}

.empty-state p {

    margin: 0;

    color: #6b7280;
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

    .page-container {

        margin-left: 0;

        padding: 20px;
    }

    .stats-grid {

        grid-template-columns: 1fr;
    }
}

</style>

</head>


<body>


<?php

include "includes/sidebar.php";

?>


<div class="page-container">

<div class="page-wrapper">


<!-- ====================================================
     HEADER
==================================================== -->

<div class="page-header">

    <h1>
        ♻️ Upcycling Ideas
    </h1>

    <p>
        Review and manage upcycling ideas submitted to UniShare.
    </p>

</div>


<!-- ====================================================
     STATISTICS
==================================================== -->

<div class="stats-grid">


    <div class="stat-card total">

        <div class="stat-label">
            Total Ideas
        </div>

        <div class="stat-number">
            <?= $totalIdeas ?>
        </div>

    </div>


    <div class="stat-card pending">

        <div class="stat-label">
            Pending
        </div>

        <div class="stat-number">
            <?= $pendingIdeas ?>
        </div>

    </div>


    <div class="stat-card approved">

        <div class="stat-label">
            Approved
        </div>

        <div class="stat-number">
            <?= $approvedIdeas ?>
        </div>

    </div>


    <div class="stat-card rejected">

        <div class="stat-label">
            Rejected
        </div>

        <div class="stat-number">
            <?= $rejectedIdeas ?>
        </div>

    </div>


</div>


<!-- ====================================================
     IDEAS TABLE
==================================================== -->

<div class="table-card">


<div class="table-header">

    <h2>
        All Upcycling Ideas
    </h2>

</div>


<?php if (empty($ideas)): ?>


<div class="empty-state">

    <div class="icon">
        ♻️
    </div>

    <h3>
        No Upcycling Ideas Yet
    </h3>

    <p>
        No ideas have been submitted yet.
    </p>

</div>


<?php else: ?>


<div class="table-wrapper">

<table>


<thead>

<tr>

    <th>
        Idea
    </th>

    <th>
        Material
    </th>

    <th>
        Difficulty
    </th>

    <th>
        Cost
    </th>

    <th>
        Status
    </th>

    <th>
        Actions
    </th>

</tr>

</thead>


<tbody>


<?php foreach ($ideas as $idea): ?>


<?php

$status = strtolower(
    $idea["status"] ?? "pending"
);

?>


<tr>


<!-- IDEA -->

<td>

    <div class="idea-title">

        <?= htmlspecialchars(
            $idea["title"] ?? "Untitled Idea"
        ) ?>

    </div>


    <div class="resource-name">

        Based on:

        <?= htmlspecialchars(
            $idea["resource_name"] ?? "Material"
        ) ?>

    </div>

</td>


<!-- MATERIAL -->

<td>

    <?= htmlspecialchars(
        $idea["material_type"] ?? "-"
    ) ?>

</td>


<!-- DIFFICULTY -->

<td>

    <?= htmlspecialchars(
        $idea["difficulty"] ?? "-"
    ) ?>

</td>


<!-- COST -->

<td>

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

</td>


<!-- STATUS -->

<td>

<?php if ($status === "pending"): ?>

    <span class="status pending">
        🟡 Pending
    </span>

<?php elseif ($status === "approved"): ?>

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

</td>


<!-- ACTION -->

<td>

<a
    href="upcycling_details.php?id=<?= (int)$idea["idea_id"] ?>"
    class="view-btn"
>
    👁️ View
</a>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>


<?php endif; ?>


</div>


</div>

</div>


</body>

</html>