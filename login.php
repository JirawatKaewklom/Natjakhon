<?php
require_once 'config.php';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

$max_attempts = 5; // กำหนดจำนวนครั้งที่อนุญาตให้ล็อกอินผิดพลาด
$lockout_time = 300; // 5 นาที (300 วินาที)

// ตรวจสอบว่าถูกล็อกหรือไม่
if (isset($_SESSION['last_attempt_time']) && (time() - $_SESSION['last_attempt_time']) < $lockout_time) {
    $error = "คุณพยายามล็อกอินผิดพลาดหลายครั้ง กรุณารอ 5 นาที";
} else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($_SESSION['login_attempts'] >= $max_attempts) {
            $_SESSION['last_attempt_time'] = time();
            $_SESSION['error'] = "คุณพยายามล็อกอินผิดพลาดหลายครั้ง กรุณารอ 5 นาที";
        } else {
            // ตรวจสอบ CSRF Token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("CSRF token ไม่ถูกต้อง");
            }

            $username = trim($_POST['username']);
            $password = trim($_POST['password']);

            $sql = "SELECT id, username, password, role, email_verified FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if ($user['email_verified'] == 0) {
                    $_SESSION['error'] = "กรุณายืนยันบัญชีของคุณก่อนเข้าสู่ระบบ";
                } elseif (password_verify($password, $user['password'])) {
                    session_regenerate_id(true); // ป้องกัน session fixation

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_attempts'] = 0; // รีเซ็ตการนับ

                    $redirect = ($user['role'] == 'admin') ? 'dashboard.php' : 'index.php';
                    header("Location: $redirect");
                    exit();
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง";
                }
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['error'] = "ไม่พบชื่อผู้ใช้";
            }
        }
        header("Location: login.php");
        exit();
    }
}

// สร้าง CSRF Token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<?php include 'head.php'; ?>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="login.php">
                <img src="natjakhon.png" alt="ร้านนัธจกรค้าส่ง" height="auto" width="200">
            </a>
        </div>
    </nav>

    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="row justify-content-center w-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header text-center bg-dark text-white">
                        <h2>เข้าสู่ระบบ</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div class="mb-3">
                                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                                <input type="text" class="form-control" id="username" name="username" required autocomplete="off">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">รหัสผ่าน</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">เข้าสู่ระบบ</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="register.php">ยังไม่มีบัญชี? ลงทะเบียนที่นี่</a>
                        </div>
                        <div class="text-center">
                            <a href="forgot_password.php">ลืมรหัสผ่าน</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        body {
            background: url('bg.jpg') no-repeat center center fixed;
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            color: #fff;
        }

        .container {
            z-index: 10;
            margin-top: 80px; /* Adjust for fixed navbar height */
        }

        .card {
            background-color: rgba(255, 255, 255, 0.9);
        }

        .card-header {
            border-radius: 10px 10px 0 0;
        }

        .form-control {
            border-radius: 5px;
        }

        .btn-success {
            border-radius: 5px;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
