<?php
require 'config.php';
require 'functions.php';

requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - ITCS333</title>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?> (Admin)</p>

    <ul>
        <li><a href="admin_students.php">Manage Students</a></li>
        <li><a href="admin_resources.php">Manage Resources</a></li>
        <li><a href="admin_change_password.php">Change My Password</a></li>
        <li><a href="index.php">Go to Homepage</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</body>
</html>
