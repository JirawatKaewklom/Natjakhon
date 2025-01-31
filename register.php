<?php
require_once 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address_user = trim($_POST['address_user']);
    $phone = trim($_POST['phone']);
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = 'Username must be between 3 and 50 characters';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    }
    
    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Validate company name
    if (empty($address_user)) {
        $errors['address_user'] = 'Company name is required';
    }
    
    // Validate phone
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors['username'] = 'Username already exists';
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors['email'] = 'Email already exists';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, address_user, phone, role) VALUES (?, ?, ?, ?, ?, 'user')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $address_user, $phone);
        
        if ($stmt->execute()) {
            $success = true;
            // Automatically log in the user
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            
            // Redirect to dashboard after 2 seconds
            header("refresh:2;url=index.php");
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Wholesale System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center mb-0">สร้างบัญชี</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                            ลงทะเบียนสำเร็จ! คุณจะถูกนำไปยังแดชบอร์ด...
                            </div>
                        <?php endif; ?>

                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $errors['general']; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <!-- Username -->
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username*</label>
                                    <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                           id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                    <?php if (isset($errors['username'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['username']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">อีเมล์*</label>
                                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                           id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['email']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Password -->
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password*</label>
                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                           id="password" name="password" required>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['password']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Confirm Password -->
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password*</label>
                                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                           id="confirm_password" name="confirm_password" required>
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['confirm_password']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Address -->
                                <div class="col-md-6 mb-3">
                                    <label for="address_user" class="form-label">ที่อยู่*</label>
                                    <input type="text" class="form-control <?php echo isset($errors['address_user']) ? 'is-invalid' : ''; ?>"
                                           id="address_user" name="address_user" value="<?php echo htmlspecialchars($address_user ?? ''); ?>" required>
                                    <?php if (isset($errors['address_user'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['address_user']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Phone -->
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">เบอร์โทรศัพท์*</label>
                                    <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                           id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                                    <?php if (isset($errors['phone'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['phone']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">ลงทะเบียน</button>
                            </div>

                            <div class="text-center">
                                <p>มีบัญชีอยู่แล้วใช่ไหม? <a href="login.php">เข้าสู่ระบบที่นี่</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
