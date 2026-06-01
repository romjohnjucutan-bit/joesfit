<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'joesfit');

date_default_timezone_set('Asia/Manila');

$pdo = null;
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

define('SITE_NAME', "Joe's Fit");
define('SITE_URL', 'http://localhost/joesfit');
define('ADMIN_URL', 'http://localhost/joesfit/admin');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/joesfit/uploads/products/');
define('UPLOAD_URL', SITE_URL . '/uploads/products/');
define('CURRENCY', '₱');
define('TAX_RATE', 0);

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateTrackingCode() {
    return 'JF-' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function formatPrice($price) {
    return CURRENCY . number_format($price, 2);
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

function addNotification($pdo, $type, $title, $message, $link = null) {
    $stmt = $pdo->prepare("INSERT INTO notifications (type, title, message, link) VALUES (?, ?, ?, ?)");
    $stmt->execute([$type, $title, $message, $link]);
}
?>
