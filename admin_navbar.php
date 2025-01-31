<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
        <img src="warehouse.jpg" alt="Website Logo" width="120" height="auto">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
            <li class="nav-item">
                    <a class="nav-link" href="index.php">มุมมองผู้ใช้ธรรมดา</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_products.php">จัดการรายการสินค้า</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">รายชื่อผู้ใช้</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="manage_orders.php">Orders</a>
                </li> -->
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="nav-link">Admin : <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>