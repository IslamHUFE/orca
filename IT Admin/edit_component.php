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

$component_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($component_id <= 0) {
    header("Location: components.php");
    exit();
}

$error = "";
$success = "";

/* =========================================================
   GET COMPONENT
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM component
        WHERE component_id = ?
    ");

    $stmt->execute([$component_id]);

    $component = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$component) {
        header("Location: components.php");
        exit();
    }

} catch (PDOException $e) {

    die("Unable to load component.");

}


/* =========================================================
   GET COMPUTERS
========================================================= */

try {

    $stmt = $pdo->query("
        SELECT computer_id, brand, model
        FROM computer
        ORDER BY brand ASC, model ASC
    ");

    $computers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $computers = [];

}


/* =========================================================
   UPDATE COMPONENT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $computer_id = isset($_POST["computer_id"])
        ? (int)$_POST["computer_id"]
        : 0;

    $type = trim($_POST["type"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $serial_number = trim($_POST["serial_number"] ?? "");
    $condition = trim($_POST["condition"] ?? "");
    $status = trim($_POST["status"] ?? "");
    $compatibility_info = trim($_POST["compatibility_info"] ?? "");

    if ($type === "") {

        $error = "Component type is required.";

    } elseif ($status === "") {

        $error = "Component status is required.";

    }


    /* =====================================================
       IMAGE
    ===================================================== */

    $image_name = $component["image"] ?? null;

    if (
        $error === "" &&
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

            $error = "Failed to upload image.";

        } else {

            $allowed_extensions = [
                "jpg",
                "jpeg",
                "png",
                "gif",
                "webp",
                "jfif"
            ];

            $original_name = $_FILES["image"]["name"];

            $extension = strtolower(
                pathinfo($original_name, PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowed_extensions)) {

                $error = "Invalid image format.";

            } else {

                $upload_dir = __DIR__ . "/uploads/components/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_image_name =
                    uniqid("component_", true) . "." . $extension;

                $destination =
                    $upload_dir . $new_image_name;

                if (
                    move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $destination
                    )
                ) {

                    /* Delete old image */

                    if (!empty($component["image"])) {

                        $old_image =
                            $upload_dir . $component["image"];

                        if (file_exists($old_image)) {
                            unlink($old_image);
                        }

                    }

                    $image_name = $new_image_name;

                } else {

                    $error = "Failed to save image.";

                }
            }
        }
    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === "") {

        try {

            $sql = "
                UPDATE component
                SET
                    computer_id = ?,
                    type = ?,
                    model = ?,
                    serial_number = ?,
                    `condition` = ?,
                    status = ?,
                    compatibility_info = ?,
                    image = ?
                WHERE component_id = ?
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $computer_id > 0 ? $computer_id : null,
                $type,
                $model,
                $serial_number,
                $condition,
                $status,
                $compatibility_info,
                $image_name,
                $component_id
            ]);

            header("Location: components.php?success=updated");
            exit();

        } catch (PDOException $e) {

            $error = "Failed to update component.";

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

    <title>Edit Component - UniShare</title>

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

        .page {
            margin-left: 240px;
            padding: 45px;
            min-height: 100vh;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            margin: 0;
            font-size: 30px;
        }

        .back-btn {
            text-decoration: none;
            color: #16803c;
            font-weight: 600;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 30px;
            max-width: 850px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: 600;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #16803c;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .current-image {
            margin-top: 10px;
        }

        .current-image img {
            width: 150px;
            height: 110px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .no-image {
            color: #6b7280;
            font-size: 13px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .actions {
            margin-top: 25px;
            display: flex;
            gap: 12px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 12px 22px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .save {
            background: #16803c;
            color: white;
        }

        .cancel {
            background: #e5e7eb;
            color: #374151;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        @media (max-width: 800px) {

            .page {
                margin-left: 200px;
                padding: 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }

        }

    </style>

</head>

<body>

<?php include "includes/sidebar.php"; ?>


<div class="page">

    <div class="top">

        <div>
            <h1>Edit Component</h1>

            <p>
                Update component information and condition.
            </p>
        </div>

        <a
            href="components.php"
            class="back-btn"
        >
            ← Back to Components
        </a>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <div class="card">

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="form-grid">


                <!-- COMPUTER -->

                <div class="form-group">

                    <label>
                        Computer
                    </label>

                    <select name="computer_id">

                        <option value="0">
                            Not assigned
                        </option>

                        <?php foreach ($computers as $computer): ?>

                            <option
                                value="<?= $computer["computer_id"] ?>"
                                <?= (
                                    (int)$computer["computer_id"]
                                    ===
                                    (int)$component["computer_id"]
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $computer["brand"]
                                ) ?>

                                -

                                <?= htmlspecialchars(
                                    $computer["model"]
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- TYPE -->

                <div class="form-group">

                    <label>
                        Component Type
                    </label>

                    <input
                        type="text"
                        name="type"
                        value="<?= htmlspecialchars(
                            $component["type"] ?? ""
                        ) ?>"
                        placeholder="RAM, SSD, CPU..."
                        required
                    >

                </div>


                <!-- MODEL -->

                <div class="form-group">

                    <label>
                        Model
                    </label>

                    <input
                        type="text"
                        name="model"
                        value="<?= htmlspecialchars(
                            $component["model"] ?? ""
                        ) ?>"
                        placeholder="Component model"
                    >

                </div>


                <!-- SERIAL -->

                <div class="form-group">

                    <label>
                        Serial Number
                    </label>

                    <input
                        type="text"
                        name="serial_number"
                        value="<?= htmlspecialchars(
                            $component["serial_number"] ?? ""
                        ) ?>"
                        placeholder="Serial number"
                    >

                </div>


                <!-- CONDITION -->

                <div class="form-group">

                    <label>
                        Condition
                    </label>

                    <select name="condition">

                        <option
                            value="good"
                            <?= ($component["condition"] ?? "") === "good"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Good
                        </option>

                        <option
                            value="used"
                            <?= ($component["condition"] ?? "") === "used"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Used
                        </option>

                        <option
                            value="damaged"
                            <?= ($component["condition"] ?? "") === "damaged"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Damaged
                        </option>

                    </select>

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option
                            value="good"
                            <?= ($component["status"] ?? "") === "good"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Good
                        </option>

                        <option
                            value="reusable"
                            <?= ($component["status"] ?? "") === "reusable"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Reusable
                        </option>

                        <option
                            value="damaged"
                            <?= ($component["status"] ?? "") === "damaged"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Damaged
                        </option>

                    </select>

                </div>


                <!-- COMPATIBILITY -->

                <div class="form-group full">

                    <label>
                        Compatibility Information
                    </label>

                    <textarea
                        name="compatibility_info"
                        placeholder="Enter compatibility information..."
                    ><?= htmlspecialchars(
                        $component["compatibility_info"] ?? ""
                    ) ?></textarea>

                </div>


                <!-- IMAGE -->

                <div class="form-group full">

                    <label>
                        Component Image
                    </label>

                    <input
                        type="file"
                        name="image"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.jfif,image/jpeg,image/png,image/gif,image/webp"
                    >

                    <?php if (!empty($component["image"])): ?>

                        <div class="current-image">

                            <p>
                                Current Image:
                            </p>

                            <img
                                src="uploads/components/<?= htmlspecialchars(
                                    $component["image"]
                                ) ?>"
                                alt="Component Image"
                            >

                        </div>

                    <?php else: ?>

                        <div class="no-image">
                            No image uploaded.
                        </div>

                    <?php endif; ?>

                </div>


            </div>


            <div class="actions">

                <button
                    type="submit"
                    class="btn save"
                >
                    Save Changes
                </button>

                <a
                    href="components.php"
                    class="btn cancel"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>
```
