<?php
require 'config.php';
require 'functions.php';
requireLogin();

// NOTE: This page shows the list of all weeks in the course.
// Students can only read, admin can also create/edit/delete weeks.
$stmt = $pdo->query("SELECT * FROM weeks ORDER BY created_at ASC");
$weeks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weekly Breakdown</title>
</head>
<body>
    <h1>Weekly Breakdown</h1>
    <?php if (isAdmin()): ?>
        <!-- NOTE: Only the teacher/admin can create a new week entry -->
        <p><a href="week_create.php">+ Add New Week</a></p>
    <?php endif; ?>
    
    <?php if (empty($weeks)): ?>
        <p>No weeks have been added yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($weeks as $w): ?>
                <li>
                    <a href="week_view.php?id=<?= $w['id'] ?>">
                        <?= htmlspecialchars($w['title']) ?>
                    </a>
<?php if (isAdmin()): ?>
                        <!-- NOTE: Admin-only tools for editing/deleting weeks -->
                        | <a href="week_edit.php?id=<?= $w['id'] ?>">Edit</a>
                        | <a href="week_delete.php?id=<?= $w['id'] ?>"
                             onclick="return confirm('Delete this week entry?');">
                             Delete
                          </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="index.php">Back to Homepage</a></p>
</body>
</html>

