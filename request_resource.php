<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $resource_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    try {
        // 1. إضافة طلب جديد
        $stmt = $pdo->prepare("INSERT INTO requests (resource_id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$resource_id, $user_id]);

        // 2. تحديث حالة المورد ليصبح 'requested' (عشان يختفي من القائمة)
        $update = $pdo->prepare("UPDATE resource SET status = 'requested' WHERE resource_id = ?");
        $update->execute([$resource_id]);

        echo "<script>alert('تم إرسال طلبك بنجاح!'); window.location.href='resources.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('خطأ: " . $e->getMessage() . "'); window.location.href='resources.php';</script>";
    }
} else {
    header("Location: resources.php");
}
?>