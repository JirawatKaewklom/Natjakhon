<?php
require_once 'config.php';

// ตรวจสอบว่าได้รับ product_id หรือไม่
if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    // ใช้ prepared statement เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);  // ใช้ "i" สำหรับ integer
    $stmt->execute();
    $product_result = $stmt->get_result();
    $product = $product_result->fetch_assoc();

    // ตรวจสอบว่ามีสินค้าในฐานข้อมูลหรือไม่
    if ($product) {
        // ลบข้อมูลในตาราง cart ที่อ้างอิงถึง product_id
        $delete_cart_stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
        $delete_cart_stmt->bind_param("i", $product_id);
        $delete_cart_stmt->execute();

        // ลบรูปภาพจากโฟลเดอร์
        if (file_exists($product['image_url'])) {
            unlink($product['image_url']);
        }

        // ลบสินค้าออกจากฐานข้อมูล
        $delete_product_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_product_stmt->bind_param("i", $product_id);
        $delete_product_stmt->execute();

        // รีไดเร็กต์ไปที่หน้าจัดการสินค้า
        header('Location: manage_products.php');
        exit();
    } else {
        echo "<div class='alert alert-danger'>Product not found.</div>";
    }
}
?>
