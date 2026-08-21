<?php

$current_page = "exchanges.php";

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

require_once "../db.php";

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";


/* =====================================================
   HANDLE ACTIONS
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    /* =================================================
       CREATE EXCHANGE
    ================================================= */

    if ($action === "create_exchange") {

        $resource_offered_id = intval(
            $_POST["resource_offered_id"] ?? 0
        );

        $resource_wanted_id = intval(
            $_POST["resource_wanted_id"] ?? 0
        );


        if ($resource_offered_id <= 0 || $resource_wanted_id <= 0) {

            $error = "Please select both resources.";

        } elseif ($resource_offered_id === $resource_wanted_id) {

            $error = "You cannot exchange a resource with itself.";

        } else {

            $sql = "
                SELECT
                    resource_id,
                    owner_id,
                    name,
                    category,
                    availability_type,
                    status
                FROM resource
                WHERE resource_id = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$resource_offered_id]);

            $offered_resource = $stmt->fetch(PDO::FETCH_ASSOC);


            $sql = "
                SELECT
                    resource_id,
                    owner_id,
                    name,
                    category,
                    availability_type,
                    status
                FROM resource
                WHERE resource_id = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$resource_wanted_id]);

            $wanted_resource = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$offered_resource) {

                $error = "Your selected resource was not found.";

            } elseif (!$wanted_resource) {

                $error = "The selected wanted resource was not found.";

            } elseif ($offered_resource["owner_id"] != $user_id) {

                $error = "You can only offer your own resource.";

            } elseif ($wanted_resource["owner_id"] == $user_id) {

                $error = "You cannot exchange with your own resource.";

            } elseif ($offered_resource["availability_type"] !== "exchange") {

                $error = "Your resource is not available for exchange.";

            } elseif ($wanted_resource["availability_type"] !== "exchange") {

                $error = "The wanted resource is not available for exchange.";

            } elseif ($offered_resource["status"] !== "available") {

                $error = "Your resource is not currently available.";

            } elseif ($wanted_resource["status"] !== "available") {

                $error = "The wanted resource is not currently available.";

            } else {

                $sql = "
                    SELECT exchange_id
                    FROM exchange
                    WHERE resource_offered_id = ?
                    AND resource_wanted_id = ?
                    AND student_a_id = ?
                    AND status IN (
                        'pending',
                        'matched',
                        'accepted'
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $resource_offered_id,
                    $resource_wanted_id,
                    $user_id
                ]);

                $existing_exchange = $stmt->fetch(PDO::FETCH_ASSOC);


                if ($existing_exchange) {

                    $error =
                        "You already have an active exchange request for these resources.";

                } else {

                    $student_b_id = $wanted_resource["owner_id"];


                    $sql = "
                        INSERT INTO exchange
                        (
                            resource_offered_id,
                            resource_wanted_id,
                            student_a_id,
                            student_b_id,
                            status
                        )
                        VALUES
                        (?, ?, ?, ?, 'pending')
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $resource_offered_id,
                        $resource_wanted_id,
                        $user_id,
                        $student_b_id
                    ]);


                    $sql = "
                        UPDATE resource
                        SET status = 'requested'
                        WHERE resource_id IN (?, ?)
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $resource_offered_id,
                        $resource_wanted_id
                    ]);


                    $message = "Exchange request sent successfully.";
                }
            }
        }
    }


    /* =================================================
       ACCEPT EXCHANGE
    ================================================= */

    elseif ($action === "accept_exchange") {

        $exchange_id = intval(
            $_POST["exchange_id"] ?? 0
        );


        $sql = "
            SELECT
                exchange_id,
                resource_offered_id,
                resource_wanted_id,
                student_a_id,
                student_b_id,
                status
            FROM exchange
            WHERE exchange_id = ?
            AND student_b_id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $exchange_id,
            $user_id
        ]);

        $exchange = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$exchange) {

            $error = "Exchange request not found.";

        } elseif ($exchange["status"] !== "pending") {

            $error = "This exchange request cannot be accepted.";

        } else {

            $sql = "
                UPDATE exchange
                SET status = 'accepted'
                WHERE exchange_id = ?
                AND student_b_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $exchange_id,
                $user_id
            ]);

            $message = "Exchange request accepted successfully.";
        }
    }


    /* =================================================
       REJECT EXCHANGE
    ================================================= */

    elseif ($action === "reject_exchange") {

        $exchange_id = intval(
            $_POST["exchange_id"] ?? 0
        );


        $sql = "
            SELECT
                exchange_id,
                resource_offered_id,
                resource_wanted_id,
                student_b_id,
                status
            FROM exchange
            WHERE exchange_id = ?
            AND student_b_id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $exchange_id,
            $user_id
        ]);

        $exchange = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$exchange) {

            $error = "Exchange request not found.";

        } elseif ($exchange["status"] !== "pending") {

            $error = "This exchange request cannot be rejected.";

        } else {

            $sql = "
                UPDATE exchange
                SET status = 'rejected'
                WHERE exchange_id = ?
                AND student_b_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $exchange_id,
                $user_id
            ]);


            $sql = "
                UPDATE resource
                SET status = 'available'
                WHERE resource_id IN (?, ?)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $exchange["resource_offered_id"],
                $exchange["resource_wanted_id"]
            ]);


            $message = "Exchange request rejected.";
        }
    }


    /* =================================================
       COMPLETE EXCHANGE
    ================================================= */

    elseif ($action === "complete_exchange") {

        $exchange_id = intval(
            $_POST["exchange_id"] ?? 0
        );


        $sql = "
            SELECT
                exchange_id,
                resource_offered_id,
                resource_wanted_id,
                student_a_id,
                student_b_id,
                status
            FROM exchange
            WHERE exchange_id = ?
            AND (
                student_a_id = ?
                OR student_b_id = ?
            )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $exchange_id,
            $user_id,
            $user_id
        ]);

        $exchange = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$exchange) {

            $error = "Exchange request not found.";

        } elseif ($exchange["status"] !== "accepted") {

            $error = "Only accepted exchanges can be completed.";

        } else {

            $sql = "
                UPDATE exchange
                SET
                    status = 'completed',
                    completed_at = NOW()
                WHERE exchange_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $exchange_id
            ]);


            $sql = "
                UPDATE resource
                SET status = 'transferred'
                WHERE resource_id IN (?, ?)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $exchange["resource_offered_id"],
                $exchange["resource_wanted_id"]
            ]);


            $message = "Exchange completed successfully.";
        }
    }


    /* =================================================
       SEND MESSAGE
    ================================================= */

    elseif ($action === "send_message") {

        $receiver_id = intval(
            $_POST["receiver_id"] ?? 0
        );

        $exchange_id = intval(
            $_POST["exchange_id"] ?? 0
        );

        $resource_id = intval(
            $_POST["resource_id"] ?? 0
        );

        $message_text = trim(
            $_POST["message"] ?? ""
        );


        if ($receiver_id <= 0 || $exchange_id <= 0) {

            $error = "Invalid message information.";

        } elseif ($message_text === "") {

            $error = "Please enter a message.";

        } elseif ($receiver_id == $user_id) {

            $error = "You cannot message yourself.";

        } else {

            $sql = "
                SELECT exchange_id
                FROM exchange
                WHERE exchange_id = ?
                AND (
                    student_a_id = ?
                    OR student_b_id = ?
                )
                AND status IN ('accepted', 'completed')
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $exchange_id,
                $user_id,
                $user_id
            ]);

            $exchange_check =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$exchange_check) {

                $error =
                    "You cannot message about this exchange.";

            } else {

                $sql = "
                    INSERT INTO messages
                    (
                        sender_id,
                        receiver_id,
                        resource_id,
                        exchange_id,
                        message
                    )
                    VALUES
                    (?, ?, ?, ?, ?)
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $user_id,
                    $receiver_id,
                    $resource_id > 0
                        ? $resource_id
                        : null,
                    $exchange_id,
                    $message_text
                ]);

                $message =
                    "Message sent successfully.";
            }
        }
    }
}


/* =====================================================
   GET MY EXCHANGES
===================================================== */

$sql = "
    SELECT

        e.exchange_id,
        e.resource_offered_id,
        e.resource_wanted_id,
        e.student_a_id,
        e.student_b_id,
        e.status AS exchange_status,
        e.created_at,
        e.completed_at,

        offered.name AS offered_name,
        offered.category AS offered_category,
        offered.image_path AS offered_image,

        wanted.name AS wanted_name,
        wanted.category AS wanted_category,
        wanted.image_path AS wanted_image,

        student_a.full_name AS student_a_name,
        student_b.full_name AS student_b_name

    FROM exchange e

    INNER JOIN resource offered
        ON e.resource_offered_id = offered.resource_id

    INNER JOIN resource wanted
        ON e.resource_wanted_id = wanted.resource_id

    LEFT JOIN users student_a
        ON e.student_a_id = student_a.user_id

    LEFT JOIN users student_b
        ON e.student_b_id = student_b.user_id

    WHERE
        e.student_a_id = ?
        OR e.student_b_id = ?

    ORDER BY e.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id,
    $user_id
]);

$exchanges = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GET MY AVAILABLE RESOURCES
===================================================== */

$sql = "
    SELECT

        resource_id,
        name,
        category,
        description,
        `condition`,
        location,
        image_path

    FROM resource

    WHERE owner_id = ?

    AND availability_type = 'exchange'

    AND status = 'available'

    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id
]);

$my_resources = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GET OTHER STUDENTS' RESOURCES
===================================================== */

$sql = "
    SELECT

        r.resource_id,
        r.owner_id,
        r.name,
        r.category,
        r.description,
        r.`condition`,
        r.location,
        r.image_path,

        u.full_name AS owner_name

    FROM resource r

    INNER JOIN users u
        ON r.owner_id = u.user_id

    WHERE r.owner_id != ?

    AND r.availability_type = 'exchange'

    AND r.status = 'available'

    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id
]);

$available_resources =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Exchanges - UniShare</title>

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

.dashboard-container {
    display: flex;
    min-height: 100vh;
}



/* =========================
   MAIN
========================= */

.main-content {
    margin-left: 240px;
    width: calc(100% - 240px);
    min-height: 100vh;
    padding: 45px;
}

.exchanges-container {
    max-width: 1100px;
    margin: auto;
}


/* =========================
   HEADER
========================= */

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
}


/* =========================
   MESSAGES
========================= */

.message {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.success {
    background: #eaf6ee;
    color: #16803c;
    border: 1px solid #cdebd8;
}

.error {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}


/* =========================
   CREATE BOX
========================= */

.create-box {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 35px;
}

.create-box h2 {
    margin: 0 0 8px;
}

.create-box p {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 25px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    font-size: 14px;
}


/* =========================
   BUTTONS
========================= */

.btn {
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-green {
    background: #16803c;
    color: white;
}

.btn-green:hover {
    background: #126b32;
}

.btn-red {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}

.btn-blue {
    background: #e8f1ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}


/* =========================
   SECTIONS
========================= */

.section-title {
    font-size: 22px;
    margin: 35px 0 18px;
}


/* =========================
   RESOURCES
========================= */

.resource-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.resource-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
}

.resource-image {
    width: 100%;
    height: 180px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.resource-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    font-size: 55px;
}

.resource-content {
    padding: 18px;
}

.category {
    display: inline-block;
    padding: 5px 9px;
    background: #eaf6ee;
    color: #16803c;
    border-radius: 15px;
    font-size: 11px;
    margin-bottom: 10px;
}

.resource-content h3 {
    margin: 0 0 8px;
}

.resource-description {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.5;
}

.owner {
    color: #6b7280;
    font-size: 12px;
}


/* =========================
   EXCHANGES
========================= */

.exchange-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.exchange-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 15px;
    padding: 22px;
}

.exchange-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.exchange-header h3 {
    margin: 0;
}

.exchange-resources {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.exchange-resource {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 15px;
    background: #fafafa;
}

.exchange-resource h4 {
    margin: 0 0 8px;
    color: #6b7280;
}

.exchange-resource p {
    color: #6b7280;
    font-size: 13px;
}


/* =========================
   STATUS
========================= */

.status {
    padding: 6px 11px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    background: #fff7e6;
    color: #b7791f;
}

.status-matched {
    background: #e8f1ff;
    color: #2563eb;
}

.status-accepted {
    background: #eaf6ee;
    color: #16803c;
}

.status-completed {
    background: #e6fffa;
    color: #0f766e;
}

.status-rejected {
    background: #fff1f2;
    color: #be123c;
}


/* =========================
   FOOTER
========================= */

.exchange-footer {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.exchange-date {
    color: #6b7280;
    font-size: 12px;
}

.actions {
    display: flex;
    gap: 8px;
    align-items: center;
}


/* =========================
   EMPTY
========================= */

.empty-state {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 15px;
    padding: 40px 25px;
    text-align: center;
}

.empty-icon {
    font-size: 50px;
    margin-bottom: 10px;
}

.empty-state h3 {
    margin: 0 0 8px;
}

.empty-state p {
    color: #6b7280;
    font-size: 14px;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 950px) {

    .resource-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 800px) {

    .sidebar {
        width: 200px;
    }

    .main-content {
        margin-left: 200px;
        width: calc(100% - 200px);
        padding: 25px;
    }

    .form-grid,
    .exchange-resources {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {

    .sidebar {
        position: relative;
        width: 100%;
        min-height: auto;
    }

    .dashboard-container {
        flex-direction: column;
    }

    .main-content {
        margin-left: 0;
        width: 100%;
    }

    .resource-grid {
        grid-template-columns: 1fr;
    }

    .exchange-header,
    .exchange-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}

</style>

</head>

<body>

<div class="dashboard-container">

    <?php include "includes/sidebar.php"; ?>


    <main class="main-content">

        <div class="exchanges-container">

            <div class="page-header">

                <h1>Exchanges</h1>

                <p>
                    Exchange your resources with other students.
                </p>

            </div>


            <?php if ($message !== ""): ?>

                <div class="message success">
                    <?= htmlspecialchars($message) ?>
                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div class="message error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <!-- CREATE EXCHANGE -->

            <div class="create-box">

                <h2>Start an Exchange</h2>

                <p>
                    Choose one of your resources and select another
                    student's resource that you would like to receive.
                </p>


                <?php if (!empty($my_resources) && !empty($available_resources)): ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="action"
                            value="create_exchange"
                        >

                        <div class="form-grid">

                            <div class="form-group">

                                <label>
                                    Resource You Offer
                                </label>

                                <select
                                    name="resource_offered_id"
                                    required
                                >

                                    <option value="">
                                        Select your resource
                                    </option>

                                    <?php foreach ($my_resources as $resource): ?>

                                        <option
                                            value="<?= $resource["resource_id"] ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $resource["name"]
                                            ) ?>

                                            -

                                            <?= htmlspecialchars(
                                                $resource["category"]
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>
                                    Resource You Want
                                </label>

                                <select
                                    name="resource_wanted_id"
                                    required
                                >

                                    <option value="">
                                        Select resource
                                    </option>

                                    <?php foreach ($available_resources as $resource): ?>

                                        <option
                                            value="<?= $resource["resource_id"] ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $resource["name"]
                                            ) ?>

                                            -

                                            <?= htmlspecialchars(
                                                $resource["category"]
                                            ) ?>

                                            -

                                            <?= htmlspecialchars(
                                                $resource["owner_name"]
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-green"
                        >
                            Send Exchange Request
                        </button>

                    </form>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">🔄</div>

                        <h3>
                            Exchange Resources Needed
                        </h3>

                        <p>
                            You need an available resource of your own
                            and another student's resource available
                            for exchange.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


            <!-- AVAILABLE RESOURCES -->

            <h2 class="section-title">
                Resources Available for Exchange
            </h2>


            <?php if (empty($available_resources)): ?>

                <div class="empty-state">

                    <div class="empty-icon">📦</div>

                    <h3>
                        No Resources Available
                    </h3>

                    <p>
                        There are currently no resources available
                        for exchange.
                    </p>

                </div>

            <?php else: ?>

                <div class="resource-grid">

                    <?php foreach ($available_resources as $resource): ?>

                        <div class="resource-card">

                            <div class="resource-image">

                                <?php if (!empty($resource["image_path"])): ?>

                                    <img
                                        src="../<?= htmlspecialchars(
                                            $resource["image_path"]
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $resource["name"]
                                        ) ?>"
                                        onerror="this.style.display='none';"
                                    >

                                <?php else: ?>

                                    <div class="no-image">
                                        📦
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="resource-content">

                                <span class="category">

                                    <?= htmlspecialchars(
                                        $resource["category"]
                                    ) ?>

                                </span>


                                <h3>

                                    <?= htmlspecialchars(
                                        $resource["name"]
                                    ) ?>

                                </h3>


                                <p class="resource-description">

                                    <?php

                                    $description =
                                        $resource["description"] ?? "";

                                    echo htmlspecialchars(
                                        strlen($description) > 90
                                            ? substr(
                                                $description,
                                                0,
                                                90
                                            ) . "..."
                                            : $description
                                    );

                                    ?>

                                </p>


                                <div class="owner">

                                    👤

                                    <?= htmlspecialchars(
                                        $resource["owner_name"]
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <!-- MY EXCHANGES -->

            <h2 class="section-title">
                My Exchanges
            </h2>


            <?php if (empty($exchanges)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        🔄
                    </div>

                    <h3>
                        No Exchanges Yet
                    </h3>

                    <p>
                        You haven't made or received any exchange
                        requests yet.
                    </p>

                </div>

            <?php else: ?>

                <div class="exchange-list">

                    <?php foreach ($exchanges as $exchange): ?>

                        <?php

                        $status =
                            $exchange["exchange_status"]
                            ?? "pending";

                        $status_class =
                            "status-" . strtolower($status);

                        ?>


                        <div class="exchange-card">


                            <div class="exchange-header">

                                <h3>
                                    Exchange #<?= $exchange["exchange_id"] ?>
                                </h3>

                                <span
                                    class="status <?= $status_class ?>"
                                >

                                    <?= ucfirst(
                                        htmlspecialchars($status)
                                    ) ?>

                                </span>

                            </div>


                            <div class="exchange-resources">


                                <div class="exchange-resource">

                                    <h4>
                                        Resource Offered
                                    </h4>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $exchange["offered_name"]
                                        ) ?>

                                    </strong>

                                    <p>

                                        Category:

                                        <?= htmlspecialchars(
                                            $exchange["offered_category"]
                                        ) ?>

                                    </p>

                                    <p>

                                        By:

                                        <?= htmlspecialchars(
                                            $exchange["student_a_name"]
                                        ) ?>

                                    </p>

                                </div>


                                <div class="exchange-resource">

                                    <h4>
                                        Resource Wanted
                                    </h4>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $exchange["wanted_name"]
                                        ) ?>

                                    </strong>

                                    <p>

                                        Category:

                                        <?= htmlspecialchars(
                                            $exchange["wanted_category"]
                                        ) ?>

                                    </p>

                                    <p>

                                        By:

                                        <?= htmlspecialchars(
                                            $exchange["student_b_name"]
                                        ) ?>

                                    </p>

                                </div>

                            </div>


                            <div class="exchange-footer">


                                <div class="exchange-date">

                                    Created:

                                    <?= htmlspecialchars(
                                        $exchange["created_at"]
                                    ) ?>


                                    <?php if (!empty($exchange["completed_at"])): ?>

                                        <br>

                                        Completed:

                                        <?= htmlspecialchars(
                                            $exchange["completed_at"]
                                        ) ?>

                                    <?php endif; ?>

                                </div>


                                <div class="actions">


                                    <?php if (
                                        $exchange["student_b_id"] == $user_id
                                        &&
                                        $status === "pending"
                                    ): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="accept_exchange"
                                            >

                                            <input
                                                type="hidden"
                                                name="exchange_id"
                                                value="<?= $exchange["exchange_id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-green"
                                            >
                                                Accept
                                            </button>

                                        </form>


                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject_exchange"
                                            >

                                            <input
                                                type="hidden"
                                                name="exchange_id"
                                                value="<?= $exchange["exchange_id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-red"
                                            >
                                                Reject
                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <?php if (
                                        (
                                            $exchange["student_a_id"] == $user_id
                                            ||
                                            $exchange["student_b_id"] == $user_id
                                        )
                                        &&
                                        $status === "accepted"
                                    ): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="complete_exchange"
                                            >

                                            <input
                                                type="hidden"
                                                name="exchange_id"
                                                value="<?= $exchange["exchange_id"] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-blue"
                                            >
                                                Mark Completed
                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <?php if (
                                        (
                                            $exchange["student_a_id"] == $user_id
                                            ||
                                            $exchange["student_b_id"] == $user_id
                                        )
                                        &&
                                        (
                                            $status === "accepted"
                                            ||
                                            $status === "completed"
                                        )
                                    ): ?>

                                        <?php

                                        if (
                                            $exchange["student_a_id"]
                                            == $user_id
                                        ) {

                                            $contact_id =
                                                $exchange["student_b_id"];

                                            $contact_name =
                                                $exchange["student_b_name"];

                                            $contact_resource_id =
                                                $exchange[
                                                    "resource_wanted_id"
                                                ];

                                        } else {

                                            $contact_id =
                                                $exchange["student_a_id"];

                                            $contact_name =
                                                $exchange["student_a_name"];

                                            $contact_resource_id =
                                                $exchange[
                                                    "resource_offered_id"
                                                ];
                                        }

                                        ?>


                                        <a
                                            href="conversation.php?user_id=<?= $contact_id ?>&exchange_id=<?= $exchange["exchange_id"] ?>&resource_id=<?= $contact_resource_id ?>"
                                            class="btn btn-blue"
                                            style="
                                                text-decoration:none;
                                                display:inline-block;
                                            "
                                        >

                                            💬 Message

                                        </a>

                                    <?php endif; ?>


                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


        </div>

    </main>

</div>

</body>

</html>