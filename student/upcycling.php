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

$search = "";
$ideas = [];
$searched = false;
$materialType = "";


/*
====================================================
GET SEARCH VALUES
====================================================
*/

$search = trim($_GET["material"] ?? "");
$selectedType = trim($_GET["type"] ?? "");

if ($search !== "" || $selectedType !== "") {

    $searched = true;

    /*
    ====================================================
    SMART MATERIAL MATCHING
    ====================================================
    */

    $lowerSearch = strtolower($search);


    /*
    ====================================================
    COMPUTER COMPONENTS
    ====================================================
    */

    if (
        strpos($lowerSearch, "computer") !== false ||
        strpos($lowerSearch, "monitor") !== false ||
        strpos($lowerSearch, "screen") !== false ||
        strpos($lowerSearch, "keyboard") !== false ||
        strpos($lowerSearch, "mouse") !== false ||
        strpos($lowerSearch, "component") !== false ||
        strpos($lowerSearch, "case") !== false
    ) {

        $materialType = "Computer Components";

    }


    /*
    ====================================================
    ELECTRONIC DEVICES
    ====================================================
    */

    elseif (
        strpos($lowerSearch, "phone") !== false ||
        strpos($lowerSearch, "smartphone") !== false ||
        strpos($lowerSearch, "mobile") !== false ||
        strpos($lowerSearch, "tablet") !== false
    ) {

        $materialType = "Electronic Devices";

    }


    /*
    ====================================================
    ELECTRONIC MEDIA
    ====================================================
    */

    elseif (
        strpos($lowerSearch, "cd") !== false ||
        strpos($lowerSearch, "disc") !== false ||
        strpos($lowerSearch, "dvd") !== false
    ) {

        $materialType = "Electronic Media";

    }


    /*
    ====================================================
    CABLES
    ====================================================
    */

    elseif (
        strpos($lowerSearch, "cable") !== false ||
        strpos($lowerSearch, "wire") !== false ||
        strpos($lowerSearch, "charger") !== false
    ) {

        $materialType = "Cables";

    }


    /*
    ====================================================
    PLASTIC
    ====================================================
    */

    elseif (
        strpos($lowerSearch, "plastic") !== false ||
        strpos($lowerSearch, "bottle") !== false
    ) {

        $materialType = "Plastic";

    }


    /*
    ====================================================
    CARDBOARD
    ====================================================
    */

    elseif (
        strpos($lowerSearch, "cardboard") !== false ||
        strpos($lowerSearch, "carton") !== false ||
        strpos($lowerSearch, "box") !== false
    ) {

        $materialType = "Cardboard";

    }


    /*
    ====================================================
    WOOD
    ====================================================
    */

    elseif (
        strpos($lowerSearch, "wood") !== false ||
        strpos($lowerSearch, "wooden") !== false ||
        strpos($lowerSearch, "scrap wood") !== false
    ) {

        $materialType = "Wood";

    }


    /*
    ====================================================
    PAPER
    ====================================================
    */

    elseif (
        strpos($lowerSearch, "paper") !== false ||
        strpos($lowerSearch, "newspaper") !== false ||
        strpos($lowerSearch, "magazine") !== false
    ) {

        $materialType = "Paper";

    }


    /*
    ====================================================
    USE DROPDOWN IF SELECTED
    ====================================================
    */

    if ($selectedType !== "") {

        $allowedTypes = [
            "Plastic",
            "Cardboard",
            "Computer Components",
            "Electronic Devices",
            "Electronic Media",
            "Cables",
            "Wood",
            "Paper"
        ];

        if (in_array($selectedType, $allowedTypes, true)) {

            $materialType = $selectedType;

        }

    }


    /*
    ====================================================
    GET IDEAS
    ====================================================
    
    APPROVED:
    Visible to everyone.
    
    PENDING / REJECTED:
    Visible only to the owner.
    
    ====================================================
    */

    if ($materialType !== "") {

        $sql = "
            SELECT
                idea_id,
                material_type,
                title,
                materials,
                steps,
                difficulty,
                estimated_cost,
                image_path,
                resource_name,
                user_id,
                status
            FROM upcycling_idea
            WHERE material_type = ?
              AND (
                    status = 'approved'
                    OR user_id = ?
                  )
            ORDER BY idea_id DESC
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $materialType,
            $user_id
        ]);

        $ideas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

<title>Upcycling - UniShare</title>


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

.page-container {

    margin-left: 240px;

    min-height: 100vh;

    padding: 45px;

}

.page-wrapper {

    max-width: 1200px;

    margin: auto;

}


/* ====================================================
   HEADER
==================================================== */

.page-header {

    margin-bottom: 28px;

}

.page-header h1 {

    margin: 0 0 8px;

    font-size: 30px;

    font-weight: 700;

}

.page-header p {

    margin: 0;

    color: #6b7280;

    font-size: 14px;

}


/* ====================================================
   SEARCH CARD
==================================================== */

.search-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 25px;

    margin-bottom: 30px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,0.03);

}


.search-title {

    margin: 0 0 8px;

    font-size: 20px;

    color: #166534;

}


.search-description {

    margin: 0 0 20px;

    color: #6b7280;

    font-size: 14px;

}


.search-form {

    display: flex;

    gap: 12px;

}


.search-input {

    flex: 1;

    padding: 13px 15px;

    border: 1px solid #d1d5db;

    border-radius: 9px;

    font-size: 14px;

    outline: none;

}


.search-input:focus {

    border-color: #16a34a;

    box-shadow:
        0 0 0 3px
        rgba(22,163,74,0.1);

}


.search-button {

    border: none;

    background: #16a34a;

    color: white;

    padding: 13px 22px;

    border-radius: 9px;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

}


.search-button:hover {

    background: #15803d;

}


/* ====================================================
   MATERIAL SELECT
==================================================== */

.material-select {

    padding: 13px 15px;

    min-width: 220px;

    border: 1px solid #d1d5db;

    border-radius: 9px;

    background: white;

    color: #374151;

    font-size: 14px;

    cursor: pointer;

    outline: none;

}


.material-select:focus {

    border-color: #16a34a;

    box-shadow:
        0 0 0 3px
        rgba(22,163,74,0.1);

}


/* ====================================================
   ADD IDEA
==================================================== */

.add-idea-link {

    margin-top: 18px;

    padding-top: 17px;

    border-top: 1px solid #eeeeee;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    font-size: 13px;

    color: #6b7280;

}


.add-idea-link a {

    color: #15803d;

    text-decoration: none;

    font-weight: 700;

}


.add-idea-link a:hover {

    text-decoration: underline;

}


/* ====================================================
   RESULT MESSAGE
==================================================== */

.result-message {

    margin-bottom: 20px;

}


.result-message h2 {

    margin: 0 0 6px;

    font-size: 21px;

}


.result-message p {

    margin: 0;

    color: #6b7280;

    font-size: 14px;

}


/* ====================================================
   IDEAS GRID
==================================================== */

.ideas-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 24px;

    align-items: stretch;

}


/* ====================================================
   IDEA CARD
==================================================== */

.idea-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    overflow: hidden;

    display: flex;

    flex-direction: column;

    min-width: 0;

    height: 100%;

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;

}


.idea-card:hover {

    transform: translateY(-4px);

    box-shadow:
        0 12px 30px
        rgba(0,0,0,0.08);

}


/* ====================================================
   IMAGE
==================================================== */

.idea-image,
.no-image {

    width: 100%;

    height: 200px;

    flex-shrink: 0;

}


.idea-image {

    display: block;

    object-fit: cover;

}


.no-image {

    display: flex;

    align-items: center;

    justify-content: center;

    background: #ecfdf5;

    font-size: 55px;

}


/* ====================================================
   CONTENT
==================================================== */

.idea-content {

    padding: 22px;

    display: flex;

    flex-direction: column;

    flex: 1;

}


/* ====================================================
   BADGES
==================================================== */

.badges-container {

    display: flex;

    flex-wrap: wrap;

    gap: 7px;

    margin-bottom: 11px;

}


.material-badge {

    display: inline-flex;

    align-items: center;

    background: #dcfce7;

    color: #15803d;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

}


.status-badge {

    display: inline-flex;

    align-items: center;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

}


.status-badge.pending {

    background: #fef3c7;

    color: #92400e;

}


.status-badge.rejected {

    background: #fee2e2;

    color: #991b1b;

}


.status-badge.approved {

    background: #dcfce7;

    color: #166534;

}


/* ====================================================
   TITLE
==================================================== */

.idea-title {

    margin: 0 0 8px;

    font-size: 20px;

    line-height: 1.35;

    color: #111827;

    display: -webkit-box;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    overflow: hidden;

}


/* ====================================================
   RESOURCE NAME
==================================================== */

.resource-name {

    color: #6b7280;

    font-size: 13px;

    margin-bottom: 18px;

    min-height: 18px;

}


.resource-name strong {

    color: #374151;

}


/* ====================================================
   DETAILS
==================================================== */

.idea-details {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 10px;

    margin-bottom: 18px;

}


.detail-box {

    background: #f9fafb;

    border: 1px solid #f0f0f0;

    border-radius: 10px;

    padding: 11px;

}


.detail-label {

    display: block;

    color: #9ca3af;

    font-size: 10px;

    font-weight: 600;

    text-transform: uppercase;

    margin-bottom: 5px;

}


.detail-value {

    color: #374151;

    font-size: 13px;

    font-weight: 700;

}


/* ====================================================
   SECTIONS
==================================================== */

.idea-section {

    margin-top: 15px;

}


.idea-section h4 {

    margin: 0 0 7px;

    font-size: 13px;

    color: #374151;

}


.idea-section p {

    margin: 0;

    color: #6b7280;

    font-size: 13px;

    line-height: 1.6;

}


/* ====================================================
   PREVIEW
==================================================== */

.idea-preview {

    margin-top: 15px;

}


.idea-preview p {

    margin: 0;

    color: #6b7280;

    font-size: 13px;

    line-height: 1.6;

}


/* ====================================================
   BUTTON
==================================================== */

.idea-actions {

    margin-top: auto;

    padding-top: 20px;

}


.details-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 100%;

    min-height: 44px;

    background: #16a34a;

    color: white !important;

    text-decoration: none;

    border-radius: 10px;

    font-size: 14px;

    font-weight: 700;

    transition: 0.2s;

}


.details-btn:hover {

    background: #15803d;

    transform: translateY(-1px);

}


/* ====================================================
   NO RESULTS
==================================================== */

.no-results {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 60px 25px;

    text-align: center;

}


.no-results-icon {

    font-size: 50px;

    margin-bottom: 15px;

}


.no-results h3 {

    margin: 0 0 8px;

    font-size: 20px;

}


.no-results p {

    margin: 0;

    color: #6b7280;

    font-size: 14px;

    line-height: 1.6;

}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 950px) {

    .ideas-grid {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 700px) {

    .page-container {

        margin-left: 0;

        padding: 25px 18px;

    }

    .search-form {

        flex-direction: column;

    }

    .idea-details {

        grid-template-columns: 1fr;

    }

    .add-idea-link {

        flex-direction: column;

        align-items: flex-start;

    }

}

</style>

</head>


<body>


<?php

include "includes/sidebar.php";

?>


<div class="page-container">

<div class="page-wrapper">


<!-- ====================================================
     HEADER
==================================================== -->

<div class="page-header">

    <h1>
        ♻️ Upcycling
    </h1>

    <p>
        Turn unused materials into something useful and creative.
    </p>

</div>


<!-- ====================================================
     SEARCH
==================================================== -->

<div class="search-card">

    <h2 class="search-title">
        What do you have?
    </h2>


    <p class="search-description">

        Enter an item or material you have,
        and UniShare will suggest creative ways to reuse it.

    </p>


    <form
        method="GET"
        class="search-form"
    >

        <input
            type="text"
            name="material"
            class="search-input"
            placeholder="Example: monitor, keyboard, phone..."
            value="<?= htmlspecialchars($search) ?>"
        >


        <select
            name="type"
            class="material-select"
        >

            <option value="">
                Select Material
            </option>

            <option
                value="Plastic"
                <?= $selectedType === "Plastic" ? "selected" : "" ?>
            >
                🧴 Plastic
            </option>

            <option
                value="Cardboard"
                <?= $selectedType === "Cardboard" ? "selected" : "" ?>
            >
                📦 Cardboard
            </option>

            <option
                value="Computer Components"
                <?= $selectedType === "Computer Components" ? "selected" : "" ?>
            >
                💻 Computer Components
            </option>

            <option
                value="Electronic Devices"
                <?= $selectedType === "Electronic Devices" ? "selected" : "" ?>
            >
                📱 Electronic Devices
            </option>

            <option
                value="Electronic Media"
                <?= $selectedType === "Electronic Media" ? "selected" : "" ?>
            >
                💿 Electronic Media
            </option>

            <option
                value="Cables"
                <?= $selectedType === "Cables" ? "selected" : "" ?>
            >
                🔌 Cables
            </option>

            <option
                value="Wood"
                <?= $selectedType === "Wood" ? "selected" : "" ?>
            >
                🪵 Wood
            </option>

            <option
                value="Paper"
                <?= $selectedType === "Paper" ? "selected" : "" ?>
            >
                📄 Paper
            </option>

        </select>


        <button
            type="submit"
            class="search-button"
        >

            ♻️ Find Ideas

        </button>

    </form>


    <div class="add-idea-link">

        <span>
            Have a creative upcycling idea?
        </span>

        <a href="add_upcycling.php">
            ➕ Add Your Idea
        </a>

    </div>

</div>


<!-- ====================================================
     RESULTS
==================================================== -->

<?php if ($searched): ?>


<div class="result-message">

    <h2>

        Ideas for:

        <?= htmlspecialchars(
            $search !== ""
                ? $search
                : $selectedType
        ) ?>

    </h2>


    <?php if (!empty($ideas)): ?>

        <p>

            We found

            <?= count($ideas) ?>

            suitable idea(s).

        </p>

    <?php endif; ?>

</div>


<?php endif; ?>


<?php if (!empty($ideas)): ?>


<div class="ideas-grid">


<?php foreach ($ideas as $idea): ?>


<?php

$ideaStatus = strtolower(
    trim($idea["status"] ?? "pending")
);

$isOwner =
    (int)$idea["user_id"] === (int)$user_id;

?>


<div class="idea-card">


    <!-- IMAGE -->

    <?php if (!empty($idea["image_path"])): ?>

        <img
            src="../<?= htmlspecialchars(
                $idea["image_path"]
            ) ?>"
            alt="<?= htmlspecialchars(
                $idea["title"]
            ) ?>"
            class="idea-image"
        >

    <?php else: ?>

        <div class="no-image">
            ♻️
        </div>

    <?php endif; ?>


    <!-- CONTENT -->

    <div class="idea-content">


        <!-- BADGES -->

        <div class="badges-container">


            <span class="material-badge">

                <?= htmlspecialchars(
                    $idea["material_type"]
                ) ?>

            </span>


            <?php if ($isOwner): ?>


                <?php if ($ideaStatus === "pending"): ?>

                    <span class="status-badge pending">

                        🟡 Pending Review

                    </span>


                <?php elseif ($ideaStatus === "rejected"): ?>

                    <span class="status-badge rejected">

                        🔴 Rejected

                    </span>


                <?php elseif ($ideaStatus === "approved"): ?>

                    <span class="status-badge approved">

                        🟢 Approved

                    </span>

                <?php endif; ?>


            <?php endif; ?>


        </div>


        <!-- TITLE -->

        <h2 class="idea-title">

            <?= htmlspecialchars(
                $idea["title"]
            ) ?>

        </h2>


        <!-- BASED ON -->

        <div class="resource-name">

            Based on:

            <strong>

                <?= htmlspecialchars(
                    $idea["resource_name"]
                    ?? "Available Material"
                ) ?>

            </strong>

        </div>


        <!-- DETAILS -->

        <div class="idea-details">


            <div class="detail-box">

                <span class="detail-label">

                    Difficulty

                </span>

                <span class="detail-value">

                    <?= htmlspecialchars(
                        $idea["difficulty"]
                        ?? "Not specified"
                    ) ?>

                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">

                    Cost

                </span>

                <span class="detail-value">


                    <?php if (

                        $idea["estimated_cost"] !== null
                        &&
                        $idea["estimated_cost"] !== ""

                    ): ?>

                        <?= number_format(
                            (float)$idea["estimated_cost"],
                            0
                        ) ?>

                        EGP

                    <?php else: ?>

                        Free

                    <?php endif; ?>


                </span>

            </div>


        </div>


        <!-- SHORT DESCRIPTION -->

        <div class="idea-preview">

            <p>

                Turn this unused

                <?= htmlspecialchars(
                    strtolower(
                        $idea["material_type"]
                    )
                ) ?>

                into something useful
                and creative.

            </p>

        </div>


        <!-- BUTTON -->

        <div class="idea-actions">

            <a
                href="upcycling_details.php?id=<?= (int)$idea["idea_id"] ?>"
                class="details-btn"
            >

                View Details →

            </a>

        </div>


    </div>

</div>


<?php endforeach; ?>


</div>


<?php elseif ($searched): ?>


<div class="no-results">


    <div class="no-results-icon">

        🔍

    </div>


    <h3>

        No Ideas Found

    </h3>


    <p>

        We couldn't find a suitable upcycling idea
        for

        "<strong>

            <?= htmlspecialchars(
                $search !== ""
                    ? $search
                    : $selectedType
            ) ?>

        </strong>".

        <br>

        Try another item such as a monitor,
        keyboard, phone, CD, cable, plastic bottle,
        cardboard box, wood, or paper.

    </p>


</div>


<?php endif; ?>


</div>

</div>


</body>

</html>