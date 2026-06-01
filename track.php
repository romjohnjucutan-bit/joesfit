<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
$pageTitle = "Track Your Order";

$code  = sanitize($_GET['code'] ?? '');
$order = null;
$items = [];
$history = [];
$error = '';

$statusSteps = [
  'pending'    => ['label'=>'Order Placed',     'icon'=>'📋', 'desc'=>'Your order has been received'],
  'confirmed'  => ['label'=>'Confirmed',         'icon'=>'✅', 'desc'=>'Order confirmed by store'],
  'processing' => ['label'=>'Processing',        'icon'=>'📦', 'desc'=>'Your jacket is being prepared'],
  'shipped'    => ['label'=>'Shipped',           'icon'=>'🚚', 'desc'=>'Order is on the way'],
  'delivered'  => ['label'=>'Delivered',         'icon'=>'🎉', 'desc'=>'Order delivered successfully'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $code) {
  if (!$code && isset($_POST['tracking_code'])) {
    $code = sanitize($_POST['tracking_code']);
  }
  if ($code) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE tracking_code = ?");
    $stmt->execute([$code]);
    $order = $stmt->fetch();

    if ($order) {
      $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
      $itemStmt->execute([$order['id']]);
      $items = $itemStmt->fetchAll();

      $histStmt = $pdo->prepare("SELECT * FROM order_history WHERE order_id = ? ORDER BY created_at ASC");
      $histStmt->execute([$order['id']]);
      $history = $histStmt->fetchAll();
    } else {
      $error = 'No order found with that tracking code.';
    }
  }
}

include 'includes/header.php';
?>

<div class="section" style="max-width:900px;margin:0 auto">
  <h1 style="font-family:var(--font-display);font-size:clamp(2.5rem,5vw,4rem);letter-spacing:1px;margin-bottom:0.5rem">
    TRACK YOUR <span style="color:var(--accent)">ORDER</span>
  </h1>
  <p style="color:var(--text-muted);margin-bottom:3rem">Enter your tracking code to see real-time order status</p>

  <!-- Tracking Form -->
  <form method="POST" style="display:flex;gap:1rem;max-width:500px;margin-bottom:3rem">
    <input type="text" name="tracking_code" class="form-control" 
           placeholder="e.g. JF-20240001" value="<?= htmlspecialchars($code) ?>" required
           style="flex:1;font-family:var(--font-mono);font-size:1rem;letter-spacing:2px">
    <button type="submit" class="btn btn-primary">Track →</button>
  </form>

  <?php if ($error): ?>
    <div style="background:rgba(239,68,68,0.1);border:1px solid #ef4444;border-radius:8px;padding:1rem 1.5rem;color:#ef4444;margin-bottom:2rem">
      😕 <?= $error ?>
    </div>
  <?php endif; ?>

  <?php if ($order): ?>
    <div style="display:grid;grid-template-columns:1fr 350px;gap:2rem;align-items:start">
      <!-- LEFT: Status + History -->
      <div>
        <!-- Status Card -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;margin-bottom:2rem">
          <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem">
            <div>
              <div style="font-family:var(--font-mono);font-size:0.75rem;letter-spacing:3px;color:var(--text-muted)">TRACKING CODE</div>
              <div style="font-family:var(--font-display);font-size:2rem;color:var(--accent);letter-spacing:3px"><?= htmlspecialchars($order['tracking_code']) ?></div>
            </div>
            <div>
              <?php
              $statusColors = [
                'pending'=>'#f59e0b','confirmed'=>'#3b82f6','processing'=>'#8b5cf6',
                'shipped'=>'#06b6d4','delivered'=>'#10b981','cancelled'=>'#ef4444','returned'=>'#6b7280'
              ];
              $sc = $statusColors[$order['status']] ?? '#888';
              ?>
              <span style="background:<?= $sc ?>20;color:<?= $sc ?>;border:1px solid <?= $sc ?>;padding:0.4rem 1rem;border-radius:50px;font-size:0.8rem;font-weight:700;letter-spacing:1px;text-transform:uppercase">
                <?= strtoupper($order['status']) ?>
              </span>
            </div>
          </div>

          <!-- Timeline -->
          <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'returned'): ?>
            <?php
            $stepKeys = array_keys($statusSteps);
            $currentIdx = array_search($order['status'], $stepKeys);
            ?>
            <div style="display:flex;justify-content:space-between;position:relative;padding:0 1rem">
              <div style="position:absolute;top:19px;left:2rem;right:2rem;height:2px;background:var(--border)"></div>
              <div style="position:absolute;top:19px;left:2rem;height:2px;background:var(--accent);transition:width 0.5s;width:<?= $currentIdx > 0 ? min(100, ($currentIdx / (count($stepKeys)-1)) * 100) : 0 ?>%"></div>
              <?php foreach ($stepKeys as $i => $key): ?>
                <?php $done = $i <= $currentIdx; ?>
                <div style="text-align:center;flex:1;position:relative;z-index:1">
                  <div style="width:40px;height:40px;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:1.1rem;border:2px solid <?= $done ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $done ? 'var(--accent)' : 'var(--bg-card)' ?>">
                    <?= $statusSteps[$key]['icon'] ?>
                  </div>
                  <div style="font-size:0.7rem;font-weight:600;margin-top:0.5rem;color:<?= $done ? 'var(--accent)' : 'var(--text-muted)' ?>"><?= $statusSteps[$key]['label'] ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div style="text-align:center;padding:1rem;color:#ef4444">
              ✕ Order <?= $order['status'] === 'cancelled' ? 'Cancelled' : 'Returned' ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Order History -->
        <?php if (!empty($history)): ?>
          <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:2rem">
            <h3 style="font-weight:700;margin-bottom:1.5rem;font-size:1rem;letter-spacing:1px;text-transform:uppercase">Update History</h3>
            <div class="tracking-steps">
              <?php foreach (array_reverse($history) as $idx => $h): ?>
                <div class="tracking-step done">
                  <div class="step-icon">✓</div>
                  <div class="step-info">
                    <div class="step-status"><?= ucfirst($h['status']) ?></div>
                    <div class="step-date"><?= date('F j, Y — g:i A', strtotime($h['created_at'])) ?></div>
                    <?php if ($h['note']): ?>
                      <div class="step-note"><?= htmlspecialchars($h['note']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Order Details -->
      <div>
        <!-- Order Items -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem">
          <h3 style="font-weight:700;margin-bottom:1rem;font-size:0.85rem;letter-spacing:1px;text-transform:uppercase">Order Items</h3>
          <?php foreach ($items as $item): ?>
            <div style="display:flex;gap:0.8rem;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--border)">
              <div style="width:55px;height:70px;border-radius:6px;background:var(--border);overflow:hidden;flex-shrink:0">
                <?php if ($item['product_image']): ?>
                  <img src="<?= htmlspecialchars($item['product_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.5rem">🧥</div>
                <?php endif; ?>
              </div>
              <div style="flex:1">
                <div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($item['product_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= $item['size'] ?> · <?= $item['color'] ?></div>
                <div style="font-size:0.8rem;color:var(--text-muted)">Qty: <?= $item['quantity'] ?></div>
              </div>
              <div style="font-weight:700;font-size:0.9rem;color:var(--accent)"><?= formatPrice($item['subtotal']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Summary -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem">
          <h3 style="font-weight:700;margin-bottom:1rem;font-size:0.85rem;letter-spacing:1px;text-transform:uppercase">Summary</h3>
          <div style="display:flex;flex-direction:column;gap:0.5rem;font-size:0.88rem">
            <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span><?= formatPrice($order['subtotal']) ?></span></div>
            <div style="display:flex;justify-content:space-between"><span>Shipping (<?= ucfirst($order['delivery_method']) ?>)</span><span><?= formatPrice($order['shipping_fee']) ?></span></div>
            <?php if ($order['discount'] > 0): ?>
              <div style="display:flex;justify-content:space-between;color:var(--accent)"><span>Discount</span><span>-<?= formatPrice($order['discount']) ?></span></div>
            <?php endif; ?>
            <hr class="divider" style="margin:0.5rem 0">
            <div style="display:flex;justify-content:space-between;font-size:1.05rem;font-weight:700">
              <span>Total</span>
              <span style="color:var(--accent)"><?= formatPrice($order['total']) ?></span>
            </div>
          </div>
        </div>

        <!-- Delivery & Payment -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem">
          <h3 style="font-weight:700;margin-bottom:1rem;font-size:0.85rem;letter-spacing:1px;text-transform:uppercase">Delivery Info</h3>
          <div style="font-size:0.88rem;display:flex;flex-direction:column;gap:0.4rem;color:var(--text-muted)">
            <div><strong style="color:var(--text)"><?= htmlspecialchars($order['customer_name']) ?></strong></div>
            <div><?= htmlspecialchars($order['shipping_address']) ?></div>
            <div><?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_province']) ?></div>
            <div style="margin-top:0.5rem">Payment: <strong style="color:var(--text)"><?= strtoupper($order['payment_method']) ?></strong></div>
            <div>Status: 
              <?php $ps = $order['payment_status']; $pc = $ps==='paid'?'#10b981':($ps==='pending'?'#f59e0b':'#ef4444'); ?>
              <strong style="color:<?= $pc ?>"><?= strtoupper($ps) ?></strong>
            </div>
            <div>Placed: <?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
