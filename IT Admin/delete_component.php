
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


/* =========================================================
   GET COMPONENT ID
========================================================= */

$component_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if ($component_id <= 0) {
    header("Location: components.php");
    exit();
}


/* =========================================================
   GET COMPONENT IMAGE
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT image
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

    header("Location: components.php?error=delete");
    exit();

}


/* =========================================================
   DELETE COMPONENT
========================================================= */

try {

    $stmt = $pdo->prepare("
        DELETE FROM component
        WHERE component_id = ?
    ");

    $stmt->execute([$component_id]);


    /* =====================================================
       DELETE IMAGE FROM UPLOADS
    ===================================================== */

    if (!empty($component["image"])) {

        $image_path =
            __DIR__ .
            "/uploads/components/" .
            $component["image"];

        if (file_exists($image_path)) {
            unlink($image_path);
        }

    }


    /* =====================================================
       REDIRECT
    ===================================================== */

    header("Location: components.php?success=deleted");
    exit();

} catch (PDOException $e) {

    header("Location: components.php?error=delete");
    exit();

}
?>



