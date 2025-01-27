<?php
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินและเป็น admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// ฟังก์ชันสำหรับคำนวณ total_amount ของคำสั่งซื้อ
function getTotalAmount($conn, $orderId) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total_amount FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total_amount'] ?? 0; // ถ้าไม่มีค่า ให้คืน 0
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Wholesale System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Admin Dashboard</h2>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
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
                        <h5 class="card-title">Total Products</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM products";
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
                        <h5 class="card-title">Total Orders</h5>
                        <?php
                        $sql = "SELECT COUNT(*) as total FROM orders";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="card-text display-4"><?php echo $row['total']; ?></p>
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
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total Items</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT o.*, u.username FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                ORDER BY o.created_at DESC LIMIT 10";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            $totalAmount = getTotalAmount($conn, $row['id']); // เรียกฟังก์ชันคำนวณ total_amount
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td><?= number_format($totalAmount); ?> items</td>
                                <td>฿<?php echo number_format($row['total_price'], 2); ?></td>
                                <td><?php echo ucfirst($row['status']); ?></td>
                                
                                <td>
                                    <form action="update_order_status.php" method="POST">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo ($row['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                            <option value="completed" <?php echo ($row['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo ($row['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                    </form>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
