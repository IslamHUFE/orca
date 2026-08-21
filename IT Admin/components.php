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
   UPLOAD FOLDER
==================================================== */

$upload_dir = __DIR__ . "/uploads/components/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/* ====================================================
   DELETE COMPONENT
==================================================== */

if (isset($_GET["delete"])) {

    $component_id = (int)$_GET["delete"];

    if ($component_id > 0) {

        try {

            /* Get image first */

            $stmt = $pdo->prepare("

                SELECT image

                FROM component

                WHERE component_id = ?

            ");

            $stmt->execute([
                $component_id
            ]);

            $component = $stmt->fetch(PDO::FETCH_ASSOC);


            /* Delete image */

            if (
                $component &&
                !empty($component["image"])
            ) {

                $image_path =
                    $upload_dir .
                    $component["image"];

                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }


            /* Delete component */

            $stmt = $pdo->prepare("

                DELETE FROM component

                WHERE component_id = ?

            ");

            $stmt->execute([
                $component_id
            ]);

            $message =
                "Component deleted successfully.";

        } catch (PDOException $e) {

            $error =
                "Unable to delete component.";
        }
    }
}


/* ====================================================
   ADD COMPONENT
==================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $computer_id =
        (int)($_POST["computer_id"] ?? 0);

    $type =
        trim($_POST["type"] ?? "");

    $model =
        trim($_POST["model"] ?? "");

    $serial_number =
        trim($_POST["serial_number"] ?? "");

    $condition =
        trim($_POST["condition"] ?? "");

    $status =
        trim($_POST["status"] ?? "");

    $compatibility_info =
        trim($_POST["compatibility_info"] ?? "");

    $image_name = null;


    /* ====================================================
       VALIDATION
    ==================================================== */

    if (empty($type)) {

        $error =
            "Please enter the component type.";

    } elseif (empty($status)) {

        $error =
            "Please select the component status.";

    } else {


        /* ====================================================
           IMAGE UPLOAD
        ==================================================== */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] === UPLOAD_ERR_OK
        ) {

            $allowed_types = [

                "image/jpeg",
                "image/png",
                "image/webp",
                "image/jpg",
                "image/jfif"

            ];

            $allowed_extensions = [

                "jpg",
                "jpeg",
                "png",
                "webp",
                "jfif"

            ];


            $file_type =
                $_FILES["image"]["type"];


            $extension =
                strtolower(
                    pathinfo(
                        $_FILES["image"]["name"],
                        PATHINFO_EXTENSION
                    )
                );


            if (
                !in_array(
                    $file_type,
                    $allowed_types
                )
            ) {

                $error =
                    "Only JPG, JPEG, JFIF, PNG and WEBP images are allowed.";

            } elseif (
                !in_array(
                    $extension,
                    $allowed_extensions
                )
            ) {

                $error =
                    "Invalid image extension.";

            } elseif (
                $_FILES["image"]["size"]
                > 5 * 1024 * 1024
            ) {

                $error =
                    "Image size must be less than 5MB.";

            } else {

                $image_name =
                    "component_" .
                    uniqid() .
                    "." .
                    $extension;


                $target_file =
                    $upload_dir .
                    $image_name;


                if (
                    !move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $target_file
                    )
                ) {

                    $error =
                        "Failed to upload image.";

                    $image_name = null;
                }
            }
        }


        /* ====================================================
           INSERT COMPONENT
        ==================================================== */

        if (empty($error)) {

            try {

                $sql = "

                    INSERT INTO component
                    (
                        computer_id,
                        type,
                        model,
                        serial_number,
                        `condition`,
                        status,
                        compatibility_info,
                        image
                    )

                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)

                ";


                $stmt =
                    $pdo->prepare($sql);


                $stmt->execute([

                    $computer_id > 0
                        ? $computer_id
                        : null,

                    $type,

                    $model,

                    $serial_number,

                    $condition,

                    $status,

                    $compatibility_info,

                    $image_name

                ]);


                $message =
                    "Component added successfully.";

            } catch (PDOException $e) {

                /* Remove uploaded image */

                if (
                    $image_name !== null &&
                    file_exists(
                        $upload_dir .
                        $image_name
                    )
                ) {

                    unlink(
                        $upload_dir .
                        $image_name
                    );
                }


                $error =
                    "Failed to add component.";
            }
        }
    }
}


/* ====================================================
   SEARCH + FILTER
==================================================== */

$search =
    trim($_GET["search"] ?? "");

$status_filter =
    trim($_GET["status"] ?? "");

$condition_filter =
    trim($_GET["condition"] ?? "");


$components = [];


/* ====================================================
   GET COMPONENTS
==================================================== */

try {

    $sql = "

        SELECT

            c.component_id,
            c.computer_id,
            c.type,
            c.model,
            c.serial_number,
            c.`condition`,
            c.status,
            c.compatibility_info,
            c.image,

            comp.brand,
            comp.model AS computer_model,
            comp.serial_number AS computer_serial

        FROM component c

        LEFT JOIN computer comp
            ON c.computer_id = comp.computer_id

        WHERE 1 = 1

    ";


    $params = [];


    /* ====================================================
       SEARCH
    ==================================================== */

    if (!empty($search)) {

        $sql .= "

            AND (

                c.type LIKE ?

                OR c.model LIKE ?

                OR c.serial_number LIKE ?

                OR comp.brand LIKE ?

                OR comp.model LIKE ?

            )

        ";


        $search_value =
            "%" .
            $search .
            "%";


        $params[] =
            $search_value;

        $params[] =
            $search_value;

        $params[] =
            $search_value;

        $params[] =
            $search_value;

        $params[] =
            $search_value;
    }


    /* ====================================================
       STATUS FILTER
    ==================================================== */

    if (!empty($status_filter)) {

        $sql .= "

            AND c.status = ?

        ";

        $params[] =
            $status_filter;
    }


    /* ====================================================
       CONDITION FILTER
    ==================================================== */

    if (!empty($condition_filter)) {

        $sql .= "

            AND c.`condition` = ?

        ";

        $params[] =
            $condition_filter;
    }


    $sql .= "

        ORDER BY c.component_id DESC

    ";


    $stmt =
        $pdo->prepare($sql);


    $stmt->execute($params);


    $components =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    $error =
        "Unable to load components.";
}


/* ====================================================
   GET COMPUTERS FOR ADD FORM
==================================================== */

$computers = [];

try {

    $stmt = $pdo->query("

        SELECT

            computer_id,
            brand,
            model,
            serial_number

        FROM computer

        ORDER BY brand ASC, model ASC

    ");

    $computers =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $error =
        "Unable to load computers.";
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
    Components - UniShare
</title>


<style>

/* ====================================================
   RESET
==================================================== */

* {
    box-sizing: border-box;
}


/* ====================================================
   BODY
==================================================== */

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


.page-wrapper {

    max-width: 1300px;

    margin: auto;
}


/* ====================================================
   HEADER
==================================================== */

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;
}

.page-header h1 {

    margin: 0 0 7px;

    font-size: 30px;
}

.page-header p {

    margin: 0;

    color: #6b7280;

    font-size: 14px;
}


/* ====================================================
   ADD BUTTON
==================================================== */

.add-btn {

    border: none;

    background: #16803c;

    color: white;

    padding: 12px 18px;

    border-radius: 8px;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;
}

.add-btn:hover {

    background: #126b32;
}


/* ====================================================
   MESSAGES
==================================================== */

.message {

    padding: 13px 16px;

    border-radius: 8px;

    margin-bottom: 20px;

    background: #dcfce7;

    color: #166534;
}

.error {

    padding: 13px 16px;

    border-radius: 8px;

    margin-bottom: 20px;

    background: #fee2e2;

    color: #b91c1c;
}


/* ====================================================
   FILTERS
==================================================== */

.filters {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 20px;

    margin-bottom: 25px;

    display: flex;

    gap: 12px;

    flex-wrap: wrap;
}

.search-input {

    flex: 1;

    min-width: 250px;

    padding: 12px 14px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    outline: none;

    font-size: 14px;
}

.search-input:focus {

    border-color: #16803c;
}

.filter-select {

    padding: 12px 14px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    background: white;

    font-size: 14px;

    outline: none;
}

.filter-btn {

    padding: 12px 18px;

    border: none;

    border-radius: 8px;

    background: #111827;

    color: white;

    cursor: pointer;
}


/* ====================================================
   COMPONENT GRID
==================================================== */

.component-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;
}


/* ====================================================
   COMPONENT CARD
==================================================== */

.component-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    overflow: hidden;

    transition: 0.2s;
}

.component-card:hover {

    transform: translateY(-3px);

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.07);
}


/* ====================================================
   IMAGE
==================================================== */

.component-image {

    width: 100%;

    height: 180px;

    background: #f1f5f3;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;
}

.component-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}

.no-image {

    font-size: 55px;

    color: #9ca3af;
}


/* ====================================================
   CARD CONTENT
==================================================== */

.card-content {

    padding: 20px;
}

.card-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 15px;
}

.card-title {

    margin: 0;

    font-size: 18px;
}

.card-model {

    color: #6b7280;

    font-size: 13px;

    margin-top: 4px;
}


/* ====================================================
   STATUS
==================================================== */

.status {

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    white-space: nowrap;
}

.status-working {

    background: #dcfce7;

    color: #15803d;
}

.status-available {

    background: #dbeafe;

    color: #1d4ed8;
}

.status-faulty {

    background: #fee2e2;

    color: #b91c1c;
}

.status-repair {

    background: #ffedd5;

    color: #c2410c;
}

.status-default {

    background: #f3f4f6;

    color: #374151;
}


/* ====================================================
   DETAILS
==================================================== */

.details {

    display: flex;

    flex-direction: column;

    gap: 9px;

    margin-top: 15px;
}

.detail {

    display: flex;

    gap: 7px;

    font-size: 13px;
}

.detail-label {

    color: #9ca3af;

    min-width: 90px;
}

.detail-value {

    color: #374151;

    font-weight: 500;
}


/* ====================================================
   COMPUTER INFO
==================================================== */

.computer-box {

    margin-top: 15px;

    padding: 12px;

    border-radius: 8px;

    background: #f8faf9;

    border: 1px solid #edf0ee;
}

.computer-box-title {

    font-size: 12px;

    color: #9ca3af;

    margin-bottom: 5px;
}

.computer-box-name {

    font-size: 13px;

    font-weight: 600;

    color: #374151;
}


/* ====================================================
   COMPATIBILITY
==================================================== */

.compatibility {

    margin-top: 15px;

    padding-top: 15px;

    border-top: 1px solid #f0f0f0;

    color: #6b7280;

    font-size: 13px;

    line-height: 1.5;
}


/* ====================================================
   ACTIONS
==================================================== */

.card-actions {

    display: flex;

    gap: 8px;

    margin-top: 18px;
}

.delete-btn {

    flex: 1;

    padding: 9px;

    border-radius: 7px;

    text-align: center;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    background: #fee2e2;

    color: #b91c1c;
}

.delete-btn:hover {

    background: #fecaca;
}


/* ====================================================
   EMPTY
==================================================== */

.empty-state {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 60px 20px;

    text-align: center;

    color: #6b7280;
}

.empty-icon {

    font-size: 55px;

    margin-bottom: 15px;
}


/* ====================================================
   MODAL
==================================================== */

.modal {

    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(0,0,0,0.45);

    align-items: center;

    justify-content: center;

    padding: 20px;

    z-index: 1000;
}

.modal-content {

    background: white;

    width: 100%;

    max-width: 650px;

    max-height: 90vh;

    overflow-y: auto;

    border-radius: 15px;

    padding: 30px;
}

.modal-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}

.modal-header h2 {

    margin: 0;

    font-size: 22px;
}

.close {

    border: none;

    background: none;

    font-size: 25px;

    cursor: pointer;

    color: #6b7280;
}


/* ====================================================
   FORM
==================================================== */

.form-group {

    margin-bottom: 17px;
}

.form-group label {

    display: block;

    margin-bottom: 7px;

    font-size: 13px;

    font-weight: 600;

    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {

    width: 100%;

    padding: 11px 13px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    outline: none;

    font-family: Arial, sans-serif;

    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {

    border-color: #16803c;
}

.form-group textarea {

    min-height: 90px;

    resize: vertical;
}

.form-row {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 15px;
}

.submit-btn {

    width: 100%;

    padding: 13px;

    background: #16803c;

    color: white;

    border: none;

    border-radius: 8px;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    margin-top: 5px;
}

.submit-btn:hover {

    background: #126b32;
}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 1100px) {

    .component-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}

@media (max-width: 800px) {

    .main-content {

        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }

    .form-row {

        grid-template-columns:
            1fr;
    }
}

@media (max-width: 600px) {

    .main-content {

        margin-left: 0;

        width: 100%;

        padding: 20px;
    }

    .component-grid {

        grid-template-columns:
            1fr;
    }

    .page-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }
}

</style>

</head>


<body>


<div class="dashboard-container">


    <!-- ====================================================
         SIDEBAR
    ==================================================== -->

    <?php include "includes/sidebar.php"; ?>


    <!-- ====================================================
         MAIN
    ==================================================== -->

    <main class="main-content">


        <div class="page-wrapper">


            <!-- ====================================================
                 HEADER
            ==================================================== -->

            <div class="page-header">

                <div>

                    <h1>
                        Components 🧩
                    </h1>

                    <p>
                        Manage computer components and their conditions.
                    </p>

                </div>


                <button
                    class="add-btn"
                    onclick="openModal()"
                >
                    + Add Component
                </button>

            </div>


            <!-- ====================================================
                 MESSAGES
            ==================================================== -->

            <?php if (!empty($message)): ?>

                <div class="message">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <?php if (!empty($error)): ?>

                <div class="error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- ====================================================
                 FILTERS
            ==================================================== -->

            <form
                method="GET"
                class="filters"
            >


                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search by type, model, serial..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <select
                    name="status"
                    class="filter-select"
                >

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="Working"
                        <?= $status_filter === "Working"
                            ? "selected"
                            : "" ?>
                    >
                        Working
                    </option>

                    <option
                        value="Available"
                        <?= $status_filter === "Available"
                            ? "selected"
                            : "" ?>
                    >
                        Available
                    </option>

                    <option
                        value="Faulty"
                        <?= $status_filter === "Faulty"
                            ? "selected"
                            : "" ?>
                    >
                        Faulty
                    </option>

                    <option
                        value="Needs Repair"
                        <?= $status_filter === "Needs Repair"
                            ? "selected"
                            : "" ?>
                    >
                        Needs Repair
                    </option>

                </select>


                <select
                    name="condition"
                    class="filter-select"
                >

                    <option value="">
                        All Conditions
                    </option>

                    <option
                        value="New"
                        <?= $condition_filter === "New"
                            ? "selected"
                            : "" ?>
                    >
                        New
                    </option>

                    <option
                        value="Good"
                        <?= $condition_filter === "Good"
                            ? "selected"
                            : "" ?>
                    >
                        Good
                    </option>

                    <option
                        value="Fair"
                        <?= $condition_filter === "Fair"
                            ? "selected"
                            : "" ?>
                    >
                        Fair
                    </option>

                    <option
                        value="Poor"
                        <?= $condition_filter === "Poor"
                            ? "selected"
                            : "" ?>
                    >
                        Poor
                    </option>

                    <option
                        value="Damaged"
                        <?= $condition_filter === "Damaged"
                            ? "selected"
                            : "" ?>
                    >
                        Damaged
                    </option>

                </select>


                <button
                    type="submit"
                    class="filter-btn"
                >
                    Search
                </button>


            </form>


            <!-- ====================================================
                 COMPONENTS
            ==================================================== -->

            <?php if (empty($components)): ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        🧩
                    </div>

                    <h2>
                        No Components Found
                    </h2>

                    <p>
                        Add your first computer component.
                    </p>

                </div>


            <?php else: ?>


                <div class="component-grid">


                    <?php foreach ($components as $component): ?>


                        <div class="component-card">


                            <!-- IMAGE -->

                            <div class="component-image">

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

                                    <div class="no-image">
                                        🧩
                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- CONTENT -->

                            <div class="card-content">


                                <div class="card-top">


                                    <div>

                                        <h2 class="card-title">

                                            <?= htmlspecialchars(
                                                $component["type"]
                                                ?: "Unknown Component"
                                            ) ?>

                                        </h2>


                                        <?php if (
                                            !empty(
                                                $component["model"]
                                            )
                                        ): ?>

                                            <div class="card-model">

                                                <?= htmlspecialchars(
                                                    $component["model"]
                                                ) ?>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <!-- STATUS -->

                                    <?php

                                    $status =
                                        strtolower(
                                            trim(
                                                $component["status"]
                                                ?? ""
                                            )
                                        );

                                    ?>


                                    <?php if (
                                        $status === "faulty"
                                    ): ?>

                                        <span
                                            class="status status-faulty"
                                        >
                                            Faulty
                                        </span>


                                    <?php elseif (
                                        $status === "working"
                                    ): ?>

                                        <span
                                            class="status status-working"
                                        >
                                            Working
                                        </span>


                                    <?php elseif (
                                        $status === "available"
                                    ): ?>

                                        <span
                                            class="status status-available"
                                        >
                                            Available
                                        </span>


                                    <?php elseif (
                                        $status === "needs repair"
                                    ): ?>

                                        <span
                                            class="status status-repair"
                                        >
                                            Needs Repair
                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="status status-default"
                                        >
                                            <?= htmlspecialchars(
                                                $component["status"]
                                                ?: "Unknown"
                                            ) ?>
                                        </span>

                                    <?php endif; ?>


                                </div>


                                <!-- DETAILS -->

                                <div class="details">


                                    <div class="detail">

                                        <span
                                            class="detail-label"
                                        >
                                            Serial:
                                        </span>

                                        <span
                                            class="detail-value"
                                        >

                                            <?= htmlspecialchars(
                                                $component[
                                                    "serial_number"
                                                ]
                                                ?: "N/A"
                                            ) ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span
                                            class="detail-label"
                                        >
                                            Condition:
                                        </span>

                                        <span
                                            class="detail-value"
                                        >

                                            <?= htmlspecialchars(
                                                $component[
                                                    "condition"
                                                ]
                                                ?: "N/A"
                                            ) ?>

                                        </span>

                                    </div>


                                </div>


                                <!-- COMPUTER -->

                                <div class="computer-box">


                                    <div
                                        class="computer-box-title"
                                    >
                                        Connected Computer
                                    </div>


                                    <?php if (
                                        !empty(
                                            $component["computer_id"]
                                        )
                                    ): ?>

                                        <div
                                            class="computer-box-name"
                                        >

                                            <?= htmlspecialchars(
                                                (
                                                    $component["brand"]
                                                    ?: "Unknown"
                                                )
                                                . " "
                                                .
                                                (
                                                    $component[
                                                        "computer_model"
                                                    ]
                                                    ?: ""
                                                )
                                            ) ?>

                                        </div>


                                        <?php if (
                                            !empty(
                                                $component[
                                                    "computer_serial"
                                                ]
                                            )
                                        ): ?>

                                            <small>

                                                Serial:
                                                <?= htmlspecialchars(
                                                    $component[
                                                        "computer_serial"
                                                    ]
                                                ) ?>

                                            </small>

                                        <?php endif; ?>


                                    <?php else: ?>

                                        <div
                                            class="computer-box-name"
                                        >
                                            Not assigned
                                        </div>

                                    <?php endif; ?>


                                </div>


                                <!-- COMPATIBILITY -->

                                <?php if (
                                    !empty(
                                        $component[
                                            "compatibility_info"
                                        ]
                                    )
                                ): ?>

                                    <div class="compatibility">

                                        <?= htmlspecialchars(
                                            $component[
                                                "compatibility_info"
                                            ]
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <!-- ACTION -->

                                <div class="card-actions">


                                    <a
                                        href="components.php?delete=<?= $component["component_id"] ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this component?')"
                                    >
                                        Delete
                                    </a>


                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


<!-- ====================================================
     ADD COMPONENT MODAL
==================================================== -->

<div
    class="modal"
    id="componentModal"
>


    <div class="modal-content">


        <div class="modal-header">

            <h2>
                Add New Component
            </h2>

            <button
                class="close"
                onclick="closeModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- TYPE + MODEL -->

            <div class="form-row">


                <div class="form-group">

                    <label>
                        Component Type *
                    </label>

                    <input
                        type="text"
                        name="type"
                        placeholder="e.g. RAM, CPU, GPU, SSD"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Model
                    </label>

                    <input
                        type="text"
                        name="model"
                        placeholder="e.g. Intel Core i5"
                    >

                </div>


            </div>


            <!-- SERIAL + COMPUTER -->

            <div class="form-row">


                <div class="form-group">

                    <label>
                        Serial Number
                    </label>

                    <input
                        type="text"
                        name="serial_number"
                        placeholder="Serial number"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Computer
                    </label>

                    <select
                        name="computer_id"
                    >

                        <option value="">
                            -- Not Assigned --
                        </option>


                        <?php foreach (
                            $computers
                            as $computer
                        ): ?>

                            <option
                                value="<?= $computer["computer_id"] ?>"
                            >

                                <?= htmlspecialchars(
                                    $computer["brand"]
                                    . " "
                                    . $computer["model"]
                                ) ?>


                                <?php if (
                                    !empty(
                                        $computer[
                                            "serial_number"
                                        ]
                                    )
                                ): ?>

                                    -
                                    <?= htmlspecialchars(
                                        $computer[
                                            "serial_number"
                                        ]
                                    ) ?>

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


            </div>


            <!-- CONDITION + STATUS -->

            <div class="form-row">


                <div class="form-group">

                    <label>
                        Condition
                    </label>

                    <select
                        name="condition"
                    >

                        <option value="">
                            Select Condition
                        </option>

                        <option value="New">
                            New
                        </option>

                        <option value="Good">
                            Good
                        </option>

                        <option value="Fair">
                            Fair
                        </option>

                        <option value="Poor">
                            Poor
                        </option>

                        <option value="Damaged">
                            Damaged
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Status *
                    </label>

                    <select
                        name="status"
                        required
                    >

                        <option value="">
                            Select Status
                        </option>

                        <option value="Working">
                            Working
                        </option>

                        <option value="Available">
                            Available
                        </option>

                        <option value="Faulty">
                            Faulty
                        </option>

                        <option value="Needs Repair">
                            Needs Repair
                        </option>

                    </select>

                </div>


            </div>


            <!-- IMAGE -->

            <div class="form-group">

                <label>
                    Component Image
                </label>

                <input
                    type="file"
                    name="image"
                    accept=".jpg,.jpeg,.jfif,.png,.webp"
                >

                <small>
                    JPG, JPEG, JFIF, PNG and WEBP — Maximum 5MB.
                </small>

            </div>


            <!-- COMPATIBILITY -->

            <div class="form-group">

                <label>
                    Compatibility Information
                </label>

                <textarea
                    name="compatibility_info"
                    placeholder="e.g. Compatible with Dell Latitude 5000 series..."
                ></textarea>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="submit-btn"
            >
                Add Component
            </button>


        </form>


    </div>


</div>


<script>

/* ====================================================
   MODAL
==================================================== */

function openModal() {

    document.getElementById(
        "componentModal"
    ).style.display = "flex";

}


function closeModal() {

    document.getElementById(
        "componentModal"
    ).style.display = "none";

}


window.onclick = function(event) {

    const modal =
        document.getElementById(
            "componentModal"
        );

    if (event.target === modal) {

        closeModal();

    }

}

</script>


</body>

</html>