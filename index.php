<?php
ob_start();
require_once 'config.php';
include 'navbar.php';
include "head.php";

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
    <div class="container mt-5">
        <!-- ส่วนของหัวข้อและข้อมูลสรุป -->
        <h2 class="text-center">ยินดีต้อนรับ, คุณ <?= htmlspecialchars($_SESSION['username']); ?></h2>
        
        <div class="row mt-4">
            <!-- จำนวนคำสั่งซื้อทั้งหมด -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title">คำสั่งซื้อทั้งหมด</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="display-4 text-warning"><?= $row['total']; ?></p>
                    </div>
                </div>
            </div>

            <!-- จำนวนคำสั่งที่รอดำเนินการ -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title">คำสั่งที่รอดำเนินการ</h5>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status = 'pending'");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        ?>
                        <p class="display-4 text-primary"><?= $row['total']; ?></p>
                    </div>
                </div>
            </div>

            <!-- ค่าใช้จ่ายทั้งหมด -->
<div class="col-md-4">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h5 class="card-title">ยอดค้างชำระ</h5>
            <?php
            $stmt = $conn->prepare("SELECT SUM(total_price) as total_price FROM orders WHERE user_id = ? AND (status = 'pending' OR status = 'processing')");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            ?>
            <p class="display-4 text-success">฿<?= number_format($row['total_price'] ?? 0, 2); ?></p>
        </div>
    </div>
</div>
        </div>

        <!-- Include shoppingcart.php -->
        <div class="row">
            <?php include 'shoppingcart.php'; ?>
        </div>
    </div>

    <div class="text-center mb-5">
        <p class="text-danger">แจ้งเตือน: กรณีสั่งซื้อสินค้าตั้งแต่เวลา 20.00 น. เป็นต้นไป ทางร้านจะเริ่มจัดส่งสินค้าอีกครั้งในวันถัดไป</p>
        <p class="text-danger">ติดต่อ: 0832276207(ร้านนัธจกรค้าส่ง)</p>
        <a href="https://line.me/ti/g2/HjlfjlNRMYYUvYZAJWu5l1uEbEwwcVAaxp6RoQ?utm_source=invitation&utm_medium=link_copy&utm_campaign=default" target="_blank">
    <img src="https://scdn.line-apps.com/n/line_add_friends/btn/en.png" alt="เข้าร่วม OpenChat">
</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>
