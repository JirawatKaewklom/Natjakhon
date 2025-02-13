<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch cart items for the logged-in user
$cart_items = $conn->query("SELECT c.id, p.name, p.price, c.quantity, p.image_url FROM cart c INNER JOIN products p ON c.product_id = p.id WHERE c.user_id = '{$_SESSION['user_id']}'");

// ตรวจสอบว่ามีสินค้าหรือไม่
if ($cart_items->num_rows == 0) {
    $empty_cart = true;
} else {
    $empty_cart = false;
}

// Handle updating quantity
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $new_quantity = $_POST['quantity'];

    // ตรวจสอบว่าเป็นจำนวนที่ถูกต้อง
    if ($new_quantity > 0) {
        // ดึงข้อมูลของสินค้านั้นจากตะกร้า
        $stmt = $conn->prepare("SELECT p.stock_quantity, c.quantity, p.id AS product_id FROM cart c INNER JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();


        // ตรวจสอบว่า stock_quantity เพียงพอหรือไม่
        if ($new_quantity <= $item['stock_quantity'] + $item['quantity']) {
            // อัปเดตจำนวนสินค้าในตะกร้า
            $conn->query("UPDATE cart SET quantity = '$new_quantity' WHERE id = '$cart_id' AND user_id = '{$_SESSION['user_id']}'");

            // อัปเดต stock_quantity ของสินค้า
            $quantity_change = $new_quantity - $item['quantity'];
            $conn->query("UPDATE products SET stock_quantity = stock_quantity - $quantity_change WHERE id = '{$item['product_id']}'");
        }
    }

    // รีเฟรชหน้า
    header('Location: cart.php');
    exit();
}

// Handle removing cart item
if (isset($_POST['remove'])) {
    $cart_id = $_POST['cart_id'];

    // ดึงข้อมูลสินค้าจากตะกร้า
    $stmt = $conn->prepare("SELECT p.stock_quantity, c.quantity, p.id AS product_id FROM cart c INNER JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();


    // ลบสินค้าจากตะกร้า
    $conn->query("DELETE FROM cart WHERE id = '$cart_id' AND user_id = '{$_SESSION['user_id']}'");

    // เพิ่ม stock_quantity ของสินค้า
    $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$item['quantity']} WHERE id = '{$item['product_id']}'");

    // รีเฟรชหน้า
    header('Location: cart.php');
    exit();
}
?>
<?php include 'head.php' ?>
<body>
<div class="container my-5">
    <h1 class="mb-4">ตระกร้าสินค้าของคุณ</h1>

    <!-- ตรวจสอบหากตะกร้าว่าง -->
    <?php if ($empty_cart): ?>
        <div class="alert alert-warning" role="alert">
        รถเข็นของคุณว่างเปล่า! เพิ่มสินค้าลงในรถเข็นของคุณ.
        </div>
    <?php else: ?>
        <!-- Cart Items -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ชื่อสินค้า</th>
                    <th>รูปภาพ</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>ยอดสุทธิ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                while ($item = $cart_items->fetch_assoc()) {
                    $total += $item['price'] * $item['quantity'];
                    ?>
                    <tr>
                        <td><?= $item['name']; ?></td>
                        <td><img src="<?= $item['image_url']; ?>" alt="<?= $item['name']; ?>" width="100"></td>
                        <td>฿<?= number_format($item['price'], 2); ?></td>
                        <td>
                            <!-- Update quantity form with input group -->
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="cart_id" value="<?= $item['id']; ?>">
                                <div class="input-group">
                                    <input type="number" name="quantity" value="<?= $item['quantity']; ?>" min="1" class="form-control" style="width: 100px;">
                                    <button type="submit" name="update_quantity" class="btn btn-warning btn-sm">อัพเดท</button>
                                </div>
                            </form>
                        </td>
                        <td>฿<?= number_format($item['price'] * $item['quantity'], 2); ?></td>
                        <td>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="cart_id" value="<?= $item['id']; ?>">
                                <button type="submit" name="remove" class="btn btn-danger btn-sm">ลบสินค้าจากตะกร้า</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="4" class="text-end"><strong>ยอดสุทธิ</strong></td>
                    <td colspan="2">฿<?= number_format($total, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <a href="checkout.php" class="btn btn-success">ดำเนินการยืนยันคำสั่ง</a>
        <a href="shoppingcart.php" class="btn btn-success">ซื้อสินค้าเพิ่ม</a>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>