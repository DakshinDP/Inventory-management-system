<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Purchase Orders';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_order') {
    $supplierId = (int) ($_POST['supplier_id'] ?? 0);
    $orderDate = $_POST['order_date'] ?? '';
    $status = $_POST['status'] ?? 'Pending';
    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_price'] ?? [];

    if ($supplierId <= 0 || $orderDate === '') {
        $errors[] = 'Supplier and order date are required.';
    }

    if (!in_array($status, ['Pending', 'Received', 'Cancelled'], true)) {
        $errors[] = 'Invalid order status.';
    }

    $items = [];
    $total = 0.0;
    foreach ($productIds as $i => $pidRaw) {
        $pid = (int) $pidRaw;
        $qty = (int) ($quantities[$i] ?? 0);
        $price = (float) ($prices[$i] ?? 0);
        if ($pid > 0 && $qty > 0 && $price >= 0) {
            $subtotal = $qty * $price;
            $items[] = ['product_id' => $pid, 'quantity' => $qty, 'unit_price' => $price, 'subtotal' => $subtotal];
            $total += $subtotal;
        }
    }

    if (!$items) {
        $errors[] = 'Add at least one valid purchase item.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO purchase_orders (supplier_id, order_date, total_amount, status, received_at) VALUES (:supplier_id, :order_date, :total_amount, :status, :received_at)');
            $stmt->execute([
                'supplier_id' => $supplierId,
                'order_date' => $orderDate,
                'total_amount' => $total,
                'status' => $status,
                'received_at' => $status === 'Received' ? date('Y-m-d H:i:s') : null,
            ]);

            $poId = (int) $pdo->lastInsertId();
            $itemStmt = $pdo->prepare('INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price, subtotal) VALUES (:purchase_order_id, :product_id, :quantity, :unit_price, :subtotal)');
            $incStmt = $pdo->prepare('UPDATE products SET quantity = quantity + :qty WHERE product_id = :product_id');

            foreach ($items as $item) {
                $itemStmt->execute($item + ['purchase_order_id' => $poId]);
                if ($status === 'Received') {
                    $incStmt->execute(['qty' => $item['quantity'], 'product_id' => $item['product_id']]);
                }
            }

            $pdo->commit();
            header('Location: purchase_orders.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to create purchase order.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $poId = (int) ($_POST['purchase_order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'Pending';

    if ($poId > 0 && in_array($newStatus, ['Pending', 'Received', 'Cancelled'], true)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT status, received_at FROM purchase_orders WHERE purchase_order_id=:id FOR UPDATE');
            $stmt->execute(['id' => $poId]);
            $order = $stmt->fetch();

            if ($order) {
                $alreadyReceived = $order['received_at'] !== null;
                if ($newStatus === 'Received' && !$alreadyReceived) {
                    $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM purchase_order_items WHERE purchase_order_id=:id');
                    $itemsStmt->execute(['id' => $poId]);
                    $incStmt = $pdo->prepare('UPDATE products SET quantity = quantity + :qty WHERE product_id = :product_id');
                    foreach ($itemsStmt->fetchAll() as $item) {
                        $incStmt->execute(['qty' => (int) $item['quantity'], 'product_id' => (int) $item['product_id']]);
                    }
                }

                $update = $pdo->prepare('UPDATE purchase_orders SET status=:status, received_at = CASE WHEN :status = "Received" AND received_at IS NULL THEN NOW() ELSE received_at END WHERE purchase_order_id=:id');
                $update->execute(['status' => $newStatus, 'id' => $poId]);
            }

            $pdo->commit();
            header('Location: purchase_orders.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to update order status.';
        }
    }
}

$suppliers = $pdo->query('SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name')->fetchAll();
$products = $pdo->query('SELECT product_id, product_name, serial_number, unit_price FROM products ORDER BY product_name')->fetchAll();
$orders = $pdo->query('SELECT po.*, s.supplier_name FROM purchase_orders po INNER JOIN suppliers s ON s.supplier_id=po.supplier_id ORDER BY po.purchase_order_id DESC')->fetchAll();

$orderDetails = null;
if (isset($_GET['view'])) {
    $viewId = (int) $_GET['view'];
    $stmt = $pdo->prepare('SELECT poi.*, p.product_name, p.serial_number FROM purchase_order_items poi INNER JOIN products p ON p.product_id = poi.product_id WHERE poi.purchase_order_id=:id');
    $stmt->execute(['id' => $viewId]);
    $orderDetails = $stmt->fetchAll();
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>
<section>
    <h1>Purchase Orders</h1>
    <?php foreach ($errors as $error): ?><div class="alert"><?= e($error) ?></div><?php endforeach; ?>

    <form method="post" class="panel" id="po-form">
        <h2>Create Purchase Order</h2>
        <input type="hidden" name="action" value="create_order">
        <div class="grid-3">
            <div><label>Supplier</label><select name="supplier_id" required><option value="">Choose supplier</option><?php foreach ($suppliers as $supplier): ?><option value="<?= (int) $supplier['supplier_id'] ?>"><?= e($supplier['supplier_name']) ?></option><?php endforeach; ?></select></div>
            <div><label>Order Date</label><input type="date" name="order_date" required value="<?= date('Y-m-d') ?>"></div>
            <div><label>Status</label><select name="status"><option>Pending</option><option>Received</option><option>Cancelled</option></select></div>
        </div>

        <table id="po-items-table">
            <thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th><th>Action</th></tr></thead>
            <tbody>
                <tr>
                    <td><select name="product_id[]" required><option value="">Choose product</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['product_id'] ?>" data-price="<?= (float) $product['unit_price'] ?>"><?= e($product['product_name']) ?> (<?= e($product['serial_number']) ?>)</option><?php endforeach; ?></select></td>
                    <td><input type="number" name="quantity[]" min="1" value="1" required></td>
                    <td><input type="number" name="unit_price[]" min="0" step="0.01" value="0" required></td>
                    <td class="line-subtotal">0.00</td>
                    <td><button type="button" class="btn-small remove-row">Remove</button></td>
                </tr>
            </tbody>
        </table>
        <div class="po-actions">
            <button type="button" class="btn-secondary" id="add-item-row">+ Add Item</button>
            <strong>Total: <span id="po-total">0.00</span></strong>
        </div>
        <button class="btn-primary" type="submit">Create Purchase Order</button>
    </form>

    <div class="panel">
        <h2>Orders</h2>
        <table>
            <thead><tr><th>ID</th><th>Supplier</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= (int) $order['purchase_order_id'] ?></td>
                    <td><?= e($order['supplier_name']) ?></td>
                    <td><?= e($order['order_date']) ?></td>
                    <td><?= formatCurrency((float) $order['total_amount']) ?></td>
                    <td>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="purchase_order_id" value="<?= (int) $order['purchase_order_id'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <?php foreach (['Pending', 'Received', 'Cancelled'] as $status): ?>
                                    <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><a href="purchase_orders.php?view=<?= (int) $order['purchase_order_id'] ?>">View Details</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($orderDetails !== null): ?>
    <div class="panel">
        <h2>Order Details</h2>
        <table>
            <thead><tr><th>Product</th><th>Serial</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($orderDetails as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e($item['serial_number']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td><?= formatCurrency((float) $item['unit_price']) ?></td>
                    <td><?= formatCurrency((float) $item['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
