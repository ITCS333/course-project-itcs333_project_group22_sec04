<?php
require 'config.php';
require 'functions.php';

requireLogin();

$resource_id = (int)($_POST['resource_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($resource_id <= 0 || $comment === '') {
    header('Location: view_resources.php');
    exit;
}

$stmt = $pdo->prepare('
    INSERT INTO resource_comments (resource_id, user_id, comment)
    VALUES (:resource_id, :user_id, :comment)
');
$stmt->execute([
    ':resource_id' => $resource_id,
    ':user_id'     => $_SESSION['user_id'],
    ':comment'     => $comment,
]);

header('Location: resource.php?id=' . $resource_id);
exit;
