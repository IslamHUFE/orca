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
Search & Filters
=================================
*/

$search = trim($_GET["search"] ?? "");
$category = $_GET["category"] ?? "";
$type = $_GET["type"] ?? "";
$location = trim($_GET["location"] ?? "");


/*
=================================
Get Categories
=================================
*/

$category_sql = "
    SELECT DISTINCT category
    FROM resource
    WHERE category IS NOT NULL
    AND category != ''
    ORDER BY category ASC
";

$category_stmt = $pdo->query($category_sql);
$categories = $category_stmt->fetchAll();


/*
=================================
Get Resources
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
        r.owner_id,
        r.created_at

    FROM resource r

    WHERE r.status = 'available'
";

$params = [];


/*
=================================
Search
=================================
*/

if ($search !== "") {

    $sql .= "
        AND (
            r.name LIKE ?
            OR r.description LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
}


/*
=================================
Category Filter
=================================
*/

if ($category !== "") {

    $sql .= "
        AND r.category = ?
    ";

    $params[] = $category;
}


/*
=================================
Type Filter
=================================
*/

if ($type !== "") {

    $sql .= "
        AND r.availability_type = ?
    ";

    $params[] = $type;
}


/*
=================================
Location Filter
=================================
*/

if ($location !== "") {

    $sql .= "
        AND r.location LIKE ?
    ";

    $params[] = "%" . $location . "%";
}


$sql .= "
    ORDER BY r.created_at DESC
";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

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

    <title>Browse Resources - UniShare</title>

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
   Browse Container
========================= */

.browse-container {
    max-width: 1150px;

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
   Search Box
========================= */

.search-box {
    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    padding: 20px;

    margin-bottom: 30px;
}

.search-form {
    display: grid;

    grid-template-columns: 2fr 1fr 1fr 1fr auto;

    gap: 12px;

    align-items: center;
}

.search-input,
.filter-select {
    width: 100%;

    padding: 12px 14px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    background: white;

    color: #111827;

    font-size: 14px;

    outline: none;
}

.search-input:focus,
.filter-select:focus {
    border-color: #16803c;
}


/* Search Button */

.search-btn {
    padding: 12px 20px;

    background: #16803c;

    color: white;

    border: none;

    border-radius: 8px;

    font-size: 14px;

    font-weight: 600;

    cursor: pointer;

    transition: 0.2s ease;
}

.search-btn:hover {
    background: #126b32;
}


/* Reset */

.reset-btn {
    display: block;

    margin-top: 15px;

    color: #6b7280;

    font-size: 13px;

    text-decoration: none;
}

.reset-btn:hover {
    color: #16803c;
}


/* =========================
   Results Header
========================= */

.results-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;
}

.results-header h2 {
    margin: 0;

    font-size: 20px;

    color: #111827;
}

.results-count {
    color: #6b7280;

    font-size: 14px;
}


/* =========================
   Resource Grid
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

    height: 220px;

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

    margin-bottom: 10px;

    font-weight: 600;
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

.resource-category {
    display: inline-block;

    margin-bottom: 12px;

    color: #6b7280;

    font-size: 13px;
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
    font-size: 60px;

    margin-bottom: 15px;
}

.empty-state h2 {
    margin: 0 0 10px;

    font-size: 22px;
}

.empty-state p {
    margin: 0;

    color: #6b7280;

    font-size: 14px;
}


/* =========================
   Responsive
========================= */

@media (max-width: 1000px) {

    .search-form {
        grid-template-columns: 1fr 1fr;
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

    .resources-grid {
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

    .search-form {
        grid-template-columns: 1fr;
    }

    .results-header {
        flex-direction: column;

        align-items: flex-start;

        gap: 5px;
    }

}

.sidebar {
    width: 240px;
    height: 100vh;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;

    display: flex;
    flex-direction: column;

    padding: 25px 15px;

    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;

    overflow-y: auto;
}
    </style>

</head>


<body>


<div class="dashboard-container">


    <!-- =========================
         Sidebar
    ========================= -->
       <?php include "includes/sidebar.php"; ?>
    <!-- =========================
         Main Content
    ========================= -->

    <main class="main-content">


        <div class="browse-container">


            <!-- =========================
                 Header
            ========================= -->

            <div class="page-header">

                <h1>
                    Browse Resources
                </h1>

                <p>
                    Search and discover resources shared by the university community.
                </p>

            </div>



            <!-- =========================
                 Search & Filters
            ========================= -->

            <div class="search-box">


                <form
                    method="GET"
                    action="browse_resources.php"
                    class="search-form"
                >


                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Search resources..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >


                    <select
                        name="category"
                        class="filter-select"
                    >

                        <option value="">
                            All Categories
                        </option>


                        <?php foreach ($categories as $cat): ?>

                            <option
                                value="<?php echo htmlspecialchars($cat["category"]); ?>"
                                <?php
                                echo
                                    ($category === $cat["category"])
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $cat["category"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <select
                        name="type"
                        class="filter-select"
                    >

                        <option value="">
                            All Types
                        </option>

                        <option
                            value="donation"
                            <?php
                            echo
                                ($type === "donation")
                                ? "selected"
                                : "";
                            ?>
                        >
                            Donation
                        </option>

                        <option
                            value="exchange"
                            <?php
                            echo
                                ($type === "exchange")
                                ? "selected"
                                : "";
                            ?>
                        >
                            Exchange
                        </option>

                    </select>


                    <input
                        type="text"
                        name="location"
                        class="search-input"
                        placeholder="Location"
                        value="<?php echo htmlspecialchars($location); ?>"
                    >


                    <button
                        type="submit"
                        class="search-btn"
                    >
                        Search
                    </button>


                </form>


                <?php if (
                    $search !== "" ||
                    $category !== "" ||
                    $type !== "" ||
                    $location !== ""
                ): ?>

                    <a
                        href="browse_resources.php"
                        class="reset-btn"
                    >
                        Clear all filters
                    </a>

                <?php endif; ?>


            </div>



            <!-- =========================
                 Results Header
            ========================= -->

            <div class="results-header">

                <h2>
                    Available Resources
                </h2>

                <span class="results-count">

                    <?php
                    echo count($resources);
                    ?>

                    resource(s) found

                </span>

            </div>



            <?php if (empty($resources)): ?>


                <!-- =========================
                     No Results
                ========================= -->

                <div class="empty-state">

                    <div class="empty-icon">
                        🔍
                    </div>

                    <h2>
                        No Resources Found
                    </h2>

                    <p>
                        Try changing your search or filters.
                    </p>

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



                                <span class="resource-category">

                                    Category:

                                    <?php
                                    echo htmlspecialchars(
                                        $resource["category"]
                                    );
                                    ?>

                                </span>



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


                                </div>



                                <!-- View -->

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