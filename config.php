<?php
session_start();

$host = 'localhost';   // عدّل حسب إعداداتك
$db   = 'itcs333_db';  // اسم قاعدة البيانات
$user = 'root';        // المستخدم
$pass = '';            // كلمة المرور لو في

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}