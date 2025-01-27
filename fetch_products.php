<?php
require_once 'config.php';

// Default query for fetching all products
$query = "SELECT * FROM products WHERE 1";

// Filter products based on search or category
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $query .= " AND name LIKE '%$search%'";
}

if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $category_id = $_GET['category_id'];
    $query .= " AND category_id = '$category_id'";
}

if (isset($_GET['subcategory_id']) && !empty($_GET['subcategory_id'])) {
    $subcategory_id = $_GET['subcategory_id'];
    $query .= " AND subcategory_id = '$subcategory_id'";
}

// Sort products
if (isset($_GET['sort_by'])) {
    $sort_by = $_GET['sort_by'];
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

// Fetch products based on the query
$products = $conn->query($query);

// Display products
while ($product = $products->fetch_assoc()) {
    echo '<div class="col-md-4 mb-4">';
    echo '<div class="card">';
    echo '<img src="' . $product['image_url'] . '" class="card-img-top" alt="' . $product['name'] . '">';
    echo '<div class="card-body">';
    echo '<h5 class="card-title">' . $product['name'] . '</h5>';
    echo '<p class="card-text">Price: $' . number_format($product['price'], 2) . '</p>';

    // Add quantity input and Add to Cart button
    echo '<form method="POST" action="shoppingcart.php">';
    echo '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
    echo '<div class="mb-3">';
    echo '<label for="quantity" class="form-label">Quantity</label>';
    echo '<input type="number" name="quantity" class="form-control" value="1" min="1" required>';
    echo '</div>';
    echo '<button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>';
    echo '</form>';

    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>
