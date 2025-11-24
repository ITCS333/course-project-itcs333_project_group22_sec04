<?php
require 'config.php';
require 'functions.php';
requireAdmin();

// NOTE: This page is used by the teacher to edit an existing week entry.

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM weeks WHERE id = :id");
$stmt->execute([':id' => $id]);
$week = $stmt->fetch();

if (!$week) {
    die('Week not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');
    $links       = trim($_POST['links'] ?? '');

    if ($title === '' || $description === '') {
        $error = 'Title and description are required.';
    } else {
        $update = $pdo->prepare("
            UPDATE weeks
            SET title = :title, description = :description, notes = :notes, links = :links
            WHERE id = :id
        ");
        $update->execute([
            ':title'       => $title,
            ':description' => $description,
            ':notes'       => $notes ?: null,
            ':links'       => $links ?: null,
            ':id'          => $id,
        ]);

        // NOTE: After saving the changes, I go back to the main weeks list.
        header('Location: weeks.php');
        exit;
    }
}
?>
