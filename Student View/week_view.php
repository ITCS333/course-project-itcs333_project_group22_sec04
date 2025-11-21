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
