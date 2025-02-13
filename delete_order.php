<?php
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินและเป็น admin หรือไม่
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// ตรวจสอบว่ามี order_id ที่ส่งมาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);

    // ลบรายการสินค้าทั้งหมดใน order_items ก่อน
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();

    // ลบคำสั่งซื้อในตาราง orders
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "ลบคำสั่งซื้อสำเร็จ";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบคำสั่งซื้อ";
    }
    $stmt->close();
}

// กลับไปที่หน้าแดชบอร์ด
header("Location: dashboard.php");
exit();
?>
