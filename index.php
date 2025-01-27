<?php
require_once 'config.php';
include 'head.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Query to fetch all pending orders for the logged-in user
$query = "SELECT * FROM orders WHERE user_id = '{$_SESSION['user_id']}' AND status = 'Pending'";
$pending_orders = $conn->query($query);

?>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = " . $_SESSION['user_id'];
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="card-text display-4"><?php echo $row['total']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Pending Orders</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = " . $_SESSION['user_id'] . " AND status = 'pending'";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="card-text display-4"><?php echo $row['total']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Spent</h5>
                        <?php
                        $sql = "SELECT SUM(total_price) as total_price FROM orders WHERE user_id = " . $_SESSION['user_id'];
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="card-text display-4">$<?php echo number_format($row['total_price'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <h3>Recent Orders</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Total</th>
                            <th>Status</th>
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
                    </tr>
                <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>