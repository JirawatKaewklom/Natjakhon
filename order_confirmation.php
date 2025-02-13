<?php
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ตรวจสอบบทบาทของผู้ใช้ (user หรือ admin)
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// ใช้ navbar ที่แตกต่างกันสำหรับ admin และ user
include $is_admin ? 'admin_navbar.php' : 'navbar.php';

// ตรวจสอบว่า order_id ถูกส่งมาหรือไม่
if (!isset($_GET['order_id'])) {
    echo "Order ID is missing!";
    exit();
}

$order_id = intval($_GET['order_id']);

// ใช้ JOIN ดึงข้อมูลคำสั่งซื้อและชื่อผู้ใช้
if ($is_admin) {
    $stmt = $conn->prepare("SELECT o.*, u.username FROM orders o 
                            LEFT JOIN users u ON o.user_id = u.id 
                            WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
}
 else {
    $stmt = $conn->prepare("SELECT o.* FROM orders o 
                            WHERE o.id = ? AND o.user_id = ?");
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
}

if (!$stmt->execute()) {
    die("Database error: " . $stmt->error);
}

$order_result = $stmt->get_result();
if ($order_result->num_rows == 0) {
    echo "Order not found.";
    exit();
}

$order = $order_result->fetch_assoc();

// ดึงจำนวนสินค้าที่ไม่ซ้ำกัน
$product_count_stmt = $conn->prepare("SELECT COUNT(DISTINCT product_id) AS total_items FROM order_items WHERE order_id = ?");
$product_count_stmt->bind_param("i", $order_id);
$product_count_stmt->execute();
$product_count_result = $product_count_stmt->get_result();
$total_items = $product_count_result->fetch_assoc()['total_items'];

// ดึงรายการสินค้าทั้งหมดในคำสั่งซื้อ
$product_stmt = $conn->prepare("SELECT p.name, p.price, oi.quantity FROM order_items oi 
                                INNER JOIN products p ON oi.product_id = p.id
                                WHERE oi.order_id = ?");
$product_stmt->bind_param("i", $order_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

// สีแสดงสถานะคำสั่งซื้อ
$status_badge = [
    'pending' => 'warning',
    'completed' => 'success',
    'canceled' => 'danger'
];
$status_class = $status_badge[$order['status']] ?? 'secondary';

?>

<?php include 'head.php' ?>
<body>
<div class="container my-5">
    <h1 class="mb-4">การยืนยันการสั่งซื้อ</h1>

    <div class="mb-4">
        <h4>ขอบคุณสำหรับการสั่งซื้อของคุณ!</h4>
        <p>คำสั่งซื้อของคุณได้รับการยืนยันแล้ว ด้านล่างคือรายละเอียดคำสั่งซื้อของคุณ.</p>
    </div>

    <div class="mb-4">
        <h5>หมายเลขออเดอร์: #<?= htmlspecialchars($order['id']); ?></h5>
        <p><strong>ที่อยู่จัดส่ง:</strong> <?= htmlspecialchars($order['address_user']); ?></p>
        <p><strong>วิธีการชำระเงิน:</strong> <?= ucfirst(htmlspecialchars($order['payment_method'])); ?></p>
        <p><strong>สถานะ:</strong> 
            <span class="badge bg-<?= $status_class; ?>"><?= ucfirst(htmlspecialchars($order['status'])); ?></span>
        </p>
        <p><strong>วันที่สั่งซื้อ:</strong> <?= date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
        <p><strong>รายการทั้งหมด:</strong> <?= number_format($total_items); ?> รายการ</p>
        <p><strong>ยอดสุทธิ:</strong> ฿<?= number_format($order['total_price'], 2); ?></p>
        
        <!-- ถ้าเป็น admin แสดงชื่อผู้สั่งซื้อ -->
        <?php if ($is_admin): ?>
            <p><strong>ผู้สั่งซื้อ:</strong> <?= htmlspecialchars($order['username']); ?> (ID: <?= htmlspecialchars($order['user_id']); ?>)</p>
        <?php endif; ?>
    </div>

    <!-- ตรวจสอบว่ามีสินค้าในคำสั่งซื้อหรือไม่ -->
    <?php if ($product_result->num_rows > 0) : ?>
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ชื่อสินค้า</th>
                        <th>ราคา</th>
                        <th>จำนวน</th>
                        <th>ยอดสุทธิ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_price = 0;
                    while ($item = $product_result->fetch_assoc()) {
                        $total_item_price = $item['price'] * $item['quantity'];
                        $total_price += $total_item_price;
                        echo "<tr>
                                <td>" . htmlspecialchars($item['name']) . "</td>
                                <td>฿" . number_format($item['price'], 2) . "</td>
                                <td>" . number_format($item['quantity']) . "</td>
                                <td>฿" . number_format($total_item_price, 2) . "</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <div class="alert alert-warning">ไม่มีรายการสินค้าในคำสั่งซื้อนี้</div>
    <?php endif; ?>

    <div class="mb-4">
        <h4>ยอดสุทธิ: ฿<?= number_format($total_price, 2); ?></h4>
    </div>

    <!-- ปุ่มนำทาง -->
    <div class="d-flex mt-4">
        <a href="pending_orders.php" class="btn btn-primary me-2">ดูคำสั่งซื้อทั้งหมด</a>
        <a href="index.php" class="btn btn-secondary me-2">กลับไปหน้าหลัก</a>
        <button onclick="window.print()" class="btn btn-success">พิมพ์ใบเสร็จ</button>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
