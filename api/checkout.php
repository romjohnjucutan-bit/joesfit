<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$cart = getCart();
if (empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// Validate & sanitize input
$name       = sanitize($_POST['customer_name'] ?? '');
$email      = filter_var($_POST['customer_email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone      = sanitize($_POST['customer_phone'] ?? '');
$address    = sanitize($_POST['shipping_address'] ?? '');
$city       = sanitize($_POST['shipping_city'] ?? '');
$province   = sanitize($_POST['shipping_province'] ?? '');
$zip        = sanitize($_POST['shipping_zip'] ?? '');
$payment    = sanitize($_POST['payment_method'] ?? 'cod');
$delivery   = sanitize($_POST['delivery_method'] ?? 'standard');
$shippingFee = (float)($_POST['shipping_fee'] ?? 150);
$discount   = (float)($_POST['coupon_discount'] ?? 0);
$notes      = sanitize($_POST['notes'] ?? '');

if (!$name || !$email || !$address || !$city || !$province) {
    echo json_encode(['success' => false, 'error' => 'Please fill all required fields']);
    exit;
}

// Validate payment method
$validPayments  = ['cod', 'gcash', 'maya', 'card'];
$validDeliveries = ['standard', 'express', 'pickup'];
if (!in_array($payment, $validPayments)) $payment = 'cod';
if (!in_array($delivery, $validDeliveries)) $delivery = 'standard';

// Calculate totals
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal + $shippingFee - $discount;
if ($total < 0) $total = 0;

// Generate unique tracking code
do {
    $trackingCode = generateTrackingCode();
    $check = $pdo->prepare("SELECT id FROM orders WHERE tracking_code = ?");
    $check->execute([$trackingCode]);
} while ($check->fetchColumn());

try {
    $pdo->beginTransaction();

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders (tracking_code, customer_id, customer_name, customer_email, customer_phone, 
            shipping_address, shipping_city, shipping_province, shipping_zip,
            payment_method, delivery_method, subtotal, shipping_fee, discount, total, notes, status, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    $customerId = isLoggedIn() ? $_SESSION['customer_id'] : null;
    $payStatus  = ($payment === 'cod') ? 'pending' : 'pending'; // In production, integrate actual gateway
    $stmt->execute([$trackingCode, $customerId, $name, $email, $phone, $address, $city, $province, $zip,
                    $payment, $delivery, $subtotal, $shippingFee, $discount, $total, $notes, $payStatus]);
    $orderId = $pdo->lastInsertId();

    // Insert items & deduct stock
    foreach ($cart as $item) {
        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, product_image, size, color, quantity, price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $itemStmt->execute([$orderId, $item['product_id'], $item['name'], $item['image'],
                            $item['size'], $item['color'], $item['quantity'], $item['price'],
                            $item['price'] * $item['quantity']]);

        // Deduct stock
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?")->execute([$item['quantity'], $item['product_id'], $item['quantity']]);

        // Check low stock (5 or fewer)
        $stockCheck = $pdo->prepare("SELECT name, stock FROM products WHERE id = ?");
        $stockCheck->execute([$item['product_id']]);
        $prod = $stockCheck->fetch();
        if ($prod && $prod['stock'] <= 5) {
            addNotification($pdo, 'low_stock', 'Low Stock: ' . $prod['name'],
                            'Only ' . $prod['stock'] . ' units remaining', '/joesfit/admin/pages/products.php');
        }
    }

    // Insert order history
    $pdo->prepare("INSERT INTO order_history (order_id, status, note) VALUES (?, 'pending', ?)")
        ->execute([$orderId, 'Order placed by ' . $name]);

    // Notification for admin
    addNotification($pdo, 'new_order', "New Order #$trackingCode",
                    "$name placed a new order worth " . formatPrice($total),
                    "/joesfit/admin/pages/orders.php?id=$orderId");

    $pdo->commit();
    clearCart();

    echo json_encode([
        'success'       => true,
        'tracking_code' => $trackingCode,
        'order_id'      => $orderId,
        'total'         => $total
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Order processing failed: ' . $e->getMessage()]);
}
?>
