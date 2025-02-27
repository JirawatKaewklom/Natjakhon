<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// สร้าง CSRF Token หากยังไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address_user']);

    // ตรวจสอบอีเมลซ้ำ
    $email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $email_check->bind_param("si", $email, $user_id);
    $email_check->execute();
    if ($email_check->get_result()->num_rows > 0) {
        die("This email is already in use.");
    }
    $email_check->close();

    // อัปโหลดรูปโปรไฟล์
    $profile_image = $user['profile_image'];
    if ($_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES["profile_image"]["tmp_name"]);
        
        if (!in_array($file_type, $allowed_types) || $_FILES["profile_image"]["size"] > 2 * 1024 * 1024) {
            die("Invalid file type or size. Only JPG, PNG, GIF under 2MB allowed.");
        }
        
        $target_dir = "uploads/";
        $file_name = uniqid() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $target_file;
        }
    }
    
    // อัปเดตข้อมูลในฐานข้อมูล
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address_user = ?, profile_image = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $address, $profile_image, $user_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: profile.php');
    exit();
}
?>

<?php include 'head.php' ?>
<body>
<div class="container my-5">
    <h1 class="text-center mb-4">โปรไฟล์ผู้ใช้</h1>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4 text-center">
            <img src="<?= htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="img-fluid rounded-circle" width="150">
        </div>
        <div class="col-md-8 col-lg-6">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <div class="mb-3">
                    <label class="form-label">ชื่อจริง</label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">นามสกุล</label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">อีเมล</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" readonly>
                        <a href="change_email.php" class="btn btn-warning">เปลี่ยน</a>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']); ?>" required pattern="\d{10}" title="กรุณากรอกหมายเลขโทรศัพท์ 10 หลัก">
                </div>
                <div class="mb-3">
                    <label class="form-label">ที่อยู่</label>
                    <textarea name="address_user" class="form-control" rows="3" required><?= htmlspecialchars($user['address_user']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">อัปโหลดรูปโปรไฟล์ใหม่</label>
                    <input type="file" name="profile_image" class="form-control">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary w-100">อัปเดตโปรไฟล์</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>