<?php

require_once "db.php";

$full_name = "Sara Ahmed";
$email = "sara@unishare.com";
$password = "123456";
$role = "student";
$university_id = "UNI001";
$is_verified = 1;

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users
        (full_name, email, password_hash, role, university_id, is_verified)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $full_name,
    $email,
    $password_hash,
    $role,
    $university_id,
    $is_verified
]);

echo "Test user created successfully!";

?>