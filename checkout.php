<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลที่อยู่จากผู้ใช้ในฐานข้อมูล (ใช้ address_user แทน address)
$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT * FROM users WHERE id = '$user_id'");
$user = $user_result->fetch_assoc();

// ดึงข้อมูลสินค้าที่อยู่ในตะกร้าของผู้ใช้
$cart_items = $conn->query("SELECT c.id, p.name, p.price, c.quantity FROM cart c INNER JOIN products p ON c.product_id = p.id WHERE c.user_id = '$user_id'");

// คำนวณราคารวมของสินค้าทั้งหมด
$total_price = 0;
while ($item = $cart_items->fetch_assoc()) {
    $total_price += $item['price'] * $item['quantity'];
}

// เมื่อผู้ใช้ยืนยันการสั่งซื้อ
if (isset($_POST['checkout'])) {
    // รับข้อมูลจากฟอร์ม
    $address = $_POST['address'];
    $payment_method = $_POST['payment_method'];

    // สร้างคำสั่งซื้อในฐานข้อมูล
    $conn->query("INSERT INTO orders (user_id, address_user, payment_method, total_price, status) VALUES ('$user_id', '$address', '$payment_method', '$total_price', 'Pending')");

    // ดึงคำสั่งซื้อที่เพิ่งสร้าง
    $order_id = $conn->insert_id;

    // ย้ายข้อมูลจากตะกร้าไปที่คำสั่งซื้อ
    $cart_items = $conn->query("SELECT * FROM cart WHERE user_id = '$user_id'");
    while ($item = $cart_items->fetch_assoc()) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $conn->query("INSERT INTO order_items (order_id, product_id, quantity) VALUES ('$order_id', '$product_id', '$quantity')");
    }

    // ลบสินค้าจากตะกร้า
    $conn->query("DELETE FROM cart WHERE user_id = '$user_id'");

    // หลังจากที่คำสั่งซื้อเสร็จสมบูรณ์ ให้ redirect ไปยังหน้าการยืนยันคำสั่งซื้อ
header('Location: order_confirmation.php?order_id=' . $order_id);
exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">Checkout</h1>

    <!-- รายการสินค้าจากตะกร้า -->
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
                // ดึงข้อมูลจากตะกร้า
                $cart_items = $conn->query("SELECT c.id, p.name, p.price, c.quantity FROM cart c INNER JOIN products p ON c.product_id = p.id WHERE c.user_id = '$user_id'");
                while ($item = $cart_items->fetch_assoc()) {
                    $total_item_price = $item['price'] * $item['quantity'];
                    echo "<tr>
                            <td>{$item['name']}</td>
                            <td>\${$item['price']}</td>
                            <td>{$item['quantity']}</td>
                            <td>\${$total_item_price}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- แสดงราคาสินค้ารวม -->
    <div class="mb-4">
        <h4>Total Price: $<?= number_format($total_price, 2); ?></h4>
    </div>

    <!-- ฟอร์มการกรอกข้อมูลการชำระเงิน -->
    <form method="POST">
        <div class="mb-3">
            <label for="address" class="form-label">Shipping Address</label>
            <!-- แสดงที่อยู่จากคอลัมน์ address_user ในฟอร์ม -->
            <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($user['address_user']); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="payment_method" class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
                <option value="credit_card">Credit Card</option>
                <option value="paypal">PayPal</option>
                <option value="bank_transfer">Bank Transfer</option>
            </select>
        </div>

        <button type="submit" name="checkout" class="btn btn-success">Place Order</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
