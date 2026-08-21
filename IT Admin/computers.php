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

$message = "";
$error = "";


/*
====================================================
CREATE UPLOAD FOLDER
====================================================
*/

$upload_dir = __DIR__ . "/uploads/computers/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/*
====================================================
DELETE COMPUTER
====================================================
*/

if (isset($_GET["delete"])) {

    $computer_id = (int)$_GET["delete"];

    try {

        // Get image first
        $stmt = $pdo->prepare("
            SELECT image
            FROM computer
            WHERE computer_id = ?
        ");

        $stmt->execute([$computer_id]);

        $computer = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete image
        if ($computer && !empty($computer["image"])) {

            $image_path = $upload_dir . $computer["image"];

            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Delete computer
        $stmt = $pdo->prepare("
            DELETE FROM computer
            WHERE computer_id = ?
        ");

        $stmt->execute([$computer_id]);

        $message = "Computer deleted successfully.";

    } catch (PDOException $e) {

        $error = "Unable to delete computer.";
    }
}


/*
====================================================
ADD COMPUTER
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

    $image_name = null;


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
        IMAGE UPLOAD
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

            $file_type = $_FILES["image"]["type"];

            if (!in_array($file_type, $allowed_types)) {

                $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

            } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {

                $error = "Image size must be less than 5MB.";

            } else {

                $extension = strtolower(
                    pathinfo(
                        $_FILES["image"]["name"],
                        PATHINFO_EXTENSION
                    )
                );

                $image_name =
                    "computer_" .
                    uniqid() .
                    "." .
                    $extension;

                $target_file =
                    $upload_dir . $image_name;

                if (!move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $target_file
                )) {

                    $error = "Failed to upload image.";
                    $image_name = null;
                }
            }
        }


        /*
        ============================================
        INSERT COMPUTER
        ============================================
        */

        if (empty($error)) {

            try {

                $sql = "
                    INSERT INTO computer
                    (
                        brand,
                        model,
                        serial_number,
                        `condition`,
                        status,
                        location,
                        description,
                        image
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
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
                    $image_name
                ]);

                $message = "Computer added successfully.";

            } catch (PDOException $e) {

                // Remove uploaded image if database insert failed
                if (
                    $image_name !== null &&
                    file_exists($upload_dir . $image_name)
                ) {

                    unlink($upload_dir . $image_name);
                }

                $error = "Failed to add computer.";
            }
        }
    }
}


/*
====================================================
GET COMPUTERS
====================================================
*/

$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");

$computers = [];

try {

    $sql = "
        SELECT
            computer_id,
            brand,
            model,
            serial_number,
            `condition`,
            status,
            location,
            description,
            created_at,
            image
        FROM computer
        WHERE 1=1
    ";

    $params = [];


    /*
    SEARCH
    */

    if (!empty($search)) {

        $sql .= "
            AND (
                brand LIKE ?
                OR model LIKE ?
                OR serial_number LIKE ?
                OR location LIKE ?
            )
        ";

        $search_value = "%" . $search . "%";

        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
    }


    /*
    STATUS FILTER
    */

    if (!empty($status_filter)) {

        $sql .= "
            AND status = ?
        ";

        $params[] = $status_filter;
    }


    $sql .= "
        ORDER BY computer_id DESC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $computers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $error = "Unable to load computers.";
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

<title>Computers - UniShare</title>


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
   MAIN CONTENT
========================================= */

.main-content {

    margin-left: 240px;

    width: calc(100% - 240px);

    min-height: 100vh;

    padding: 40px;
}


.page-wrapper {

    max-width: 1250px;

    margin: auto;
}


/* =========================================
   HEADER
========================================= */

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


/* =========================================
   ADD BUTTON
========================================= */

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


/* =========================================
   MESSAGE
========================================= */

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


/* =========================================
   SEARCH AREA
========================================= */

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


/* =========================================
   COMPUTER GRID
========================================= */

.computer-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;
}


/* =========================================
   COMPUTER CARD
========================================= */

.computer-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    overflow: hidden;

    transition: 0.2s;
}

.computer-card:hover {

    transform: translateY(-3px);

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.07);
}


/* =========================================
   IMAGE
========================================= */

.computer-image {

    width: 100%;

    height: 190px;

    background: #f1f5f3;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;
}

.computer-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}

.no-image {

    font-size: 55px;

    color: #9ca3af;
}


/* =========================================
   CARD CONTENT
========================================= */

.card-content {

    padding: 20px;
}

.card-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 12px;
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


/* =========================================
   STATUS
========================================= */

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

.status-repair {

    background: #ffedd5;

    color: #c2410c;
}

.status-broken {

    background: #fee2e2;

    color: #b91c1c;
}


/* =========================================
   DETAILS
========================================= */

.details {

    display: flex;

    flex-direction: column;

    gap: 8px;

    margin-top: 15px;
}

.detail {

    display: flex;

    gap: 7px;

    font-size: 13px;
}

.detail-label {

    color: #9ca3af;

    min-width: 85px;
}

.detail-value {

    color: #374151;

    font-weight: 500;
}


/* =========================================
   DESCRIPTION
========================================= */

.description {

    margin-top: 15px;

    padding-top: 15px;

    border-top: 1px solid #f0f0f0;

    color: #6b7280;

    font-size: 13px;

    line-height: 1.5;
}


/* =========================================
   ACTIONS
========================================= */

.card-actions {

    display: flex;

    gap: 8px;

    margin-top: 18px;
}

.edit-btn,
.delete-btn {

    flex: 1;

    padding: 9px;

    border-radius: 7px;

    text-align: center;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;
}

.edit-btn {

    background: #eaf6ee;

    color: #16803c;
}

.delete-btn {

    background: #fee2e2;

    color: #b91c1c;
}

.edit-btn:hover {

    background: #d8f0df;
}

.delete-btn:hover {

    background: #fecaca;
}


/* =========================================
   MODAL
========================================= */

.modal {

    display: none;

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,0.45);

    align-items: center;

    justify-content: center;

    padding: 20px;

    z-index: 1000;
}

.modal-content {

    background: white;

    width: 100%;

    max-width: 600px;

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


/* =========================================
   FORM
========================================= */

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

    grid-template-columns: 1fr 1fr;

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


/* =========================================
   EMPTY
========================================= */

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


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 1100px) {

    .computer-grid {

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

    .page-header {

        align-items: flex-start;

        gap: 15px;
    }
}

@media (max-width: 600px) {

    .main-content {

        margin-left: 0;

        width: 100%;

        padding: 20px;
    }

    .computer-grid {

        grid-template-columns: 1fr;
    }

    .form-row {

        grid-template-columns: 1fr;
    }

    .page-header {

        flex-direction: column;
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

                <div>

                    <h1>
                        Computers
                    </h1>

                    <p>
                        Manage university computers and their conditions.
                    </p>

                </div>

                <button
                    class="add-btn"
                    onclick="openModal()"
                >
                    + Add Computer
                </button>

            </div>


            <!-- MESSAGES -->

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


            <!-- FILTERS -->

            <form
                method="GET"
                class="filters"
            >

                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search by brand, model, serial number..."
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
                        value="working"
                        <?= $status_filter === "working" ? "selected" : "" ?>
                    >
                        Working
                    </option>

                    <option
                        value="needs_repair"
                        <?= $status_filter === "needs_repair" ? "selected" : "" ?>
                    >
                        Needs Repair
                    </option>

                    <option
                        value="not_repairable"
                        <?= $status_filter === "not_repairable" ? "selected" : "" ?>
                    >
                        Not Repairable
                    </option>

                </select>


                <button
                    type="submit"
                    class="filter-btn"
                >
                    Search
                </button>

            </form>


            <!-- COMPUTERS -->

            <?php if (empty($computers)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        🖥️
                    </div>

                    <h2>
                        No Computers Found
                    </h2>

                    <p>
                        Add your first university computer.
                    </p>

                </div>

            <?php else: ?>


                <div class="computer-grid">


                    <?php foreach ($computers as $computer): ?>


                        <div class="computer-card">


                            <!-- IMAGE -->

                            <div class="computer-image">

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


                            <!-- CONTENT -->

                            <div class="card-content">


                                <div class="card-top">

                                    <div>

                                        <h2 class="card-title">

                                            <?= htmlspecialchars(
                                                $computer["brand"]
                                            ) ?>

                                        </h2>

                                        <div class="card-model">

                                            <?= htmlspecialchars(
                                                $computer["model"]
                                            ) ?>

                                        </div>

                                    </div>


                                    <?php

                                    $status = strtolower(
                                        $computer["status"]
                                    );

                                    ?>


                                    <?php if ($status === "working"): ?>

                                        <span class="status status-working">
                                            Working
                                        </span>

                                    <?php elseif (
                                        $status === "needs_repair"
                                    ): ?>

                                        <span class="status status-repair">
                                            Needs Repair
                                        </span>

                                    <?php else: ?>

                                        <span class="status status-broken">
                                            Not Repairable
                                        </span>

                                    <?php endif; ?>


                                </div>


                                <!-- DETAILS -->

                                <div class="details">


                                    <div class="detail">

                                        <span class="detail-label">
                                            Serial:
                                        </span>

                                        <span class="detail-value">

                                            <?= htmlspecialchars(
                                                $computer["serial_number"]
                                                ?: "N/A"
                                            ) ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span class="detail-label">
                                            Condition:
                                        </span>

                                        <span class="detail-value">

                                            <?= htmlspecialchars(
                                                $computer["condition"]
                                                ?: "N/A"
                                            ) ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span class="detail-label">
                                            Location:
                                        </span>

                                        <span class="detail-value">

                                            <?= htmlspecialchars(
                                                $computer["location"]
                                                ?: "N/A"
                                            ) ?>

                                        </span>

                                    </div>


                                </div>


                                <!-- DESCRIPTION -->

                                <?php if (
                                    !empty($computer["description"])
                                ): ?>

                                    <div class="description">

                                        <?= htmlspecialchars(
                                            $computer["description"]
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <!-- ACTIONS -->

                                <div class="card-actions">

                                    <a
                                        href="edit_computer.php?id=<?= $computer["computer_id"] ?>"
                                        class="edit-btn"
                                    >
                                        Edit
                                    </a>


                                    <a
                                        href="computers.php?delete=<?= $computer["computer_id"] ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this computer?')"
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


<!-- ADD COMPUTER MODAL -->

<div
    class="modal"
    id="computerModal"
>

    <div class="modal-content">


        <div class="modal-header">

            <h2>
                Add New Computer
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


            <div class="form-row">


                <div class="form-group">

                    <label>
                        Brand *
                    </label>

                    <input
                        type="text"
                        name="brand"
                        placeholder="e.g. Dell"
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
                        placeholder="e.g. Latitude 5420"
                        required
                    >

                </div>


            </div>


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
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        placeholder="e.g. Computer Lab 1"
                    >

                </div>


            </div>


            <div class="form-row">


                <div class="form-group">

                    <label>
                        Condition
                    </label>

                    <select name="condition">

                        <option value="">
                            Select Condition
                        </option>

                        <option value="new">
                            New
                        </option>

                        <option value="good">
                            Good
                        </option>

                        <option value="fair">
                            Fair
                        </option>

                        <option value="poor">
                            Poor
                        </option>

                        <option value="damaged">
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

                        <option value="working">
                            Working
                        </option>

                        <option value="needs_repair">
                            Needs Repair
                        </option>

                        <option value="not_repairable">
                            Not Repairable
                        </option>

                    </select>

                </div>


            </div>


            <div class="form-group">

                <label>
                    Computer Image
                </label>

                <input
                    type="file"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp,.jfif"
                >

                <small>
                    Maximum size: 5MB
                </small>

            </div>


            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    placeholder="Write a short description about this computer..."
                ></textarea>

            </div>


            <button
                type="submit"
                class="submit-btn"
            >
                Add Computer
            </button>


        </form>


    </div>

</div>


<script>

function openModal() {

    document.getElementById("computerModal").style.display = "flex";

}

function closeModal() {

    document.getElementById("computerModal").style.display = "none";

}


window.onclick = function(event) {

    const modal =
        document.getElementById("computerModal");

    if (event.target === modal) {

        closeModal();

    }

}

</script>


</body>

</html>
```
