<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Reports';

$totalStockValue = (float) $pdo->query('SELECT IFNULL(SUM(quantity * unit_price),0) FROM products')->fetchColumn();
$lowStock = $pdo->query('SELECT product_name, serial_number, quantity FROM products WHERE quantity < 5 ORDER BY quantity ASC')->fetchAll();
$orderStatus = $pdo->query('SELECT status, COUNT(*) as total FROM purchase_orders GROUP BY status')->fetchAll();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>
<section>
    <h1>Reports</h1>
    <div class="cards one-col">
        <article class="card"><h3>Total Stock Value</h3><p><?= formatCurrency($totalStockValue) ?></p></article>
    </div>

    <div class="panel">
        <h2>Low Stock Items (Quantity &lt; 5)</h2>
        <table>
            <thead><tr><th>Product</th><th>Serial Number</th><th>Quantity</th></tr></thead>
            <tbody>
            <?php if (!$lowStock): ?>
                <tr><td colspan="3">No low stock items.</td></tr>
            <?php else: ?>
                <?php foreach ($lowStock as $item): ?>
                    <tr><td><?= e($item['product_name']) ?></td><td><?= e($item['serial_number']) ?></td><td><?= (int) $item['quantity'] ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h2>Orders by Status</h2>
        <table>
            <thead><tr><th>Status</th><th>Total Orders</th></tr></thead>
            <tbody>
                <?php foreach ($orderStatus as $row): ?>
                    <tr><td><?= e($row['status']) ?></td><td><?= (int) $row['total'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
