<?php
require 'config.php';
require 'functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);

// Fetch resource
$stmt = $pdo->prepare('SELECT * FROM resources WHERE id = ?');
$stmt->execute([$id]);
$resource = $stmt->fetch();

if (!$resource) {
    die('Resource not found.');
}

// Fetch comments
$cmtStmt = $pdo->prepare('
    SELECT rc.*, u.name
    FROM resource_comments rc
    JOIN users u ON rc.user_id = u.id
    WHERE rc.resource_id = ?
    ORDER BY rc.created_at DESC
');
$cmtStmt->execute([$id]);
$comments = $cmtStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($resource['title']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 2rem auto; }
        a { text-decoration: none; color: #004080; }
        a:hover { text-decoration: underline; }
        .comment { margin-bottom: 1rem; }
        .meta { font-size: 0.9rem; color: #666; }
        textarea { width: 100%; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($resource['title']) ?></h1>

    <h3>Description</h3>
    <p><?= nl2br(htmlspecialchars($resource['description'])) ?></p>

    <h3>Link</h3>
    <p>
        <a href="<?= htmlspecialchars($resource['link']) ?>" target="_blank">
            <?= htmlspecialchars($resource['link']) ?>
        </a>
    </p>

    <hr>

    <h2>Discussion</h2>

    <form action="resource_comment_add.php" method="post">
        <input type="hidden" name="resource_id" value="<?= $id ?>">
        <textarea name="comment" rows="3" required></textarea><br>
        <button type="submit">Post Comment</button>
    </form>

    <h3>Comments</h3>

    <?php if (empty($comments)): ?>
        <p>No comments yet.</p>
    <?php else: ?>
        <?php foreach ($comments as $c): ?>
            <div class="comment">
                <div class="meta">
                    <strong><?= htmlspecialchars($c['name']) ?></strong>
                    (<?= $c['created_at'] ?>)
                </div>
                <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                <?php if (canModify($c['user_id'])): ?>
                    <div>
                        <a href="resource_comment_delete.php?id=<?= $c['id'] ?>&resource=<?= $id ?>">
                            Delete
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>

    <p><a href="view_resources.php">&larr; Back to Resources</a></p>
</body>
</html>
