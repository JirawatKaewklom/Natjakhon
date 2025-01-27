<?php
require_once 'config.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT * FROM users WHERE id = '$user_id'");
$user = $user_result->fetch_assoc();

// เมื่อมีการอัปเดตข้อมูลผู้ใช้
if (isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // ใช้ Google Geocoding API เพื่อแปลงที่อยู่เป็น latitude และ longitude
    $geocode_url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=YOUR_GOOGLE_MAPS_API_KEY";
    $geocode_result = json_decode(file_get_contents($geocode_url));

    if ($geocode_result->status == 'OK') {
        $latitude = $geocode_result->results[0]->geometry->location->lat;
        $longitude = $geocode_result->results[0]->geometry->location->lng;
    } else {
        $latitude = null;
        $longitude = null;
    }

    // อัปโหลดรูปภาพ (รูปหน้าปก)
    if ($_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $target_file;
        } else {
            $profile_image = $user['profile_image']; // ใช้รูปเดิมหากไม่อัปโหลดใหม่
        }
    } else {
        $profile_image = $user['profile_image']; // ใช้รูปเดิมหากไม่อัปโหลดใหม่
    }

    // อัปเดตข้อมูลในฐานข้อมูล
    $conn->query("UPDATE users SET first_name = '$first_name', last_name = '$last_name', email = '$email', phone = '$phone', address = '$address', latitude = '$latitude', longitude = '$longitude', profile_image = '$profile_image' WHERE id = '$user_id'");

    // แสดงข้อความสำเร็จ
    echo "<div class='alert alert-success'>Profile updated successfully!</div>";
    header('Location: profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">User Profile</h1>

    <div class="row">
        <div class="col-md-4">
            <!-- รูปหน้าปก -->
            <img src="<?= $user['profile_image']; ?>" alt="Profile Image" class="img-fluid rounded-circle mb-3" width="150">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="profile_image" class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" id="profile_image">
                </div>
        </div>

        <div class="col-md-8">
            <form method="POST">
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?= $user['first_name']; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?= $user['last_name']; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $user['email']; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= $user['phone']; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= $user['address_user']; ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
