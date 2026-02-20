<?php

declare(strict_types=1);

$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="brand">College Lab IMS</div>
    <nav>
        <a class="nav-item <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
        <a class="nav-item <?= $current === 'products.php' ? 'active' : '' ?>" href="products.php">Products</a>
        <a class="nav-item <?= $current === 'purchase_orders.php' ? 'active' : '' ?>" href="purchase_orders.php">Purchase Orders</a>
        <a class="nav-item <?= $current === 'suppliers.php' ? 'active' : '' ?>" href="suppliers.php">Suppliers</a>
        <a class="nav-item <?= $current === 'reports.php' ? 'active' : '' ?>" href="reports.php">Reports</a>
        <a class="nav-item logout" href="auth/logout.php">Logout</a>
    </nav>
</aside>
<main class="content">
