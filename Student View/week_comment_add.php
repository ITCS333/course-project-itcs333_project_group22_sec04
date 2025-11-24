<?php
require 'config.php';
require 'functions.php';
requireLogin();

// NOTE: This file handles inserting a new comment for a specific week.

$week_id = (int)($_POST['week_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($week_id <= 0 || $comment === '') {
    // NOTE: Simple validation: if something is wrong, I just send the user back.
    header('Location: weeks.php');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO week_comments (week_id, user_id, comment)
    VALUES (:week_id, :user_id, :comment)
");
$stmt->execute([
    ':week_id' => $week_id,
    ':user_id' => $_SESSION['user_id'],
    ':comment' => $comment,
]);

header('Location: week_view.php?id=' . $week_id);
exit;
