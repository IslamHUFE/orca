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

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $category = trim($_POST["category"]);
    $description = trim($_POST["description"]);
    $condition = trim($_POST["condition"]);
    $location = trim($_POST["location"]);
    $availability_type = $_POST["availability_type"];

    /*
    =========================
    Basic Validation
    =========================
    */

    if (
        empty($name) ||
        empty($category) ||
        empty($description) ||
        empty($condition) ||
        empty($location) ||
        empty($availability_type)
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

        $error = "Please upload an image.";

    } else {

        /*
        =========================
        Image Validation
        =========================
        */

        $image = $_FILES["image"];

        $allowed_types = [
    "image/jpeg",
    "image/png",
    "image/webp",
    "image/jfif"
];

        if (!in_array($image["type"], $allowed_types)) {

            $error = "Only JPG, PNG and WEBP images are allowed.";

        } elseif ($image["size"] > 5 * 1024 * 1024) {

            $error = "Image size must be less than 5MB.";

        } else {

            /*
            =========================
            Create Unique File Name
            =========================
            */

            $extension = pathinfo($image["name"], PATHINFO_EXTENSION);

            $new_file_name = uniqid("resource_", true) . "." . $extension;

            $upload_folder = "../uploads/resources/";

            $upload_path = $upload_folder . $new_file_name;

            /*
            =========================
            Move Image
            =========================
            */

            if (move_uploaded_file($image["tmp_name"], $upload_path)) {

                $image_path = "uploads/resources/" . $new_file_name;

                /*
                =========================
                Insert Resource
                =========================
                */

                $sql = "
                    INSERT INTO resource
                    (
                        owner_id,
                        name,
                        category,
                        description,
                        `condition`,
                        location,
                        availability_type,
                        status,
                        image_path
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'available', ?)
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $user_id,
                    $name,
                    $category,
                    $description,
                    $condition,
                    $location,
                    $availability_type,
                    $image_path
                ]);

                $message = "Resource added successfully!";

            } else {

                $error = "Failed to upload the image.";

            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UniShare - Add Resource</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>

        .form-container {
            max-width: 800px;
            background: white;
            padding: 30px;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-btn {
            border: none;
            background: #16803c;
            color: white;
            padding: 12px 22px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .submit-btn:hover {
            background: #126b32;
        }

        .success-message {
            background: #eaf6ee;
            color: #16803c;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #16803c;
            font-size: 14px;
        }

    </style>

</head>

<body>

<div class="main-content">

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

    <h1>Add Resource</h1>

    <p style="margin: 8px 0 25px; color: #6b7280;">
        Share a resource with your university community.
    </p>


    <?php if (!empty($message)): ?>

        <div class="success-message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="form-container">

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">

                <label for="name">
                    Resource Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="e.g. Scientific Calculator"
                    required
                >

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label for="category">
                        Category
                    </label>

                    <input
                        type="text"
                        id="category"
                        name="category"
                        placeholder="e.g. Electronics"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="condition">
                        Condition
                    </label>

                    <select id="condition" name="condition" required>

                        <option value="">
                            Select condition
                        </option>

                        <option value="New">
                            New
                        </option>

                        <option value="Good">
                            Good
                        </option>

                        <option value="Used">
                            Used
                        </option>

                        <option value="Needs Repair">
                            Needs Repair
                        </option>

                    </select>

                </div>

            </div>


            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Describe the resource..."
                    required
                ></textarea>

            </div>


            <div class="form-group">

                <label for="location">
                    Location
                </label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="e.g. Faculty of Science"
                    required
                >

            </div>


            <div class="form-group">

                <label for="availability_type">
                    How do you want to share it?
                </label>

                <select
                    id="availability_type"
                    name="availability_type"
                    required
                >

                    <option value="">
                        Select option
                    </option>

                    <option value="donation">
                        Donation
                    </option>

                    <option value="exchange">
                        Exchange
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="image">
                    Resource Image
                </label>

                <input
    type="file"
    id="image"
    name="image"
    accept=".jpg,.jpeg,.png,.webp,.jfif"
    required
>

                <small style="color:#6b7280;">
                    Maximum size: 5MB. JPG, PNG or WEBP or jfif only.
                </small>

            </div>


            <button
                type="submit"
                class="submit-btn"
            >
                Add Resource
            </button>

        </form>

    </div>

</div>

</body>

</html>