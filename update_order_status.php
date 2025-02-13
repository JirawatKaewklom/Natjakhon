<?php
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้เป็น admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    // หากสถานะเป็น 'cancelled' ให้ลบคำสั่งซื้อและข้อมูลที่เกี่ยวข้อง
    if ($status === 'cancelled') {
        // ลบข้อมูลในตาราง order_items ที่เกี่ยวข้อง
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // ลบคำสั่งซื้อจากตาราง orders
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            header('Location: dashboard.php'); // กลับไปหน้าแดชบอร์ด
            exit();
        } else {
            echo "เกิดข้อผิดพลาดในการลบคำสั่งซื้อ";
        }
    } else {
        // หากสถานะไม่ใช่ 'cancelled' ให้ทำการอัพเดทสถานะ
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);

        if ($stmt->execute()) {
            header('Location: dashboard.php'); // กลับไปหน้าแดชบอร์ด
            exit();
        } else {
            echo "เกิดข้อผิดพลาดในการอัพเดทสถานะคำสั่งซื้อ";
        }
    }
}
?>
