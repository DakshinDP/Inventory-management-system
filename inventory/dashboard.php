<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Dashboard';

$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalSuppliers = (int) $pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
$totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn();
$totalValue = (float) $pdo->query('SELECT IFNULL(SUM(quantity * unit_price), 0) FROM products')->fetchColumn();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>
<section>
    <h1>Dashboard</h1>
    <div class="cards">
        <article class="card"><h3>Total Products</h3><p><?= $totalProducts ?></p></article>
        <article class="card"><h3>Total Suppliers</h3><p><?= $totalSuppliers ?></p></article>
        <article class="card"><h3>Total Purchase Orders</h3><p><?= $totalOrders ?></p></article>
        <article class="card"><h3>Total Inventory Value</h3><p><?= formatCurrency($totalValue) ?></p></article>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
