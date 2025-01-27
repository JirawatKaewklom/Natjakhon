<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Filter and sort parameters
$category = isset($_GET['category']) ? intval($_GET['category']) : '';
$subcategory = isset($_GET['subcategory']) ? intval($_GET['subcategory']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'p.name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 99999;

// Build dynamic query
$sql = "SELECT p.*, c.name as category_id, s.name as secondary_category_id 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories s ON p.secondary_category_id  = s.id
        WHERE p.price BETWEEN $min_price AND $max_price";

if ($category) {
    $sql .= " AND p.category_id = $category";
}
if ($subcategory) {
    $sql .= " AND p.secondary_category_id = $subcategory";
}
$sql .= " ORDER BY " . $conn->real_escape_string($sort) . " " . $conn->real_escape_string($order);

$result = $conn->query($sql);

// Fetch categories
$categories_query = "SELECT * FROM categories";
$categories_result = $conn->query($categories_query);

// Fetch subcategories
$subcategories_query = "SELECT * FROM subcategories";
$subcategories_result = $conn->query($subcategories_query);
?>

<body>
    <div class="container mt-5">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-md-3">
                <form method="get" id="filterForm">
                    <div class="card mb-4">
                        <div class="card-header">Filter & Sort</div>
                        <div class="card-body">

                            <!-- Category Filter -->
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" id="categorySelect" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php while($cat = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo $cat['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Subcategory Filter -->
                            <div class="mb-3" id="subcategoryContainer" 
                                 style="display: <?php echo $category ? 'block' : 'none'; ?>;">
                                <label class="form-label">Subcategory</label>
                                <select name="subcategory" id="subcategorySelect" class="form-select">
                                    <option value="">All Subcategories</option>
                                    <?php while($cat = $subcategories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo ($subcategory == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo $cat['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Price Range -->
                            <div class="mb-3">
                                <label class="form-label">Price Range</label>
                                <div class="input-group">
                                    <input type="number" name="min_price" class="form-control" 
                                           placeholder="Min" value="<?php echo $min_price; ?>">
                                    <input type="number" name="max_price" class="form-control" 
                                           placeholder="Max" value="<?php echo $max_price; ?>">
                                </div>
                            </div>

                            <!-- Sorting -->
                            <div class="mb-3">
                                <label class="form-label">Sort By</label>
                                <select name="sort" class="form-select">
                                    <option value="p.name" <?php echo ($sort == 'p.name') ? 'selected' : ''; ?>>Name</option>
                                    <option value="p.price" <?php echo ($sort == 'p.price') ? 'selected' : ''; ?>>Price</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Order</label>
                                <select name="order" class="form-select">
                                    <option value="ASC" <?php echo ($order == 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                                    <option value="DESC" <?php echo ($order == 'DESC') ? 'selected' : ''; ?>>Descending</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-9">
                <div class="row">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <img src="<?php echo $row['image_path']; ?>" class="card-img-top" alt="<?php echo $row['name']; ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                    <p class="card-text"><?php echo $row['description']; ?></p>
                                    <p class="card-text fw-bold">
                                        $<?php echo number_format($row['price'], 2); ?> 
                                        <span class="badge bg-secondary"><?php echo $row['category_id']; ?></span>
                                        <span class="badge bg-info"><?php echo $row['secondary_category_id']; ?></span>
                                    </p>
                                    <button type="button" 
                                            class="btn btn-primary w-100 add-to-cart" 
                                            data-product-id="<?php echo $row['id']; ?>">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Product added to cart!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding to cart');
                });
            });
        });
    });
    </script>
</body>
</html>