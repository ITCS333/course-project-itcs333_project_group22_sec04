<?php require 'config.php'; ?>
<?php require 'functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Homepage</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; }
        a { margin-right: 10px; }
    </style>
</head>
<body>
    <h1>Welcome to ITCS333 Course Page</h1>
    <p>This is the public homepage for the course.</p>

    <?php if (isLoggedIn()): ?>
        <p>Hello, <?= htmlspecialchars($_SESSION['name']) ?>!</p>
        <p>
            <a href="view_resources.php">View Course Resources</a>
            <?php if (isAdmin()): ?>
                | <a href="admin_dashboard.php">Admin Dashboard</a>
            <?php endif; ?>
            | <a href="logout.php">Logout</a>
        </p>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>
</body>
</html>
