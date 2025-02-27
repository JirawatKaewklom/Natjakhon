<?php
require_once 'config.php';
require 'vendor/autoload.php'; // โหลด PHPMailer
include 'navbar.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_email'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $new_email = htmlspecialchars($_POST['new_email']);

    // ตรวจสอบอีเมลซ้ำ
    $email_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $email_check->bind_param("s", $new_email);
    $email_check->execute();
    if ($email_check->get_result()->num_rows > 0) {
        die("This email is already in use.");
    }
    $email_check->close();

    // ส่งอีเมลยืนยัน
    $verification_code = bin2hex(random_bytes(16));
    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['new_email'] = $new_email;

    $verification_link = "https://jojo.infotopia.club/project/verify_email.php?code=$verification_code";
    $subject = "Email Verification";
    $message = "กรุณาคลิกลิงก์ต่อไปนี้เพื่อยืนยันที่อยู่อีเมลใหม่ของคุณ: $verification_link";

    $mail = new PHPMailer(true);
    try {
        // ตั้งค่าเซิร์ฟเวอร์ SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // เซิร์ฟเวอร์ SMTP ของคุณ
        $mail->SMTPAuth = true;
        $mail->Username = 'jojo18995@gmail.com'; // อีเมลของคุณ
        $mail->Password = 'mqgd gbmg twtz yakx'; // รหัสผ่านอีเมลของคุณ
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // ตั้งค่าผู้ส่งและผู้รับ
        $mail->setFrom('no-reply@yourdomain.com', 'NatjakhonWholesale');
        $mail->addAddress($new_email);

        // ตั้งค่าเนื้อหาอีเมล
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();

        // อัปเดตอีเมลใหม่และตั้งค่า email_verified เป็น 0
        $stmt = $conn->prepare("UPDATE users SET email = ?, email_verified = 0 WHERE id = ?");
        $stmt->bind_param("si", $new_email, $user_id);
        $stmt->execute();
        $stmt->close();

        echo "<script>   
                    window.location.href = 'login.php';
                    window.close();
        </script>";
    } catch (Exception $e) {
        echo "Failed to send verification email. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<?php include 'head.php' ?>
<body>
<div class="container my-5">
    <h1 class="text-center mb-4">เปลี่ยนอีเมล</h1>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <div class="mb-3">
                    <label class="form-label">อีเมลใหม่</label>
                    <input type="email" name="new_email" class="form-control" required>
                </div>
                <button type="submit" name="change_email" class="btn btn-primary w-100">ส่งอีเมลยืนยัน</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>