<?php
require 'config.php';
require 'functions.php';

requireLogin();

// Load all resources
$stmt = $pdo->query('SELECT * FROM resources ORDER BY created_at DESC');
$resources = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Resources</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 2rem auto; }
        a { text-decoration: none; color: #004080; }
        a:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; }
    </style>
</head>
<body>
    <h1>Course Resources</h1>

    <p><a href="index.php">&larr; Back to Homepage</a></p>

    <?php if (empty($resources)): ?>
        <p>No resources have been added yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Link</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($resources as $res): ?>
                <tr>
                    <td>
                        <a href="resource.php?id=<?= $res['id'] ?>">
                            <?= htmlspecialchars($res['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($res['description']) ?></td>
                    <td>
                        <a href="<?= htmlspecialchars($res['link']) ?>" target="_blank">Open</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
