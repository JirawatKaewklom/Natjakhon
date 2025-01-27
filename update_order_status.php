<?php
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้เป็น admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
if (isset($_POST['status']) && isset($_POST['order_id'])) {
    $status = $_POST['status'];
    $order_id = $_POST['order_id'];

    // ตรวจสอบค่าของ status ก่อนทำการอัพเดต
    $valid_status = ['pending', 'processing', 'completed', 'cancelled'];
    if (in_array($status, $valid_status)) {
        // ใช้ prepared statement เพื่ออัพเดต status ในฐานข้อมูล
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        
        if ($stmt->execute()) {
            // ถ้าการอัพเดตสำเร็จ ให้กลับไปที่หน้าเดิม
            header('Location: dashboard.php');
            exit();
        } else {
            // ถ้ามีข้อผิดพลาดเกิดขึ้น
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Invalid status value.";
    }
} else {
    echo "Missing required fields.";
}
?>
