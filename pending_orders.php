<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
$query = "SELECT * FROM orders WHERE user_id = ? AND status IN ('Pending', 'Processing', 'Completed', 'Cancelled')";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending_orders = $stmt->get_result();
?>

<?php include 'head.php' ?>
<body>
<div class="container my-5">
    <h1 class="mb-4 text-center">รายละเอียดคำสั่งซื้อทั้งหมด</h1>

    <?php if ($pending_orders->num_rows > 0): ?>
        <div class="row">
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

                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">หมายเลขออเดอร์: <?= htmlspecialchars($order['id']); ?></h5>
                            <p class="card-text">วันที่สั่งซื้อ: <?= date('d M Y', strtotime($order['created_at'])); ?></p>
                            <p class="card-text">รายการทั้งหมด: <?= number_format($total_items); ?> รายการ</p>
                            <p class="card-text text-success">ยอดสุทธิ: ฿<?= number_format($order['total_price'], 2); ?></p>
                            <p class="card-text text-muted">สถานะ: <?= ucfirst(htmlspecialchars($order['status'])); ?></p>
                            <a href="order_confirmation.php?order_id=<?= htmlspecialchars($order['id']); ?>" class="btn btn-primary btn-sm w-100" data-bs-toggle="tooltip" title="ดูคำสั่งซื้อรายละเอียดเพิ่มเติม">ดูคำสั่งซื้อ</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-danger">คุณไม่มีคำสั่งซื้อในขณะนี้.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// เริ่มต้น Tooltip เมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
})
</script>
</body>
</html>
