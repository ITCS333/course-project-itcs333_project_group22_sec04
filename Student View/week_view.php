<?php
require 'config.php';
require 'functions.php';
requireLogin();

// NOTE: This page shows the details of a single week and its discussion.

$id = (int)($_GET['id'] ?? 0);

// Fetch week data
$stmt = $pdo->prepare("SELECT * FROM weeks WHERE id = :id");
$stmt->execute([':id' => $id]);
$week = $stmt->fetch();

if (!$week) {
    die('Week not found.');
}
// Fetch comments
$commentsStmt = $pdo->prepare("
    SELECT wc.*, u.name 
    FROM week_comments wc
    JOIN users u ON wc.user_id = u.id
    WHERE wc.week_id = :id
    ORDER BY wc.created_at DESC
");
$commentsStmt->execute([':id' => $id]);
$comments = $commentsStmt->fetchAll();

// Helper: format links as clickable
function formatLinks($linksText) {
    // NOTE: This is a very simple formatter: it splits by lines and prints each line as a link.
    $lines = preg_split('/\r\n|\r|\n/', $linksText);
    $output = '<ul>';
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $escaped = htmlspecialchars($line);
        $output .= "<li><a href=\"{$escaped}\" target=\"_blank\">{$escaped}</a></li>";
    }
    $output .= '</ul>';
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($week['title']) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($week['title']) ?></h1>

    <h3>Description</h3>
    <p><?= nl2br(htmlspecialchars($week['description'])) ?></p>

    <?php if (!empty($week['notes'])): ?>
        <h3>Notes</h3>
        <p><?= nl2br(htmlspecialchars($week['notes'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($week['links'])): ?>
        <h3>Links</h3>
        <?= formatLinks($week['links']); ?>
    <?php endif; ?>

    <hr>

    <h2>Discussion</h2>

    <!-- NOTE: Any logged-in user (student or teacher) can post a comment here -->
    <form action="week_comment_add.php" method="post">
        <input type="hidden" name="week_id" value="<?= $id ?>">
        <textarea name="comment" rows="3" cols="60" required></textarea><br>
        <button type="submit">Post Comment</button>
    </form>

    <ul>
        <?php foreach ($comments as $c): ?>
            <li>
                <strong><?= htmlspecialchars($c['name']) ?></strong>
                (<?= $c['created_at'] ?>):<br>
                <?= nl2br(htmlspecialchars($c['comment'])) ?><br>

                <?php if (canModify($c['user_id'])): ?>
                    <!-- NOTE: The owner of the comment or the admin can delete it -->
                    <a href="week_comment_delete.php?id=<?= $c['id'] ?>&week=<?= $id ?>">Delete</a>
                <?php endif; ?>
            </li>
            <hr>
        <?php endforeach; ?>
    </ul>

    <p><a href="weeks.php">Back to Weekly Breakdown</a></p>
</body>
</html>
