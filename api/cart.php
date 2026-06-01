<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

switch ($action) {
    case 'get':
        echo json_encode(['success' => true, 'cart' => getCart(), 'count' => getCartCount()]);
        break;

    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $name      = sanitize($_POST['name'] ?? '');
        $price     = (float)($_POST['price'] ?? 0);
        $image     = sanitize($_POST['image'] ?? '');
        $size      = sanitize($_POST['size'] ?? '');
        $color     = sanitize($_POST['color'] ?? '');
        $qty       = max(1, (int)($_POST['quantity'] ?? 1));

        if (!$productId || !$name || !$price) {
            echo json_encode(['success' => false, 'error' => 'Invalid product data']);
            break;
        }

        // Verify product exists and is in stock
        $stmt = $pdo->prepare("SELECT id, price, sale_price, stock FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $p = $stmt->fetch();

        if (!$p) {
            echo json_encode(['success' => false, 'error' => 'Product not available']);
            break;
        }
        if ($p['stock'] < $qty) {
            echo json_encode(['success' => false, 'error' => 'Insufficient stock']);
            break;
        }

        $actualPrice = $p['sale_price'] ?: $p['price'];
        addToCart($productId, $name, $actualPrice, $image, $size, $color, $qty);
        echo json_encode(['success' => true, 'cart' => getCart(), 'count' => getCartCount()]);
        break;

    case 'update':
        $key = $_POST['key'] ?? '';
        $qty = (int)($_POST['qty'] ?? 0);
        updateCartQty($key, $qty);
        $cart  = getCart();
        $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
        echo json_encode(['success' => true, 'cart' => $cart, 'count' => getCartCount(), 'total' => $total]);
        break;

    case 'remove':
        $key = $_POST['key'] ?? '';
        removeFromCart($key);
        echo json_encode(['success' => true, 'cart' => getCart(), 'count' => getCartCount()]);
        break;

    case 'clear':
        clearCart();
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
?>
