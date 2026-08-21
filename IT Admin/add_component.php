<?php

session_start();

/*
====================================================
CHECK LOGIN
====================================================
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}


/*
====================================================
DATABASE
====================================================
*/

require_once "../db.php";


/*
====================================================
VARIABLES
====================================================
*/

$error = "";
$success = "";

$computer_id = "";
$type = "";
$model = "";
$serial_number = "";
$condition = "";
$status = "";
$compatibility_info = "";

$image_name = null;


/*
====================================================
LOAD COMPUTERS
====================================================
*/

try {

    $sql = "
        SELECT
            computer_id,
            brand,
            model,
            serial_number
        FROM computer
        ORDER BY brand ASC, model ASC
    ";

    $stmt = $pdo->query($sql);

    $computers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $computers = [];

    $error = "Unable to load computers.";

}


/*
====================================================
FORM SUBMISSION
====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    ================================================
    GET FORM DATA
    ================================================
    */

    $computer_id = trim($_POST["computer_id"] ?? "");
    $type = trim($_POST["type"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $serial_number = trim($_POST["serial_number"] ?? "");
    $condition = trim($_POST["condition"] ?? "");
    $status = trim($_POST["status"] ?? "");
    $compatibility_info = trim($_POST["compatibility_info"] ?? "");


    /*
    ================================================
    VALIDATION
    ================================================
    */

    if ($computer_id === "") {

        $error = "Please select a computer.";

    } elseif ($type === "") {

        $error = "Please enter the component type.";

    } elseif ($condition === "") {

        $error = "Please select the component condition.";

    } elseif ($status === "") {

        $error = "Please select the component status.";

    }


    /*
    ================================================
    IMAGE UPLOAD
    ================================================
    */

    if ($error === "" && isset($_FILES["image"])) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

                $error = "Failed to upload image.";

            } else {

                $allowed_extensions = [
                    "jpg",
                    "jpeg",
                    "png",
                    "gif",
                    "jfif",
                    "webp"
                ];

                $file_name = $_FILES["image"]["name"];

                $file_tmp = $_FILES["image"]["tmp_name"];

                $file_size = $_FILES["image"]["size"];

                $file_extension = strtolower(
                    pathinfo($file_name, PATHINFO_EXTENSION)
                );


                /*
                ====================================
                CHECK EXTENSION
                ====================================
                */

                if (!in_array($file_extension, $allowed_extensions)) {

                    $error = "Invalid image format. Allowed: JPG, JPEG, PNG, GIF, JFIF, WEBP.";

                }


                /*
                ====================================
                CHECK SIZE
                ====================================
                */

                if ($error === "" && $file_size > 5 * 1024 * 1024) {

                    $error = "Image size must be less than 5MB.";

                }


                /*
                ====================================
                CHECK REAL IMAGE
                ====================================
                */

                if ($error === "") {

                    $image_info = getimagesize($file_tmp);

                    if ($image_info === false) {

                        $error = "The uploaded file is not a valid image.";

                    }

                }


                /*
                ====================================
                SAVE IMAGE
                ====================================
                */

                if ($error === "") {

                    $upload_directory = __DIR__ . "/uploads/components/";


                    /*
                    Create folder if it doesn't exist
                    */

                    if (!is_dir($upload_directory)) {

                        mkdir(
                            $upload_directory,
                            0777,
                            true
                        );

                    }


                    /*
                    Generate unique image name
                    */

                    $image_name =
                        "component_" .
                        time() .
                        "_" .
                        uniqid() .
                        "." .
                        $file_extension;


                    $uploaded_file =
                        $upload_directory .
                        $image_name;


                    /*
                    Move uploaded image
                    */

                    if (!move_uploaded_file(
                        $file_tmp,
                        $uploaded_file
                    )) {

                        $error = "Failed to save image.";

                        $image_name = null;

                    }

                }

            }

        }

    }


    /*
    ================================================
    INSERT COMPONENT
    ================================================
    */

    if ($error === "") {

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
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";


            $stmt = $pdo->prepare($sql);


            $stmt->execute([
                $computer_id,
                $type,
                $model,
                $serial_number,
                $condition,
                $status,
                $compatibility_info,
                $image_name
            ]);


            /*
            ========================================
            SUCCESS
            ========================================
            */

            header(
                "Location: components.php?success=added"
            );

            exit();


        } catch (PDOException $e) {

            /*
            ========================================
            DELETE IMAGE IF DATABASE FAILED
            ========================================
            */

            if ($image_name !== null) {

                $uploaded_file =
                    __DIR__ .
                    "/uploads/components/" .
                    $image_name;


                if (file_exists($uploaded_file)) {

                    unlink($uploaded_file);

                }

            }


            $error = "Failed to add component.";

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

    <title>Add Component - UniShare</title>


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


        .container {

            max-width: 850px;

            margin: auto;

        }


        .header {

            margin-bottom: 30px;

        }


        .header h1 {

            margin: 0 0 8px;

            font-size: 30px;

        }


        .header p {

            margin: 0;

            color: #6b7280;

        }


        .card {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 30px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.04);

        }


        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .full {

            grid-column: 1 / -1;

        }


        label {

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 8px;

        }


        input,
        select,
        textarea {

            width: 100%;

            padding: 12px 14px;

            border: 1px solid #d1d5db;

            border-radius: 8px;

            font-size: 14px;

            outline: none;

            background: white;

        }


        input:focus,
        select:focus,
        textarea:focus {

            border-color: #16803c;

            box-shadow: 0 0 0 3px #eaf6ee;

        }


        textarea {

            min-height: 120px;

            resize: vertical;

        }


        .file-info {

            margin-top: 6px;

            font-size: 12px;

            color: #6b7280;

        }


        .error {

            background: #fee2e2;

            color: #991b1b;

            border: 1px solid #fecaca;

            padding: 12px 15px;

            border-radius: 8px;

            margin-bottom: 20px;

        }


        .buttons {

            display: flex;

            justify-content: flex-end;

            gap: 12px;

            margin-top: 30px;

        }


        .btn {

            padding: 12px 20px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            border: none;

            cursor: pointer;

        }


        .btn-cancel {

            background: #f3f4f6;

            color: #374151;

        }


        .btn-cancel:hover {

            background: #e5e7eb;

        }


        .btn-save {

            background: #16803c;

            color: white;

        }


        .btn-save:hover {

            background: #126b32;

        }


        @media (max-width: 800px) {

            .page {

                margin-left: 0;

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


<?php

/*
====================================================
SIDEBAR
====================================================
*/

include __DIR__ . "/includes/sidebar.php";

?>


<div class="page">


    <div class="container">


        <div class="header">

            <h1>
                Add Component
            </h1>

            <p>
                Add a reusable or repairable component to the system.
            </p>

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

                    <div class="form-group full">

                        <label>
                            Computer
                        </label>

                        <select
                            name="computer_id"
                            required
                        >

                            <option value="">
                                Select Computer
                            </option>


                            <?php foreach ($computers as $computer): ?>

                                <option
                                    value="<?= htmlspecialchars($computer["computer_id"]) ?>"
                                    <?= $computer_id == $computer["computer_id"] ? "selected" : "" ?>
                                >

                                    <?= htmlspecialchars($computer["brand"]) ?>

                                    -

                                    <?= htmlspecialchars($computer["model"]) ?>

                                    <?php if (!empty($computer["serial_number"])): ?>

                                        (<?= htmlspecialchars($computer["serial_number"]) ?>)

                                    <?php endif; ?>

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
                            placeholder="e.g. RAM, HDD, SSD, CPU"
                            value="<?= htmlspecialchars($type) ?>"
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
                            placeholder="Component model"
                            value="<?= htmlspecialchars($model) ?>"
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
                            placeholder="Serial number"
                            value="<?= htmlspecialchars($serial_number) ?>"
                        >

                    </div>



                    <!-- CONDITION -->

                    <div class="form-group">

                        <label>
                            Condition
                        </label>

                        <select
                            name="condition"
                            required
                        >

                            <option value="">
                                Select Condition
                            </option>

                            <option
                                value="good"
                                <?= $condition === "good" ? "selected" : "" ?>
                            >
                                Good
                            </option>

                            <option
                                value="damaged"
                                <?= $condition === "damaged" ? "selected" : "" ?>
                            >
                                Damaged
                            </option>

                            <option
                                value="used"
                                <?= $condition === "used" ? "selected" : "" ?>
                            >
                                Used
                            </option>

                            <option
                                value="reusable"
                                <?= $condition === "reusable" ? "selected" : "" ?>
                            >
                                Reusable
                            </option>

                        </select>

                    </div>



                    <!-- STATUS -->

                    <div class="form-group">

                        <label>
                            Status
                        </label>

                        <select
                            name="status"
                            required
                        >

                            <option value="">
                                Select Status
                            </option>

                            <option
                                value="good"
                                <?= $status === "good" ? "selected" : "" ?>
                            >
                                Good
                            </option>

                            <option
                                value="reusable"
                                <?= $status === "reusable" ? "selected" : "" ?>
                            >
                                Reusable
                            </option>

                            <option
                                value="damaged"
                                <?= $status === "damaged" ? "selected" : "" ?>
                            >
                                Damaged
                            </option>

                            <option
                                value="in_use"
                                <?= $status === "in_use" ? "selected" : "" ?>
                            >
                                In Use
                            </option>

                            <option
                                value="available"
                                <?= $status === "available" ? "selected" : "" ?>
                            >
                                Available
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
                        ><?= htmlspecialchars($compatibility_info) ?></textarea>

                    </div>



                    <!-- IMAGE -->

                    <div class="form-group full">

                        <label>
                            Component Image
                        </label>

                        <input
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.png,.gif,.jfif,.webp,image/jpeg,image/png,image/gif,image/webp"
                        >

                        <div class="file-info">

                            Supported formats:
                            JPG, JPEG, PNG, GIF, JFIF, WEBP.
                            Maximum size: 5MB.

                        </div>

                    </div>


                </div>



                <!-- BUTTONS -->

                <div class="buttons">

                    <a
                        href="components.php"
                        class="btn btn-cancel"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="btn btn-save"
                    >
                        Add Component
                    </button>

                </div>


            </form>


        </div>


    </div>


</div>


</body>

</html>