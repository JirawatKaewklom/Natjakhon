<?php
require_once 'config.php';
include 'navbar.php';

// Create order functionality
if (isset($_POST['create_order'])) {
    // Start a transaction
    $conn->begin_transaction();

    try {
        // Insert into orders table
        $insert_order = "INSERT INTO orders (total_price) VALUES (0)";
        $conn->query($insert_order);
        $order_id = $conn->insert_id;

        // Total price calculation
        $total_price = 0;

        // Insert cart items into order_items
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            // Get product details
            $product_query = "SELECT price FROM products WHERE id = $product_id";
            $product_result = $conn->query($product_query);
            $product = $product_result->fetch_assoc();
            $item_price = $product['price'];
            
            // Calculate item total
            $item_total = $item_price * $quantity;
            $total_price += $item_total;

            // Insert into order_items
            $insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                            VALUES ($order_id, $product_id, $quantity, $item_price)";
            $conn->query($insert_item);
        }

        // Update order total price
        $update_order = "UPDATE orders SET total_price = $total_price WHERE id = $order_id";
        $conn->query($update_order);

        // Commit transaction
        $conn->commit();

        // Clear cart
        unset($_SESSION['cart']);
        $success_message = "Order created successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error creating order: " . $e->getMessage();
    }
}

// Fetch cart items
$cart_items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $ids_string = implode(',', $product_ids);
    $sql = "SELECT * FROM products WHERE id IN ($ids_string)";
    $result = $conn->query($sql);
    
    while($row = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$row['id']];
        $row['quantity'] = $quantity;
        $row['subtotal'] = $row['price'] * $quantity;
        $cart_items[] = $row;
        $total += $row['subtotal'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <?php 
        if (isset($success_message)) {
            echo "<div class='alert alert-success'>$success_message</div>";
        }
        if (isset($error_message)) {
            echo "<div class='alert alert-danger'>$error_message</div>";
        }
        ?>
        
        <h2>Your Cart</h2>
        <?php if (empty($cart_items)): ?>
            <p>Your cart is empty</p>
        <?php else: ?>
            <form method="post">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): ?>
                        <tr>
                            <td><?php echo $item['name']; ?></td>
                            <td>$<?php echo $item['price']; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo $item['subtotal']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Total:</td>
                            <td>$<?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
                <button type="submit" name="create_order" class="btn btn-success">Create Order</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>