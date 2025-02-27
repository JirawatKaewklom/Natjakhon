<?php
require_once 'config.php';

function isTokenExpired($token, $expiry = 1200) { // 20 minutes
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return true; // Invalid token format
    }
    list($token, $timestamp) = $parts;
    return (time() - $timestamp) > $expiry;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันอีเมล</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .message {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .link {
            color: #007BFF;
            text-decoration: none;
        }
        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        if (isset($_GET['token'])) {
            $token = $_GET['token'];

            if (isTokenExpired($token)) {
                echo "<div class='message'>ลิงก์ยืนยันหมดอายุแล้ว</div>";
                exit;
            }

            $stmt = $conn->prepare("SELECT id, email_verified FROM users WHERE verification_token = ?");
            if ($stmt) {
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();

                    if ($user['email_verified'] == 0) {
                        $updateStmt = $conn->prepare("UPDATE users SET email_verified = 1 WHERE verification_token = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("s", $token);
                            $updateStmt->execute();
                            echo "<div class='message'>ยืนยันอีเมลสำเร็จ คุณสามารถ <a class='link' href='login.php'>เข้าสู่ระบบ</a> ได้แล้ว</div>";
                        } else {
                            echo "<div class='message'>เกิดข้อผิดพลาดในการยืนยันอีเมล</div>";
                        }
                    } else {
                        echo "<div class='message'>อีเมลนี้ได้รับการยืนยันแล้ว</div>";
                    }
                } else {
                    echo "<div class='message'>ลิงก์ยืนยันไม่ถูกต้อง</div>";
                }
                $stmt->close();
            } else {
                echo "<div class='message'>เกิดข้อผิดพลาดในการตรวจสอบโทเค็น</div>";
            }
        } else {
            echo "<div class='message'>ไม่มีโทเค็นที่ให้มา</div>";
        }
        ?>
    </div>
</body>
</html>