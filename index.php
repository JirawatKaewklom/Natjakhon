<?php
require_once 'config.php';
include 'head.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลคำสั่งซื้อที่รอดำเนินการ
$query = "SELECT * FROM orders WHERE user_id = ? AND status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending_orders = $stmt->get_result();

// ดึงข้อมูลคำสั่งซื้อที่เสร็จสิ้นแล้ว
$query_completed = "SELECT * FROM orders WHERE user_id = ? AND status = 'Completed'";
$stmt_completed = $conn->prepare($query_completed);
$stmt_completed->bind_param("i", $_SESSION['user_id']);
$stmt_completed->execute();
$completed_orders = $stmt_completed->get_result();

?>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2>ยินดีต้อนรับ, คุณ <?= htmlspecialchars($_SESSION['username']); ?></h2>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">คำสั่งซื้อทั้งหมด</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="card-text display-4"><?= $row['total']; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">คำสั่งที่รอดำเนินการ</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status = 'pending'");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="card-text display-4"><?= $row['total']; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">ค่าใช้จ่ายทั้งหมด</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT SUM(total_price) as total_price FROM orders WHERE user_id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="card-text display-4">฿<?= number_format($row['total_price'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ตารางแสดงออเดอร์ที่รอดำเนินการ -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h3>คำสั่งที่รอดำเนินการ</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>หมายเลขออเดอร์</th>
                            <th>วันที่</th>
                            <th>รายการทั้งหมด</th>
                            <th>ยอดสุทธิ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($order = $pending_orders->fetch_assoc()): ?>
                        <?php
                        // นับจำนวนสินค้าที่ไม่ซ้ำกันในออเดอร์นี้
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
                            <td><?= number_format($total_items); ?> รายการ</td>
                            <td>฿<?= number_format($order['total_price'], 2); ?></td>
                            <td><span class="badge bg-warning"><?= ucfirst(htmlspecialchars($order['status'])); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ตารางแสดงออเดอร์ที่เสร็จสิ้นแล้ว -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h3>คำสั่งซื้อที่เสร็จสมบูรณ์แล้ว</h3>
                <table class="table table-bordered">
                    <thead class="table-success">
                        <tr>
                            <th>หมายเลขออเดอร์</th>
                            <th>วันที่</th>
                            <th>รายการสินค้าทั้งหมด</th>
                            <th>จำนวนเงินทั้งหมด</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($order = $completed_orders->fetch_assoc()): ?>
                        <?php
                        // นับจำนวนสินค้าที่ไม่ซ้ำกันในออเดอร์นี้
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
                            <td><?= number_format($total_items); ?> products</td>
                            <td>฿<?= number_format($order['total_price'], 2); ?></td>
                            <td><span class="badge bg-success"><?= ucfirst(htmlspecialchars($order['status'])); ?></span></td>
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
