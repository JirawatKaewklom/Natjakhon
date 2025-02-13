<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Updated query to include role
    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Store role in session
            
            // Redirect based on role
            if ($user['role'] == 'admin') {
                header('Location: dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit();
        }
    }
    
    $error = "Invalid username or password";
}
?>

<?php include 'head.php'; ?>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="row justify-content-center w-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header text-center bg-dark text-white">
                        <h2>ร้านนัธจกรค้าส่ง</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">เข้าสู่ระบบ</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="register.php">ยังไม่มีบัญชี? ลงทะเบียนที่นี่</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        body {
            background: url('bg.jpg') no-repeat center center fixed;
            background-size: cover; /* ทำให้ภาพไม่เบลอและครอบคลุมทั้งหน้าจอ */
            background-position: center center;
            background-attachment: fixed; /* ทำให้ภาพยึดตำแหน่งคงที่ */
            filter: none; /* กำจัดเอฟเฟกต์เบลอหากมี */
            color: #fff;
        }

        .container {
            z-index: 10;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.9); /* White background with transparency */
        }

        .card-header {
            border-radius: 10px 10px 0 0;
        }

        .form-control {
            border-radius: 5px;
        }

        .btn-primary {
            border-radius: 5px;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
