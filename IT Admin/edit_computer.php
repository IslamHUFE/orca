```php
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../db.php";

$error = "";
$success = "";

$upload_dir = __DIR__ . "/uploads/computers/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/*
====================================================
GET COMPUTER ID
====================================================
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: computers.php");
    exit();
}

$computer_id = (int)$_GET["id"];


/*
====================================================
GET COMPUTER
====================================================
*/

$stmt = $pdo->prepare("
    SELECT
        computer_id,
        brand,
        model,
        serial_number,
        `condition`,
        status,
        location,
        description,
        image
    FROM computer
    WHERE computer_id = ?
");

$stmt->execute([$computer_id]);

$computer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$computer) {
    header("Location: computers.php");
    exit();
}


/*
====================================================
UPDATE COMPUTER
====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $brand = trim($_POST["brand"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $serial_number = trim($_POST["serial_number"] ?? "");
    $condition = trim($_POST["condition"] ?? "");
    $status = trim($_POST["status"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $description = trim($_POST["description"] ?? "");

    $old_image = $computer["image"];
    $new_image = $old_image;


    /*
    ================================================
    VALIDATION
    ================================================
    */

    if (
        empty($brand) ||
        empty($model) ||
        empty($status)
    ) {

        $error = "Please fill in Brand, Model and Status.";

    } else {


        /*
        ============================================
        NEW IMAGE
        ============================================
        */

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

            $file_type = $_FILES["image"]["type"];

            $extension = strtolower(
                pathinfo(
                    $_FILES["image"]["name"],
                    PATHINFO_EXTENSION
                )
            );


            if (!in_array($file_type, $allowed_types)) {

                $error =
                    "Only JPG, JPEG, JFIF, PNG and WEBP images are allowed.";

            } elseif (!in_array($extension, $allowed_extensions)) {

                $error =
                    "Invalid image extension.";

            } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {

                $error =
                    "Image size must be less than 5MB.";

            } else {

                $new_image =
                    "computer_" .
                    uniqid() .
                    "." .
                    $extension;

                $target_file =
                    $upload_dir . $new_image;


                if (!move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $target_file
                )) {

                    $error = "Failed to upload new image.";

                    $new_image = $old_image;
                }
            }
        }


        /*
        ============================================
        UPDATE DATABASE
        ============================================
        */

        if (empty($error)) {

            try {

                $sql = "
                    UPDATE computer
                    SET
                        brand = ?,
                        model = ?,
                        serial_number = ?,
                        `condition` = ?,
                        status = ?,
                        location = ?,
                        description = ?,
                        image = ?
                    WHERE computer_id = ?
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $brand,
                    $model,
                    $serial_number,
                    $condition,
                    $status,
                    $location,
                    $description,
                    $new_image,
                    $computer_id
                ]);


                /*
                ====================================
                DELETE OLD IMAGE
                ====================================
                */

                if (
                    $new_image !== $old_image &&
                    !empty($old_image)
                ) {

                    $old_image_path =
                        $upload_dir . $old_image;

                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }


                $success =
                    "Computer updated successfully.";


                /*
                ====================================
                REFRESH DATA
                ====================================
                */

                $stmt = $pdo->prepare("
                    SELECT
                        computer_id,
                        brand,
                        model,
                        serial_number,
                        `condition`,
                        status,
                        location,
                        description,
                        image
                    FROM computer
                    WHERE computer_id = ?
                ");

                $stmt->execute([$computer_id]);

                $computer =
                    $stmt->fetch(PDO::FETCH_ASSOC);


            } catch (PDOException $e) {

                /*
                Delete newly uploaded image
                if database update failed
                */

                if (
                    $new_image !== $old_image &&
                    file_exists($upload_dir . $new_image)
                ) {

                    unlink($upload_dir . $new_image);
                }

                $error =
                    "Failed to update computer.";
            }
        }
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

<title>Edit Computer - UniShare</title>


<style>

/* =========================================
   RESET
========================================= */

* {
    box-sizing: border-box;
}


/* =========================================
   BODY
========================================= */

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #f8faf9;

    color: #111827;
}


/* =========================================
   MAIN
========================================= */

.main-content {

    margin-left: 240px;

    width: calc(100% - 240px);

    min-height: 100vh;

    padding: 45px;
}

.page-wrapper {

    max-width: 900px;

    margin: auto;
}


/* =========================================
   HEADER
========================================= */

.page-header {

    margin-bottom: 25px;
}

.page-header h1 {

    margin: 0 0 8px;

    font-size: 30px;
}

.page-header p {

    margin: 0;

    color: #6b7280;

    font-size: 14px;
}


/* =========================================
   CARD
========================================= */

.form-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 15px;

    padding: 30px;
}


/* =========================================
   MESSAGES
========================================= */

.success {

    background: #dcfce7;

    color: #166534;

    padding: 13px 15px;

    border-radius: 8px;

    margin-bottom: 20px;
}

.error {

    background: #fee2e2;

    color: #b91c1c;

    padding: 13px 15px;

    border-radius: 8px;

    margin-bottom: 20px;
}


/* =========================================
   FORM
========================================= */

.form-row {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 18px;
}

.form-group {

    margin-bottom: 20px;
}

.form-group label {

    display: block;

    margin-bottom: 8px;

    color: #374151;

    font-size: 13px;

    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {

    width: 100%;

    padding: 12px 14px;

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

    min-height: 110px;

    resize: vertical;
}


/* =========================================
   CURRENT IMAGE
========================================= */

.image-section {

    margin-bottom: 25px;
}

.image-section label {

    display: block;

    margin-bottom: 10px;

    font-size: 13px;

    font-weight: 600;

    color: #374151;
}

.current-image {

    width: 220px;

    height: 150px;

    border-radius: 10px;

    overflow: hidden;

    border: 1px solid #e5e7eb;

    background: #f1f5f3;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 12px;
}

.current-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}

.no-image {

    font-size: 50px;

    color: #9ca3af;
}


/* =========================================
   FILE INPUT
========================================= */

.file-info {

    display: block;

    margin-top: 7px;

    color: #9ca3af;

    font-size: 12px;
}


/* =========================================
   BUTTONS
========================================= */

.actions {

    display: flex;

    gap: 12px;

    margin-top: 10px;
}

.update-btn {

    border: none;

    background: #16803c;

    color: white;

    padding: 12px 22px;

    border-radius: 8px;

    font-weight: bold;

    cursor: pointer;
}

.update-btn:hover {

    background: #126b32;
}

.cancel-btn {

    text-decoration: none;

    background: #f3f4f6;

    color: #374151;

    padding: 12px 22px;

    border-radius: 8px;

    font-weight: 600;
}

.cancel-btn:hover {

    background: #e5e7eb;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 800px) {

    .main-content {

        margin-left: 200px;

        width: calc(100% - 200px);

        padding: 25px;
    }

    .form-row {

        grid-template-columns: 1fr;
    }
}


@media (max-width: 600px) {

    .main-content {

        margin-left: 0;

        width: 100%;

        padding: 20px;
    }

}

</style>

</head>


<body>


<div class="dashboard-container">


    <?php include "includes/sidebar.php"; ?>


    <main class="main-content">

        <div class="page-wrapper">


            <!-- HEADER -->

            <div class="page-header">

                <h1>
                    Edit Computer
                </h1>

                <p>
                    Update computer information and image.
                </p>

            </div>


            <!-- MESSAGES -->

            <?php if (!empty($success)): ?>

                <div class="success">

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <?php if (!empty($error)): ?>

                <div class="error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- FORM CARD -->

            <div class="form-card">


                <form
                    method="POST"
                    enctype="multipart/form-data"
                >


                    <!-- BRAND + MODEL -->

                    <div class="form-row">


                        <div class="form-group">

                            <label>
                                Brand *
                            </label>

                            <input
                                type="text"
                                name="brand"
                                value="<?= htmlspecialchars($computer["brand"] ?? "") ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Model *
                            </label>

                            <input
                                type="text"
                                name="model"
                                value="<?= htmlspecialchars($computer["model"] ?? "") ?>"
                                required
                            >

                        </div>


                    </div>


                    <!-- SERIAL + LOCATION -->

                    <div class="form-row">


                        <div class="form-group">

                            <label>
                                Serial Number
                            </label>

                            <input
                                type="text"
                                name="serial_number"
                                value="<?= htmlspecialchars($computer["serial_number"] ?? "") ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Location
                            </label>

                            <input
                                type="text"
                                name="location"
                                value="<?= htmlspecialchars($computer["location"] ?? "") ?>"
                            >

                        </div>


                    </div>


                    <!-- CONDITION + STATUS -->

                    <div class="form-row">


                        <div class="form-group">

                            <label>
                                Condition
                            </label>

                            <select name="condition">

                                <option value="">
                                    Select Condition
                                </option>

                                <option
                                    value="new"
                                    <?= ($computer["condition"] ?? "") === "new" ? "selected" : "" ?>
                                >
                                    New
                                </option>

                                <option
                                    value="good"
                                    <?= ($computer["condition"] ?? "") === "good" ? "selected" : "" ?>
                                >
                                    Good
                                </option>

                                <option
                                    value="fair"
                                    <?= ($computer["condition"] ?? "") === "fair" ? "selected" : "" ?>
                                >
                                    Fair
                                </option>

                                <option
                                    value="poor"
                                    <?= ($computer["condition"] ?? "") === "poor" ? "selected" : "" ?>
                                >
                                    Poor
                                </option>

                                <option
                                    value="damaged"
                                    <?= ($computer["condition"] ?? "") === "damaged" ? "selected" : "" ?>
                                >
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

                                <option
                                    value="working"
                                    <?= ($computer["status"] ?? "") === "working" ? "selected" : "" ?>
                                >
                                    Working
                                </option>

                                <option
                                    value="needs_repair"
                                    <?= ($computer["status"] ?? "") === "needs_repair" ? "selected" : "" ?>
                                >
                                    Needs Repair
                                </option>

                                <option
                                    value="not_repairable"
                                    <?= ($computer["status"] ?? "") === "not_repairable" ? "selected" : "" ?>
                                >
                                    Not Repairable
                                </option>

                            </select>

                        </div>


                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            placeholder="Write a description..."
                        ><?= htmlspecialchars($computer["description"] ?? "") ?></textarea>

                    </div>


                    <!-- CURRENT IMAGE -->

                    <div class="image-section">

                        <label>
                            Current Image
                        </label>


                        <div class="current-image">

                            <?php if (!empty($computer["image"])): ?>

                                <img
                                    src="uploads/computers/<?= htmlspecialchars($computer["image"]) ?>"
                                    alt="Computer"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    🖥️
                                </div>

                            <?php endif; ?>

                        </div>


                        <label>
                            Change Image
                        </label>

                        <input
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.jfif,.png,.webp"
                        >

                        <span class="file-info">
                            JPG, JPEG, JFIF, PNG and WEBP — Maximum 5MB.
                        </span>

                    </div>


                    <!-- BUTTONS -->

                    <div class="actions">

                        <button
                            type="submit"
                            class="update-btn"
                        >
                            Save Changes
                        </button>


                        <a
                            href="computers.php"
                            class="cancel-btn"
                        >
                            Cancel
                        </a>

                    </div>


                </form>


            </div>


        </div>

    </main>


</div>


</body>

</html>
```
