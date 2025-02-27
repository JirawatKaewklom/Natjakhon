<?php
require_once 'config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $token = bin2hex(random_bytes(32));
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        
        $sql = "INSERT INTO password_resets (user_id, token) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        
        // ส่งอีเมลที่มีลิงก์รีเซ็ตรหัสผ่าน
        $reset_link = "https://jojo.infotopia.club/project/reset_password.php?token=$token";
        
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'jojo18995@gmail.com';
            $mail->Password   = 'mqgd gbmg twtz yakx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom('your-email@gmail.com', 'NatjakhonWholesale');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset';
            $mail->Body    = "Click the link to reset your password: <a href='$reset_link'>$reset_link</a>";

            $mail->send();
            $message = "ลิงก์รีเซ็ตรหัสผ่านถูกส่งไปยังอีเมลของคุณแล้ว";
        } catch (Exception $e) {
            $error = "ไม่สามารถส่งอีเมลได้: {$mail->ErrorInfo}";
        }
    } else {
        $error = "ไม่พบอีเมลนี้ในระบบ";
    }
}
?>

<?php include 'head.php'; ?>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="row justify-content-center w-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header text-center bg-dark text-white">
                        <h2>ลืมรหัสผ่าน</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if (isset($message)): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">อีเมล</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">ส่งลิงก์รีเซ็ตรหัสผ่าน</button>
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