<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Query to fetch all pending orders for the logged-in user
$query = "SELECT * FROM orders WHERE user_id = '{$_SESSION['user_id']}' AND status = 'Pending'";
$pending_orders = $conn->query($query);

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
    <h1 class="mb-4">Your Pending Orders</h1>

    <?php if ($pending_orders->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Total Amount</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $pending_orders->fetch_assoc()): ?>
                    
                    <?php
                    // คำนวณจำนวนสินค้าทั้งหมดในคำสั่งซื้อจากตาราง order_items
                    $order_id = $order['id'];
                    $item_query = "SELECT SUM(quantity) AS total_quantity FROM order_items WHERE order_id = '$order_id'";
                    $item_result = $conn->query($item_query);
                    $item_data = $item_result->fetch_assoc();
                    $total_items = $item_data['total_quantity'];
                    ?>

                    <tr>
                        <td><?= $order['id']; ?></td>
                        <td><?= date('d M Y', strtotime($order['order_date'])); ?></td>
                        <td><?= number_format($total_items); ?> items</td> <!-- Display Total Items -->
                        <td>฿<?= number_format($order['total_price'], 2); ?></td>
                        <td><?= ucfirst($order['status']); ?></td>
                        <td>
                            <a href="order_confirmation.php?order_id=<?= $order['id']; ?>" class="btn btn-primary btn-sm">View Order</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have no pending orders at the moment.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
