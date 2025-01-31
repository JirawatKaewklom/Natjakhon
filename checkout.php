<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลที่อยู่จากผู้ใช้ในฐานข้อมูล
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ดึงข้อมูลสินค้าที่อยู่ในตะกร้าของผู้ใช้
$cart_items = $conn->query("SELECT c.id, p.name, p.price, c.quantity FROM cart c INNER JOIN products p ON c.product_id = p.id WHERE c.user_id = '$user_id'");

// คำนวณราคารวมของสินค้าทั้งหมด
$total_price = 0;
while ($item = $cart_items->fetch_assoc()) {
    $total_price += $item['price'] * $item['quantity'];
}

// เมื่อผู้ใช้ยืนยันการสั่งซื้อ
if (isset($_POST['checkout'])) {
    $address = htmlspecialchars($_POST['address']);
    $payment_method = htmlspecialchars($_POST['payment_method']);
    $receipt_file = null;

    // จัดการไฟล์ใบเสร็จสำหรับ QR Code Payment
    if ($payment_method === "qr_code" && isset($_FILES['receipt_upload'])) {
        $upload_dir = "uploads/receipts/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // สร้างโฟลเดอร์หากยังไม่มี
        }
        $receipt_file = $upload_dir . basename($_FILES["receipt_upload"]["name"]);
        if (!move_uploaded_file($_FILES["receipt_upload"]["tmp_name"], $receipt_file)) {
            die("Error uploading receipt file.");
        }
    }

    // สร้างคำสั่งซื้อในฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO orders (user_id, address_user, payment_method, total_price, receipt_file, status) VALUES (?, ?, ?, ?, ?, ?)");
    $status = "Pending";
    $stmt->bind_param("issdss", $user_id, $address, $payment_method, $total_price, $receipt_file, $status);

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;

        // ย้ายข้อมูลจากตะกร้าไปที่คำสั่งซื้อ
        $cart_items = $conn->query("SELECT * FROM cart WHERE user_id = '$user_id'");
        while ($item = $cart_items->fetch_assoc()) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt_item->bind_param("iii", $order_id, $product_id, $quantity);
            $stmt_item->execute();
        }

        // ลบสินค้าจากตะกร้า
        $conn->query("DELETE FROM cart WHERE user_id = '$user_id'");

        // เปลี่ยนเส้นทางไปยังหน้าการยืนยันคำสั่งซื้อ
        header('Location: order_confirmation.php?order_id=' . $order_id);
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">ดำเนินการคำสั่งซื้อ</h1>

    <!-- รายการสินค้าจากตะกร้า -->
    <div class="table-responsive mb-4">
        <table class="table">
            <thead>
                <tr>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>ยอดสุทธิ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ดึงข้อมูลจากตะกร้า
                $cart_items = $conn->query("SELECT c.id, p.name, p.price, c.quantity FROM cart c INNER JOIN products p ON c.product_id = p.id WHERE c.user_id = '$user_id'");
                while ($item = $cart_items->fetch_assoc()) {
                    $total_item_price = $item['price'] * $item['quantity'];
                    echo "<tr>
                            <td>{$item['name']}</td>
                            <td>\${$item['price']}</td>
                            <td>{$item['quantity']}</td>
                            <td>\${$total_item_price}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- แสดงราคาสินค้ารวม -->
    <div class="mb-4">
        <h4>ยอดสุทธิ: ฿<?= number_format($total_price, 2); ?></h4>
    </div>

    <!-- ฟอร์มการกรอกข้อมูลการชำระเงิน -->
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

        <!-- ข้อความสำหรับเก็บเงินปลายทาง -->
        <div id="cash_on_delivery_message" class="alert alert-info mb-3" style="display: block;">
            ทางร้านจะติดต่อหาลูกค้าทางเบอร์โทรศัพท์ หากว่าติดต่อไม่ได้หรือไม่มีการติดต่อกลับ รายการสั่งซื้อจะถูกยกเลิก
        </div>

        <!-- QR Code Display -->
        <div id="qr_code_section" class="mb-3" style="display: none;">
            <label for="qr_code_image" class="form-label">สแกน QR Code นี้เพื่อชำระเงิน</label>
            <img src="qrpayment.jpg" alt="QR Code" class="img-fluid" style="max-width: 200px;">
        </div>

        <!-- Upload Receipt -->
        <div id="upload_receipt_section" class="mb-3" style="display: none;">
            <label for="receipt_upload" class="form-label">อัพโหลดใบเสร็จการชำระเงิน</label>
            <input type="file" name="receipt_upload" id="receipt_upload" class="form-control" accept="image/*" onchange="enablePlaceOrderButton()">
        </div>

        <!-- ปุ่ม Place Order (แสดงเสมอ) -->
        <button type="submit" name="checkout" id="place_order_button" class="btn btn-success">ยืนยันคำสั่งซื้อ</button>
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
            qrCodeSection.style.display = "block";
            uploadReceiptSection.style.display = "block";
            placeOrderButton.style.display = "none";
            cashOnDeliveryMessage.style.display = "none"; // Hide message for QR code payment
        } else {
            qrCodeSection.style.display = "none";
            uploadReceiptSection.style.display = "none";
            placeOrderButton.style.display = "block";
            cashOnDeliveryMessage.style.display = "block"; // Show message for cash on delivery
        }
    }

    function enablePlaceOrderButton() {
        const receiptUpload = document.getElementById("receipt_upload");
        const placeOrderButton = document.getElementById("place_order_button");

        if (receiptUpload.files.length > 0) {
            placeOrderButton.style.display = "block";
        } else {
            placeOrderButton.style.display = "none";
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
