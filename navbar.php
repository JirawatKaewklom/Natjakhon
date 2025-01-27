
<?php include 'head.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Wholesale System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="shoppingcart.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pending_orders.php">Orders</a>
                </li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Admin Panel</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
            <li class="nav-item">
                    <a class="nav-link" href="cart.php"><img src="cart.png" alt="Website Logo" width="20" height="20"></a>
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