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

$error = "";
$success = "";

$material_type = "";
$title = "";
$materials = "";
$steps = "";
$difficulty = "";
$estimated_cost = "";

/*
====================================================
ADD UPCYCLING IDEA
====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $material_type = trim($_POST["material_type"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $materials = trim($_POST["materials"] ?? "");
    $steps = trim($_POST["steps"] ?? "");
    $difficulty = trim($_POST["difficulty"] ?? "");
    $estimated_cost = trim($_POST["estimated_cost"] ?? "");


    /*
    ================================================
    VALIDATION
    ================================================
    */

    if (
        $material_type === "" ||
        $title === "" ||
        $materials === "" ||
        $steps === "" ||
        $difficulty === ""
    ) {

        $error = "Please fill in all required fields.";

    }


    /*
    ================================================
    ESTIMATED COST
    ================================================
    */

    elseif ($estimated_cost !== "" && !is_numeric($estimated_cost)) {

        $error = "Estimated cost must be a valid number.";

    }


    /*
    ================================================
    SAVE IDEA
    ================================================
    */

    else {

        $image_path = null;


        /*
        ============================================
        IMAGE UPLOAD
        ============================================
        */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES["image"]["error"] === UPLOAD_ERR_OK) {

                $allowed_extensions = [
                    "jpg",
                    "jpeg",
                    "png",
                    "webp"
                ];

                $file_name = $_FILES["image"]["name"];

                $file_tmp = $_FILES["image"]["tmp_name"];

                $extension = strtolower(
                    pathinfo($file_name, PATHINFO_EXTENSION)
                );


                if (!in_array($extension, $allowed_extensions)) {

                    $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

                }

                elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {

                    $error = "Image size must be less than 5MB.";

                }

                else {

                    /*
                    ==================================
                    CREATE UPLOAD FOLDER
                    ==================================
                    */

                    $upload_directory = "../uploads/upcycling/";

                    if (!is_dir($upload_directory)) {

                        mkdir(
                            $upload_directory,
                            0777,
                            true
                        );

                    }


                    /*
                    ==================================
                    UNIQUE FILE NAME
                    ==================================
                    */

                    $new_file_name =
                        "upcycling_" .
                        time() .
                        "_" .
                        uniqid() .
                        "." .
                        $extension;


                    $destination =
                        $upload_directory .
                        $new_file_name;


                    if (move_uploaded_file(
                        $file_tmp,
                        $destination
                    )) {

                        $image_path =
                            "uploads/upcycling/" .
                            $new_file_name;

                    }

                    else {

                        $error = "Failed to upload the image.";

                    }

                }

            }

            else {

                $error = "There was an error uploading the image.";

            }

        }


        /*
        ============================================
        INSERT
        ============================================
        */

        if ($error === "") {

            $sql = "
                INSERT INTO upcycling_idea
                (
                    user_id,
                    material_type,
                    title,
                    materials,
                    steps,
                    difficulty,
                    estimated_cost,
                    image_path,
                    resource_name,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'pending'
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $user_id,
                $material_type,
                $title,
                $materials,
                $steps,
                $difficulty,
                $estimated_cost !== ""
                    ? $estimated_cost
                    : null,
                $image_path,
                $title,
            ]);


            $success =
                "Your upcycling idea has been submitted successfully and is waiting for approval.";


            /*
            ========================================
            CLEAR FORM
            ========================================
            */

            $material_type = "";
            $title = "";
            $materials = "";
            $steps = "";
            $difficulty = "";
            $estimated_cost = "";

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

<title>Add Upcycling Idea - UniShare</title>


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

    max-width: 900px;

    margin: auto;

}


/* ====================================================
   HEADER
==================================================== */

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


/* ====================================================
   FORM CARD
==================================================== */

.form-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 30px;

    box-shadow: 0 5px 20px rgba(0,0,0,0.04);

}


/* ====================================================
   INFO
==================================================== */

.info-box {

    background: #ecfdf5;

    border: 1px solid #bbf7d0;

    color: #166534;

    border-radius: 10px;

    padding: 14px 16px;

    margin-bottom: 25px;

    font-size: 13px;

    line-height: 1.6;

}


/* ====================================================
   FORM GROUP
==================================================== */

.form-group {

    margin-bottom: 20px;

}


.form-group label {

    display: block;

    margin-bottom: 7px;

    color: #374151;

    font-size: 13px;

    font-weight: 700;

}


.required {

    color: #dc2626;

}


.form-control {

    width: 100%;

    padding: 12px 14px;

    border: 1px solid #d1d5db;

    border-radius: 9px;

    font-family: Arial, sans-serif;

    font-size: 14px;

    outline: none;

    transition: 0.2s;

}


.form-control:focus {

    border-color: #16a34a;

    box-shadow:
        0 0 0 3px rgba(22,163,74,0.10);

}


textarea.form-control {

    min-height: 120px;

    resize: vertical;

    line-height: 1.6;

}


/* ====================================================
   TWO COLUMNS
==================================================== */

.form-row {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 15px;

}


/* ====================================================
   FILE
==================================================== */

.file-input {

    padding: 10px;

    background: #f9fafb;

}


/* ====================================================
   BUTTONS
==================================================== */

.form-actions {

    display: flex;

    gap: 12px;

    margin-top: 25px;

}


.submit-btn {

    flex: 1;

    border: none;

    background: #16a34a;

    color: white;

    padding: 13px 20px;

    border-radius: 9px;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.2s;

}


.submit-btn:hover {

    background: #15803d;

}


.cancel-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 13px 20px;

    border: 1px solid #d1d5db;

    border-radius: 9px;

    color: #374151;

    background: white;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;

}


.cancel-btn:hover {

    background: #f9fafb;

}


/* ====================================================
   MESSAGES
==================================================== */

.error-message {

    background: #fef2f2;

    border: 1px solid #fecaca;

    color: #b91c1c;

    padding: 13px 15px;

    border-radius: 9px;

    margin-bottom: 20px;

    font-size: 13px;

}


.success-message {

    background: #ecfdf5;

    border: 1px solid #bbf7d0;

    color: #166534;

    padding: 13px 15px;

    border-radius: 9px;

    margin-bottom: 20px;

    font-size: 13px;

}


/* ====================================================
   RESPONSIVE
==================================================== */

@media (max-width: 700px) {

    .page-container {

        margin-left: 0;

        padding: 25px 18px;

    }

    .form-row {

        grid-template-columns: 1fr;

    }

    .form-actions {

        flex-direction: column;

    }

}

</style>

</head>


<body>


<?php include "includes/sidebar.php"; ?>


<div class="page-container">

<div class="page-wrapper">


<!-- ====================================================
     HEADER
==================================================== -->

<div class="page-header">

    <h1>
        ➕ Add Upcycling Idea
    </h1>

    <p>
        Share a creative way to give unused materials a second life.
    </p>

</div>


<div class="form-card">


<!-- ====================================================
     INFO
==================================================== -->

<div class="info-box">

    💡 Your idea will be reviewed before it becomes visible
    to other UniShare students.

</div>


<!-- ====================================================
     MESSAGES
==================================================== -->

<?php if ($error !== ""): ?>

    <div class="error-message">

        <?= htmlspecialchars($error) ?>

    </div>

<?php endif; ?>


<?php if ($success !== ""): ?>

    <div class="success-message">

        <?= htmlspecialchars($success) ?>

    </div>

<?php endif; ?>


<!-- ====================================================
     FORM
==================================================== -->

<form
    method="POST"
    enctype="multipart/form-data"
>


<!-- MATERIAL -->

<div class="form-group">

    <label>

        Material Type
        <span class="required">*</span>

    </label>


    <select
        name="material_type"
        class="form-control"
        required
    >

        <option value="">
            Select Material
        </option>

        <option
            value="Plastic"
            <?= $material_type === "Plastic" ? "selected" : "" ?>
        >
            🧴 Plastic
        </option>

        <option
            value="Cardboard"
            <?= $material_type === "Cardboard" ? "selected" : "" ?>
        >
            📦 Cardboard
        </option>

        <option
            value="Computer Components"
            <?= $material_type === "Computer Components" ? "selected" : "" ?>
        >
            💻 Computer Components
        </option>

        <option
            value="Electronic Devices"
            <?= $material_type === "Electronic Devices" ? "selected" : "" ?>
        >
            📱 Electronic Devices
        </option>

        <option
            value="Electronic Media"
            <?= $material_type === "Electronic Media" ? "selected" : "" ?>
        >
            💿 Electronic Media
        </option>

        <option
            value="Cables"
            <?= $material_type === "Cables" ? "selected" : "" ?>
        >
            🔌 Cables
        </option>

        <option
            value="Wood"
            <?= $material_type === "Wood" ? "selected" : "" ?>
        >
            🪵 Wood
        </option>

        <option
            value="Paper"
            <?= $material_type === "Paper" ? "selected" : "" ?>
        >
            📄 Paper
        </option>

    </select>

</div>


<!-- TITLE -->

<div class="form-group">

    <label>

        Idea Title
        <span class="required">*</span>

    </label>


    <input
        type="text"
        name="title"
        class="form-control"
        placeholder="Example: Plastic Bottle Plant Pot"
        value="<?= htmlspecialchars($title ?? "") ?>"
        required
    >

</div>


<!-- MATERIALS + STEPS -->

<div class="form-group">

    <label>

        Materials Needed
        <span class="required">*</span>

    </label>


    <textarea
        name="materials"
        class="form-control"
        placeholder="Example: plastic bottle, soil, small plant, scissors..."
        required
    ><?= htmlspecialchars($materials ?? "") ?></textarea>

</div>


<div class="form-group">

    <label>

        Steps
        <span class="required">*</span>

    </label>


    <textarea
        name="steps"
        class="form-control"
        placeholder="Describe the steps clearly..."
        required
    ><?= htmlspecialchars($steps ?? "") ?></textarea>

</div>


<!-- DIFFICULTY + COST -->

<div class="form-row">


    <div class="form-group">

        <label>

            Difficulty
            <span class="required">*</span>

        </label>


        <select
            name="difficulty"
            class="form-control"
            required
        >

            <option value="">
                Select Difficulty
            </option>

            <option
                value="Easy"
                <?= ($difficulty ?? "") === "Easy"
                    ? "selected"
                    : "" ?>
            >
                Easy
            </option>

            <option
                value="Medium"
                <?= ($difficulty ?? "") === "Medium"
                    ? "selected"
                    : "" ?>
            >
                Medium
            </option>

            <option
                value="Hard"
                <?= ($difficulty ?? "") === "Hard"
                    ? "selected"
                    : "" ?>
            >
                Hard
            </option>

        </select>

    </div>


    <div class="form-group">

        <label>
            Estimated Cost
        </label>


        <input
            type="number"
            name="estimated_cost"
            class="form-control"
            placeholder="Example: 25"
            min="0"
            step="0.01"
            value="<?= htmlspecialchars($estimated_cost ?? "") ?>"
        >

    </div>

</div>


<!-- IMAGE -->

<div class="form-group">

    <label>
        Idea Image
    </label>


    <input
        type="file"
        name="image"
        class="form-control file-input"
        accept=".jpg,.jpeg,.png,.webp"
    >

</div>


<!-- ACTIONS -->

<div class="form-actions">


    <a
        href="upcycling.php"
        class="cancel-btn"
    >

        Cancel

    </a>


    <button
        type="submit"
        class="submit-btn"
    >

        ♻️ Submit Idea

    </button>


</div>


</form>


</div>

</div>

</div>


</body>

</html>