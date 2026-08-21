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

$full_name = $_SESSION["full_name"];
$user_id = $_SESSION["user_id"];

require_once "../db.php";

// My Resources
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM resource
    WHERE owner_id = ?
");

$stmt->execute([$user_id]);

$my_resources = $stmt->fetchColumn();


// Available Resources
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM resource
    WHERE status = 'available'
");

$available_resources = $stmt->fetchColumn();


// My Requests
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM requests
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$my_requests = $stmt->fetchColumn();


// Completed Exchanges
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM exchange
    WHERE status = 'completed'
    AND (student_a_id = ? OR student_b_id = ?)
");

$stmt->execute([$user_id, $user_id]);

$completed_exchanges = $stmt->fetchColumn();

// Available Resources List
$stmt = $pdo->query("
    SELECT resource_id, name, category, description,
           `condition`, location, availability_type, image_path
    FROM resource
    WHERE status = 'available'
    ORDER BY created_at DESC
    LIMIT 6
");
$resources = $stmt->fetchAll();


?>

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
   Welcome Section
========================= */

.welcome-section {
    background: #16803c;
    color: white;
    padding: 30px;
    border-radius: 14px;
}

.welcome-section h2 {
    font-size: 24px;
    margin-bottom: 10px;
}

.welcome-section p {
    opacity: 0.9;
    font-size: 15px;
}

/* =========================
   Statistics Cards
========================= */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 25px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: #eaf6ee;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 23px;
}

.stat-card p {
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 5px;
}

.stat-card h3 {
    font-size: 25px;
    color: #111827;
}

/* =========================
   Resources Section
========================= */

.resources-section {
    margin-top: 35px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 21px;
    color: #111827;
    margin-bottom: 5px;
}

.section-header p {
    color: #6b7280;
    font-size: 14px;
}

.view-all {
    text-decoration: none;
    color: #16803c;
    font-size: 14px;
    font-weight: 600;
}

.view-all:hover {
    text-decoration: underline;
}


/* Resources Grid */

.resources-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}


/* Resource Card */

.resource-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: 0.2s;
}

.resource-card:hover {
    transform: translateY(-3px);
}


/* Image */

.resource-image {
    width: 100%;
    height: 180px;
    background: #f3f4f6;
    overflow: hidden;
}

.resource-image img {
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


/* Content */

.resource-content {
    padding: 18px;
}

.resource-category {
    display: inline-block;
    font-size: 11px;
    color: #16803c;
    background: #eaf6ee;
    padding: 5px 9px;
    border-radius: 20px;
    margin-bottom: 10px;
}

.resource-content h3 {
    font-size: 18px;
    color: #111827;
    margin-bottom: 8px;
}

.resource-description {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.5;
    min-height: 40px;
}


/* Resource Info */

.resource-info {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 15px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.resource-info span {
    color: #6b7280;
    font-size: 11px;
}


/* Footer */

.resource-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

.availability-type {
    font-size: 12px;
    font-weight: 600;
    color: #16803c;
}

.details-btn {
    text-decoration: none;
    color: white;
    background: #16803c;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
}

.details-btn:hover {
    background: #126b32;
}


/* Empty State */

.empty-resources {
    grid-column: 1 / -1;
    background: #ffffff;
    border: 1px dashed #d1d5db;
    border-radius: 12px;
    padding: 50px 20px;
    text-align: center;
}

.empty-resources div {
    font-size: 45px;
    margin-bottom: 15px;
}

.empty-resources h3 {
    color: #374151;
    margin-bottom: 8px;
}

.empty-resources p {
    color: #6b7280;
    font-size: 14px;
}

</style>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UniShare - Student Dashboard</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

</head>

<body>

    <div class="dashboard-container">

       <?php include "includes/sidebar.php"; ?>
        <!-- Main Content -->

        <main class="main-content">

            <!-- Topbar -->

            <header class="topbar">

                <div>
                    <h1>Dashboard</h1>
                    <p>Manage your resources and requests.</p>
                </div>

                <div class="user-info">

                    <div class="user-avatar">
                        <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                    </div>

                    <div class="user-details">

                        <strong>
                            <?php echo htmlspecialchars($full_name); ?>
                        </strong>

                        <span>
                            Student
                        </span>

                    </div>

                </div>

            </header>


            <!-- Welcome -->

            <section class="welcome-section">

                <h2>
                    Welcome back, <?php echo htmlspecialchars($full_name); ?> 👋
                </h2>

                <p>
                    Share what you no longer need and find resources that can help you.
                </p>

            </section>
<section class="stats-grid">

    <!-- My Resources -->
    <div class="stat-card">

        <div class="stat-icon">
            📦
        </div>

        <div>
            <p>My Resources</p>
            <h3><?php echo $my_resources; ?></h3>
        </div>

    </div>


    <!-- Available Resources -->
    <div class="stat-card">

        <div class="stat-icon">
            🔍
        </div>

        <div>
            <p>Available Resources</p>
            <h3><?php echo $available_resources; ?></h3>
        </div>

    </div>


    <!-- My Requests -->
    <div class="stat-card">

        <div class="stat-icon">
            📋
        </div>

        <div>
            <p>My Requests</p>
            <h3><?php echo $my_requests; ?></h3>
        </div>

    </div>


    <!-- Completed Exchanges -->
    <div class="stat-card">

        <div class="stat-icon">
            🔄
        </div>

        <div>
            <p>Completed Exchanges</p>
            <h3><?php echo $completed_exchanges; ?></h3>
        </div>

    </div>

</section>

<section class="resources-section">

    <div class="section-header">

        <div>
            <h2>Available Resources</h2>
            <p>Find resources shared by students.</p>
        </div>

        <a href="#" class="view-all">
            View All
        </a>

    </div>


    <div class="resources-grid">

        <?php if (count($resources) > 0): ?>

            <?php foreach ($resources as $resource): ?>

                <div class="resource-card">

                    <div class="resource-image">

                        <?php if (!empty($resource["image_path"])): ?>

                            <img
                                src="../<?php echo htmlspecialchars($resource["image_path"]); ?>"
                                alt="<?php echo htmlspecialchars($resource["name"]); ?>"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                📦
                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="resource-content">

                        <span class="resource-category">
                            <?php echo htmlspecialchars($resource["category"]); ?>
                        </span>

                        <h3>
                            <?php echo htmlspecialchars($resource["name"]); ?>
                        </h3>

                        <p class="resource-description">
                            <?php
                            echo htmlspecialchars(
                                mb_strimwidth(
                                    $resource["description"] ?? "",
                                    0,
                                    90,
                                    "..."
                                )
                            );
                            ?>
                        </p>


                        <div class="resource-info">

                            <span>
                                📍
                                <?php echo htmlspecialchars($resource["location"] ?? "Not specified"); ?>
                            </span>

                            <span>
                                <?php echo htmlspecialchars($resource["condition"] ?? "Not specified"); ?>
                            </span>

                        </div>


                        <div class="resource-footer">

                            <span class="availability-type">
                                <?php echo ucfirst(htmlspecialchars($resource["availability_type"])); ?>
                            </span>

                            <a
    href="resource_details.php?id=<?php echo $resource['resource_id']; ?>"
    class="details-btn"
>
    View Details
</a>
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-resources">

                <div>📦</div>

                <h3>No resources available yet</h3>

                <p>
                    Be the first to share a resource with your university community.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>
        </main>

    </div>

</body>

</html>