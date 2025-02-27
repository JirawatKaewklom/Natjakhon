<?php
require_once 'config.php';
include 'admin_navbar.php';
include "head.php";

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

// ฟังก์ชันสำหรับการดึงข้อมูลจำนวนคำสั่งซื้อในแต่ละวัน
function getSalesReportByDate($conn, $date) {
    $query = "SELECT o.*, u.username FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE DATE(o.created_at) = ? 
              ORDER BY o.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $date);
    $stmt->execute();
    return $stmt->get_result();
}

// ฟังก์ชันสำหรับการพิมพ์รายงาน
function printReport($conn, $date) {
    $result = getSalesReportByDate($conn, $date);
    ?>
    <br>
    <div class="container">
        <h2>รายงานการซื้อขายประจำวันที่ <?= date('d M Y', strtotime($date)); ?></h2>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>หมายเลขออเดอร์</th>
                    <th>ลูกค้า</th>
                    <th>วันที่</th>
                    <th>รายการสินค้าทั้งหมด</th>
                    <th>จำนวนเงินทั้งหมด</th>
                    <th>สถานะ</th>
                    <th>การยืนยันคำสั่งซื้อ</th>
                    <th>เปลี่ยนสถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) {
                    $totalAmount = getTotalAmount($conn, $row['id']);
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td><?= number_format($totalAmount); ?> รายการ</td>
                        <td>฿<?php echo number_format($row['total_price'], 2); ?></td>
                        <td><?php echo ucfirst($row['status']); ?></td>
                        <td><button onclick="viewOrderConfirmation(<?php echo $row['id']; ?>)" class="btn btn-info">ดูการยืนยันคำสั่งซื้อ</button></td>
                        <td>
                            <form method="POST" action="update_status.php">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="report_date" value="<?= $date; ?>">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                                <option value="processing" <?php echo ($row['status'] == 'processing') ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                                                <option value="completed" <?php echo ($row['status'] == 'completed') ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                                                <option value="cancelled" <?php echo ($row['status'] == 'cancelled') ? 'selected' : ''; ?>>ยกเลิก</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <button onclick="window.print();" class="btn btn-primary">พิมพ์รายงาน</button>
    </div>
    <?php
}

?>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center">รายงานการซื้อขาย</h2>
            </div>
        </div>

        <!-- ฟอร์มเลือกวันที่เพื่อดูรายงานการซื้อขาย -->
        <div class="row mt-4">
            <div class="col-md-12">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="date" name="report_date" class="form-control" required>
                        <button type="submit" class="btn btn-success">ดูรายงาน</button>
                    </div>
                </form>
            </div>
        </div>

        <?php
        if (isset($_GET['report_date'])) {
            $date = $_GET['report_date'];
            printReport($conn, $date);
        }
        ?>

    </div>

    <script>
    function viewOrderConfirmation(orderId) {
        window.open('order_confirmation.php?order_id=' + orderId, '_blank');
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
