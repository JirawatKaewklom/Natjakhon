<?php
require_once 'config.php';

$searchQuery = $_GET['search'] ?? '';

// Default query for fetching all products
$query = "SELECT * FROM products WHERE name LIKE '%$searchQuery%'";

// Fetch products based on search
$products = $conn->query($query);

if ($products->num_rows > 0) {
    while ($product = $products->fetch_assoc()) {
        echo '
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="' . $product['image_url'] . '" class="card-img-top" alt="' . $product['name'] . '">
                <div class="card-body">
                    <h5 class="card-title">' . $product['name'] . '</h5>
                    <p class="card-text">Price: $' . number_format($product['price'], 2) . '</p>
                    <form method="POST">
                        <input type="hidden" name="product_id" value="' . $product['id'] . '">
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>';
    }
} else {
    echo '<p>No products found</p>';
}
?>
