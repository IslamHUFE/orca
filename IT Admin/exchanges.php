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
GET EXCHANGES
====================================================
*/

$sql = "
    SELECT
        e.exchange_id,

        e.resource_offered_id,
        e.resource_wanted_id,

        e.student_a_id,
        e.student_b_id,

        e.status,
        e.created_at,
        e.completed_at,

        u1.full_name AS student_a_name,
        u1.email AS student_a_email,

        u2.full_name AS student_b_name,
        u2.email AS student_b_email,

        r1.name AS offered_resource_name,
        r2.name AS wanted_resource_name

    FROM exchange e

    LEFT JOIN users u1
        ON e.student_a_id = u1.user_id

    LEFT JOIN users u2
        ON e.student_b_id = u2.user_id

    LEFT JOIN resource r1
        ON e.resource_offered_id = r1.resource_id

    LEFT JOIN resource r2
        ON e.resource_wanted_id = r2.resource_id

    ORDER BY e.exchange_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$exchanges = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
====================================================
COUNTS
====================================================
*/

$total_exchanges = count($exchanges);

$pending_count = 0;
$matched_count = 0;
$accepted_count = 0;
$completed_count = 0;
$rejected_count = 0;
$cancelled_count = 0;

foreach ($exchanges as $exchange) {

    switch ($exchange["status"]) {

        case "pending":
            $pending_count++;
            break;

        case "matched":
            $matched_count++;
            break;

        case "accepted":
            $accepted_count++;
            break;

        case "completed":
            $completed_count++;
            break;

        case "rejected":
            $rejected_count++;
            break;

        case "cancelled":
            $cancelled_count++;
            break;
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

<title>Exchanges - IT Admin</title>


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
   MAIN
==================================================== */

.page-container {

    margin-left: 240px;

    min-height: 100vh;

    padding: 40px;

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

    color: #111827;

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

    gap: 16px;

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

    font-size: 12px;

    font-weight: 600;

    margin-bottom: 8px;

}

.stat-number {

    font-size: 26px;

    font-weight: 700;

    color: #111827;

}


/* ====================================================
   TABLE CARD
==================================================== */

.table-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.04);

}


/* ====================================================
   TABLE HEADER
==================================================== */

.table-header {

    padding: 22px 25px;

    border-bottom: 1px solid #eeeeee;

}

.table-header h2 {

    margin: 0;

    font-size: 19px;

}

.table-header p {

    margin: 6px 0 0;

    color: #6b7280;

    font-size: 13px;

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

    min-width: 950px;

}

th {

    background: #f9fafb;

    color: #6b7280;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 0.4px;

    text-align: left;

    padding: 15px;

    border-bottom: 1px solid #e5e7eb;

}

td {

    padding: 16px 15px;

    border-bottom: 1px solid #f0f0f0;

    font-size: 13px;

    vertical-align: middle;

}

tr:last-child td {

    border-bottom: none;

}

tr:hover td {

    background: #fcfdfc;

}


/* ====================================================
   STUDENTS
==================================================== */

.student-name {

    font-weight: 700;

    color: #111827;

    margin-bottom: 4px;

}

.student-email {

    font-size: 11px;

    color: #9ca3af;

}


/* ====================================================
   RESOURCES
==================================================== */

.resource-name {

    font-weight: 600;

    color: #374151;

    max-width: 170px;

}

.resource-arrow {

    color: #16a34a;

    font-size: 18px;

    font-weight: bold;

    text-align: center;

}


/* ====================================================
   STATUS
==================================================== */

.status {

    display: inline-flex;

    align-items: center;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

}

.status.pending {

    background: #fef3c7;

    color: #92400e;

}

.status.matched {

    background: #dbeafe;

    color: #1d4ed8;

}

.status.accepted {

    background: #dcfce7;

    color: #166534;

}

.status.completed {

    background: #d1fae5;

    color: #065f46;

}

.status.rejected {

    background: #fee2e2;

    color: #991b1b;

}

.status.cancelled {

    background: #f3f4f6;

    color: #4b5563;

}


/* ====================================================
   DATE
==================================================== */

.date {

    color: #6b7280;

    font-size: 12px;

}


/* ====================================================
   VIEW BUTTON
==================================================== */

.view-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 8px 13px;

    background: #ecfdf5;

    color: #15803d;

    text-decoration: none;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 700;

    transition: 0.2s;

}

.view-btn:hover {

    background: #dcfce7;

}


/* ====================================================
   EMPTY
==================================================== */

.empty-state {

    text-align: center;

    padding: 60px 20px;

}

.empty-icon {

    font-size: 50px;

    margin-bottom: 15px;

}

.empty-state h3 {

    margin: 0 0 8px;

    font-size: 18px;

}

.empty-state p {

    margin: 0;

    color: #6b7280;

    font-size: 13px;

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

        padding: 25px 18px;

    }

    .stats-grid {

        grid-template-columns: 1fr;

    }

}


/* ====================================================
   SIDEBAR COLLAPSE SUPPORT
==================================================== */

.sidebar.collapsed {

    width: 75px;

}

body.sidebar-collapsed .page-container {

    margin-left: 75px;

}

.sidebar.collapsed .sidebar-logo span,
.sidebar.collapsed .nav-link span:not(.nav-icon) {

    display: none;

}

.sidebar.collapsed .nav-link {

    justify-content: center;

}

</style>

</head>


<body>


<?php include "includes/sidebar.php"; ?>


<div class="page-container">

<div class="page-wrapper">


<!-- ====================================================
     HEADER
==================================================== -->

<div class="page-header">

    <h1>
        🔄 Exchanges
    </h1>

    <p>
        Review and manage resource exchanges between students.
    </p>

</div>


<!-- ====================================================
     STATISTICS
==================================================== -->

<div class="stats-grid">


<div class="stat-card">

    <div class="stat-label">
        Total Exchanges
    </div>

    <div class="stat-number">
        <?= $total_exchanges ?>
    </div>

</div>


<div class="stat-card">

    <div class="stat-label">
        Pending
    </div>

    <div class="stat-number">
        <?= $pending_count ?>
    </div>

</div>


<div class="stat-card">

    <div class="stat-label">
        Accepted
    </div>

    <div class="stat-number">
        <?= $accepted_count ?>
    </div>

</div>


<div class="stat-card">

    <div class="stat-label">
        Completed
    </div>

    <div class="stat-number">
        <?= $completed_count ?>
    </div>

</div>


</div>


<!-- ====================================================
     TABLE
==================================================== -->

<div class="table-card">


<div class="table-header">

    <h2>
        All Exchanges
    </h2>

    <p>
        Monitor exchange requests and their current status.
    </p>

</div>


<?php if (!empty($exchanges)): ?>


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
        Offered Resource
    </th>

    <th>
        ⇄
    </th>

    <th>
        Wanted Resource
    </th>

    <th>
        Student B
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


<?php foreach ($exchanges as $exchange): ?>


<tr>


<td>

    <strong>
        #<?= (int)$exchange["exchange_id"] ?>
    </strong>

</td>


<!-- STUDENT A -->

<td>

    <div class="student-name">

        <?= htmlspecialchars(
            $exchange["student_a_name"]
            ?? "Unknown Student"
        ) ?>

    </div>

    <div class="student-email">

        <?= htmlspecialchars(
            $exchange["student_a_email"]
            ?? ""
        ) ?>

    </div>

</td>


<!-- OFFERED -->

<td>

    <div class="resource-name">

        <?= htmlspecialchars(
            $exchange["offered_resource_name"]
            ?? "Unknown Resource"
        ) ?>

    </div>

</td>


<!-- ARROW -->

<td class="resource-arrow">

    ⇄

</td>


<!-- WANTED -->

<td>

    <div class="resource-name">

        <?= htmlspecialchars(
            $exchange["wanted_resource_name"]
            ?? "Unknown Resource"
        ) ?>

    </div>

</td>


<!-- STUDENT B -->

<td>

    <div class="student-name">

        <?= htmlspecialchars(
            $exchange["student_b_name"]
            ?? "Unknown Student"
        ) ?>

    </div>

    <div class="student-email">

        <?= htmlspecialchars(
            $exchange["student_b_email"]
            ?? ""
        ) ?>

    </div>

</td>


<!-- STATUS -->

<td>

<?php

$current_status =
    $exchange["status"] ?? "pending";

$status_label =
    ucfirst($current_status);

$status_icon = "🟡";

if ($current_status === "matched") {

    $status_icon = "🔵";

} elseif ($current_status === "accepted") {

    $status_icon = "🟢";

} elseif ($current_status === "completed") {

    $status_icon = "✅";

} elseif ($current_status === "rejected") {

    $status_icon = "🔴";

} elseif ($current_status === "cancelled") {

    $status_icon = "⚪";

}

?>

<span
    class="status <?= htmlspecialchars($current_status) ?>"
>

    <?= $status_icon ?>

    <?= htmlspecialchars($status_label) ?>

</span>

</td>


<!-- DATE -->

<td>

    <div class="date">

        <?= !empty($exchange["created_at"])
            ? date(
                "M d, Y",
                strtotime(
                    $exchange["created_at"]
                )
            )
            : "-"
        ?>

    </div>

</td>


<!-- VIEW -->

<td>

    <a
        href="exchange_details.php?id=<?= (int)$exchange["exchange_id"] ?>"
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


<?php else: ?>


<div class="empty-state">

    <div class="empty-icon">
        🔄
    </div>

    <h3>
        No Exchanges Found
    </h3>

    <p>
        There are currently no exchange requests.
    </p>

</div>


<?php endif; ?>


</div>


</div>

</div>


</body>

</html>