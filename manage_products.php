<?php
require_once 'config.php';
include 'admin_navbar.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// เพิ่มสินค้าใหม่
if (isset($_POST['add_product'])) {
    // รับข้อมูลจากฟอร์ม
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];

    // จัดการการอัปโหลดไฟล์
    $image = $_FILES['image'];
    $image_name = $image['name'];
    $image_tmp_name = $image['tmp_name'];
    $image_size = $image['size'];
    $image_error = $image['error'];

    // ตรวจสอบว่าไม่มีข้อผิดพลาดในไฟล์
    if ($image_error === 0) {
        // ตรวจสอบขนาดไฟล์ไม่เกิน 5MB
        if ($image_size <= 5000000) {
            $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
            // ตรวจสอบนามสกุลไฟล์ให้เป็นรูปภาพ
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($image_extension, $allowed_extensions)) {
                // สร้างชื่อไฟล์ใหม่เพื่อไม่ให้ซ้ำกับไฟล์อื่น
                $new_image_name = uniqid('', true) . "." . $image_extension;
                $image_upload_path = 'uploads/' . $new_image_name;

                // อัปโหลดไฟล์ไปยังโฟลเดอร์
                if (move_uploaded_file($image_tmp_name, $image_upload_path)) {
                    // บันทึกข้อมูลสินค้าลงฐานข้อมูล
                    $conn->query("INSERT INTO products (name, description, price, stock_quantity, image_url, category_id, subcategory_id) 
                                VALUES ('$name', '$description', '$price', '$stock_quantity', '$image_upload_path', '$category_id', '$subcategory_id')");

                    echo "<div class='alert alert-success'>Product added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error uploading image.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Invalid image file type. Only JPG, JPEG, PNG, GIF are allowed.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Image size is too large. Max size is 5MB.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Error uploading image.</div>";
    }
}

// ดึงรายการหมวดหมู่หลักและหมวดหมู่ย่อย
$categories_result = $conn->query("SELECT * FROM categories");
$subcategories_result = $conn->query("SELECT * FROM subcategories");

// แสดงรายการสินค้าจากฐานข้อมูล
$products_result = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">Manage Products</h1>

    <!-- Form to add product -->
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" name="price" class="form-control" step="0.01" required>
        </div>
        <div class="mb-3">
            <label for="stock_quantity" class="form-label">Stock Quantity</label>
            <input type="number" name="stock_quantity" class="form-control" required>
        </div>

        <!-- Dropdown for Category -->
        <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select name="category_id" id="category_id" class="form-control" required>
                <option value="">Select Category</option>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?= $category['id']; ?>"><?= $category['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Dropdown for Subcategory -->
        <div class="mb-3">
            <label for="subcategory_id" class="form-label">Subcategory</label>
            <select name="subcategory_id" id="subcategory_id" class="form-control" required>
                <option value="">Select Subcategory</option>
                <?php while ($subcategory = $subcategories_result->fetch_assoc()): ?>
                    <option value="<?= $subcategory['id']; ?>" class="subcategory_option" data-category="<?= $subcategory['category_id']; ?>"><?= $subcategory['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Product Image</label>
            <input type="file" name="image" class="form-control" accept="image/*" required>
        </div>
        <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
    </form>

    <!-- Product List -->
    <h2 class="mt-5">Product List</h2>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Product Name</th>
            <th>Price</th>
            <th>Stock Quantity</th>
            <th>Image</th>
            <th>Category</th>
            <th>Subcategory</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($product = $products_result->fetch_assoc()): ?>
            <tr>
                <td><?= $product['name']; ?></td>
                <td>$<?= number_format($product['price'], 2); ?></td>
                <td><?= $product['stock_quantity']; ?></td>
                <td><img src="<?= $product['image_url']; ?>" alt="<?= $product['name']; ?>" width="100"></td>
                <td><?= $product['category_id']; ?></td>
                <td><?= $product['subcategory_id']; ?></td>
                <td>
                    <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    <form method="POST" action="delete_product.php" class="d-inline">
                        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                        <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script to filter subcategories based on category -->
<script>
    document.getElementById('category_id').addEventListener('change', function() {
        const selectedCategory = this.value;
        const subcategories = document.querySelectorAll('.subcategory_option');
        subcategories.forEach(function(subcategory) {
            if (subcategory.getAttribute('data-category') === selectedCategory || selectedCategory === '') {
                subcategory.style.display = 'block';
            } else {
                subcategory.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>
