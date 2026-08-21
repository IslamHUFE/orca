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


/*
=================================
Get User Resources
=================================
*/

$sql = "
    SELECT
        r.resource_id,
        r.name,
        r.category,
        r.description,
        r.`condition`,
        r.location,
        r.status,
        r.availability_type,
        r.image_path,
        r.created_at

    FROM resource r

    WHERE r.owner_id = ?

    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$resources = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Resources - UniShare</title>

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

    <style>

/* =========================
   General
========================= */

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family: Arial, sans-serif;

    background: #f8faf9;

    color: #111827;
}


/* =========================
   Dashboard
========================= */

.dashboard-container {
    display: flex;

    min-height: 100vh;
}




/* =========================
   Main Content
========================= */

.main-content {
    margin-left: 240px;

    width: calc(100% - 240px);

    min-height: 100vh;

    padding: 45px;
}


/* =========================
   My Resources
========================= */

.resources-container {
    max-width: 1100px;

    margin: 0 auto;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0 0 8px;

    font-size: 32px;

    color: #111827;
}

.page-header p {
    margin: 0;

    color: #6b7280;

    font-size: 15px;
}


/* =========================
   Resources Grid
========================= */

.resources-grid {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 25px;
}


/* =========================
   Resource Card
========================= */

.resource-card {
    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    overflow: hidden;

    transition: 0.2s ease;
}

.resource-card:hover {
    transform: translateY(-3px);

    box-shadow:
        0 8px 25px
        rgba(0, 0, 0, 0.08);
}


/* =========================
   Image
========================= */

.resource-image {
    width: 100%;

    height: 230px;

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
    font-size: 65px;
}


/* =========================
   Card Content
========================= */

.resource-content {
    padding: 22px;
}

.resource-type {
    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;

    margin-bottom: 10px;
}

.type-donation {
    background: #eaf6ee;

    color: #16803c;
}

.type-exchange {
    background: #e8f1ff;

    color: #2563eb;
}

.resource-content h2 {
    margin: 0 0 10px;

    font-size: 22px;

    color: #111827;
}

.resource-description {
    color: #6b7280;

    font-size: 14px;

    line-height: 1.6;

    margin-bottom: 20px;
}


/* =========================
   Information
========================= */

.resource-info {
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


/* =========================
   Status
========================= */

.status {
    display: inline-block;

    padding: 5px 9px;

    border-radius: 15px;

    font-size: 12px;

    font-weight: 600;
}

.status-available {
    background: #eaf6ee;

    color: #16803c;
}

.status-pending {
    background: #fff7e6;

    color: #b7791f;
}

.status-requested {
    background: #fff7e6;

    color: #b7791f;
}

.status-approved {
    background: #e8f1ff;

    color: #2563eb;
}

.status-completed {
    background: #e6fffa;

    color: #0f766e;
}


/* =========================
   View Button
========================= */

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

    transition: 0.2s ease;
}

.view-btn:hover {
    background: #126b32;
}


/* =========================
   Empty State
========================= */

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

    color: #111827;

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

.add-resource-btn:hover {
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
        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }

    .resources-grid {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 600px) {

    .sidebar {
        position: relative;

        width: 100%;

        height: auto;

        min-height: auto;
    }

    .dashboard-container {
        flex-direction: column;
    }

    .main-content {
        margin-left: 0;

        width: 100%;
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


        <div class="resources-container">


            <!-- =========================
                 Header
            ========================= -->

            <div class="page-header">

                <h1>
                    My Resources
                </h1>

                <p>
                    Manage all resources you have added to UniShare.
                </p>

            </div>



            <?php if (empty($resources)): ?>


                <!-- =========================
                     Empty State
                ========================= -->

                <div class="empty-state">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h2>
                        No Resources Yet
                    </h2>

                    <p>
                        You haven't added any resources yet.
                    </p>

                    <a
                        href="add_resource.php"
                        class="add-resource-btn"
                    >
                        Add a Resource
                    </a>

                </div>


            <?php else: ?>


                <!-- =========================
                     Resources
                ========================= -->

                <div class="resources-grid">


                    <?php foreach ($resources as $resource): ?>


                        <div class="resource-card">


                            <!-- Image -->

                            <div class="resource-image">

                                <?php if (!empty($resource["image_path"])): ?>

                                    <img
                                        src="../<?php echo htmlspecialchars($resource["image_path"]); ?>"
                                        alt="<?php echo htmlspecialchars($resource["name"]); ?>"
                                        onerror="this.style.display='none';"
                                    >

                                <?php else: ?>

                                    <div class="no-image">
                                        📦
                                    </div>

                                <?php endif; ?>

                            </div>



                            <!-- Content -->

                            <div class="resource-content">


                                <?php

                                $resource_type =
                                    strtolower(
                                        $resource["availability_type"]
                                    );

                                ?>


                                <span
                                    class="resource-type type-<?php echo htmlspecialchars($resource_type); ?>"
                                >

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars(
                                            $resource_type
                                        )
                                    );
                                    ?>

                                </span>



                                <h2>

                                    <?php
                                    echo htmlspecialchars(
                                        $resource["name"]
                                    );
                                    ?>

                                </h2>



                                <p class="resource-description">

                                    <?php

                                    $description =
                                        $resource["description"] ?? "";

                                    if (strlen($description) > 100) {

                                        echo htmlspecialchars(
                                            substr(
                                                $description,
                                                0,
                                                100
                                            )
                                        ) . "...";

                                    } else {

                                        echo htmlspecialchars(
                                            $description
                                        );

                                    }

                                    ?>

                                </p>



                                <!-- Information -->

                                <div class="resource-info">


                                    <div class="info-row">

                                        <span class="info-label">
                                            Category
                                        </span>

                                        <span class="info-value">

                                            <?php
                                            echo htmlspecialchars(
                                                $resource["category"]
                                            );
                                            ?>

                                        </span>

                                    </div>



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
                                            Status
                                        </span>

                                        <span class="info-value">


                                            <?php

                                            $status =
                                                $resource["status"]
                                                ?? "available";

                                            $status_class =
                                                "status-" .
                                                strtolower(
                                                    $status
                                                );

                                            ?>


                                            <span
                                                class="status <?php echo $status_class; ?>"
                                            >

                                                <?php
                                                echo ucfirst(
                                                    htmlspecialchars(
                                                        $status
                                                    )
                                                );
                                                ?>

                                            </span>


                                        </span>

                                    </div>


                                </div>



                                <!-- View Resource -->

                                <a
                                    href="resource_details.php?id=<?php echo $resource["resource_id"]; ?>"
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