<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['code'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$verification_code = $_GET['code'];

if ($verification_code === $_SESSION['verification_code']) {
    $new_email = $_SESSION['new_email'];

    // อัปเดต email_verified เป็น 1
    $stmt = $conn->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // ล้างข้อมูลการยืนยัน
    unset($_SESSION['verification_code']);
    unset($_SESSION['new_email']);

    echo "Email has been successfully verified and updated.";
    header('Location: login.php');
    exit();
} else {
    // คืนค่าอีเมลเดิมและตั้งค่า email_verified เป็น 1
    $stmt = $conn->prepare("UPDATE users SET email = (SELECT email FROM users WHERE id = ?), email_verified = 1 WHERE id = ?");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo "Invalid verification code. Email has been reverted to the original.";
    header('Location: login.php');
    exit();
}
?>