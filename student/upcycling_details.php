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


/*
====================================================
GET IDEA ID
====================================================
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: upcycling.php");
    exit();

}

$idea_id = (int) $_GET["id"];


/*
====================================================
GET APPROVED UPCYCLING IDEA
====================================================
*/

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
        resource_name
    FROM upcycling_idea
    WHERE idea_id = ?
      AND status = 'approved'
    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $idea_id
]);

$idea = $stmt->fetch(PDO::FETCH_ASSOC);


/*
====================================================
IDEA NOT FOUND
====================================================
*/

if (!$idea) {
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Idea Not Available - UniShare</title>

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

.page-container {
    margin-left: 240px;
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 40px;
}

.empty-card {

    width: 100%;
    max-width: 520px;

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 20px;

    padding: 45px 35px;

    text-align: center;

    box-shadow: 0 8px 30px rgba(0,0,0,0.05);
}

.empty-icon {

    width: 85px;
    height: 85px;

    margin: 0 auto 22px;

    border-radius: 50%;

    background: #ecfdf5;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 42px;
}

.empty-card h1 {

    margin: 0 0 12px;

    font-size: 25px;

    color: #111827;
}

.empty-card p {

    margin: 0 auto 25px;

    max-width: 420px;

    color: #6b7280;

    font-size: 14px;

    line-height: 1.7;
}

.back-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 12px 22px;

    background: #16a34a;

    color: white;

    text-decoration: none;

    border-radius: 10px;

    font-size: 14px;

    font-weight: 700;

    transition: 0.2s;
}

.back-btn:hover {

    background: #15803d;

    transform: translateY(-1px);
}

@media (max-width: 700px) {

    .page-container {

        margin-left: 0;

        padding: 25px 18px;
    }

    .empty-card {

        padding: 35px 22px;
    }
}

</style>

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="page-container">

    <div class="empty-card">

        <div class="empty-icon">
            ♻️
        </div>

        <h1>
            This Idea Isn't Available Yet
        </h1>

        <p>
            This upcycling idea is currently waiting for
            approval from the UniShare team.
            Please check back later.
        </p>

        <a
            href="upcycling.php"
            class="back-btn"
        >
            ← Back to Upcycling Ideas
        </a>

    </div>

</div>

</body>

</html>

<?php
    exit();
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
    <?= htmlspecialchars($idea["title"]) ?> - UniShare
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

.page-container {

    margin-left: 240px;

    min-height: 100vh;

    padding: 45px;

}

.page-wrapper {

    max-width: 950px;

    margin: auto;

}


/* ====================================================
   BACK BUTTON
==================================================== */

.back-button {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 22px;

    color: #15803d;

    text-decoration: none;

    font-size: 14px;

    font-weight: 700;

}

.back-button:hover {

    text-decoration: underline;

}


/* ====================================================
   CARD
==================================================== */

.details-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    overflow: hidden;

    box-shadow: 0 5px 25px rgba(0,0,0,0.05);

}


/* ====================================================
   IMAGE
==================================================== */

.idea-image {

    width: 100%;

    height: 350px;

    object-fit: cover;

    display: block;

}

.no-image {

    width: 100%;

    height: 350px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #ecfdf5;

    font-size: 90px;

}


/* ====================================================
   CONTENT
==================================================== */

.details-content {

    padding: 32px;

}


/* ====================================================
   BADGE
==================================================== */

.material-badge {

    display: inline-block;

    background: #dcfce7;

    color: #15803d;

    padding: 7px 13px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 700;

    margin-bottom: 12px;

}


/* ====================================================
   TITLE
==================================================== */

.details-title {

    margin: 0 0 10px;

    font-size: 30px;

    line-height: 1.3;

    color: #111827;

}


/* ====================================================
   RESOURCE
==================================================== */

.resource-name {

    color: #6b7280;

    font-size: 14px;

    margin-bottom: 25px;

}

.resource-name strong {

    color: #374151;

}


/* ====================================================
   INFO GRID
==================================================== */

.details-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 14px;

    margin-bottom: 30px;

}


.detail-box {

    background: #f9fafb;

    border: 1px solid #eeeeee;

    border-radius: 11px;

    padding: 15px;

}


.detail-label {

    display: block;

    color: #9ca3af;

    font-size: 10px;

    text-transform: uppercase;

    font-weight: 700;

    margin-bottom: 6px;

}


.detail-value {

    color: #374151;

    font-size: 14px;

    font-weight: 700;

}


/* ====================================================
   SECTIONS
==================================================== */

.detail-section {

    margin-top: 25px;

}


.detail-section h3 {

    margin: 0 0 12px;

    font-size: 17px;

    color: #166534;

}


.detail-section-content {

    background: #f8faf9;

    border-radius: 11px;

    padding: 17px;

    color: #4b5563;

    font-size: 14px;

    line-height: 1.8;

}


/* ====================================================
   FOOTER
==================================================== */

.details-footer {

    margin-top: 30px;

    padding-top: 22px;

    border-top: 1px solid #eeeeee;

}


.back-main-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 12px 20px;

    background: #16a34a;

    color: white;

    text-decoration: none;

    border-radius: 9px;

    font-size: 14px;

    font-weight: 700;

    transition: 0.2s;

}


.back-main-btn:hover {

    background: #15803d;

}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 700px) {

    .page-container {

        margin-left: 0;

        padding: 25px 18px;

    }

    .details-content {

        padding: 22px;

    }

    .details-title {

        font-size: 24px;

    }

    .details-grid {

        grid-template-columns: 1fr;

    }

    .idea-image,
    .no-image {

        height: 230px;

    }

}

</style>

</head>


<body>


<?php include "includes/sidebar.php"; ?>


<div class="page-container">

<div class="page-wrapper">


<!-- ====================================================
     BACK
==================================================== -->

<a
    href="upcycling.php"
    class="back-button"
>

    ← Back to Upcycling

</a>


<!-- ====================================================
     DETAILS CARD
==================================================== -->

<div class="details-card">


<!-- ====================================================
     IMAGE
==================================================== -->

<?php if (!empty($idea["image_path"])): ?>

    <img
        src="../<?= htmlspecialchars($idea["image_path"]) ?>"
        alt="<?= htmlspecialchars($idea["title"]) ?>"
        class="idea-image"
    >

<?php else: ?>

    <div class="no-image">

        ♻️

    </div>

<?php endif; ?>


<!-- ====================================================
     CONTENT
==================================================== -->

<div class="details-content">


<!-- ====================================================
     MATERIAL
==================================================== -->

<span class="material-badge">

    <?= htmlspecialchars(
        $idea["material_type"] ?? "Material"
    ) ?>

</span>


<!-- ====================================================
     TITLE
==================================================== -->

<h1 class="details-title">

    <?= htmlspecialchars(
        $idea["title"]
    ) ?>

</h1>


<!-- ====================================================
     RESOURCE
==================================================== -->

<div class="resource-name">

    Based on:

    <strong>

        <?= htmlspecialchars(
            $idea["resource_name"] ?? "Available Material"
        ) ?>

    </strong>

</div>


<!-- ====================================================
     DIFFICULTY / COST
==================================================== -->

<div class="details-grid">


    <div class="detail-box">

        <span class="detail-label">

            Difficulty

        </span>

        <span class="detail-value">

            <?= htmlspecialchars(
                $idea["difficulty"] ?? "Not specified"
            ) ?>

        </span>

    </div>


    <div class="detail-box">

        <span class="detail-label">

            Estimated Cost

        </span>

        <span class="detail-value">

            <?php if (
                $idea["estimated_cost"] !== null &&
                $idea["estimated_cost"] !== ""
            ): ?>

                <?= number_format(
                    (float)$idea["estimated_cost"],
                    2
                ) ?>

                EGP

            <?php else: ?>

                Free

            <?php endif; ?>

        </span>

    </div>


</div>


<!-- ====================================================
     MATERIALS
==================================================== -->

<div class="detail-section">

    <h3>

        🧰 Materials Needed

    </h3>


    <div class="detail-section-content">

        <?= nl2br(
            htmlspecialchars(
                $idea["materials"] ?? "Not specified"
            )
        ) ?>

    </div>

</div>


<!-- ====================================================
     STEPS
==================================================== -->

<div class="detail-section">

    <h3>

        🛠️ How to Make It

    </h3>


    <div class="detail-section-content">

        <?= nl2br(
            htmlspecialchars(
                $idea["steps"] ?? "Not specified"
            )
        ) ?>

    </div>

</div>


<!-- ====================================================
     FOOTER
==================================================== -->

<div class="details-footer">

    <a
        href="upcycling.php"
        class="back-main-btn"
    >

        ← Back to Upcycling Ideas

    </a>

</div>


</div>

</div>


</div>

</div>


</body>

</html>