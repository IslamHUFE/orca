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

$request_message = "";
$request_error = "";


/*
=================================
Validate Resource ID
=================================
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: dashboard.php");
    exit();
}

$resource_id = (int) $_GET["id"];


/*
=================================
Get Resource Details
=================================
*/

$sql = "
    SELECT
        resource_id,
        owner_id,
        name,
        category,
        description,
        `condition`,
        location,
        availability_type,
        status,
        image_path,
        created_at
    FROM resource
    WHERE resource_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$resource_id]);

$resource = $stmt->fetch();

if (!$resource) {
    die("Resource not found.");
}


/*
=================================
Handle Resource Request
=================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    User cannot request their own resource
    */

    if ($resource["owner_id"] == $user_id) {

        $request_error = "You cannot request your own resource.";

    }

    /*
    Resource must be available
    */

    elseif ($resource["status"] !== "available") {

        $request_error = "This resource is not available.";

    }

    else {

        try {

            /*
            ==========================================
            Start Transaction
            ==========================================
            */

            $pdo->beginTransaction();


            /*
            ==========================================
            Check Existing Pending Request
            ==========================================
            */

            $check_stmt = $pdo->prepare("
                SELECT request_id
                FROM requests
                WHERE resource_id = ?
                AND user_id = ?
                AND status = 'pending'
                LIMIT 1
            ");

            $check_stmt->execute([
                $resource_id,
                $user_id
            ]);

            $existing_request = $check_stmt->fetch();


            if ($existing_request) {

                $pdo->rollBack();

                $request_error =
                    "You have already requested this resource.";

            }

            else {

                /*
                ==========================================
                DONATION
                ==========================================
                */

                if ($resource["availability_type"] === "donation") {

                    /*
                    Check Existing Donation Request
                    */

                    $check_donation = $pdo->prepare("
                        SELECT donation_id
                        FROM donation
                        WHERE resource_id = ?
                        AND requester_id = ?
                        AND status IN ('requested', 'approved')
                        LIMIT 1
                    ");

                    $check_donation->execute([
                        $resource_id,
                        $user_id
                    ]);

                    $existing_donation =
                        $check_donation->fetch();


                    if ($existing_donation) {

                        $pdo->rollBack();

                        $request_error =
                            "You have already requested this donation.";

                    }

                    else {

                        /*
                        ==========================================
                        Insert Request
                        ==========================================
                        */

                        $insert_request = $pdo->prepare("
                            INSERT INTO requests
                            (
                                resource_id,
                                user_id,
                                status
                            )
                            VALUES (?, ?, 'pending')
                        ");

                        $insert_request->execute([
                            $resource_id,
                            $user_id
                        ]);


                        /*
                        ==========================================
                        Insert Donation
                        ==========================================
                        */

                        $insert_donation = $pdo->prepare("
                            INSERT INTO donation
                            (
                                resource_id,
                                requester_id,
                                status,
                                requested_at
                            )
                            VALUES (?, ?, 'requested', NOW())
                        ");

                        $insert_donation->execute([
                            $resource_id,
                            $user_id
                        ]);


                        /*
                        ==========================================
                        Update Resource Status
                        ==========================================
                        */

                        $update_resource = $pdo->prepare("
                            UPDATE resource
                            SET status = 'requested'
                            WHERE resource_id = ?
                        ");

                        $update_resource->execute([
                            $resource_id
                        ]);


                        /*
                        ==========================================
                        Commit Transaction
                        ==========================================
                        */

                        $pdo->commit();

                        $request_message =
                            "Your donation request has been submitted successfully!";


                        /*
                        Update Current Resource
                        */

                        $resource["status"] = "requested";
                    }
                }


                /*
                ==========================================
                EXCHANGE
                ==========================================
                */

                else {

                    $insert_request = $pdo->prepare("
                        INSERT INTO requests
                        (
                            resource_id,
                            user_id,
                            status
                        )
                        VALUES (?, ?, 'pending')
                    ");

                    $insert_request->execute([
                        $resource_id,
                        $user_id
                    ]);


                    $pdo->commit();

                    $request_message =
                        "Your request has been submitted successfully!";
                }
            }

        }

        catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $request_error =
                "Something went wrong while submitting your request.";

            // For debugging only:
            // $request_error = $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($resource["name"]); ?> - UniShare
    </title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>

        .details-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #16803c;
            text-decoration: none;
            font-size: 14px;
        }

        .resource-details-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 45% 55%;
        }

        .details-image {
            background: #f3f4f6;
            min-height: 450px;
        }

        .details-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .details-no-image {
            height: 100%;
            min-height: 450px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 70px;
        }

        .details-content {
            padding: 40px;
        }

        .details-category {
            display: inline-block;
            padding: 6px 10px;
            background: #eaf6ee;
            color: #16803c;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .details-content h1 {
            font-size: 30px;
            margin-bottom: 15px;
            color: #111827;
        }

        .details-description {
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .details-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .info-label {
            color: #6b7280;
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            color: #111827;
            font-size: 14px;
        }

        .request-btn {
            width: 100%;
            margin-top: 30px;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #16803c;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .request-btn:hover {
            background: #126b32;
        }

    </style>

</head>

<body>

<main class="main-content">

    <div class="details-container">

        <a href="dashboard.php" class="back-link">
            ← Back to Dashboard
        </a>


        <div class="resource-details-card">


            <!-- Image -->

            <div class="details-image">

                <?php if (!empty($resource["image_path"])): ?>

                    <img
                        src="../<?php echo htmlspecialchars($resource["image_path"]); ?>"
                        alt="<?php echo htmlspecialchars($resource["name"]); ?>"
                    >

                <?php else: ?>

                    <div class="details-no-image">
                        📦
                    </div>

                <?php endif; ?>

            </div>


            <!-- Information -->

            <div class="details-content">

                <span class="details-category">

                    <?php
                    echo htmlspecialchars($resource["category"]);
                    ?>

                </span>


                <h1>

                    <?php
                    echo htmlspecialchars($resource["name"]);
                    ?>

                </h1>


                <p class="details-description">

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $resource["description"]
                        )
                    );
                    ?>

                </p>


                <div class="details-info">

                    <div class="info-row">

                        <span class="info-label">
                            Condition
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $resource["condition"]
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Location
                        </span>

                        <span class="info-value">

                            <?php
                            echo htmlspecialchars(
                                $resource["location"]
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Sharing Type
                        </span>

                        <span class="info-value">

                            <?php
                            echo ucfirst(
                                htmlspecialchars(
                                    $resource["availability_type"]
                                )
                            );
                            ?>

                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Status
                        </span>

                        <span class="info-value">

                            <?php
                            echo ucfirst(
                                htmlspecialchars(
                                    $resource["status"]
                                )
                            );
                            ?>

                        </span>

                    </div>

                </div>


                <?php if (!empty($request_message)): ?>

                    <div class="success-message">
                        <?php echo htmlspecialchars($request_message); ?>
                    </div>

                <?php endif; ?>


                <?php if (!empty($request_error)): ?>

                    <div class="error-message">
                        <?php echo htmlspecialchars($request_error); ?>
                    </div>

                <?php endif; ?>


                <?php if (
                    $resource["owner_id"] != $user_id &&
                    $resource["status"] === "available"
                ): ?>

                    <form method="POST">

                        <button
                            type="submit"
                            class="request-btn"
                        >
                            Request Resource
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>

</body>

</html>