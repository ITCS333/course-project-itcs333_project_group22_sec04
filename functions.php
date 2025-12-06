<?php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        // ممكن ترجع للـ homepage أو رسالة خطأ
        header('Location: index.php');
        exit;
    }
}
function canModify($ownerId) {
    // Admins can modify anything; owners can modify their own content
    if (isAdmin()) {
        return true;
    }
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$ownerId;
}