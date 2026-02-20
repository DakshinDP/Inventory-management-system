<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Products';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['product_id'] ?? 0);

    $data = [
        'product_name' => trim($_POST['product_name'] ?? ''),
        'category' => $_POST['category'] ?? '',
        'brand' => trim($_POST['brand'] ?? ''),
        'model' => trim($_POST['model'] ?? ''),
        'serial_number' => trim($_POST['serial_number'] ?? ''),
        'specifications' => trim($_POST['specifications'] ?? ''),
        'quantity' => (int) ($_POST['quantity'] ?? 0),
        'unit_price' => (float) ($_POST['unit_price'] ?? 0),
        'status' => $_POST['status'] ?? 'Available',
    ];

    if ($data['product_name'] === '' || $data['serial_number'] === '') {
        $errors[] = 'Product name and serial number are required.';
    }

    if (!in_array($data['category'], ['Computer', 'Component'], true)) {
        $errors[] = 'Invalid category.';
    }

    if (!in_array($data['status'], ['Available', 'In Use', 'Under Maintenance'], true)) {
        $errors[] = 'Invalid status.';
    }

    if ($data['quantity'] < 0 || $data['unit_price'] < 0) {
        $errors[] = 'Quantity and price must be non-negative.';
    }

    if (!$errors) {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO products (product_name, category, brand, model, serial_number, specifications, quantity, unit_price, status) VALUES (:product_name, :category, :brand, :model, :serial_number, :specifications, :quantity, :unit_price, :status)');
                $stmt->execute($data);
            }

            if ($action === 'update' && $id > 0) {
                $stmt = $pdo->prepare('UPDATE products SET product_name=:product_name, category=:category, brand=:brand, model=:model, serial_number=:serial_number, specifications=:specifications, quantity=:quantity, unit_price=:unit_price, status=:status WHERE product_id=:id');
                $stmt->execute($data + ['id' => $id]);
            }
            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Unable to save product. Ensure serial number is unique.';
        }
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = :id');
    $stmt->execute(['id' => $deleteId]);
    header('Location: products.php');
    exit;
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = :id');
    $stmt->execute(['id' => $editId]);
    $editProduct = $stmt->fetch();
}

$products = $pdo->query('SELECT * FROM products ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>
<section>
    <h1>Products</h1>
    <?php foreach ($errors as $error): ?><div class="alert"><?= e($error) ?></div><?php endforeach; ?>

    <form method="post" class="panel">
        <h2><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h2>
        <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
        <input type="hidden" name="product_id" value="<?= (int) ($editProduct['product_id'] ?? 0) ?>">
        <div class="grid-2">
            <div><label>Product Name</label><input required name="product_name" value="<?= e($editProduct['product_name'] ?? '') ?>"></div>
            <div><label>Category</label><select name="category" required>
                <?php foreach (['Computer', 'Component'] as $cat): ?>
                    <option value="<?= $cat ?>" <?= (($editProduct['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select></div>
            <div><label>Brand</label><input name="brand" value="<?= e($editProduct['brand'] ?? '') ?>"></div>
            <div><label>Model</label><input name="model" value="<?= e($editProduct['model'] ?? '') ?>"></div>
            <div><label>Serial Number</label><input required name="serial_number" value="<?= e($editProduct['serial_number'] ?? '') ?>"></div>
            <div><label>Status</label><select name="status"><?php foreach (['Available', 'In Use', 'Under Maintenance'] as $status): ?><option value="<?= $status ?>" <?= (($editProduct['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></div>
            <div><label>Quantity</label><input required type="number" min="0" name="quantity" value="<?= (int) ($editProduct['quantity'] ?? 0) ?>"></div>
            <div><label>Unit Price</label><input required type="number" min="0" step="0.01" name="unit_price" value="<?= (float) ($editProduct['unit_price'] ?? 0) ?>"></div>
        </div>
        <label>Specifications</label><textarea name="specifications" rows="3"><?= e($editProduct['specifications'] ?? '') ?></textarea>
        <button class="btn-primary" type="submit"><?= $editProduct ? 'Update' : 'Add' ?> Product</button>
    </form>

    <div class="panel">
        <div class="table-top"><h2>Product List</h2><input type="search" placeholder="Search products..." data-search-input="products-table"></div>
        <table id="products-table">
            <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Serial</th><th>Qty</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= (int) $product['product_id'] ?></td>
                    <td><?= e($product['product_name']) ?></td>
                    <td><?= e($product['category']) ?></td>
                    <td><?= e($product['serial_number']) ?></td>
                    <td><?= (int) $product['quantity'] ?></td>
                    <td><?= formatCurrency((float) $product['unit_price']) ?></td>
                    <td><?= e($product['status']) ?></td>
                    <td><a href="products.php?edit=<?= (int) $product['product_id'] ?>">Edit</a> | <a onclick="return confirm('Delete product?')" href="products.php?delete=<?= (int) $product['product_id'] ?>">Delete</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
