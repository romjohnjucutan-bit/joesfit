<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$code = strtoupper(sanitize($_POST['code'] ?? ''));
if (!$code) {
    echo json_encode(['success' => false, 'error' => 'Please enter a coupon code']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM coupons 
    WHERE code = ? AND is_active = 1 
    AND (expires_at IS NULL OR expires_at >= CURDATE())
    AND (max_uses IS NULL OR used_count < max_uses)
");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['success' => false, 'error' => 'Invalid or expired coupon code']);
    exit;
}

// Calculate cart subtotal
require_once '../includes/auth.php';
$cart = getCart();
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));

if ($subtotal < $coupon['min_order']) {
    echo json_encode(['success' => false, 'error' => 'Minimum order of ' . formatPrice($coupon['min_order']) . ' required']);
    exit;
}

$discount = $coupon['type'] === 'percent'
    ? round($subtotal * ($coupon['value'] / 100), 2)
    : min($coupon['value'], $subtotal);

echo json_encode(['success' => true, 'discount' => $discount, 'code' => $code]);
?>
