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
$full_name = $_SESSION["full_name"];


/*
=========================
Get My Requests
=========================
*/

$sql = "
    SELECT
        r.request_id,
        r.resource_id,
        r.status AS request_status,
        r.created_at,

        res.name,
        res.category,
        res.description,
        res.`condition`,
        res.location,
        res.availability_type,
        res.image_path

    FROM requests r

    INNER JOIN resource res
        ON r.resource_id = res.resource_id

    WHERE r.user_id = ?

    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([$user_id]);

$requests = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Requests - UniShare</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7f6;
            color: #1f2937;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }


        

        /* =========================
           Main Content
        ========================= */

        .main-content {
            flex: 1;
            padding: 30px 40px;
        }


        /* =========================
           Topbar
        ========================= */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .topbar h1 {
            font-size: 28px;
            color: #111827;
            margin-bottom: 5px;
        }

        .topbar p {
            color: #6b7280;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #16803c;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 18px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .user-details strong {
            font-size: 14px;
        }

        .user-details span {
            color: #6b7280;
            font-size: 12px;
        }


        /* =========================
           Page Header
        ========================= */

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h2 {
            font-size: 22px;
            color: #111827;
            margin-bottom: 6px;
        }

        .page-header p {
            color: #6b7280;
            font-size: 14px;
        }


        /* =========================
           Requests
        ========================= */

        .requests-container {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .request-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px;
            display: flex;
            gap: 20px;
            transition: 0.2s;
        }

        .request-card:hover {
            transform: translateY(-2px);
        }


        /* =========================
           Image
        ========================= */

        .request-image {
            width: 160px;
            height: 140px;
            background: #f3f4f6;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .request-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 45px;
        }


        /* =========================
           Content
        ========================= */

        .request-content {
            flex: 1;
        }

        .request-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .request-category {
            display: inline-block;
            font-size: 11px;
            color: #16803c;
            background: #eaf6ee;
            padding: 5px 9px;
            border-radius: 20px;
            margin-bottom: 8px;
        }

        .request-content h3 {
            font-size: 19px;
            color: #111827;
            margin-bottom: 8px;
        }

        .request-description {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 15px;
        }


        /* =========================
           Status
        ========================= */

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status.pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .status.approved {
            background: #eaf6ee;
            color: #16803c;
        }

        .status.rejected {
            background: #fef2f2;
            color: #dc2626;
        }


        /* =========================
           Info
        ========================= */

        .request-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            border-top: 1px solid #f0f0f0;
            padding-top: 12px;
        }

        .request-info span {
            color: #6b7280;
            font-size: 12px;
        }


        /* =========================
           Footer
        ========================= */

        .request-footer {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .request-date {
            color: #9ca3af;
            font-size: 12px;
        }

        .details-btn {
            text-decoration: none;
            color: white;
            background: #16803c;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .details-btn:hover {
            background: #126b32;
        }


        /* =========================
           Empty State
        ========================= */

        .empty-state {
            background: white;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 70px 20px;
            text-align: center;
        }

        .empty-state .icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .browse-btn {
            display: inline-block;
            background: #16803c;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
        }

        .browse-btn:hover {
            background: #126b32;
        }


        /* =========================
           Responsive
        ========================= */

        @media (max-width: 800px) {

            .sidebar {
                width: 200px;
            }

            .main-content {
                padding: 25px;
            }

            .request-card {
                flex-direction: column;
            }

            .request-image {
                width: 100%;
                height: 200px;
            }

        }

    </style>

</head>


<body>

<div class="dashboard-container">


     <?php include "includes/sidebar.php"; ?>

    <!-- =========================
         Main Content
    ========================= -->

    <main class="main-content">


        <!-- Topbar -->

        <header class="topbar">

            <div>

                <h1>My Requests</h1>

                <p>Track the resources you have requested.</p>

            </div>


            <div class="user-info">

                <div class="user-avatar">

                    <?php
                    echo strtoupper(substr($full_name, 0, 1));
                    ?>

                </div>


                <div class="user-details">

                    <strong>

                        <?php
                        echo htmlspecialchars($full_name);
                        ?>

                    </strong>

                    <span>
                        Student
                    </span>

                </div>

            </div>

        </header>



        <!-- Page Header -->

        <section class="page-header">

            <h2>Your Requests</h2>

            <p>
                Here you can view and track all your resource requests.
            </p>

        </section>



        <!-- Requests -->

        <section class="requests-container">


            <?php if (count($requests) > 0): ?>


                <?php foreach ($requests as $request): ?>


                    <div class="request-card">


                        <!-- Image -->

                        <div class="request-image">

                            <?php if (!empty($request["image_path"])): ?>

                                <img
                                    src="../<?php echo htmlspecialchars($request["image_path"]); ?>"
                                    alt="<?php echo htmlspecialchars($request["name"]); ?>"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    📦
                                </div>

                            <?php endif; ?>

                        </div>



                        <!-- Content -->

                        <div class="request-content">


                            <div class="request-top">


                                <div>

                                    <span class="request-category">

                                        <?php
                                        echo htmlspecialchars(
                                            $request["category"] ?? "Other"
                                        );
                                        ?>

                                    </span>


                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $request["name"]
                                        );
                                        ?>

                                    </h3>

                                </div>


                                <?php

                                $status = strtolower(
                                    $request["request_status"]
                                );

                                ?>

                                <span class="status <?php echo htmlspecialchars($status); ?>">

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars($status)
                                    );
                                    ?>

                                </span>


                            </div>



                            <p class="request-description">

                                <?php

                                echo htmlspecialchars(
                                    mb_strimwidth(
                                        $request["description"] ?? "",
                                        0,
                                        150,
                                        "..."
                                    )
                                );

                                ?>

                            </p>



                            <div class="request-info">

                                <span>

                                    📍

                                    <?php
                                    echo htmlspecialchars(
                                        $request["location"] ?? "Not specified"
                                    );
                                    ?>

                                </span>


                                <span>

                                    Condition:

                                    <?php
                                    echo htmlspecialchars(
                                        $request["condition"] ?? "Not specified"
                                    );
                                    ?>

                                </span>


                                <span>

                                    Type:

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars(
                                            $request["availability_type"]
                                        )
                                    );
                                    ?>

                                </span>

                            </div>



                            <div class="request-footer">

                                <span class="request-date">

                                    Requested on:

                                    <?php
                                    echo date(
                                        "M d, Y",
                                        strtotime($request["created_at"])
                                    );
                                    ?>

                                </span>


                                <a
                                    href="resource_details.php?id=<?php echo $request["resource_id"]; ?>"
                                    class="details-btn"
                                >
                                    View Resource
                                </a>

                            </div>


                        </div>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-state">

                    <div class="icon">
                        📋
                    </div>

                    <h3>
                        You haven't made any requests yet
                    </h3>

                    <p>
                        Browse available resources and request something you need.
                    </p>

                    <a
                        href="dashboard.php"
                        class="browse-btn"
                    >
                        Browse Resources
                    </a>

                </div>


            <?php endif; ?>


        </section>


    </main>

</div>

</body>

</html>