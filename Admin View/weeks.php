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
