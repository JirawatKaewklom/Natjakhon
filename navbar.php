<?php include 'head.php'; ?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <img src="warehouse.jpg" alt="Website Logo" width="120" height="auto">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">มุมมองผู้ดูแล</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="shoppingcart.php">รายการสินค้า</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pending_orders.php">คำสั่งซื้อของฉัน</a>
                </li>
            </ul>
            <ul class="navbar-nav">
            <li class="nav-item">
                    <a class="nav-link" href="cart.php"><img src="shoppingcart.png" alt="Cart logo" width="25" height="auto"></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
