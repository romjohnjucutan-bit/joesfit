<?php
require_once '../../includes/config.php';
require_once '../includes/admin_auth.php';
requireAdmin();
$pdo->query("UPDATE notifications SET is_read=1");
header('Location: /joesfit/admin/pages/notifications.php');
exit;
