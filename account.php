<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();
$pageTitle = 'My Account';
$customer = getCustomer();

$orders = $pdo->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC");
$orders->execute([$_SESSION['customer_id']]);
$orders = $orders->fetchAll();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /joesfit/login.php');
    exit;
}

// Handle profile update
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone'] ?? '');
    $pdo->prepare("UPDATE customers SET name=?,phone=? WHERE id=?")->execute([$name,$phone,$_SESSION['customer_id']]);
    $_SESSION['customer']['name'] = $name;
    $msg = 'Profile updated!';
}

$custFull = $pdo->prepare("SELECT * FROM customers WHERE id=?");
$custFull->execute([$_SESSION['customer_id']]);
$custFull = $custFull->fetch();

include 'includes/header.php';
?>

<div class="section" style="max-width:1000px;margin:0 auto">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem">
    <div style="display:flex;align-items:center;gap:1rem">
      <div style="width:56px;height:56px;border-radius:50%;background:var(--accent);color:white;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:700">
        <?= strtoupper(substr($customer['name'],0,1)) ?>
      </div>
      <div>
        <h1 style="font-family:var(--font-display);font-size:2rem;letter-spacing:1px">
          <?= strtoupper(htmlspecialchars($customer['name'])) ?>
        </h1>
        <div style="color:var(--text-muted);font-size:0.85rem"><?= htmlspecialchars($customer['email']) ?></div>
      </div>
    </div>
    <a href="?logout=1" class="btn btn-outline btn-sm" onclick="return confirm('Sign out?')">Sign Out</a>
  </div>

  <?php if ($msg): ?>
    <div style="background:rgba(16,185,129,0.1);border:1px solid #10b981;border-radius:8px;padding:0.8rem 1rem;color:#10b981;margin-bottom:1.5rem">✅ <?= $msg ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 2fr;gap:2rem;align-items:start">
    <!-- Profile -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1.5rem">
      <h3 style="font-weight:700;margin-bottom:1.5rem;font-size:0.95rem;letter-spacing:0.5px">Profile Settings</h3>
      <form method="POST">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($custFull['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($custFull['email']) ?>" disabled style="opacity:0.6">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($custFull['phone'] ?? '') ?>" placeholder="09XX XXX XXXX">
        </div>
        <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
      </form>
    </div>

    <!-- Orders -->
    <div>
      <h3 style="font-weight:700;margin-bottom:1.5rem;font-size:0.95rem;letter-spacing:0.5px">My Orders (<?= count($orders) ?>)</h3>
      <?php if (empty($orders)): ?>
        <div style="text-align:center;padding:3rem;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;color:var(--text-muted)">
          <div style="font-size:3rem;margin-bottom:1rem">📦</div>
          <p>No orders yet</p>
          <a href="/joesfit/shop.php" class="btn btn-primary mt-2" style="margin-top:1rem">Start Shopping</a>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:1rem">
          <?php foreach ($orders as $o): ?>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1.2rem">
              <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.8rem">
                <div>
                  <div style="font-family:var(--font-mono);font-size:0.85rem;color:var(--accent);font-weight:700"><?= htmlspecialchars($o['tracking_code']) ?></div>
                  <div style="font-size:0.78rem;color:var(--text-muted)"><?= date('M d, Y', strtotime($o['created_at'])) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:0.5rem">
                  <span class="badge badge-<?= $o['status'] ?>"><?= $o['status'] ?></span>
                  <strong style="color:var(--accent)">₱<?= number_format($o['total'],2) ?></strong>
                </div>
              </div>
              <div style="display:flex;gap:0.5rem">
                <a href="/joesfit/track.php?code=<?= $o['tracking_code'] ?>" class="btn btn-outline btn-sm">Track Order</a>
                <?php if ($o['status'] === 'delivered'): ?>
                  <?php
                  $items = $pdo->prepare("SELECT DISTINCT product_id FROM order_items WHERE order_id=?");
                  $items->execute([$o['id']]);
                  $firstProduct = $pdo->prepare("SELECT p.slug FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=? LIMIT 1");
                  $firstProduct->execute([$o['id']]);
                  $fp = $firstProduct->fetch();
                  ?>
                  <?php if ($fp): ?>
                    <a href="/joesfit/product.php?slug=<?= $fp['slug'] ?>#reviews" class="btn btn-outline btn-sm">⭐ Review</a>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
