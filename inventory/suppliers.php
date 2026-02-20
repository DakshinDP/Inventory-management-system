<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Suppliers';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['supplier_id'] ?? 0);

    $data = [
        'supplier_name' => trim($_POST['supplier_name'] ?? ''),
        'contact_person' => trim($_POST['contact_person'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
    ];

    if ($data['supplier_name'] === '' || $data['email'] === '') {
        $errors[] = 'Supplier name and email are required.';
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!$errors) {
        if ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (:supplier_name, :contact_person, :phone, :email, :address)');
            $stmt->execute($data);
        }

        if ($action === 'update' && $id > 0) {
            $stmt = $pdo->prepare('UPDATE suppliers SET supplier_name=:supplier_name, contact_person=:contact_person, phone=:phone, email=:email, address=:address WHERE supplier_id=:id');
            $stmt->execute($data + ['id' => $id]);
        }

        header('Location: suppliers.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM suppliers WHERE supplier_id=:id');
    $stmt->execute(['id' => $deleteId]);
    header('Location: suppliers.php');
    exit;
}

$editSupplier = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE supplier_id=:id');
    $stmt->execute(['id' => $editId]);
    $editSupplier = $stmt->fetch();
}

$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>
<section>
    <h1>Suppliers</h1>
    <?php foreach ($errors as $error): ?><div class="alert"><?= e($error) ?></div><?php endforeach; ?>

    <form method="post" class="panel">
        <h2><?= $editSupplier ? 'Edit Supplier' : 'Add Supplier' ?></h2>
        <input type="hidden" name="action" value="<?= $editSupplier ? 'update' : 'create' ?>">
        <input type="hidden" name="supplier_id" value="<?= (int) ($editSupplier['supplier_id'] ?? 0) ?>">
        <div class="grid-2">
            <div><label>Supplier Name</label><input required name="supplier_name" value="<?= e($editSupplier['supplier_name'] ?? '') ?>"></div>
            <div><label>Contact Person</label><input name="contact_person" value="<?= e($editSupplier['contact_person'] ?? '') ?>"></div>
            <div><label>Phone</label><input name="phone" value="<?= e($editSupplier['phone'] ?? '') ?>"></div>
            <div><label>Email</label><input required type="email" name="email" value="<?= e($editSupplier['email'] ?? '') ?>"></div>
        </div>
        <label>Address</label><textarea name="address" rows="3"><?= e($editSupplier['address'] ?? '') ?></textarea>
        <button class="btn-primary" type="submit"><?= $editSupplier ? 'Update' : 'Add' ?> Supplier</button>
    </form>

    <div class="panel">
        <h2>Supplier List</h2>
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?= (int) $supplier['supplier_id'] ?></td>
                    <td><?= e($supplier['supplier_name']) ?></td>
                    <td><?= e($supplier['contact_person']) ?></td>
                    <td><?= e($supplier['phone']) ?></td>
                    <td><?= e($supplier['email']) ?></td>
                    <td><?= e($supplier['address']) ?></td>
                    <td><a href="suppliers.php?edit=<?= (int) $supplier['supplier_id'] ?>">Edit</a> | <a onclick="return confirm('Delete supplier?')" href="suppliers.php?delete=<?= (int) $supplier['supplier_id'] ?>">Delete</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
