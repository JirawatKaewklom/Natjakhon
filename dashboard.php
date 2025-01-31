<?php
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินและเป็น admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// ฟังก์ชันสำหรับคำนวณ total_amount ของคำสั่งซื้อ (นับจำนวนสินค้าที่แตกต่างกัน)
function getTotalAmount($conn, $orderId) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT product_id) as total_amount FROM order_items WHERE order_id = ?");
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
                <h2>แผงควบคุมผู้ดูแลระบบ</h2>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">จำนวนผู้ใช้ทั้งหมด</h5>
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
                        <h5 class="card-title">จำนวนสินค้าในคลังทั้งหมด</h5>
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
                        <h5 class="card-title">จำนวนรายการสั่งซื้อทั้งหมด</h5>
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
        
        <!-- Recent Orders -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h3>คำสั่งซื้อล่าสุด</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>หมายเลขออเดอร์</th>
                            <th>ลูกค้า</th>
                            <th>วันที่</th>
                            <th>รายการสินค้าทั้งหมด</th>
                            <th>จำนวนเงินทั้งหมด</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                            <th>ข้อมูลคำสั่งซื้อ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT o.*, u.username FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                WHERE o.status != 'completed'
                                ORDER BY o.created_at DESC LIMIT 10";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            // คำนวณจำนวนสินค้าที่แตกต่างกัน
                            $totalAmount = getTotalAmount($conn, $row['id']);
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td><?= number_format($totalAmount); ?> รายการ</td>
                                <td>฿<?php echo number_format($row['total_price'], 2); ?></td>
                                <td><?php echo ucfirst($row['status']); ?></td>
                                <td>
                                    <form action="update_order_status.php" method="POST" class="d-flex align-items-center">
                                        <select name="status" class="form-select form-select-sm me-2">
                                            <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                            <option value="processing" <?php echo ($row['status'] == 'processing') ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                                            <option value="completed" <?php echo ($row['status'] == 'completed') ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                                            <option value="cancelled" <?php echo ($row['status'] == 'cancelled') ? 'selected' : ''; ?>>ยกเลิก</option>
                                        </select>
                                        <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">อัพเดท</button>
                                    </form>
                                </td>
                                <td>
                                    <a href="order_confirmation.php?order_id=<?= $row['id']; ?>" class="btn btn-info btn-sm">ดูรายการคำสั่งซื้อ</a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Completed Orders -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h3>คำสั่งซื้อที่เสร็จสมบูรณ์แล้ว</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>หมายเลขออเดอร์</th>
                            <th>ลูกค้า</th>
                            <th>วันที่</th>
                            <th>รายการสินค้าทั้งหมด</th>
                            <th>จำนวนเงินทั้งหมด</th>
                            <th>ข้อมูลคำสั่งซื้อ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT o.*, u.username FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                WHERE o.status = 'completed'
                                ORDER BY o.created_at DESC LIMIT 10";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            // คำนวณจำนวนสินค้าที่แตกต่างกัน
                            $totalAmount = getTotalAmount($conn, $row['id']);
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td><?= number_format($totalAmount); ?> รายการ</td>
                                <td>฿<?php echo number_format($row['total_price'], 2); ?></td>
                                <td>
                                    <a href="order_confirmation.php?order_id=<?= $row['id']; ?>" class="btn btn-info btn-sm">ดูรายการคำสั่งซื้อ</a>
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
