<?php
require 'config.php';
require 'functions.php';

requireLogin();

$comment_id  = (int)($_GET['id'] ?? 0);
$resource_id = (int)($_GET['resource'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM resource_comments WHERE id = ?');
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if ($comment && canModify($comment['user_id'])) {
    $del = $pdo->prepare('DELETE FROM resource_comments WHERE id = ?');
    $del->execute([$comment_id]);
}

header('Location: resource.php?id=' . $resource_id);
exit;
