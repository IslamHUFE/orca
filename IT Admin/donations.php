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

$message = "";
$error = "";


/* ====================================================
   HANDLE DONATION ACTION
==================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $resource_id = (int)($_POST["resource_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($resource_id <= 0) {

        $error = "Invalid resource.";

    } else {

        try {

            /* ====================================================
               APPROVE DONATION
            ==================================================== */

            if ($action === "approve") {

                $stmt = $pdo->prepare("
                    UPDATE resource
                    SET status = 'approved'
                    WHERE resource_id = ?
                    AND availability_type = 'donation'
                ");

                $stmt->execute([
                    $resource_id
                ]);

                $message = "Donation approved successfully.";
            }


            /* ====================================================
               REJECT DONATION
            ==================================================== */

            elseif ($action === "reject") {

                $stmt = $pdo->prepare("
                    UPDATE resource
                    SET status = 'rejected'
                    WHERE resource_id = ?
                    AND availability_type = 'donation'
                ");

                $stmt->execute([
                    $resource_id
                ]);

                $message = "Donation rejected successfully.";
            }


            /* ====================================================
               MARK COMPLETED
            ==================================================== */

            elseif ($action === "complete") {

                $stmt = $pdo->prepare("
                    UPDATE resource
                    SET status = 'transferred'
                    WHERE resource_id = ?
                    AND availability_type = 'donation'
                ");

                $stmt->execute([
                    $resource_id
                ]);

                $stmt = $pdo->prepare("
                    UPDATE donation
                    SET
                        status = 'completed',
                        completed_at = NOW()
                    WHERE resource_id = ?
                    AND status = 'approved'
                ");

                $stmt->execute([
                    $resource_id
                ]);

                $message = "Donation marked as completed.";
            }


            else {

                $error = "Invalid action.";
            }

        } catch (PDOException $e) {

            $error = "Failed to update donation.";
        }
    }
}


/* ====================================================
   GET ALL DONATIONS
==================================================== */

$donations = [];

try {

    $stmt = $pdo->query("

        SELECT

            r.resource_id,
            r.name AS resource_name,
            r.category,
            r.description,
            r.`condition`,
            r.location,
            r.status AS resource_status,
            r.created_at,

            /* DONOR */
            u.user_id AS donor_id,
            u.full_name AS donor_name,
            u.email AS donor_email,

            /* DONATION */
            d.donation_id,
            d.requester_id,
            d.status AS donation_status,
            d.requested_at,
            d.completed_at,

            /* REQUESTER */
            requester.full_name AS requester_name,
            requester.email AS requester_email

        FROM resource r

        /* DONOR */
        LEFT JOIN users u
            ON r.owner_id = u.user_id

        /* DONATION */
        LEFT JOIN donation d
            ON r.resource_id = d.resource_id

        /* REQUESTER */
        LEFT JOIN users requester
            ON d.requester_id = requester.user_id

        WHERE r.availability_type = 'donation'

        ORDER BY r.created_at DESC

    ");

    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    $error = "Unable to load donations.";
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
    Donation Management - UniShare
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

    border: 1px solid #bbf7d0;
}

.error {

    padding: 13px 16px;

    margin-bottom: 20px;

    border-radius: 8px;

    background: #fee2e2;

    color: #b91c1c;

    border: 1px solid #fecaca;
}


/* ====================================================
   TABLE CARD
==================================================== */

.table-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    overflow: hidden;

    box-shadow:
        0 2px 8px rgba(0, 0, 0, 0.03);
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

    min-width: 1100px;

    border-collapse: collapse;
}

thead {

    background: #f3f6f4;
}

th {

    padding: 16px;

    text-align: left;

    font-size: 13px;

    color: #374151;

    border-bottom: 1px solid #e5e7eb;

    white-space: nowrap;
}

td {

    padding: 16px;

    font-size: 14px;

    border-bottom: 1px solid #f0f0f0;

    vertical-align: middle;
}

tbody tr:hover {

    background: #fafafa;
}


/* ====================================================
   USER
==================================================== */

.user-name {

    font-weight: 600;

    color: #111827;

    margin-bottom: 4px;
}

.user-email {

    font-size: 12px;

    color: #6b7280;
}


/* ====================================================
   RESOURCE
==================================================== */

.resource-name {

    font-weight: 600;

    color: #111827;

    margin-bottom: 4px;
}

.resource-category {

    font-size: 12px;

    color: #6b7280;
}


/* ====================================================
   STATUS
==================================================== */

.status {

    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;

    text-transform: capitalize;
}

.status-available {

    background: #dcfce7;

    color: #166534;
}

.status-requested {

    background: #fef3c7;

    color: #92400e;
}

.status-approved {

    background: #dbeafe;

    color: #1d4ed8;
}

.status-rejected {

    background: #fee2e2;

    color: #b91c1c;
}

.status-completed {

    background: #ccfbf1;

    color: #0f766e;
}

.status-transferred {

    background: #ede9fe;

    color: #6d28d9;
}


/* ====================================================
   ACTIONS
==================================================== */

.actions {

    display: flex;

    gap: 8px;

    flex-wrap: wrap;
}

.action-form {

    margin: 0;
}

.action-btn {

    border: none;

    border-radius: 7px;

    padding: 8px 13px;

    font-size: 12px;

    font-weight: 600;

    cursor: pointer;
}

.approve-btn {

    background: #16803c;

    color: white;
}

.approve-btn:hover {

    background: #126b32;
}

.reject-btn {

    background: #fee2e2;

    color: #b91c1c;
}

.reject-btn:hover {

    background: #fecaca;
}

.complete-btn {

    background: #e8f1ff;

    color: #2563eb;
}

.complete-btn:hover {

    background: #dbeafe;
}


/* ====================================================
   EMPTY
==================================================== */

.empty-state {

    padding: 70px 20px;

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

    font-size: 20px;
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
}

</style>

</head>


<body>


<div class="dashboard-container">


    <!-- ====================================================
         SIDEBAR
    ==================================================== -->

    <?php include __DIR__ . "/includes/sidebar.php"; ?>


    <!-- ====================================================
         MAIN CONTENT
    ==================================================== -->

    <main class="main-content">


        <div class="page-header">

            <h1>
                Donation Management 🎁
            </h1>

            <p>
                Manage donated resources and donation requests.
            </p>

        </div>


        <!-- SUCCESS -->

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
             TABLE
        ==================================================== -->

        <div class="table-card">


        <?php if (empty($donations)): ?>


            <div class="empty-state">

                <div class="empty-icon">
                    🎁
                </div>

                <h3>
                    No Donations Found
                </h3>

                <p>
                    There are currently no donated resources.
                </p>

            </div>


        <?php else: ?>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Donor
                            </th>

                            <th>
                                Resource
                            </th>

                            <th>
                                Requester
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($donations as $donation): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <strong>
                                    #<?= (int)$donation["resource_id"] ?>
                                </strong>

                            </td>


                            <!-- DONOR -->

                            <td>

                                <div class="user-name">

                                    <?= htmlspecialchars(
                                        $donation["donor_name"]
                                        ?: "Unknown"
                                    ) ?>

                                </div>

                                <div class="user-email">

                                    <?= htmlspecialchars(
                                        $donation["donor_email"]
                                        ?: "N/A"
                                    ) ?>

                                </div>

                            </td>


                            <!-- RESOURCE -->

                            <td>

                                <div class="resource-name">

                                    <?= htmlspecialchars(
                                        $donation["resource_name"]
                                        ?: "Unnamed Resource"
                                    ) ?>

                                </div>

                                <div class="resource-category">

                                    <?= htmlspecialchars(
                                        $donation["category"]
                                        ?: "N/A"
                                    ) ?>

                                </div>

                            </td>


                            <!-- REQUESTER -->

                            <td>

                                <?php if (
                                    !empty($donation["requester_name"])
                                ): ?>

                                    <div class="user-name">

                                        <?= htmlspecialchars(
                                            $donation["requester_name"]
                                        ) ?>

                                    </div>

                                    <div class="user-email">

                                        <?= htmlspecialchars(
                                            $donation["requester_email"]
                                        ) ?>

                                    </div>

                                <?php else: ?>

                                    <span style="color:#9ca3af;">
                                        No requester
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php

                                /*
                                If there is a donation request,
                                show donation status.

                                Otherwise show resource status.
                                */

                                $status =
                                    !empty($donation["donation_status"])
                                    ? $donation["donation_status"]
                                    : $donation["resource_status"];

                                $status_class =
                                    strtolower(
                                        str_replace(
                                            " ",
                                            "-",
                                            trim($status)
                                        )
                                    );

                                ?>

                                <span
                                    class="status status-<?= htmlspecialchars(
                                        $status_class
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        ucfirst($status)
                                    ) ?>

                                </span>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?= htmlspecialchars(
                                    $donation["created_at"]
                                    ?: "N/A"
                                ) ?>

                            </td>


                            <!-- ACTIONS -->

                            <td>


                                <?php

                                /*
                                If there is NO donation request,
                                admin can approve/reject
                                the donated resource itself.
                                */

                                if (
                                    empty($donation["donation_id"])
                                    &&
                                    $donation["resource_status"] === "available"
                                ):

                                ?>

                                    <div class="actions">


                                        <!-- APPROVE -->

                                        <form
                                            method="POST"
                                            class="action-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="resource_id"
                                                value="<?= (int)$donation["resource_id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="approve"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn approve-btn"
                                            >
                                                Approve
                                            </button>

                                        </form>


                                        <!-- REJECT -->

                                        <form
                                            method="POST"
                                            class="action-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="resource_id"
                                                value="<?= (int)$donation["resource_id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn reject-btn"
                                            >
                                                Reject
                                            </button>

                                        </form>


                                    </div>


                                <?php

                                /*
                                Donation request exists
                                and is requested.
                                */

                                elseif (
                                    !empty($donation["donation_id"])
                                    &&
                                    $donation["donation_status"] === "requested"
                                ):

                                ?>

                                    <div class="actions">

                                        <form
                                            method="POST"
                                            class="action-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="resource_id"
                                                value="<?= (int)$donation["resource_id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="approve"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn approve-btn"
                                            >
                                                Approve
                                            </button>

                                        </form>


                                        <form
                                            method="POST"
                                            class="action-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="resource_id"
                                                value="<?= (int)$donation["resource_id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn reject-btn"
                                            >
                                                Reject
                                            </button>

                                        </form>

                                    </div>


                                <?php

                                /*
                                Approved donation
                                */

                                elseif (
                                    !empty($donation["donation_id"])
                                    &&
                                    $donation["donation_status"] === "approved"
                                ):

                                ?>

                                    <form
                                        method="POST"
                                        class="action-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="resource_id"
                                            value="<?= (int)$donation["resource_id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="complete"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn complete-btn"
                                        >
                                            Complete
                                        </button>

                                    </form>


                                <?php else: ?>


                                    <span
                                        style="
                                            color:#9ca3af;
                                            font-size:12px;
                                        "
                                    >
                                        No action
                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>