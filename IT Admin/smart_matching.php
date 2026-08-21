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


/* ====================================================
   VARIABLES
==================================================== */

$selected_component_id = (int)($_GET["component_id"] ?? 0);

$selected_component = null;

$matching_components = [];

$error = "";


/* ====================================================
   GET ALL COMPONENTS
==================================================== */

try {

    $stmt = $pdo->query("

        SELECT
            component_id,
            computer_id,
            type,
            model,
            serial_number,
            `condition`,
            status,
            compatibility_info,
            image

        FROM component

        ORDER BY type ASC, model ASC

    ");

    $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $components = [];

    $error = "Unable to load components.";

}


/* ====================================================
   SMART MATCHING
==================================================== */

if ($selected_component_id > 0) {

    try {

        /* ====================================================
           GET SELECTED COMPONENT
        ==================================================== */

        $stmt = $pdo->prepare("

            SELECT
                component_id,
                computer_id,
                type,
                model,
                serial_number,
                `condition`,
                status,
                compatibility_info,
                image

            FROM component

            WHERE component_id = ?

            LIMIT 1

        ");

        $stmt->execute([
            $selected_component_id
        ]);

        $selected_component =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$selected_component) {

            $error = "Component not found.";

        } else {

            /* ====================================================
               GET POSSIBLE MATCHES
            ==================================================== */

            $selected_type =
                trim($selected_component["type"] ?? "");

            $selected_model =
                trim($selected_component["model"] ?? "");

            $compatibility_info =
                trim(
                    $selected_component["compatibility_info"] ?? ""
                );


            /*
             * We search for components that:
             *
             * 1. Have the same type
             * OR
             * 2. Their compatibility information mentions
             *    the selected component's model/type
             *
             * 3. They are not the selected component itself
             *
             * 4. They are not faulty/damaged
             */

            $stmt = $pdo->prepare("

                SELECT

                    component_id,
                    computer_id,
                    type,
                    model,
                    serial_number,
                    `condition`,
                    status,
                    compatibility_info,
                    image

                FROM component

                WHERE component_id != ?

                AND (

                    type = ?

                    OR compatibility_info LIKE ?

                    OR compatibility_info LIKE ?

                )

                AND (

                    status IS NULL
                    OR status NOT IN ('Faulty', 'Damaged')

                )

                AND (

                    `condition` IS NULL
                    OR `condition` NOT IN ('Damaged', 'Faulty')

                )

                ORDER BY

                    CASE

                        WHEN type = ?
                        THEN 1

                        WHEN model = ?
                        THEN 2

                        ELSE 3

                    END,

                    type ASC,
                    model ASC

            ");


            $stmt->execute([

                $selected_component_id,

                $selected_type,

                "%" . $selected_type . "%",

                "%" . $selected_model . "%",

                $selected_type,

                $selected_model

            ]);


            $matching_components =
                $stmt->fetchAll(PDO::FETCH_ASSOC);

        }

    } catch (PDOException $e) {

        $error =
            "Unable to find matching components.";

    }

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
    Smart Component Matching - UniShare
</title>


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


/* ====================================================
   MAIN CONTENT
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

    margin-bottom: 30px;
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
   ERROR
==================================================== */

.error {

    padding: 14px 18px;

    margin-bottom: 20px;

    border-radius: 10px;

    background: #fee2e2;

    color: #b91c1c;
}


/* ====================================================
   SEARCH CARD
==================================================== */

.search-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 25px;

    margin-bottom: 25px;

    box-shadow:
        0 2px 8px rgba(0, 0, 0, 0.03);
}


.search-card h2 {

    margin: 0 0 20px;

    font-size: 20px;
}


.search-form {

    display: flex;

    gap: 12px;

    align-items: center;
}


.search-form select {

    flex: 1;

    padding: 13px 15px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    background: white;

    font-size: 14px;

    outline: none;
}


.search-form select:focus {

    border-color: #16803c;

    box-shadow:
        0 0 0 3px rgba(22,128,60,0.08);
}


.match-btn {

    padding: 13px 22px;

    border: none;

    border-radius: 8px;

    background: #16803c;

    color: white;

    font-weight: 600;

    cursor: pointer;
}


.match-btn:hover {

    background: #126b32;
}


/* ====================================================
   SELECTED COMPONENT
==================================================== */

.selected-card {

    background: white;

    border: 1px solid #d1fae5;

    border-left: 5px solid #16803c;

    border-radius: 14px;

    padding: 25px;

    margin-bottom: 25px;

    display: flex;

    gap: 22px;

    align-items: center;
}


.component-image {

    width: 120px;

    height: 100px;

    border-radius: 10px;

    background: #f1f5f3;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    flex-shrink: 0;
}


.component-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.no-image {

    font-size: 40px;

    color: #9ca3af;
}


.selected-info {

    flex: 1;
}


.selected-info h2 {

    margin: 0 0 12px;

    font-size: 21px;
}


.selected-info p {

    margin: 6px 0;

    color: #6b7280;

    font-size: 14px;
}


/* ====================================================
   STATUS BADGES
==================================================== */

.badge {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;
}


.badge-good {

    background: #dcfce7;

    color: #166534;
}


.badge-warning {

    background: #fef3c7;

    color: #92400e;
}


.badge-danger {

    background: #fee2e2;

    color: #b91c1c;
}


/* ====================================================
   MATCHING HEADER
==================================================== */

.results-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 18px;
}


.results-header h2 {

    margin: 0;

    font-size: 21px;
}


.result-count {

    padding: 6px 12px;

    border-radius: 20px;

    background: #ecfdf5;

    color: #166534;

    font-size: 13px;

    font-weight: 600;
}


/* ====================================================
   COMPONENT GRID
==================================================== */

.component-grid {

    display: grid;

    grid-template-columns:
        repeat(auto-fill, minmax(280px, 1fr));

    gap: 20px;
}


/* ====================================================
   COMPONENT CARD
==================================================== */

.component-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 20px;

    transition: 0.2s;
}


.component-card:hover {

    transform: translateY(-3px);

    box-shadow:
        0 6px 18px rgba(0, 0, 0, 0.07);

    border-color: #bbf7d0;
}


.card-top {

    display: flex;

    gap: 15px;

    align-items: center;

    margin-bottom: 15px;
}


.card-image {

    width: 75px;

    height: 65px;

    border-radius: 8px;

    background: #f1f5f3;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    flex-shrink: 0;
}


.card-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.card-no-image {

    font-size: 27px;

    color: #9ca3af;
}


.card-title {

    flex: 1;
}


.card-title h3 {

    margin: 0 0 5px;

    font-size: 16px;
}


.card-title span {

    color: #6b7280;

    font-size: 13px;
}


.component-details {

    border-top: 1px solid #f0f0f0;

    padding-top: 14px;
}


.detail {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 9px;

    font-size: 13px;
}


.detail-label {

    color: #6b7280;
}


.detail-value {

    font-weight: 600;

    text-align: right;
}


.compatibility {

    margin-top: 14px;

    padding: 11px;

    background: #f8faf9;

    border-radius: 8px;

    font-size: 12px;

    color: #4b5563;

    line-height: 1.5;
}


.compatibility strong {

    color: #374151;
}


/* ====================================================
   EMPTY STATE
==================================================== */

.empty-state {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 50px 25px;

    text-align: center;
}


.empty-icon {

    font-size: 50px;

    margin-bottom: 15px;
}


.empty-state h3 {

    margin: 0 0 8px;

    font-size: 19px;
}


.empty-state p {

    margin: 0;

    color: #6b7280;

    font-size: 14px;
}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 800px) {

    .main-content {

        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }


    .search-form {

        flex-direction: column;

        align-items: stretch;
    }


    .selected-card {

        flex-direction: column;

        align-items: flex-start;
    }


    .component-grid {

        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>


<div class="dashboard-container">


    <!-- ====================================================
         SIDEBAR
    ==================================================== -->

   <?php

include __DIR__ . "/includes/sidebar.php";

?>


    <!-- ====================================================
         MAIN CONTENT
    ==================================================== -->

    <main class="main-content">


        <!-- HEADER -->

        <div class="page-header">

            <h1>
                Smart Component Matching 🔄
            </h1>

            <p>
                Find compatible components that can be reused
                in university computers.
            </p>

        </div>


        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- ====================================================
             SELECT COMPONENT
        ==================================================== -->

        <div class="search-card">

            <h2>
                Find Compatible Components
            </h2>


            <form
                method="GET"
                class="search-form"
            >

                <select
                    name="component_id"
                    required
                >

                    <option value="">
                        -- Select a component --
                    </option>


                    <?php foreach ($components as $component): ?>

                        <option
                            value="<?= $component["component_id"] ?>"
                            <?= (
                                $selected_component_id
                                ==
                                $component["component_id"]
                            )
                            ? "selected"
                            : ""
                            ?>
                        >

                            <?= htmlspecialchars(
                                $component["type"]
                            ) ?>

                            <?php if (
                                !empty($component["model"])
                            ): ?>

                                -
                                <?= htmlspecialchars(
                                    $component["model"]
                                ) ?>

                            <?php endif; ?>

                            <?php if (
                                !empty($component["serial_number"])
                            ): ?>

                                (
                                <?= htmlspecialchars(
                                    $component["serial_number"]
                                ) ?>
                                )

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="match-btn"
                >
                    🔍 Find Matches
                </button>

            </form>

        </div>


        <?php if ($selected_component): ?>


            <!-- ====================================================
                 SELECTED COMPONENT
            ==================================================== -->

            <div class="selected-card">


                <div class="component-image">

                    <?php if (
                        !empty(
                            $selected_component["image"]
                        )
                    ): ?>

                        <img
                            src="uploads/components/<?= htmlspecialchars(
                                $selected_component["image"]
                            ) ?>"
                            alt="Component"
                            onerror="this.style.display='none';"
                        >

                    <?php else: ?>

                        <div class="no-image">
                            🔧
                        </div>

                    <?php endif; ?>

                </div>


                <div class="selected-info">

                    <h2>

                        <?= htmlspecialchars(
                            $selected_component["type"]
                        ) ?>

                        <?php if (
                            !empty(
                                $selected_component["model"]
                            )
                        ): ?>

                            -
                            <?= htmlspecialchars(
                                $selected_component["model"]
                            ) ?>

                        <?php endif; ?>

                    </h2>


                    <p>

                        <strong>
                            Serial Number:
                        </strong>

                        <?= htmlspecialchars(
                            $selected_component["serial_number"]
                            ?: "N/A"
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Condition:
                        </strong>

                        <?= htmlspecialchars(
                            $selected_component["condition"]
                            ?: "N/A"
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Status:
                        </strong>

                        <?php

                        $selected_status =
                            strtolower(
                                $selected_component["status"]
                                ?? ""
                            );

                        if (
                            $selected_status === "available"
                        ):

                        ?>

                            <span class="badge badge-good">
                                Available
                            </span>

                        <?php elseif (
                            $selected_status === "faulty"
                            ||
                            $selected_status === "damaged"
                        ): ?>

                            <span class="badge badge-danger">
                                <?= htmlspecialchars(
                                    $selected_component["status"]
                                ) ?>
                            </span>

                        <?php else: ?>

                            <span class="badge badge-warning">
                                <?= htmlspecialchars(
                                    $selected_component["status"]
                                    ?: "Unknown"
                                ) ?>
                            </span>

                        <?php endif; ?>

                    </p>


                    <?php if (
                        !empty(
                            $selected_component[
                                "compatibility_info"
                            ]
                        )
                    ): ?>

                        <p>

                            <strong>
                                Compatibility:
                            </strong>

                            <?= htmlspecialchars(
                                $selected_component[
                                    "compatibility_info"
                                ]
                            ) ?>

                        </p>

                    <?php endif; ?>


                </div>


            </div>


            <!-- ====================================================
                 MATCHING RESULTS
            ==================================================== -->

            <div class="results-header">

                <h2>
                    Compatible Components
                </h2>


                <span class="result-count">

                    <?= count(
                        $matching_components
                    ) ?>

                    Match(es)

                </span>

            </div>


            <?php if (
                !empty($matching_components)
            ): ?>


                <div class="component-grid">


                    <?php foreach (
                        $matching_components
                        as $component
                    ): ?>


                        <div class="component-card">


                            <div class="card-top">


                                <div class="card-image">

                                    <?php if (
                                        !empty(
                                            $component["image"]
                                        )
                                    ): ?>

                                        <img
                                            src="uploads/components/<?= htmlspecialchars(
                                                $component["image"]
                                            ) ?>"
                                            alt="Component"
                                            onerror="this.style.display='none';"
                                        >

                                    <?php else: ?>

                                        <div class="card-no-image">
                                            🔧
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div class="card-title">

                                    <h3>

                                        <?= htmlspecialchars(
                                            $component["type"]
                                        ) ?>

                                    </h3>


                                    <span>

                                        <?= htmlspecialchars(
                                            $component["model"]
                                            ?: "Unknown Model"
                                        ) ?>

                                    </span>

                                </div>


                            </div>


                            <div class="component-details">


                                <div class="detail">

                                    <span class="detail-label">
                                        Serial Number
                                    </span>

                                    <span class="detail-value">

                                        <?= htmlspecialchars(
                                            $component[
                                                "serial_number"
                                            ]
                                            ?: "N/A"
                                        ) ?>

                                    </span>

                                </div>


                                <div class="detail">

                                    <span class="detail-label">
                                        Condition
                                    </span>

                                    <span class="detail-value">

                                        <?= htmlspecialchars(
                                            $component[
                                                "condition"
                                            ]
                                            ?: "N/A"
                                        ) ?>

                                    </span>

                                </div>


                                <div class="detail">

                                    <span class="detail-label">
                                        Status
                                    </span>

                                    <span class="detail-value">

                                        <?= htmlspecialchars(
                                            $component[
                                                "status"
                                            ]
                                            ?: "N/A"
                                        ) ?>

                                    </span>

                                </div>


                                <?php if (
                                    !empty(
                                        $component[
                                            "compatibility_info"
                                        ]
                                    )
                                ): ?>

                                    <div class="compatibility">

                                        <strong>
                                            Compatibility:
                                        </strong>

                                        <?= htmlspecialchars(
                                            $component[
                                                "compatibility_info"
                                            ]
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        🔍
                    </div>

                    <h3>
                        No Compatible Components Found
                    </h3>

                    <p>
                        No available component matches
                        the selected component.
                    </p>

                </div>


            <?php endif; ?>


        <?php else: ?>


            <!-- ====================================================
                 INITIAL STATE
            ==================================================== -->

            <div class="empty-state">

                <div class="empty-icon">
                    🔧
                </div>

                <h3>
                    Select a Component
                </h3>

                <p>
                    Choose a component above to find
                    compatible reusable components.
                </p>

            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>