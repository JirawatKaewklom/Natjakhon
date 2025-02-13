<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'wholesale_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตรวจสอบการเริ่ม session ก่อน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Image upload function
function uploadProductImage($file) {
    $target_dir = "uploads/products/";

    // Create directory if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $unique_filename = uniqid() . '_' . basename($file["name"]);
    $target_file = $target_dir . $unique_filename;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }

    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }

    // Allow certain file formats
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($imageFileType, $allowed_types)) {
        return false;
    }

    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    } else {
        return false;
    }
}
?>
