<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['customer_id']);
}

function getCustomer() {
    return $_SESSION['customer'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /joesfit/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function getCart() {
    return $_SESSION['cart'] ?? [];
}

function getCartCount() {
    $cart = getCart();
    return array_sum(array_column($cart, 'quantity'));
}

function getCartTotal($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function addToCart($productId, $name, $price, $image, $size, $color, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $key = $productId . '_' . $size . '_' . $color;
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $productId,
            'name'       => $name,
            'price'      => $price,
            'image'      => $image,
            'size'       => $size,
            'color'      => $color,
            'quantity'   => $quantity,
        ];
    }
}

function updateCartQty($key, $qty) {
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } else {
        $_SESSION['cart'][$key]['quantity'] = $qty;
    }
}

function removeFromCart($key) {
    unset($_SESSION['cart'][$key]);
}

function clearCart() {
    $_SESSION['cart'] = [];
}
?>
