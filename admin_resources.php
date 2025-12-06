<?php
require 'config.php';
require 'functions.php';

requireAdmin();

// ----- Handle form submits -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');

    if ($action === 'add' && $title && $link) {
        $stmt = $pdo->prepare(
            'INSERT INTO resources (title, description, link) VALUES (?, ?, ?)'
        );
        $stmt->execute([$title, $description, $link]);
    } elseif ($action === 'edit' && $id && $title && $link) {
        $stmt = $pdo->prepare(
            'UPDATE resources SET title = ?, description = ?, link = ? WHERE id = ?'
        );
        $stmt->execute([$title, $description, $link, $id]);
    } elseif ($action === 'delete' && $id) {
        $stmt = $pdo->prepare('DELETE FROM resources WHERE id = ?');
        $stmt->execute([$id]);
    }

    header('Location: admin_resources.php');
    exit;
}

// ----- If editing, load that resource -----
$editingResource = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare('SELECT * FROM resources WHERE id = ?');
    $stmt->execute([$editId]);
    $editingResource = $stmt->fetch();
}

// ----- Load all resources -----
$stmt = $pdo->query('SELECT * FROM resources ORDER BY created_at DESC');
$resources = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Resources - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 2rem auto; }
        h1 { margin-bottom: 1rem; }
        form { border: 1px solid #ccc; padding: 1rem; margin-bottom: 2rem; }
        label { display: block; margin-top: 0.5rem; font-weight: bold; }
        input[type="text"], input[type="url"], textarea {
            width: 100%; padding: 0.5rem; margin-top: 0.25rem;
        }
        button { margin-top: 0.75rem; padding: 0.4rem 0.8rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; }
    </style>
</head>
<body>
    <h1>Manage Course Resources</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?> (Admin)</p>
    <p><a href="admin_dashboard.php">&larr; Back to Dashboard</a></p>

    <h2><?= $editingResource ? 'Edit Resource' : 'Add New Resource' ?></h2>

    <form method="post" action="admin_resources.php">
        <input type="hidden" name="action"
               value="<?= $editingResource ? 'edit' : 'add' ?>">
        <?php if ($editingResource): ?>
            <input type="hidden" name="id"
                   value="<?= htmlspecialchars($editingResource['id']) ?>">
        <?php endif; ?>

        <label for="title">Title</label>
        <input type="text" id="title" name="title" required
               value="<?= htmlspecialchars($editingResource['title'] ?? '') ?>">

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($editingResource['description'] ?? '') ?></textarea>

        <label for="link">Resource Link</label>
        <input type="url" id="link" name="link" required
               value="<?= htmlspecialchars($editingResource['link'] ?? '') ?>">

        <button type="submit">
            <?= $editingResource ? 'Save Changes' : 'Add Resource' ?>
        </button>

        <?php if ($editingResource): ?>
            <a href="admin_resources.php" style="margin-left: 0.5rem;">Cancel Edit</a>
        <?php endif; ?>
    </form>

    <h2>Existing Resources</h2>

    <?php if (empty($resources)): ?>
        <p>No resources yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($resources as $res): ?>
                <tr>
                    <td><?= htmlspecialchars($res['title']) ?></td>
                    <td><?= htmlspecialchars($res['description']) ?></td>
                    <td><a href="<?= htmlspecialchars($res['link']) ?>" target="_blank">Open</a></td>
                    <td>
                        <a href="admin_resources.php?edit_id=<?= $res['id'] ?>">Edit</a>
                        |
                        <form method="post" action="admin_resources.php" style="display:inline;"
                              onsubmit="return confirm('Delete this resource?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $res['id'] ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
