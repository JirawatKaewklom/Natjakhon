<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['remove'])) {
    $cart_id = $_POST['cart_id'];

    // ลบรายการสินค้าจากตะกร้า
    $conn->query("DELETE FROM cart WHERE id = '$cart_id' AND user_id = '{$_SESSION['user_id']}'");

    // รีไดเร็กต์ไปหน้า cart
    header('Location: cart.php');
    exit();
}
?>
