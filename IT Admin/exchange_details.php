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
GET EXCHANGE ID
====================================================
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: exchanges.php");
    exit();
}

$exchange_id = (int) $_GET["id"];


/*
====================================================
HANDLE STATUS UPDATE
====================================================
*/

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if (
        $action === "accepted" ||
        $action === "rejected" ||
        $action === "completed" ||
        $action === "cancelled"
    ) {

        if ($action === "completed") {

            $sql = "
                UPDATE exchange
                SET
                    status = ?,
                    completed_at = NOW()
                WHERE exchange_id = ?
            ";

        } else {

            $sql = "
                UPDATE exchange
                SET status = ?
                WHERE exchange_id = ?
            ";
        }

        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$action, $exchange_id])) {

            $message = "Exchange status updated successfully.";
            $message_type = "success";
        }

    }
}


/*
====================================================
GET EXCHANGE DETAILS
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
        u1.university_id AS student_a_university_id,

        u2.full_name AS student_b_name,
        u2.email AS student_b_email,
        u2.university_id AS student_b_university_id,

        r1.name AS offered_resource_name,
        r1.category AS offered_resource_category,

        r2.name AS wanted_resource_name,
        r2.category AS wanted_resource_category

    FROM exchange e

    LEFT JOIN users u1
        ON e.student_a_id = u1.user_id

    LEFT JOIN users u2
        ON e.student_b_id = u2.user_id

    LEFT JOIN resource r1
        ON e.resource_offered_id = r1.resource_id

    LEFT JOIN resource r2
        ON e.resource_wanted_id = r2.resource_id

    WHERE e.exchange_id = ?

    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([$exchange_id]);

$exchange = $stmt->fetch(PDO::FETCH_ASSOC);


/*
====================================================
NOT FOUND
====================================================
*/

if (!$exchange) {

    echo "Exchange not found.";
    exit();

}


$status = $exchange["status"] ?? "pending";


/*
====================================================
STATUS
====================================================
*/

$status_label = ucfirst($status);

$status_icon = "🟡";

if ($status === "matched") {

    $status_icon = "🔵";

} elseif ($status === "accepted") {

    $status_icon = "🟢";

} elseif ($status === "completed") {

    $status_icon = "✅";

} elseif ($status === "rejected") {

    $status_icon = "🔴";

} elseif ($status === "cancelled") {

    $status_icon = "⚪";

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
    Exchange #<?= (int)$exchange["exchange_id"] ?> - IT Admin
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
   MAIN
==================================================== */

.page-container {

    margin-left: 240px;

    min-height: 100vh;

    padding: 40px;

}

.page-wrapper {

    max-width: 1000px;

    margin: auto;

}


/* ====================================================
   BACK
==================================================== */

.back-link {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 22px;

    color: #15803d;

    text-decoration: none;

    font-size: 14px;

    font-weight: 700;

}

.back-link:hover {

    text-decoration: underline;

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

    font-size: 14px;

}


/* ====================================================
   MESSAGE
==================================================== */

.message {

    padding: 13px 16px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-size: 14px;

    font-weight: 600;

}

.message.success {

    background: #dcfce7;

    color: #166534;

}


/* ====================================================
   MAIN CARD
==================================================== */

.details-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    padding: 30px;

    box-shadow:
        0 6px 25px rgba(0,0,0,0.05);

}


/* ====================================================
   TOP
==================================================== */

.exchange-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    padding-bottom: 25px;

    border-bottom: 1px solid #eeeeee;

}

.exchange-id {

    font-size: 13px;

    color: #6b7280;

}

.exchange-id strong {

    color: #111827;

}


/* ====================================================
   STATUS
==================================================== */

.status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 8px 14px;

    border-radius: 20px;

    font-size: 12px;

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
   EXCHANGE AREA
==================================================== */

.exchange-area {

    display: grid;

    grid-template-columns: 1fr 70px 1fr;

    align-items: center;

    gap: 20px;

    margin: 30px 0;

}


/* ====================================================
   PERSON CARD
==================================================== */

.person-card {

    background: #f9fafb;

    border: 1px solid #eeeeee;

    border-radius: 14px;

    padding: 22px;

}

.person-title {

    font-size: 11px;

    text-transform: uppercase;

    color: #9ca3af;

    font-weight: 700;

    margin-bottom: 10px;

}

.person-name {

    font-size: 18px;

    font-weight: 700;

    margin-bottom: 7px;

}

.person-email {

    font-size: 12px;

    color: #6b7280;

    margin-bottom: 5px;

}

.person-university {

    font-size: 12px;

    color: #6b7280;

}


/* ====================================================
   RESOURCE
==================================================== */

.resource-box {

    margin-top: 18px;

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 14px;

}

.resource-label {

    display: block;

    font-size: 10px;

    color: #9ca3af;

    text-transform: uppercase;

    font-weight: 700;

    margin-bottom: 6px;

}

.resource-name {

    font-size: 14px;

    font-weight: 700;

    color: #374151;

}

.resource-category {

    display: inline-block;

    margin-top: 7px;

    font-size: 10px;

    color: #15803d;

    background: #ecfdf5;

    padding: 4px 8px;

    border-radius: 12px;

}


/* ====================================================
   ARROW
==================================================== */

.exchange-arrow {

    width: 55px;

    height: 55px;

    border-radius: 50%;

    background: #ecfdf5;

    color: #15803d;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    font-weight: bold;

    margin: auto;

}


/* ====================================================
   INFO
==================================================== */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-top: 10px;

}

.info-box {

    background: #f9fafb;

    border-radius: 11px;

    padding: 15px;

}

.info-label {

    display: block;

    font-size: 10px;

    color: #9ca3af;

    text-transform: uppercase;

    font-weight: 700;

    margin-bottom: 6px;

}

.info-value {

    font-size: 13px;

    font-weight: 700;

    color: #374151;

}


/* ====================================================
   ACTIONS
==================================================== */

.actions {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    margin-top: 30px;

    padding-top: 25px;

    border-top: 1px solid #eeeeee;

}

.action-btn {

    border: none;

    cursor: pointer;

    padding: 11px 18px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 700;

}

.accept {

    background: #16a34a;

    color: white;

}

.accept:hover {

    background: #15803d;

}

.complete {

    background: #059669;

    color: white;

}

.complete:hover {

    background: #047857;

}

.reject {

    background: #dc2626;

    color: white;

}

.reject:hover {

    background: #b91c1c;

}

.cancel {

    background: #6b7280;

    color: white;

}

.cancel:hover {

    background: #4b5563;

}


/* ====================================================
   FOOTER
==================================================== */

.footer {

    margin-top: 25px;

    text-align: center;

}

.footer a {

    color: #15803d;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 800px) {

    .page-container {

        margin-left: 0;

        padding: 25px 18px;

    }

    .exchange-area {

        grid-template-columns: 1fr;

    }

    .exchange-arrow {

        transform: rotate(90deg);

    }

    .info-grid {

        grid-template-columns: 1fr;

    }

    .exchange-top {

        align-items: flex-start;

        flex-direction: column;

    }

}

</style>

</head>


<body>


<?php include "includes/sidebar.php"; ?>


<div class="page-container">

<div class="page-wrapper">


<!-- BACK -->

<a
    href="exchanges.php"
    class="back-link"
>

    ← Back to Exchanges

</a>


<!-- HEADER -->

<div class="page-header">

    <h1>
        🔄 Exchange Details
    </h1>

    <p>
        View the complete details of this resource exchange.
    </p>

</div>


<?php if ($message !== ""): ?>

<div class="message <?= htmlspecialchars($message_type) ?>">

    <?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>


<!-- CARD -->

<div class="details-card">


<!-- TOP -->

<div class="exchange-top">

    <div class="exchange-id">

        Exchange ID:

        <strong>
            #<?= (int)$exchange["exchange_id"] ?>
        </strong>

    </div>


    <span class="status <?= htmlspecialchars($status) ?>">

        <?= $status_icon ?>

        <?= htmlspecialchars($status_label) ?>

    </span>

</div>


<!-- EXCHANGE -->

<div class="exchange-area">


<!-- STUDENT A -->

<div class="person-card">

    <div class="person-title">
        Student A
    </div>

    <div class="person-name">

        <?= htmlspecialchars(
            $exchange["student_a_name"]
            ?? "Unknown Student"
        ) ?>

    </div>

    <div class="person-email">

        📧

        <?= htmlspecialchars(
            $exchange["student_a_email"]
            ?? "-"
        ) ?>

    </div>

    <div class="person-university">

        🎓 University ID:

        <?= htmlspecialchars(
            $exchange["student_a_university_id"]
            ?? "-"
        ) ?>

    </div>


    <div class="resource-box">

        <span class="resource-label">
            Offered Resource
        </span>

        <div class="resource-name">

            <?= htmlspecialchars(
                $exchange["offered_resource_name"]
                ?? "Unknown Resource"
            ) ?>

        </div>

        <?php if (!empty($exchange["offered_resource_category"])): ?>

        <span class="resource-category">

            <?= htmlspecialchars(
                $exchange["offered_resource_category"]
            ) ?>

        </span>

        <?php endif; ?>

    </div>

</div>


<!-- ARROW -->

<div class="exchange-arrow">

    ⇄

</div>


<!-- STUDENT B -->

<div class="person-card">

    <div class="person-title">
        Student B
    </div>

    <div class="person-name">

        <?= htmlspecialchars(
            $exchange["student_b_name"]
            ?? "Unknown Student"
        ) ?>

    </div>

    <div class="person-email">

        📧

        <?= htmlspecialchars(
            $exchange["student_b_email"]
            ?? "-"
        ) ?>

    </div>

    <div class="person-university">

        🎓 University ID:

        <?= htmlspecialchars(
            $exchange["student_b_university_id"]
            ?? "-"
        ) ?>

    </div>


    <div class="resource-box">

        <span class="resource-label">
            Wanted Resource
        </span>

        <div class="resource-name">

            <?= htmlspecialchars(
                $exchange["wanted_resource_name"]
                ?? "Unknown Resource"
            ) ?>

        </div>

        <?php if (!empty($exchange["wanted_resource_category"])): ?>

        <span class="resource-category">

            <?= htmlspecialchars(
                $exchange["wanted_resource_category"]
            ) ?>

        </span>

        <?php endif; ?>

    </div>

</div>


</div>


<!-- INFO -->

<div class="info-grid">


<div class="info-box">

    <span class="info-label">
        Created At
    </span>

    <span class="info-value">

        <?= !empty($exchange["created_at"])
            ? date(
                "M d, Y - h:i A",
                strtotime($exchange["created_at"])
            )
            : "-"
        ?>

    </span>

</div>


<div class="info-box">

    <span class="info-label">
        Current Status
    </span>

    <span class="info-value">

        <?= htmlspecialchars(
            ucfirst($status)
        ) ?>

    </span>

</div>


<div class="info-box">

    <span class="info-label">
        Completed At
    </span>

    <span class="info-value">

        <?= !empty($exchange["completed_at"])
            ? date(
                "M d, Y - h:i A",
                strtotime($exchange["completed_at"])
            )
            : "-"
        ?>

    </span>

</div>


</div>


<!-- ACTIONS -->

<?php if (
    $status !== "completed" &&
    $status !== "rejected" &&
    $status !== "cancelled"
): ?>

<div class="actions">


<?php if (
    $status === "pending" ||
    $status === "matched"
): ?>

<form method="POST">

    <input
        type="hidden"
        name="action"
        value="accepted"
    >

    <button
        type="submit"
        class="action-btn accept"
        onclick="return confirm('Accept this exchange?');"
    >

        ✓ Accept Exchange

    </button>

</form>

<?php endif; ?>


<?php if ($status === "accepted"): ?>

<form method="POST">

    <input
        type="hidden"
        name="action"
        value="completed"
    >

    <button
        type="submit"
        class="action-btn complete"
        onclick="return confirm('Mark this exchange as completed?');"
    >

        ✓ Mark Completed

    </button>

</form>

<?php endif; ?>


<form method="POST">

    <input
        type="hidden"
        name="action"
        value="rejected"
    >

    <button
        type="submit"
        class="action-btn reject"
        onclick="return confirm('Reject this exchange?');"
    >

        ✕ Reject

    </button>

</form>


<form method="POST">

    <input
        type="hidden"
        name="action"
        value="cancelled"
    >

    <button
        type="submit"
        class="action-btn cancel"
        onclick="return confirm('Cancel this exchange?');"
    >

        Cancel

    </button>

</form>


</div>

<?php endif; ?>


</div>


<!-- FOOTER -->

<div class="footer">

    <a href="exchanges.php">

        ← Return to All Exchanges

    </a>

</div>


</div>


</div>

</div>


</body>

</html>