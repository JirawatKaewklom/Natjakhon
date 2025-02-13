<?php
require_once 'config.php';
include 'navbar.php';

// ตรวจสอบว่า session user_id มีค่าหรือไม่ ถ้าไม่มีส่งกลับไปหน้า login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ดึงข้อมูลสินค้าจากตะกร้า
$stmt = $conn->prepare("SELECT c.id, c.product_id, p.name, p.price, c.quantity FROM cart c 
                        INNER JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// ตรวจสอบว่ามีสินค้าหรือไม่
$no_items_in_cart = $cart_items->num_rows == 0;

// คำนวณราคารวม
$total_price = 0;
while ($item = $cart_items->fetch_assoc()) {
    $total_price += $item['price'] * $item['quantity'];
}

// เมื่อกด Checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    if ($no_items_in_cart) {
        echo "กรุณาเพิ่มสินค้าลงในตะกร้าก่อนทำการสั่งซื้อ.";
        exit();
    }

    $address = htmlspecialchars($_POST['address']);
    $payment_method = htmlspecialchars($_POST['payment_method']);
    $receipt_file = null;

    // ป้องกันการอัพโหลดใบเสร็จกรณีไม่มีสินค้าหรือเลือกช่องทางชำระเงินไม่ใช่ QR Code
    if ($payment_method === "qr_code" && $no_items_in_cart) {
        echo "กรุณาเพิ่มสินค้าลงในตะกร้าก่อนทำการสั่งซื้อและเลือกการชำระเงินด้วย QR Code.";
        exit();
    }

    // จัดการอัปโหลดใบเสร็จสำหรับ QR Code Payment
    if ($payment_method === "qr_code" && isset($_FILES['receipt_upload']) && $_FILES['receipt_upload']['error'] == 0) {
        $upload_dir = "uploads/receipts/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES["receipt_upload"]["name"], PATHINFO_EXTENSION));
        $allowed_ext = ["jpg", "jpeg", "png", "pdf"];
        if (!in_array($file_ext, $allowed_ext)) {
            die("Invalid file type! Only JPG, PNG, and PDF are allowed.");
        }

        if ($_FILES["receipt_upload"]["size"] > 5 * 1024 * 1024) { // จำกัดขนาด 5MB
            die("File size exceeds 5MB limit!");
        }

        $receipt_file = $upload_dir . uniqid("receipt_", true) . "." . $file_ext;
        if (!move_uploaded_file($_FILES["receipt_upload"]["tmp_name"], $receipt_file)) {
            die("Error uploading receipt file.");
        }
    }

    // สร้างคำสั่งซื้อ
    $stmt = $conn->prepare("INSERT INTO orders (user_id, address_user, payment_method, total_price, receipt_file, status) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $status = "Pending";
    $stmt->bind_param("issdss", $user_id, $address, $payment_method, $total_price, $receipt_file, $status);

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;

        // เพิ่มสินค้าเข้าไปใน order_items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
        $cart_items = $conn->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
        $cart_items->bind_param("i", $user_id);
        $cart_items->execute();
        $result = $cart_items->get_result();

        while ($item = $result->fetch_assoc()) {
            $stmt->bind_param("iii", $order_id, $item['product_id'], $item['quantity']);
            $stmt->execute();
        }

        // ลบสินค้าออกจากตะกร้า
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // ไปที่หน้า Order Confirmation
        header('Location: order_confirmation.php?order_id=' . $order_id);
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
<?php include 'head.php' ?>
<body>
<div class="container my-5">
    <h1 class="mb-4">ดำเนินการคำสั่งซื้อ</h1>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="address" class="form-label">ที่อยู่การจัดส่ง</label>
            <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($user['address_user']); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="payment_method" class="form-label">ช่องทางการชำระเงิน</label>
            <select id="payment_method" name="payment_method" class="form-select" onchange="togglePaymentOptions()" required>
                <option value="cash_on_delivery">เก็บเงินปลายทาง</option>
                <option value="qr_code">QR Code Payment</option>
            </select>
        </div>

        <div id="cash_on_delivery_message" class="alert alert-info mb-3">
            ทางร้านจะติดต่อหาลูกค้าทางเบอร์โทรศัพท์ หากว่าติดต่อไม่ได้ รายการสั่งซื้อจะถูกยกเลิก
        </div>

        <div id="qr_code_section" class="mb-3" style="display: none;">
            <label class="form-label">สแกน QR Code นี้เพื่อชำระเงิน</label>
            <img src="qrpayment.jpg" alt="QR Code" class="img-fluid" style="max-width: 200px;">
        </div>

        <div id="upload_receipt_section" class="mb-3" style="display: none;">
            <label for="receipt_upload" class="form-label">อัพโหลดใบเสร็จ</label>
            <input type="file" name="receipt_upload" id="receipt_upload" class="form-control" accept="image/*" onchange="enablePlaceOrderButton()">
        </div>

        <button type="submit" name="checkout" id="place_order_button" class="btn btn-success" <?= $no_items_in_cart ? 'disabled' : ''; ?>>ยืนยันคำสั่งซื้อ</button>
    </form>
</div>

<script>
    function togglePaymentOptions() {
        const paymentMethod = document.getElementById("payment_method").value;
        const qrCodeSection = document.getElementById("qr_code_section");
        const uploadReceiptSection = document.getElementById("upload_receipt_section");
        const placeOrderButton = document.getElementById("place_order_button");
        const cashOnDeliveryMessage = document.getElementById("cash_on_delivery_message");

        if (paymentMethod === "qr_code") {
            if (<?= $no_items_in_cart ? 'true' : 'false' ?>) {
                alert("กรุณาเพิ่มสินค้าลงในตะกร้าก่อนทำการสั่งซื้อ.");
                placeOrderButton.disabled = true;
                qrCodeSection.style.display = "none";
                uploadReceiptSection.style.display = "none";
            } else {
                qrCodeSection.style.display = "block";
                uploadReceiptSection.style.display = "block";
                placeOrderButton.disabled = true;
                cashOnDeliveryMessage.style.display = "none";
            }
        } else {
            qrCodeSection.style.display = "none";
            uploadReceiptSection.style.display = "none";
            placeOrderButton.disabled = false;
            cashOnDeliveryMessage.style.display = "block";
        }
    }

    function enablePlaceOrderButton() {
        const receiptUpload = document.getElementById("receipt_upload");
        const placeOrderButton = document.getElementById("place_order_button");

        if (receiptUpload.files.length > 0) {
            placeOrderButton.disabled = false;
        } else {
            placeOrderButton.disabled = true;
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
