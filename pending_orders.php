<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
$query = "SELECT * FROM orders WHERE user_id = ? AND status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending_orders = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">รายละเอียดคำสั่งซื้อทั้งหมด</h1>

    <?php if ($pending_orders->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>หมายเลขออเดอร์</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>รายการทั้งหมด</th>
                    <th>ยอดสุทธิ</th>
                    <th>สถานะ</th>
                    <th>รายละเอียดคำสั่งซื้อ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $pending_orders->fetch_assoc()): ?>
                    
                    <?php
                    // ดึงจำนวนสินค้าที่ไม่ซ้ำกันในคำสั่งซื้อนี้
                    $order_id = $order['id'];
                    $item_stmt = $conn->prepare("SELECT COUNT(DISTINCT product_id) AS total_items FROM order_items WHERE order_id = ?");
                    $item_stmt->bind_param("i", $order_id);
                    $item_stmt->execute();
                    $item_result = $item_stmt->get_result();
                    $item_data = $item_result->fetch_assoc();
                    $total_items = $item_data['total_items'];
                    ?>

                    <tr>
                        <td><?= htmlspecialchars($order['id']); ?></td>
                        <td><?= date('d M Y', strtotime($order['created_at'])); ?></td>
                        <td><?= number_format($total_items); ?> รายการ</td> <!-- ✅ เปลี่ยนจาก quantity เป็นจำนวนสินค้า -->
                        <td>฿<?= number_format($order['total_price'], 2); ?></td>
                        <td><?= ucfirst(htmlspecialchars($order['status'])); ?></td>
                        <td>
                            <a href="order_confirmation.php?order_id=<?= htmlspecialchars($order['id']); ?>" class="btn btn-primary btn-sm">ดูคำสั่งซื้อ</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>คุณไม่มีคำสั่งซื้อในขณะนี้.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
