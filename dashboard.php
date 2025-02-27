<?php
require_once 'config.php';
include 'admin_navbar.php';
include "head.php";

// ตรวจสอบว่าผู้ใช้ล็อกอินและเป็น admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// ฟังก์ชันเชื่อมต่อฐานข้อมูลและตรวจสอบข้อผิดพลาด
function executeQuery($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die('Query preparation failed: ' . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param(...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result();
}

// ฟังก์ชันสำหรับคำนวณ total_amount ของคำสั่งซื้อ (นับจำนวนสินค้าที่แตกต่างกัน)
function getTotalAmount($conn, $orderId) {
    $query = "SELECT COUNT(DISTINCT product_id) as total_amount FROM order_items WHERE order_id = ?";
    $result = executeQuery($conn, $query, ['i', $orderId]);
    $row = $result->fetch_assoc();
    return $row['total_amount'] ?? 0; // ถ้าไม่มีค่า ให้คืน 0
}

// ฟังก์ชันสำหรับการดึงข้อมูลจำนวนผู้ใช้ทั้งหมด
function getTotalUsers($conn) {
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $result = executeQuery($conn, $query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// ฟังก์ชันสำหรับการดึงข้อมูลจำนวนสินค้าทั้งหมด
function getTotalProducts($conn) {
    $query = "SELECT COUNT(*) as total FROM products";
    $result = executeQuery($conn, $query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// ฟังก์ชันสำหรับการดึงข้อมูลจำนวนคำสั่งซื้อทั้งหมดของวันนี้
function getTotalOrdersToday($conn) {
    $query = "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()";
    $result = executeQuery($conn, $query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// ฟังก์ชันสำหรับคืนจำนวนสินค้ากลับสู่ stock_quantity
function restoreStockQuantity($conn, $orderId) {
    $query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $result = executeQuery($conn, $query, ['i', $orderId]);
    
    while ($row = $result->fetch_assoc()) {
        $updateQuery = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
        executeQuery($conn, $updateQuery, ['ii', $row['quantity'], $row['product_id']]);
    }
}
?>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center">แผงควบคุมผู้ดูแลระบบ</h2>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">จำนวนผู้ใช้ทั้งหมด</h5>
                        <p class="card-text display-4"><?= getTotalUsers($conn); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">จำนวนสินค้าในคลังทั้งหมด</h5>
                        <p class="card-text display-4"><?= getTotalProducts($conn); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">รายการคำสั่งซื้อทั้งหมดของวันนี้</h5>
                        <p class="card-text display-4"><?= getTotalOrdersToday($conn); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h3 class="text-info">รายการคำสั่งซื้อ</h3>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-bordered table-hover">
                        <thead class="table-primary">
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
                            $query = "SELECT o.*, u.username FROM orders o 
                                    JOIN users u ON o.user_id = u.id 
                                    WHERE DATE(o.created_at) = CURDATE()
                                    ORDER BY o.created_at DESC";
                            $result = executeQuery($conn, $query);
                            while ($row = $result->fetch_assoc()) {
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
        </div>

        <!-- Completed Orders -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h3 class="text-success">คำสั่งซื้อที่เสร็จสมบูรณ์แล้ว</h3>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-bordered table-hover">
                        <thead class="table-success">
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
                            $query = "SELECT o.*, u.username FROM orders o 
                                    JOIN users u ON o.user_id = u.id 
                                    WHERE o.status = 'completed'
                                    ORDER BY o.created_at DESC LIMIT 10";
                            $result = executeQuery($conn, $query);
                            while ($row = $result->fetch_assoc()) {
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
                                        <form action="delete_order.php" method="POST" class="d-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบคำสั่งซื้อนี้?');">
                                        <input type="hidden" name="order_id" value="<?= $row['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
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

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
