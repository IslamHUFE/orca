<?php
$current_page = "impact.php";
?>

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
Total Resources
=================================
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM resource
    WHERE owner_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$total_resources = $stmt->fetch()["total"];


/*
=================================
Total Donations
=================================
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM resource
    WHERE owner_id = ?
    AND availability_type = 'donation'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$total_donations = $stmt->fetch()["total"];


/*
=================================
Total Exchanges
=================================
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM resource
    WHERE owner_id = ?
    AND availability_type = 'exchange'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$total_exchanges = $stmt->fetch()["total"];


/*
=================================
Available Resources
=================================
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM resource
    WHERE owner_id = ?
    AND status = 'available'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$available_resources = $stmt->fetch()["total"];


/*
=================================
Completed Exchanges
=================================
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM exchange
    WHERE status = 'completed'
    AND (
        student_a_id = ?
        OR student_b_id = ?
    )
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id,
    $user_id
]);

$completed_exchanges = $stmt->fetch()["total"];


/*
=================================
Completed Donations
=================================
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM donation d

    INNER JOIN resource r
        ON d.resource_id = r.resource_id

    WHERE r.owner_id = ?
    AND d.status = 'completed'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$completed_donations = $stmt->fetch()["total"];


/*
=================================
Total Completed Contributions
=================================
*/

$total_completed =
    $completed_donations +
    $completed_exchanges;

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Impact - UniShare</title>

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
   Impact Container
========================= */

.impact-container {
    max-width: 1100px;

    margin: 0 auto;
}


/* =========================
   Header
========================= */

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
   Impact Hero
========================= */

.impact-hero {
    background: #16803c;

    color: white;

    border-radius: 18px;

    padding: 35px;

    margin-bottom: 30px;

    position: relative;

    overflow: hidden;
}

.impact-hero::after {
    content: "♻";

    position: absolute;

    right: 35px;

    top: 15px;

    font-size: 100px;

    opacity: 0.12;
}

.impact-hero h2 {
    margin: 0 0 10px;

    font-size: 26px;
}

.impact-hero p {
    margin: 0;

    max-width: 650px;

    line-height: 1.7;

    font-size: 15px;

    opacity: 0.95;
}


/* =========================
   Statistics
========================= */

.impact-grid {
    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 20px;

    margin-bottom: 30px;
}

.impact-card {
    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    padding: 25px;

    transition: 0.2s ease;
}

.impact-card:hover {
    transform: translateY(-3px);

    box-shadow:
        0 8px 25px
        rgba(0, 0, 0, 0.07);
}

.impact-icon {
    width: 45px;

    height: 45px;

    background: #eaf6ee;

    color: #16803c;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

    margin-bottom: 18px;
}

.impact-number {
    font-size: 30px;

    font-weight: 700;

    color: #111827;

    margin-bottom: 5px;
}

.impact-label {
    color: #6b7280;

    font-size: 14px;
}


/* =========================
   Contribution Section
========================= */

.contribution-section {
    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    padding: 28px;

    margin-bottom: 30px;
}

.contribution-section h2 {
    margin: 0 0 20px;

    font-size: 21px;
}

.contribution-grid {
    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 20px;
}

.contribution-item {
    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 20px;

    display: flex;

    align-items: center;

    gap: 15px;
}

.contribution-icon {
    font-size: 30px;
}

.contribution-info strong {
    display: block;

    font-size: 24px;

    margin-bottom: 4px;
}

.contribution-info span {
    color: #6b7280;

    font-size: 13px;
}


/* =========================
   Message
========================= */

.impact-message {
    background: #eaf6ee;

    border: 1px solid #cdebd8;

    border-radius: 15px;

    padding: 25px;

    text-align: center;
}

.impact-message .message-icon {
    font-size: 40px;

    margin-bottom: 10px;
}

.impact-message h2 {
    margin: 0 0 8px;

    font-size: 20px;

    color: #16803c;
}

.impact-message p {
    margin: 0;

    color: #4b5563;

    font-size: 14px;

    line-height: 1.6;
}


/* =========================
   Responsive
========================= */

@media (max-width: 900px) {

    .impact-grid {
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

}

@media (max-width: 650px) {

    .impact-grid {
        grid-template-columns: 1fr;
    }

    .contribution-grid {
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


        <div class="impact-container">


            <!-- =========================
                 Header
            ========================= -->

            <div class="page-header">

                <h1>
                    My Impact
                </h1>

                <p>
                    See how your contributions are helping the university community.
                </p>

            </div>



            <!-- =========================
                 Hero
            ========================= -->

            <div class="impact-hero">

                <h2>
                    Make an Impact with UniShare 🌱
                </h2>

                <p>
                    Every resource you share helps reduce waste,
                    support other students, and build a more sustainable
                    university community.
                </p>

            </div>



            <!-- =========================
                 Statistics
            ========================= -->

            <div class="impact-grid">


                <div class="impact-card">

                    <div class="impact-icon">
                        📦
                    </div>

                    <div class="impact-number">
                        <?php echo $total_resources; ?>
                    </div>

                    <div class="impact-label">
                        Total Resources Shared
                    </div>

                </div>



                <div class="impact-card">

                    <div class="impact-icon">
                        🎁
                    </div>

                    <div class="impact-number">
                        <?php echo $total_donations; ?>
                    </div>

                    <div class="impact-label">
                        Donations
                    </div>

                </div>



                <div class="impact-card">

                    <div class="impact-icon">
                        🔄
                    </div>

                    <div class="impact-number">
                        <?php echo $total_exchanges; ?>
                    </div>

                    <div class="impact-label">
                        Exchanges
                    </div>

                </div>



                <div class="impact-card">

                    <div class="impact-icon">
                        🟢
                    </div>

                    <div class="impact-number">
                        <?php echo $available_resources; ?>
                    </div>

                    <div class="impact-label">
                        Currently Available
                    </div>

                </div>



                <div class="impact-card">

                    <div class="impact-icon">
                        ✅
                    </div>

                    <div class="impact-number">
                        <?php echo $total_completed; ?>
                    </div>

                    <div class="impact-label">
                        Completed Contributions
                    </div>

                </div>



                <div class="impact-card">

                    <div class="impact-icon">
                        ♻
                    </div>

                    <div class="impact-number">
                        <?php echo $completed_exchanges; ?>
                    </div>

                    <div class="impact-label">
                        Completed Exchanges
                    </div>

                </div>


            </div>



            <!-- =========================
                 Contribution Details
            ========================= -->

            <div class="contribution-section">

                <h2>
                    Your Contribution
                </h2>


                <div class="contribution-grid">


                    <div class="contribution-item">

                        <div class="contribution-icon">
                            🎁
                        </div>

                        <div class="contribution-info">

                            <strong>
                                <?php echo $completed_donations; ?>
                            </strong>

                            <span>
                                Successfully completed donations
                            </span>

                        </div>

                    </div>



                    <div class="contribution-item">

                        <div class="contribution-icon">
                            🔄
                        </div>

                        <div class="contribution-info">

                            <strong>
                                <?php echo $completed_exchanges; ?>
                            </strong>

                            <span>
                                Successfully completed exchanges
                            </span>

                        </div>

                    </div>


                </div>

            </div>



            <!-- =========================
                 Impact Message
            ========================= -->

            <div class="impact-message">

                <div class="message-icon">
                    🌱
                </div>

                <h2>
                    Keep Sharing, Keep Making a Difference!
                </h2>

                <p>
                    Your shared resources give useful items a second life
                    and help other students access what they need.
                    Every contribution counts.
                </p>

            </div>


        </div>


    </main>


</div>


</body>

</html>