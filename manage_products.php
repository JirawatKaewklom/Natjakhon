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
    $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];

    $image = $_FILES['image'];
    $image_name = $image['name'];
    $image_tmp_name = $image['tmp_name'];
    $image_size = $image['size'];
    $image_error = $image['error'];

    if ($image_error === 0 && $image_size <= 5000000) {
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($image_extension, $allowed_extensions)) {
            $new_image_name = uniqid('', true) . '.' . $image_extension;
            $image_upload_path = 'uploads/' . $new_image_name;
            if (move_uploaded_file($image_tmp_name, $image_upload_path)) {
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock_quantity, image_url, category_id, subcategory_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdisii", $name, $description, $price, $stock_quantity, $image_upload_path, $category_id, $subcategory_id);
                $stmt->execute();
                $_SESSION['success_message'] = "เพิ่มสินค้าสำเร็จ!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }
}

// ดึงข้อมูลหมวดหมู่และหมวดหมู่ย่อย
$categories_result = $conn->query("SELECT * FROM categories");
$subcategories_result = $conn->query("SELECT * FROM subcategories");
$products_result = $conn->query("SELECT p.*, c.name AS category_name, s.name AS subcategory_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN subcategories s ON p.subcategory_id = s.id");
?>

<?php include 'head.php'; ?>
<body>
<div class="container my-5">
    <h1 class="mb-4 text-center">จัดการสินค้า</h1>

    <form method="POST" action="" enctype="multipart/form-data" class="shadow p-4 bg-light rounded">
        <div class="mb-3">
            <label class="form-label">ชื่อสินค้า</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">รายละเอียดสินค้า</label>
            <textarea name="description" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">ราคา</label>
            <input type="number" name="price" class="form-control" step="0.01" required>
        </div>
        <div class="mb-3">
            <label class="form-label">จำนวนสินค้าในสต็อก</label>
            <input type="number" name="stock_quantity" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">หมวดหมู่</label>
            <select name="category_id" id="category_id" class="form-control" required>
                <option value="">เลือกหมวดหมู่</option>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?= $category['id']; ?>"><?= $category['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">หมวดหมู่ย่อย</label>
            <select name="subcategory_id" id="subcategory_id" class="form-control" required>
                <option value="">เลือกหมวดหมู่ย่อย</option>
                <?php while ($subcategory = $subcategories_result->fetch_assoc()): ?>
                    <option value="<?= $subcategory['id']; ?>" class="subcategory_option" data-category="<?= $subcategory['category_id']; ?>"><?= $subcategory['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">อัปโหลดรูปภาพสินค้า</label>
            <input type="file" name="image" class="form-control" accept="image/*" required>
        </div>
        <button type="submit" name="add_product" class="btn btn-success w-100">เพิ่มสินค้า</button>
    </form>

    <h2 class="mt-5 text-center">รายการสินค้า</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
        <tr>
            <th>ชื่อสินค้า</th>
            <th>ราคา</th>
            <th>จำนวนสินค้า</th>
            <th>รูปภาพ</th>
            <th>หมวดหมู่</th>
            <th>หมวดหมู่ย่อย</th>
            <th>การจัดการ</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($product = $products_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= number_format($product['price'], 2); ?> บาท</td>
                <td><?= $product['stock_quantity']; ?></td>
                <td><img src="<?= $product['image_url']; ?>" width="100"></td>
                <td><?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($product['subcategory_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn btn-warning btn-sm">แก้ไข</a>
                    <form method="POST" action="delete_product.php" class="d-inline">
                        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                        <button type="submit" name="delete" class="btn btn-danger btn-sm">ลบ</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script>
    document.getElementById('category_id').addEventListener('change', function() {
        const selectedCategory = this.value;
        document.querySelectorAll('.subcategory_option').forEach(sub => {
            sub.style.display = sub.getAttribute('data-category') === selectedCategory ? 'block' : 'none';
        });
    });
</script>
</body>
</html>
