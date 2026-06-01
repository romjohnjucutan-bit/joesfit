<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$cartCount = getCartCount();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? $pageTitle . " — " . SITE_NAME : SITE_NAME . " | Premium Jackets" ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="/joesfit/assets/css/style.css">
  <script>
    // Apply dark mode before render to prevent flash
    (function() {
      const t = localStorage.getItem('theme');
      if (t) document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="/joesfit/" class="nav-logo">JOE'S<span>FIT</span></a>

  <ul class="nav-links">
    <li><a href="/joesfit/" class="<?= $currentPage === 'index' ? 'active' : '' ?>">Home</a></li>
    <li><a href="/joesfit/shop.php" class="<?= $currentPage === 'shop' ? 'active' : '' ?>">Shop</a></li>
    <li><a href="/joesfit/shop.php?category=varsity-jackets">Varsity</a></li>
    <li><a href="/joesfit/shop.php?category=bomber-jackets">Bomber</a></li>
    <li><a href="/joesfit/track.php" class="<?= $currentPage === 'track' ? 'active' : '' ?>">Track Order</a></li>
  </ul>

  <div class="nav-actions">
    <!-- Search -->
    <form id="searchForm" style="display:flex;align-items:center;gap:0.3rem;">
      <input id="searchInput" type="text" placeholder="Search..." 
             style="padding:0.4rem 0.8rem;border:1px solid var(--border);border-radius:6px;background:var(--bg);color:var(--text);font-size:0.82rem;width:130px;outline:none;">
    </form>

    <!-- Cart -->
    <button class="nav-btn" data-cart-open aria-label="Cart">
      🛍️
      <span class="cart-badge"><?= $cartCount ?></span>
    </button>

    <!-- Dark mode -->
    <div class="dark-toggle" id="darkToggle" role="button" aria-label="Toggle dark mode"></div>

    <!-- Account (only for logged-in users) -->
    <?php if (isLoggedIn()): ?>
      <a href="/joesfit/account.php" class="nav-btn" title="My Account">👤</a>
    <?php endif; ?>
  </div>
</nav>

<!-- CART SIDEBAR -->
<div class="cart-overlay" id="cartOverlay"></div>
<div class="cart-sidebar" id="cartSidebar">
  <div class="cart-header">
    <h2>Your Cart <span class="cart-count-text" style="font-size:1rem;color:var(--accent)">(<?= $cartCount ?>)</span></h2>
    <button class="cart-close" id="cartClose">✕</button>
  </div>
  <div class="cart-items" id="cartItems">
    <div class="loading-spinner"></div>
  </div>
  <div class="cart-footer">
    <div class="cart-total">
      <span>Total</span>
      <span class="cart-total-amount" id="cartTotal">₱0.00</span>
    </div>
    <button class="btn btn-primary btn-full" id="checkoutBtn">Proceed to Checkout →</button>
  </div>
</div>

<!-- CHECKOUT MODAL -->
<div class="modal-overlay" id="checkoutModal">
  <div class="modal" style="max-width:650px">
    <div class="modal-header">
      <h2>Checkout</h2>
      <button class="cart-close" id="checkoutClose">✕</button>
    </div>
    <form id="checkoutForm">
    <div class="modal-body">
      <h3 style="font-size:0.85rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-muted);margin-bottom:1rem">Shipping Details</h3>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="customer_name" class="form-control" required 
                 value="<?= isLoggedIn() ? htmlspecialchars(getCustomer()['name'] ?? '') : '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="customer_email" class="form-control" required
                 value="<?= isLoggedIn() ? htmlspecialchars(getCustomer()['email'] ?? '') : '' ?>">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="tel" name="customer_phone" class="form-control" placeholder="09XX XXX XXXX">
        </div>
        <div class="form-group">
          <label class="form-label">ZIP Code</label>
          <input type="text" name="shipping_zip" class="form-control" placeholder="1100">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Street Address</label>
        <input type="text" name="shipping_address" class="form-control" required placeholder="House #, Street, Barangay">
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">City / Municipality</label>
          <input type="text" name="shipping_city" class="form-control" required placeholder="Quezon City">
        </div>
        <div class="form-group">
          <label class="form-label">Province / Region</label>
          <input type="text" name="shipping_province" class="form-control" required placeholder="Metro Manila">
        </div>
      </div>

      <hr class="divider">
      <h3 style="font-size:0.85rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-muted);margin-bottom:1rem">Delivery Method</h3>
      <div class="delivery-options">
        <div class="delivery-option selected" data-value="standard" data-fee="150">
          📦 Standard<br><small>3-5 days</small>
          <div class="delivery-price">₱150</div>
        </div>
        <div class="delivery-option" data-value="express" data-fee="250">
          🚀 Express<br><small>1-2 days</small>
          <div class="delivery-price">₱250</div>
        </div>
        <div class="delivery-option" data-value="pickup" data-fee="0">
          🏪 Pickup<br><small>Same day</small>
          <div class="delivery-price">FREE</div>
        </div>
      </div>
      <input type="hidden" name="delivery_method" id="deliveryMethod" value="standard">
      <input type="hidden" name="shipping_fee" id="shippingFee" value="150">

      <hr class="divider">
      <h3 style="font-size:0.85rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-muted);margin-bottom:1rem">Payment Method</h3>
      <div class="payment-options">
        <div class="payment-option selected" data-value="cod">
          <span class="payment-icon">💵</span> Cash on Delivery
        </div>
        <div class="payment-option" data-value="gcash">
          <span class="payment-icon">📱</span> GCash
        </div>
        <div class="payment-option" data-value="maya">
          <span class="payment-icon">💳</span> Maya
        </div>
        <div class="payment-option" data-value="card">
          <span class="payment-icon">🏦</span> Credit/Debit Card
        </div>
      </div>
      <input type="hidden" name="payment_method" id="paymentMethod" value="cod">

      <hr class="divider">
      <h3 style="font-size:0.85rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-muted);margin-bottom:1rem">Coupon Code</h3>
      <div style="display:flex;gap:0.5rem">
        <input type="text" id="couponCode" class="form-control" placeholder="Enter coupon code" style="flex:1">
        <button type="button" id="applyCoupon" class="btn btn-outline btn-sm">Apply</button>
      </div>
      <input type="hidden" name="coupon_discount" id="couponDiscount" value="0">

      <hr class="divider">
      <h3 style="font-size:0.85rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-muted);margin-bottom:1rem">Order Summary</h3>
      <div style="display:flex;flex-direction:column;gap:0.5rem;font-size:0.9rem">
        <div style="display:flex;justify-content:space-between"><span>Subtotal</span><strong id="summarySubtotal">₱0.00</strong></div>
        <div style="display:flex;justify-content:space-between"><span>Shipping</span><strong id="summaryFee">₱150.00</strong></div>
        <div style="display:flex;justify-content:space-between"><span>Discount</span><strong id="summaryDiscount">₱0.00</strong></div>
        <hr class="divider">
        <div style="display:flex;justify-content:space-between;font-size:1.1rem"><strong>Total</strong><strong style="color:var(--accent)" id="summaryTotal">₱150.00</strong></div>
      </div>

      <div class="form-group mt-2">
        <label class="form-label">Order Notes (optional)</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary btn-full btn-lg">
        Place Order 🎉
      </button>
    </div>
    </form>
  </div>
</div>

<!-- ORDER SUCCESS MODAL -->
<div class="modal-overlay" id="successModal">
  <div class="modal success-modal">
    <div class="modal-body" style="padding:2rem;text-align:center">
      <div class="success-icon">✅</div>
      <h2 style="font-family:var(--font-display);font-size:2.5rem;letter-spacing:1px;margin-bottom:0.5rem">ORDER PLACED!</h2>
      <p style="color:var(--text-muted)">Thank you for your purchase! Your tracking code is:</p>
      <div class="tracking-code-display" id="trackingCodeDisplay">JF-0000</div>
      <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:2rem">Save this code to track your order status</p>
      <div style="display:flex;gap:1rem;justify-content:center">
        <button class="btn btn-primary" id="trackOrderBtn">Track My Order</button>
        <button class="btn btn-outline" id="successClose">Continue Shopping</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>
