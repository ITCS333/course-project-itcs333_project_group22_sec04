<?php
require 'config.php';
require 'functions.php';
requireLogin();

// NOTE: This page deletes a week comment if the current user is allowed to.

$comment_id = (int)($_GET['id'] ?? 0);
$week_id    = (int)($_GET['week'] ?? 0);

// Fetch the comment to check ownership
$stmt = $pdo->prepare("SELECT * FROM week_comments WHERE id = :id");
$stmt->execute([':id' => $comment_id]);
$comment = $stmt->fetch();

if ($comment && canModify($comment['user_id'])) {
    $del = $pdo->prepare("DELETE FROM week_comments WHERE id = :id");
    $del->execute([':id' => $comment_id]);
    // NOTE: If user is not owner or admin, I do nothing silently.
}

header('Location: week_view.php?id=' . $week_id);
exit;