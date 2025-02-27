<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = password_hash(trim($_POST['new_password']), PASSWORD_DEFAULT);
    
    $sql = "SELECT user_id FROM password_resets WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $reset = $result->fetch_assoc();
        $user_id = $reset['user_id'];
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_password, $user_id);
        $stmt->execute();
        
        $sql = "DELETE FROM password_resets WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        $message = "รหัสผ่านของคุณถูกเปลี่ยนเรียบร้อยแล้ว";
    } else {
        $error = "ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้อง";
    }
} else {
    $token = $_GET['token'];
}
?>

<?php include 'head.php'; ?>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="row justify-content-center w-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header text-center bg-dark text-white">
                        <h2>รีเซ็ตรหัสผ่าน</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if (isset($message)): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">ตั้งรหัสผ่านใหม่</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="login.php">กลับไปที่หน้าเข้าสู่ระบบ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>