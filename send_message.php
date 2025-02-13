<?php
// ตรวจสอบการเริ่ม session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo 'โปรดล็อกอินก่อน';
    exit();
}

if (isset($_POST['message'])) {
    $message = $_POST['message'];
    $user_id = $_SESSION['user_id'];
    $is_admin = false;  // เป็นข้อความจากผู้ใช้

    // ถ้าเป็น Admin ให้เป็น true
    if ($_SESSION['role'] == 'admin') {
        $is_admin = true;
    }

    // ส่งข้อความไปยังฐานข้อมูล
    $query = "INSERT INTO chat_messages (user_id, message, is_admin) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssi', $user_id, $message, $is_admin);
    $stmt->execute();

    // แสดงข้อความที่เพิ่งส่ง
    echo '<div class="mb-3 ' . ($is_admin ? 'text-end' : 'text-start') . '">';
    echo '<strong>' . ($_SESSION['username'] ?? 'User') . '</strong>';
    echo '<p>' . htmlspecialchars($message) . '</p>';
    echo '<small>' . date('M d, Y H:i:s') . '</small>';
    echo '</div>';
}
?>
