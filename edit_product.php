<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลสินค้าจากฐานข้อมูลเพื่อทำการแก้ไข
$product_id = $_GET['id'];
$product_query = $conn->query("SELECT * FROM products WHERE id = '$product_id'");
$product = $product_query->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// ดึงข้อมูลหมวดหมู่หลัก (Categories)
$categories_query = $conn->query("SELECT * FROM categories");

// ดึงข้อมูลหมวดหมู่ย่อย (Subcategories) สำหรับหมวดหมู่ที่เลือกในตอนนี้
$subcategories_query = $conn->query("SELECT * FROM subcategories WHERE category_id = '".$product['category_id']."'");

// แก้ไขข้อมูลสินค้าหลังจากฟอร์มถูกส่ง
if (isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    
    // การจัดการการอัปโหลดไฟล์รูปภาพ
    $image_url = $product['image_url'];  // ใช้รูปภาพเดิมเป็นค่าเริ่มต้น

    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
        $image_name = $_FILES['image_url']['name'];
        $image_tmp_name = $_FILES['image_url']['tmp_name'];
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($image_extension, $allowed_extensions)) {
            $new_image_name = uniqid('product_', true) . '.' . $image_extension;
            $upload_dir = 'uploads/';
            $upload_path = $upload_dir . $new_image_name;

            if (move_uploaded_file($image_tmp_name, $upload_path)) {
                $image_url = $upload_path;
            } else {
                echo "<div class='alert alert-danger'>Error uploading image.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Invalid image file type. Allowed types are JPG, JPEG, PNG, GIF.</div>";
        }
    }

    $update_query = "UPDATE products SET 
                        name = '$name', 
                        description = '$description', 
                        price = '$price', 
                        stock_quantity = '$stock_quantity',
                        category_id = '$category_id',
                        subcategory_id = '$subcategory_id',
                        image_url = '$image_url'
                    WHERE id = '$product_id'";

    if ($conn->query($update_query)) {
        header('Location: manage_products.php');
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error updating product: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1>Edit Product</h1>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= $product['name']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?= $product['description']; ?></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" class="form-control" id="price" name="price" value="<?= $product['price']; ?>" step="0.01" required>
        </div>
        <div class="mb-3">
            <label for="stock_quantity" class="form-label">Stock Quantity</label>
            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?= $product['stock_quantity']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <?php while ($category = $categories_query->fetch_assoc()): ?>
                    <option value="<?= $category['id']; ?>" <?= ($category['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                        <?= $category['name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="subcategory_id" class="form-label">Subcategory</label>
            <select class="form-select" id="subcategory_id" name="subcategory_id" required>
                <?php while ($subcategory = $subcategories_query->fetch_assoc()): ?>
                    <option value="<?= $subcategory['id']; ?>" <?= ($subcategory['id'] == $product['subcategory_id']) ? 'selected' : ''; ?>>
                        <?= $subcategory['name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="image_url" class="form-label">Product Image</label>
            <input type="file" class="form-control" id="image_url" name="image_url">
            <small>Leave blank to keep current image. Current image:</small><br>
            <img src="<?= $product['image_url']; ?>" alt="Current Image" width="150">
        </div>
        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
    </form>
</div>
<script>
    document.getElementById('category_id').addEventListener('change', function () {
        const categoryId = this.value;
        const subcategorySelect = document.getElementById('subcategory_id');

        fetch(`get_subcategories.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                subcategorySelect.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        subcategorySelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.textContent = 'No subcategories available';
                    option.disabled = true;
                    subcategorySelect.appendChild(option);
                }
            })
            .catch(error => console.error('Error fetching subcategories:', error));
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
