<?php
require 'config.php';
require 'functions.php';
requireAdmin();

// NOTE: This page lets the teacher create a new weekly entry.

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');
    $links       = trim($_POST['links'] ?? '');

    if ($title === '' || $description === '') {
        $error = 'Title and description are required.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO weeks (title, description, notes, links)
            VALUES (:title, :description, :notes, :links)
        ");
        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':notes'       => $notes ?: null,
            ':links'       => $links ?: null,
        ]);

        // NOTE: After inserting a new week, I go back to the main weeks page.
        header('Location: weeks.php');
        exit;
    }
}
?>


