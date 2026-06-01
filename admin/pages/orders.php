<?php
require_once '../../includes/config.php';
require_once '../includes/admin_auth.php';

/** @var PDO $pdo */
global $pdo;
assert($pdo !== null);

// Handle status update BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = (int)$_POST['order_id'];
    $action  = $_POST['action'];

    if ($action === 'update_status') {
        $status = sanitize($_POST['status']);
        $note   = sanitize($_POST['note'] ?? '');
        $validStatuses = ['pending','confirmed','processing','shipped','delivered','cancelled','returned'];
        if (in_array($status, $validStatuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?");
            if ($stmt !== null) {
                $stmt->execute([$status, $orderId]);
            }
            $stmt = $pdo->prepare("INSERT INTO order_history (order_id,status,note,updated_by) VALUES (?,?,?,?)");
            if ($stmt !== null) {
                $stmt->execute([$orderId, $status, $note, $_SESSION['admin_id']]);
            }
            // Notify on delivery
            if ($status === 'delivered') {
                $stmt = $pdo->prepare("UPDATE orders SET payment_status='paid' WHERE id=? AND payment_method='cod'");
                if ($stmt !== null) {
                    $stmt->execute([$orderId]);
                }
            }
            header('Location: /joesfit/admin/pages/orders.php?updated=1&id='.$orderId);
            exit;
        }
    }
}

$pageTitle = 'Orders Management';
require_once '../includes/admin_header.php';

// Filters
$status   = sanitize($_GET['status'] ?? '');
$search   = sanitize($_GET['search'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo   = sanitize($_GET['date_to'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page-1)*$perPage;

$where  = ['1=1'];
$params = [];
if ($status) { $where[] = 'o.status=?'; $params[] = $status; }
if ($search) { $where[] = '(o.tracking_code LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($dateFrom) { $where[] = 'DATE(o.created_at)>=?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(o.created_at)<=?'; $params[] = $dateTo; }
$whereSQL = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $whereSQL");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$perPage);

$stmt = $pdo->prepare("SELECT o.* FROM orders o WHERE $whereSQL ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Single order detail view
$viewOrder = null;
$orderItems = [];
$orderHistory = [];
if (isset($_GET['id'])) {
    $viewOrder = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    if ($viewOrder !== null) {
        $viewOrder->execute([(int)$_GET['id']]);
        $viewOrder = $viewOrder->fetch();
        if ($viewOrder) {
            $oi = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
            if ($oi !== null) {
                $oi->execute([$viewOrder['id']]); $orderItems = $oi->fetchAll();
            }
            $oh = $pdo->prepare("SELECT oh.*, s.name as staff_name FROM order_history oh LEFT JOIN staff s ON oh.updated_by=s.id WHERE oh.order_id=? ORDER BY oh.created_at DESC");
            if ($oh !== null) {
                $oh->execute([$viewOrder['id']]); $orderHistory = $oh->fetchAll();
            }
        }
    }
}
?>

<?php if (isset($_GET['updated'])): ?>
  <div class="error-msg" style="background:rgba(16,185,129,0.1);border-color:#10b981;color:#10b981;padding:0.8rem 1rem;border-radius:8px;margin-bottom:1.5rem;border:1px solid #10b981">
    ✅ Order status updated successfully.
  </div>
<?php endif; ?>

<!-- ORDER DETAIL VIEW -->
<?php if ($viewOrder): ?>
<div style="margin-bottom:2rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div>
      <a href="/joesfit/admin/pages/orders.php" style="color:var(--text-muted);font-size:0.85rem">← Back to Orders</a>
      <h2 style="font-family:var(--font-display);font-size:2rem;letter-spacing:1px;margin-top:0.3rem">
        ORDER <span style="color:var(--accent)"><?= htmlspecialchars($viewOrder['tracking_code']) ?></span>
      </h2>
    </div>
    <span class="badge badge-<?= $viewOrder['status'] ?>" style="font-size:0.85rem;padding:0.5rem 1rem">
      <?= strtoupper($viewOrder['status']) ?>
    </span>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem">
    <div>
      <!-- Items -->
      <div class="card mb-2" style="margin-bottom:1.5rem">
        <div class="card-header"><span class="card-title">Order Items</span></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Product</th><th>Size</th><th>Color</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($orderItems as $item): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:0.7rem">
                      <div style="width:40px;height:50px;border-radius:6px;background:var(--bg-3);overflow:hidden;flex-shrink:0">
                        <?php if ($item['product_image']): ?>
                          <img src="<?= htmlspecialchars($item['product_image']) ?>" style="width:100%;height:100%;object-fit:cover">
                        <?php else: ?>
                          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.2rem">🧥</div>
                        <?php endif; ?>
                      </div>
                      <span style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($item['product_name']) ?></span>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($item['size'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($item['color'] ?? '—') ?></td>
                  <td><?= $item['quantity'] ?></td>
                  <td>₱<?= number_format($item['price'],2) ?></td>
                  <td style="font-weight:700;color:var(--accent)">₱<?= number_format($item['subtotal'],2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:0.3rem;align-items:flex-end;font-size:0.88rem">
          <div style="display:flex;gap:3rem"><span style="color:var(--text-muted)">Subtotal</span><strong>₱<?= number_format($viewOrder['subtotal'],2) ?></strong></div>
          <div style="display:flex;gap:3rem"><span style="color:var(--text-muted)">Shipping (<?= ucfirst($viewOrder['delivery_method']) ?>)</span><strong>₱<?= number_format($viewOrder['shipping_fee'],2) ?></strong></div>
          <?php if ($viewOrder['discount']>0): ?>
            <div style="display:flex;gap:3rem;color:var(--green)"><span>Discount</span><strong>-₱<?= number_format($viewOrder['discount'],2) ?></strong></div>
          <?php endif; ?>
          <div style="display:flex;gap:3rem;font-size:1rem;border-top:1px solid var(--border);padding-top:0.5rem;margin-top:0.3rem">
            <span>Total</span><strong style="color:var(--accent)">₱<?= number_format($viewOrder['total'],2) ?></strong>
          </div>
        </div>
      </div>

      <!-- Update Status -->
      <div class="card">
        <div class="card-header"><span class="card-title">Update Order Status</span></div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">New Status</label>
                <select name="status" class="form-control">
                  <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled','returned'] as $s): ?>
                    <option value="<?= $s ?>" <?= $viewOrder['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Note (optional)</label>
                <input type="text" name="note" class="form-control" placeholder="Add a note...">
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Status →</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right column -->
    <div>
      <!-- Customer Info -->
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header"><span class="card-title">Customer & Delivery</span></div>
        <div class="card-body" style="font-size:0.88rem;display:flex;flex-direction:column;gap:0.5rem">
          <div><strong><?= htmlspecialchars($viewOrder['customer_name']) ?></strong></div>
          <div style="color:var(--text-muted)"><?= htmlspecialchars($viewOrder['customer_email']) ?></div>
          <?php if ($viewOrder['customer_phone']): ?>
            <div style="color:var(--text-muted)"><?= htmlspecialchars($viewOrder['customer_phone']) ?></div>
          <?php endif; ?>
          <hr class="divider">
          <div><?= htmlspecialchars($viewOrder['shipping_address']) ?></div>
          <div><?= htmlspecialchars($viewOrder['shipping_city']) ?>, <?= htmlspecialchars($viewOrder['shipping_province']) ?></div>
          <?php if ($viewOrder['shipping_zip']): ?><div><?= htmlspecialchars($viewOrder['shipping_zip']) ?></div><?php endif; ?>
          <hr class="divider">
          <div>Payment: <strong><?= strtoupper($viewOrder['payment_method']) ?></strong></div>
          <div>Pay Status: <span class="badge badge-<?= $viewOrder['payment_status']==='paid'?'paid':'unpaid' ?>"><?= strtoupper($viewOrder['payment_status']) ?></span></div>
          <?php if ($viewOrder['notes']): ?>
            <hr class="divider">
            <div style="color:var(--text-muted);font-style:italic">Note: <?= htmlspecialchars($viewOrder['notes']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Order History -->
      <div class="card">
        <div class="card-header"><span class="card-title">History</span></div>
        <div class="card-body" style="padding:0">
          <?php foreach ($orderHistory as $h): ?>
            <div style="padding:0.8rem 1.2rem;border-bottom:1px solid var(--border)">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.2rem">
                <span class="badge badge-<?= $h['status'] ?>"><?= $h['status'] ?></span>
                <span style="font-size:0.72rem;color:var(--text-light)"><?= timeAgo($h['created_at']) ?></span>
              </div>
              <?php if ($h['note']): ?><div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.2rem"><?= htmlspecialchars($h['note']) ?></div><?php endif; ?>
              <?php if ($h['staff_name']): ?><div style="font-size:0.75rem;color:var(--text-light)">by <?= htmlspecialchars($h['staff_name']) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php else: ?>

<!-- ORDERS LIST -->
<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem">
    <span class="card-title">All Orders (<?= $total ?>)</span>
    <div style="display:flex;gap:0.7rem;flex-wrap:wrap;align-items:center">
      <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center">
        <div class="search-bar">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="status" class="form-control" style="width:auto">
          <option value="">All Status</option>
          <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled','returned'] as $s): ?>
            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" class="form-control" style="width:auto" value="<?= $dateFrom ?>" placeholder="From">
        <input type="date" name="date_to" class="form-control" style="width:auto" value="<?= $dateTo ?>" placeholder="To">
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if ($status||$search||$dateFrom||$dateTo): ?>
          <a href="/joesfit/admin/pages/orders.php" class="btn btn-sm" style="color:var(--text-muted)">Clear</a>
        <?php endif; ?>
      </form>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tracking</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Delivery</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <?php
          $itemCount = $pdo->prepare("SELECT SUM(quantity) FROM order_items WHERE order_id=?");
          $itemCount->execute([$o['id']]); $itemCount = $itemCount->fetchColumn();
          ?>
          <tr>
            <td>
              <a href="?id=<?= $o['id'] ?>" style="color:var(--accent);font-family:var(--font-mono);font-size:0.82rem;font-weight:700">
                <?= htmlspecialchars($o['tracking_code']) ?>
              </a>
            </td>
            <td>
              <div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($o['customer_name']) ?></div>
              <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($o['customer_email']) ?></div>
            </td>
            <td style="text-align:center"><?= $itemCount ?></td>
            <td style="font-weight:700;color:var(--accent)">₱<?= number_format($o['total'],2) ?></td>
            <td>
              <div style="font-size:0.82rem"><?= strtoupper($o['payment_method']) ?></div>
              <span class="badge badge-<?= $o['payment_status']==='paid'?'paid':'unpaid' ?>" style="font-size:0.65rem"><?= $o['payment_status'] ?></span>
            </td>
            <td style="font-size:0.82rem"><?= ucfirst($o['delivery_method']) ?></td>
            <td><span class="badge badge-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
            <td style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
            <td>
              <a href="?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
          <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--text-muted)">No orders found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.5rem;display:flex;gap:0.5rem;flex-wrap:wrap">
      <?php for ($i=1;$i<=$totalPages;$i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"
           style="width:34px;height:34px;border-radius:6px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:0.85rem;<?= $i===$page?'background:var(--accent);border-color:var(--accent);color:white;':'' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>
