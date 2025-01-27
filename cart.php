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
        // อัปเดตจำนวนสินค้าในตะกร้า
        $conn->query("UPDATE cart SET quantity = '$new_quantity' WHERE id = '$cart_id' AND user_id = '{$_SESSION['user_id']}'");
    }

    // รีเฟรชหน้า
    header('Location: cart.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">Your Shopping Cart</h1>

    <!-- ตรวจสอบหากตะกร้าว่าง -->
    <?php if ($empty_cart): ?>
        <div class="alert alert-warning" role="alert">
            Your cart is empty! Add some products to your cart.
        </div>
    <?php else: ?>
        <!-- Cart Items -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Image</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
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
                        <td>$<?= number_format($item['price'], 2); ?></td>
                        <td>
                            <!-- Update quantity form with input group -->
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="cart_id" value="<?= $item['id']; ?>">
                                <div class="input-group">
                                    <input type="number" name="quantity" value="<?= $item['quantity']; ?>" min="1" class="form-control" style="width: 0px;">
                                    <button type="submit" name="update_quantity" class="btn btn-warning btn-sm">Update</button>
                                </div>
                            </form>
                        </td>
                        <td>$<?= number_format($item['price'] * $item['quantity'], 2); ?></td>
                        <td>
                            <form method="POST" action="remove_cart_item.php">
                                <input type="hidden" name="cart_id" value="<?= $item['id']; ?>">
                                <button type="submit" name="remove" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="4" class="text-end"><strong>Total</strong></td>
                    <td colspan="2">$<?= number_format($total, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
        <a href="shoppingcart.php" class="btn btn-success">Buy more</a>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
