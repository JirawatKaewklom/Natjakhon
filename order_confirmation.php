<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ตรวจสอบว่าได้รับค่า order_id ผ่านทาง URL หรือไม่
if (!isset($_GET['order_id'])) {
    echo "Order ID is missing!";
    exit();
}

$order_id = $_GET['order_id'];

// ดึงข้อมูลคำสั่งซื้อจากฐานข้อมูล
$order_result = $conn->query("SELECT * FROM orders WHERE id = '$order_id' AND user_id = '{$_SESSION['user_id']}'");

if ($order_result->num_rows == 0) {
    echo "Order not found.";
    exit();
}

$order = $order_result->fetch_assoc();

// ดึงรายการสินค้าจากคำสั่งซื้อ (แยกออกจากตาราง orders)
$product_result = $conn->query("SELECT p.name, p.price, oi.quantity FROM order_items oi 
                                INNER JOIN products p ON oi.product_id = p.id
                                WHERE oi.order_id = '$order_id'");

// คำนวณจำนวนสินค้าทั้งหมด
$total_items = 0;
while ($item = $product_result->fetch_assoc()) {
    $total_items += $item['quantity'];
}

// รีเซ็ต product_result สำหรับการแสดงผลรายการสินค้าอีกครั้ง
$product_result = $conn->query("SELECT p.name, p.price, oi.quantity FROM order_items oi 
                                INNER JOIN products p ON oi.product_id = p.id
                                WHERE oi.order_id = '$order_id'");

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
    <h1 class="mb-4">Order Confirmation</h1>

    <div class="mb-4">
        <h4>Thank you for your order!</h4>
        <p>Your order has been successfully placed. Below are the details of your order.</p>
    </div>

    <!-- Order Details -->
    <div class="mb-4">
        <h5>Order ID: #<?= $order['id']; ?></h5>
        <p><strong>Shipping Address:</strong> <?= $order['address_user']; ?></p>
        <p><strong>Payment Method:</strong> <?= ucfirst($order['payment_method']); ?></p>
        <p><strong>Status:</strong> <?= ucfirst($order['status']); ?></p>
        <p><strong>Order Date:</strong> <?= date('d M Y', strtotime($order['order_date'])); ?></p>
        <p><strong>Total Amount:</strong> <?= number_format($total_items); ?> items</p>
        <p><strong>Total Price:</strong> ฿<?= number_format($order['total_price'], 2); ?></p>
    </div>

    <!-- Order Items -->
    <div class="table-responsive mb-4">
        <table class="table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_price = 0;
                while ($item = $product_result->fetch_assoc()) {
                    $total_item_price = $item['price'] * $item['quantity'];
                    $total_price += $total_item_price;
                    echo "<tr>
                            <td>{$item['name']}</td>
                            <td>฿{$item['price']}</td>
                            <td>{$item['quantity']}</td>
                            <td>฿{$total_item_price}</td>
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

    <!-- Go to Order History -->
    <!-- <a href="order_history.php" class="btn btn-primary">View Order History</a> -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
