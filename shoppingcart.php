<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ดึงหมวดหมู่หลักและหมวดหมู่ย่อยจากฐานข้อมูล
$categories = $conn->query("SELECT * FROM categories");
$subcategories = $conn->query("SELECT * FROM subcategories");

// รับค่าจากฟอร์มกรองและเรียงลำดับ
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
$subcategory_id = isset($_GET['subcategory_id']) ? $_GET['subcategory_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : '';

// สร้างคำสั่ง SQL สำหรับกรองและเรียงลำดับ
$query = "SELECT * FROM products WHERE 1";

if ($category_id) {
    $query .= " AND category_id = '$category_id'";
}

if ($subcategory_id) {
    $query .= " AND subcategory_id = '$subcategory_id'";
}

if ($search) {
    $query .= " AND name LIKE '%$search%'";
}

if ($sort_by) {
    if ($sort_by == 'price_asc') {
        $query .= " ORDER BY price ASC";
    } elseif ($sort_by == 'price_desc') {
        $query .= " ORDER BY price DESC";
    } elseif ($sort_by == 'name_asc') {
        $query .= " ORDER BY name ASC";
    } elseif ($sort_by == 'name_desc') {
        $query .= " ORDER BY name DESC";
    }
}

// Fetch products
$products = $conn->query($query);

// Add to cart functionality
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Check if item is already in the cart
    $check_cart = $conn->query("SELECT * FROM cart WHERE product_id = '$product_id' AND user_id = '{$_SESSION['user_id']}'");

    if ($check_cart->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = quantity + $quantity WHERE product_id = '$product_id' AND user_id = '{$_SESSION['user_id']}'");
    } else {
        $conn->query("INSERT INTO cart (product_id, quantity, user_id) VALUES ('$product_id', '$quantity', '{$_SESSION['user_id']}')");
    }

    echo "<div class='alert alert-success'>Item added to cart successfully!</div>";

    // Redirect to cart.php
    header('Location: cart.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">Shopping Cart</h1>

    <!-- Search and Filter Form -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" value="<?= $search; ?>" placeholder="Search for products">
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select">
                    <option value="">Select Category</option>
                    <?php while ($category = $categories->fetch_assoc()): ?>
                        <option value="<?= $category['id']; ?>" <?= $category['id'] == $category_id ? 'selected' : ''; ?>>
                            <?= $category['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="subcategory_id" class="form-select">
                    <option value="">Select Subcategory</option>
                    <?php while ($subcategory = $subcategories->fetch_assoc()): ?>
                        <option value="<?= $subcategory['id']; ?>" <?= $subcategory['id'] == $subcategory_id ? 'selected' : ''; ?>>
                            <?= $subcategory['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort_by" class="form-select">
                    <option value="">Sort By</option>
                    <option value="price_asc" <?= $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                    <option value="price_desc" <?= $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                    <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name (A to Z)</option>
                    <option value="name_desc" <?= $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name (Z to A)</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Product List -->
    <div class="row">
        <?php while ($product = $products->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="<?= $product['image_url']; ?>" class="card-img-top" alt="<?= $product['name']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= $product['name']; ?></h5>
                        <p class="card-text"><?= $product['description']; ?></p>
                        <p class="card-text">Price: $<?= number_format($product['price'], 2); ?></p>
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                            </div>
                            <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
