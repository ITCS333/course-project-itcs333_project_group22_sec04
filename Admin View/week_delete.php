<?php
require 'config.php';
require 'functions.php';
requireAdmin();

// NOTE: This page deletes a week entry. Only admin can access it.
$id = (int)($_GET['id'] ?? 0);

$del = $pdo->prepare("DELETE FROM weeks WHERE id = :id");
$del->execute([':id' => $id]);

// NOTE: When a week is deleted, its comments are deleted automatically because of ON DELETE CASCADE.
header('Location: weeks.php');
exit;
