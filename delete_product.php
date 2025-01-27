<?php
require_once 'config.php';

// ตรวจสอบว่าได้รับ product_id หรือไม่
if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    // ดึงข้อมูลรูปภาพจากฐานข้อมูล
    $product_result = $conn->query("SELECT * FROM products WHERE id = $product_id");
    $product = $product_result->fetch_assoc();

    // ลบรูปภาพจากโฟลเดอร์
    if (file_exists($product['image_url'])) {
        unlink($product['image_url']);
    }

    // ลบสินค้าออกจากฐานข้อมูล
    $conn->query("DELETE FROM products WHERE id = $product_id");

    header('Location: manage_products.php');
    exit();
}
?>
