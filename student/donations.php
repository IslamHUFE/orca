<?php

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


/*
=====================================================
HANDLE DONATION ACTIONS
=====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $donation_id = intval($_POST["donation_id"] ?? 0);


    /*
    =================================================
    ACCEPT DONATION
    =================================================
    */

    if ($action === "accept_donation") {

        try {

            $pdo->beginTransaction();

            /*
            Get donation
            */

            $sql = "
                SELECT
                    d.donation_id,
                    d.resource_id,
                    d.requester_id,
                    d.status,
                    r.owner_id,
                    r.status AS resource_status
                FROM donation d
                INNER JOIN resource r
                    ON d.resource_id = r.resource_id
                WHERE d.donation_id = ?
                AND r.owner_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $donation_id,
                $user_id
            ]);

            $donation = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$donation) {

                $pdo->rollBack();

                $error = "Donation request not found.";

            } elseif ($donation["status"] !== "requested") {

                $pdo->rollBack();

                $error = "This donation request cannot be accepted.";

            } else {

                /*
                Update donation
                */

                $sql = "
                    UPDATE donation
                    SET status = 'approved'
                    WHERE donation_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation_id
                ]);


                /*
                Update resource
                */

                $sql = "
                    UPDATE resource
                    SET status = 'approved'
                    WHERE resource_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation["resource_id"]
                ]);


                /*
                Update request
                */

                $sql = "
                    UPDATE requests
                    SET status = 'approved'
                    WHERE resource_id = ?
                    AND user_id = ?
                    AND status = 'pending'
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation["resource_id"],
                    $donation["requester_id"]
                ]);


                $pdo->commit();

                $message = "Donation request accepted successfully.";
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Something went wrong while accepting the donation.";
        }
    }


    /*
    =================================================
    REJECT DONATION
    =================================================
    */

    elseif ($action === "reject_donation") {

        try {

            $pdo->beginTransaction();


            /*
            Get donation
            */

            $sql = "
                SELECT
                    d.donation_id,
                    d.resource_id,
                    d.requester_id,
                    d.status,
                    r.owner_id
                FROM donation d
                INNER JOIN resource r
                    ON d.resource_id = r.resource_id
                WHERE d.donation_id = ?
                AND r.owner_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $donation_id,
                $user_id
            ]);

            $donation = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$donation) {

                $pdo->rollBack();

                $error = "Donation request not found.";

            } elseif ($donation["status"] !== "requested") {

                $pdo->rollBack();

                $error = "This donation request cannot be rejected.";

            } else {

                /*
                Update donation
                */

                $sql = "
                    UPDATE donation
                    SET status = 'rejected'
                    WHERE donation_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation_id
                ]);


                /*
                Make resource available again
                */

                $sql = "
                    UPDATE resource
                    SET status = 'available'
                    WHERE resource_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation["resource_id"]
                ]);


                /*
                Update request
                */

                $sql = "
                    UPDATE requests
                    SET status = 'rejected'
                    WHERE resource_id = ?
                    AND user_id = ?
                    AND status = 'pending'
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation["resource_id"],
                    $donation["requester_id"]
                ]);


                $pdo->commit();

                $message = "Donation request rejected.";
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Something went wrong while rejecting the donation.";
        }
    }


    /*
    =================================================
    COMPLETE DONATION
    =================================================
    */

    elseif ($action === "complete_donation") {

        try {

            $pdo->beginTransaction();


            /*
            Get donation
            */

            $sql = "
                SELECT
                    d.donation_id,
                    d.resource_id,
                    d.requester_id,
                    d.status,
                    r.owner_id
                FROM donation d
                INNER JOIN resource r
                    ON d.resource_id = r.resource_id
                WHERE d.donation_id = ?
                AND (
                    r.owner_id = ?
                    OR d.requester_id = ?
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $donation_id,
                $user_id,
                $user_id
            ]);

            $donation = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$donation) {

                $pdo->rollBack();

                $error = "Donation not found.";

            } elseif ($donation["status"] !== "approved") {

                $pdo->rollBack();

                $error = "Only approved donations can be completed.";

            } else {

                /*
                Update donation
                */

                $sql = "
                    UPDATE donation
                    SET
                        status = 'completed',
                        completed_at = NOW()
                    WHERE donation_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation_id
                ]);


                /*
                Update resource
                */

                $sql = "
                    UPDATE resource
                    SET status = 'transferred'
                    WHERE resource_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation["resource_id"]
                ]);


                /*
                Update request
                */

                $sql = "
                    UPDATE requests
                    SET status = 'completed'
                    WHERE resource_id = ?
                    AND user_id = ?
                    AND status = 'approved'
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $donation["resource_id"],
                    $donation["requester_id"]
                ]);


                $pdo->commit();

                $message = "Donation completed successfully.";
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Something went wrong while completing the donation.";
        }
    }
}


/*
=====================================================
GET MY DONATIONS
=====================================================
*/

$sql = "
    SELECT

        r.resource_id,
        r.name,
        r.category,
        r.description,
        r.`condition`,
        r.location,
        r.status AS resource_status,
        r.image_path,
        r.created_at,

        d.donation_id,
        d.status AS donation_status,
        d.requester_id,
        d.requested_at,
        d.completed_at,

        u.full_name AS requester_name

    FROM resource r

    LEFT JOIN donation d
        ON r.resource_id = d.resource_id

    LEFT JOIN users u
        ON d.requester_id = u.user_id

    WHERE r.owner_id = ?
    AND r.availability_type = 'donation'

    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id
]);

$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>My Donations - UniShare</title>

<link
    rel="stylesheet"
    href="../assets/css/dashboard.css"
>

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



/* =====================================
   MAIN
===================================== */

.main-content {
    margin-left: 240px;

    width: calc(100% - 240px);

    min-height: 100vh;

    padding: 45px;
}

.donations-container {
    max-width: 1100px;

    margin: auto;
}


/* =====================================
   HEADER
===================================== */

.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    margin: 0 0 8px;

    font-size: 32px;
}

.page-header p {
    margin: 0;

    color: #6b7280;
}


/* =====================================
   MESSAGES
===================================== */

.message {
    padding: 14px 16px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 14px;
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


/* =====================================
   DONATION GRID
===================================== */

.donations-grid {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 25px;
}


/* =====================================
   CARD
===================================== */

.donation-card {
    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    overflow: hidden;

    transition: 0.2s;
}

.donation-card:hover {
    transform: translateY(-3px);

    box-shadow:
        0 8px 25px rgba(0, 0, 0, 0.08);
}


/* =====================================
   IMAGE
===================================== */

.donation-image {
    width: 100%;

    height: 230px;

    background: #f3f4f6;

    overflow: hidden;
}

.donation-image img {
    width: 100%;

    height: 100%;

    object-fit: cover;
}

.no-image {
    width: 100%;

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 65px;
}


/* =====================================
   CONTENT
===================================== */

.donation-content {
    padding: 22px;
}

.donation-category {
    display: inline-block;

    padding: 6px 10px;

    background: #eaf6ee;

    color: #16803c;

    border-radius: 20px;

    font-size: 12px;

    margin-bottom: 12px;
}

.donation-content h2 {
    margin: 0 0 10px;

    font-size: 22px;
}

.donation-description {
    color: #6b7280;

    font-size: 14px;

    line-height: 1.6;

    margin-bottom: 20px;
}


/* =====================================
   INFO
===================================== */

.donation-info {
    border-top: 1px solid #e5e7eb;

    padding-top: 15px;

    display: flex;

    flex-direction: column;

    gap: 10px;
}

.info-row {
    display: flex;

    justify-content: space-between;

    gap: 15px;
}

.info-label {
    color: #6b7280;

    font-size: 13px;
}

.info-value {
    color: #111827;

    font-size: 13px;

    font-weight: 600;

    text-align: right;
}


/* =====================================
   STATUS
===================================== */

.status {
    display: inline-block;

    padding: 5px 10px;

    border-radius: 15px;

    font-size: 12px;

    font-weight: 600;
}

.status-available {
    background: #eaf6ee;

    color: #16803c;
}

.status-requested {
    background: #fff7e6;

    color: #b7791f;
}

.status-approved {
    background: #e8f1ff;

    color: #2563eb;
}

.status-rejected {
    background: #fff1f2;

    color: #be123c;
}

.status-completed {
    background: #e6fffa;

    color: #0f766e;
}

.status-transferred {
    background: #f3e8ff;

    color: #7c3aed;
}


/* =====================================
   REQUEST BOX
===================================== */

.request-box {
    margin-top: 20px;

    padding-top: 18px;

    border-top: 1px solid #e5e7eb;
}

.request-title {
    font-size: 14px;

    font-weight: 700;

    margin-bottom: 10px;
}

.requester {
    color: #4b5563;

    font-size: 13px;

    margin-bottom: 12px;
}

.actions {
    display: flex;

    gap: 8px;

    flex-wrap: wrap;
}

.btn {
    border: none;

    border-radius: 8px;

    padding: 10px 15px;

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

.view-btn {
    display: block;

    width: 100%;

    margin-top: 20px;

    padding: 12px;

    background: #16803c;

    color: white;

    text-align: center;

    text-decoration: none;

    border-radius: 8px;

    font-size: 14px;

    font-weight: 600;
}

.view-btn:hover {
    background: #126b32;
}


/* =====================================
   EMPTY
===================================== */

.empty-state {
    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    padding: 60px 30px;

    text-align: center;
}

.empty-icon {
    font-size: 65px;

    margin-bottom: 15px;
}

.empty-state h2 {
    margin: 0 0 10px;

    font-size: 22px;
}

.empty-state p {
    color: #6b7280;

    margin-bottom: 25px;
}

.add-resource-btn {
    display: inline-block;

    padding: 12px 22px;

    background: #16803c;

    color: white;

    text-decoration: none;

    border-radius: 8px;

    font-size: 14px;

    font-weight: 600;
}


/* =====================================
   RESPONSIVE
===================================== */

@media (max-width: 900px) {

    .donations-grid {
        grid-template-columns: 1fr;
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
}

@media (max-width: 600px) {

    .dashboard-container {
        flex-direction: column;
    }

    .sidebar {
        position: relative;

        width: 100%;

        min-height: auto;
    }

    .main-content {
        margin-left: 0;

        width: 100%;
    }

    .info-row {
        flex-direction: column;

        gap: 4px;
    }

    .info-value {
        text-align: left;
    }
}

</style>

</head>


<body>


<div class="dashboard-container">


<?php include "includes/sidebar.php"; ?>




<!-- =====================================
     MAIN
===================================== -->

<main class="main-content">

<div class="donations-container">


    <div class="page-header">

        <h1>
            My Donations
        </h1>

        <p>
            Manage resources you have donated to the university community.
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


    <?php if (empty($donations)): ?>


        <div class="empty-state">

            <div class="empty-icon">
                🎁
            </div>

            <h2>
                No Donations Yet
            </h2>

            <p>
                You haven't donated any resources yet.
            </p>

            <a
                href="add_resource.php"
                class="add-resource-btn"
            >
                Add a Resource
            </a>

        </div>


    <?php else: ?>


        <div class="donations-grid">


            <?php foreach ($donations as $donation): ?>


                <div class="donation-card">


                    <!-- IMAGE -->

                    <div class="donation-image">

                        <?php if (!empty($donation["image_path"])): ?>

                            <img
                                src="../<?= htmlspecialchars($donation["image_path"]) ?>"
                                alt="<?= htmlspecialchars($donation["name"]) ?>"
                                onerror="this.style.display='none';"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                🎁
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- CONTENT -->

                    <div class="donation-content">


                        <span class="donation-category">

                            <?= htmlspecialchars(
                                $donation["category"]
                            ) ?>

                        </span>


                        <h2>

                            <?= htmlspecialchars(
                                $donation["name"]
                            ) ?>

                        </h2>


                        <p class="donation-description">

                            <?php

                            $description =
                                $donation["description"] ?? "";

                            if (strlen($description) > 100) {

                                echo htmlspecialchars(
                                    substr($description, 0, 100)
                                ) . "...";

                            } else {

                                echo htmlspecialchars(
                                    $description
                                );
                            }

                            ?>

                        </p>


                        <!-- INFO -->

                        <div class="donation-info">


                            <div class="info-row">

                                <span class="info-label">
                                    Condition
                                </span>

                                <span class="info-value">

                                    <?= htmlspecialchars(
                                        $donation["condition"]
                                    ) ?>

                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Location
                                </span>

                                <span class="info-value">

                                    <?= htmlspecialchars(
                                        $donation["location"]
                                    ) ?>

                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Status
                                </span>

                                <span class="info-value">

                                    <?php

                                    $status =
                                        $donation["donation_status"]
                                        ?? "available";

                                    $status_class =
                                        "status-" .
                                        strtolower($status);

                                    ?>

                                    <span
                                        class="status <?= $status_class ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars($status)
                                        ) ?>

                                    </span>

                                </span>

                            </div>


                            <?php if (!empty($donation["requester_name"])): ?>

                                <div class="info-row">

                                    <span class="info-label">
                                        Requested By
                                    </span>

                                    <span class="info-value">

                                        <?= htmlspecialchars(
                                            $donation["requester_name"]
                                        ) ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                        </div>


                        <!-- REQUEST ACTIONS -->

                        <?php if (
                            $donation["donation_status"] === "requested"
                            &&
                            !empty($donation["donation_id"])
                        ): ?>


                            <div class="request-box">

                                <div class="request-title">
                                    Donation Request
                                </div>


                                <div class="requester">

                                    👤
                                    <?= htmlspecialchars(
                                        $donation["requester_name"]
                                        ?? "Student"
                                    ) ?>

                                    wants this resource.

                                </div>


                                <div class="actions">


                                    <!-- ACCEPT -->

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="accept_donation"
                                        >

                                        <input
                                            type="hidden"
                                            name="donation_id"
                                            value="<?= $donation["donation_id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-green"
                                        >
                                            Accept
                                        </button>

                                    </form>


                                    <!-- REJECT -->

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject_donation"
                                        >

                                        <input
                                            type="hidden"
                                            name="donation_id"
                                            value="<?= $donation["donation_id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-red"
                                        >
                                            Reject
                                        </button>

                                    </form>


                                </div>

                            </div>


                        <?php endif; ?>


                        <!-- COMPLETE -->

                        <?php if (
                            $donation["donation_status"] === "approved"
                            &&
                            !empty($donation["donation_id"])
                        ): ?>


                            <div class="request-box">

                                <div class="requester">

                                    The donation has been approved.

                                    <br>

                                    After handing the resource to the student,
                                    mark the donation as completed.

                                </div>


                                <div class="actions">


                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="complete_donation"
                                        >

                                        <input
                                            type="hidden"
                                            name="donation_id"
                                            value="<?= $donation["donation_id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-blue"
                                        >
                                            Mark Completed
                                        </button>

                                    </form>


                                </div>

                            </div>


                        <?php endif; ?>


                        <!-- COMPLETED -->

                        <?php if (
                            $donation["donation_status"] === "completed"
                        ): ?>

                            <div class="request-box">

                                <div class="requester">

                                    ✅ Donation successfully completed.

                                    <?php if (
                                        !empty($donation["completed_at"])
                                    ): ?>

                                        <br>

                                        Completed:
                                        <?= htmlspecialchars(
                                            $donation["completed_at"]
                                        ) ?>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- VIEW RESOURCE -->

                        <a
                            href="resource_details.php?id=<?= $donation["resource_id"] ?>"
                            class="view-btn"
                        >
                            View Resource
                        </a>


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