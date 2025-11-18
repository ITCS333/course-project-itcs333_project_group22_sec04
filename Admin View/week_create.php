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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Week</title>
</head>
<body>
    <h1>Add New Week</h1>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
 <form method="post">
        <label>Title (e.g. "Week 1: Introduction to HTML"):<br>
            <input type="text" name="title" required>
        </label><br><br>

        <label>Description:<br>
            <textarea name="description" rows="5" cols="60" required></textarea>
        </label><br><br>

        <label>Notes (optional):<br>
            <textarea name="notes" rows="4" cols="60"></textarea>
        </label><br><br>

        <label>Links (optional, one URL per line):<br>
            <textarea name="links" rows="4" cols="60"></textarea>
        </label><br><br>

        <button type="submit">Create Week</button>
    </form>

    <p><a href="weeks.php">Back to Weekly Breakdown</a></p>
</body>
</html>


