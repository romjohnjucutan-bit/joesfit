<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function getAdmin() {
    return $_SESSION['admin'] ?? null;
}

function isAdmin() {
    return ($_SESSION['admin']['role'] ?? '') === 'admin';
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: /joesfit/admin/login.php');
        exit;
    }
}

function requireSuperAdmin() {
    requireAdmin();
    if (!isAdmin()) {
        header('Location: /joesfit/admin/index.php?error=access_denied');
        exit;
    }
}
?>
