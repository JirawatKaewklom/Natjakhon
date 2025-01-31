<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ตรวจสอบว่ามี order_id หรือไม่
if (!isset($_GET['order_id'])) {
    echo "Order ID is missing!";
    exit();
}

$order_id = intval($_GET['order_id']); // แปลงเป็นตัวเลขเพื่อป้องกัน SQL Injection

// ดึงข้อมูลคำสั่งซื้อ
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows == 0) {
    echo "Order not found.";
    exit();
}

$order = $order_result->fetch_assoc();

// ดึงจำนวนสินค้าที่ไม่ซ้ำกัน
$product_count_stmt = $conn->prepare("SELECT COUNT(DISTINCT product_id) AS total_items FROM order_items WHERE order_id = ?");
$product_count_stmt->bind_param("i", $order_id);
$product_count_stmt->execute();
$product_count_result = $product_count_stmt->get_result();
$total_items = $product_count_result->fetch_assoc()['total_items'];

// ดึงรายการสินค้าทั้งหมดในคำสั่งซื้อ
$product_stmt = $conn->prepare("SELECT p.name, p.price, oi.quantity FROM order_items oi 
                                INNER JOIN products p ON oi.product_id = p.id
                                WHERE oi.order_id = ?");
$product_stmt->bind_param("i", $order_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">การยืนยันการสั่งซื้อ</h1>

    <div class="mb-4">
        <h4>ขอบคุณสำหรับการสั่งซื้อของคุณ!</h4>
        <p>การสั่งซื้อของคุณสำเร็จแล้ว ด้านล่างนี้คือรายละเอียดการสั่งซื้อของคุณ.</p>
    </div>

    <!-- Order Details -->
    <div class="mb-4">
        <h5>หมายเลขออเดอร์: #<?= htmlspecialchars($order['id']); ?></h5>
        <p><strong>ที่อยู่จัดส่ง:</strong> <?= htmlspecialchars($order['address_user']); ?></p>
        <p><strong>วิธีการชำระเงิน:</strong> <?= ucfirst(htmlspecialchars($order['payment_method'])); ?></p>
        <p><strong>สถานะ:</strong> <?= ucfirst(htmlspecialchars($order['status'])); ?></p>
        <p><strong>วันที่สั่งซื้อ:</strong> <?= date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
        <p><strong>รายการทั้งหมด:</strong> <?= number_format($total_items); ?> รายการ</p>
        <p><strong>ยอดสุทธิ:</strong> ฿<?= number_format($order['total_price'], 2); ?></p>
    </div>

    <!-- Order Items -->
    <div class="table-responsive mb-4">
        <table class="table">
            <thead>
                <tr>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>ยอดสุทธิ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_price = 0;
                while ($item = $product_result->fetch_assoc()) {
                    $total_item_price = $item['price'] * $item['quantity'];
                    $total_price += $total_item_price;
                    echo "<tr>
                            <td>" . htmlspecialchars($item['name']) . "</td>
                            <td>฿" . number_format($item['price'], 2) . "</td>
                            <td>" . number_format($item['quantity']) . "</td>
                            <td>฿" . number_format($total_item_price, 2) . "</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Total Price -->
    <div class="mb-4">
        <h4>Total Price: ฿<?= number_format($total_price, 2); ?></h4>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
