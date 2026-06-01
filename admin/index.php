<?php
$pageTitle = 'Dashboard';
require_once 'includes/admin_header.php';

// Validate database connection
if (!$pdo) {
    die('Database connection failed. Please check your configuration.');
}

// ── Stats ──────────────────────────────────────────────────────────────────
$today       = date('Y-m-d');
$thisMonth   = date('Y-m');
$lastMonth   = date('Y-m', strtotime('-1 month'));

$totalRevenue    = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled','returned')")->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND status NOT IN ('cancelled','returned')");
$stmt->execute([$thisMonth]);
$monthRevenue    = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND status NOT IN ('cancelled','returned')");
$stmt->execute([$lastMonth]);
$lastMonthRev    = $stmt->fetchColumn() ?? 0;

$totalOrders     = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=?");
$stmt->execute([$today]);
$todayOrders     = $stmt->fetchColumn() ?? 0;

$pendingOrders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn() ?? 0;
$totalProducts   = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn() ?? 0;
$lowStockItems   = $pdo->query("SELECT COUNT(*) FROM products WHERE stock<=5 AND is_active=1")->fetchColumn() ?? 0;
$totalCustomers  = $pdo->query("SELECT COUNT(DISTINCT customer_email) FROM orders")->fetchColumn() ?? 0;
$pendingReviews  = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved=0")->fetchColumn() ?? 0;

$revChange = $lastMonthRev > 0 ? round((($monthRevenue - $lastMonthRev) / $lastMonthRev) * 100, 1) : 0;

// ── Recent Orders ──────────────────────────────────────────────────────────
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10")->fetchAll() ?? [];

// ── Top Products ──────────────────────────────────────────────────────────
$topProducts = $pdo->query("
  SELECT p.name, p.image, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as total_revenue
  FROM order_items oi JOIN products p ON oi.product_id = p.id
  JOIN orders o ON oi.order_id = o.id
  WHERE o.status NOT IN ('cancelled','returned')
  GROUP BY p.id ORDER BY total_sold DESC LIMIT 5
")->fetchAll() ?? [];

// ── Revenue last 7 days ────────────────────────────────────────────────────
$weekRevenue = $pdo->query("
  SELECT DATE(created_at) as day, COALESCE(SUM(total),0) as rev
  FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND status NOT IN ('cancelled','returned')
  GROUP BY DATE(created_at) ORDER BY day ASC
")->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];

// ── Order status distribution ─────────────────────────────────────────────
$statusDist = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll() ?? [];
$statusMap  = array_column($statusDist, 'cnt', 'status');

// ── Recent activity (order history) ──────────────────────────────────────
$recentActivity = $pdo->query("
  SELECT oh.*, o.tracking_code, o.customer_name
  FROM order_history oh JOIN orders o ON oh.order_id = o.id
  ORDER BY oh.created_at DESC LIMIT 8
")->fetchAll() ?? [];
?>

<!-- STATS GRID -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-value">₱<?= number_format($totalRevenue/1000, 1) ?>K</div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-change <?= $revChange >= 0 ? 'up' : 'down' ?>">
      <?= $revChange >= 0 ? '▲' : '▼' ?> <?= abs($revChange) ?>% vs last month
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-value"><?= $totalOrders ?></div>
    <div class="stat-label">Total Orders</div>
    <div class="stat-change up">+<?= $todayOrders ?> today</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⏳</div>
    <div class="stat-value"><?= $pendingOrders ?></div>
    <div class="stat-label">Pending Orders</div>
    <div class="stat-change <?= $pendingOrders > 0 ? 'down' : 'up' ?>">
      <?= $pendingOrders > 0 ? 'Needs attention' : 'All clear!' ?>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🧥</div>
    <div class="stat-value"><?= $totalProducts ?></div>
    <div class="stat-label">Active Products</div>
    <?php if ($lowStockItems): ?>
      <div class="stat-change down">⚠️ <?= $lowStockItems ?> low stock</div>
    <?php else: ?>
      <div class="stat-change up">Stock levels OK</div>
    <?php endif; ?>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👤</div>
    <div class="stat-value"><?= $totalCustomers ?></div>
    <div class="stat-label">Customers</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⭐</div>
    <div class="stat-value"><?= $pendingReviews ?></div>
    <div class="stat-label">Pending Reviews</div>
    <?php if ($pendingReviews): ?>
      <div class="stat-change down"><a href="/joesfit/admin/pages/reviews.php" style="color:inherit">Review now →</a></div>
    <?php endif; ?>
  </div>
</div>

<!-- CHARTS ROW -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
  <!-- Revenue Chart -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📈 Revenue — Last 7 Days</span>
      <span style="font-size:0.8rem;color:var(--text-muted)">₱<?= number_format($monthRevenue,2) ?> this month</span>
    </div>
    <div class="card-body">
      <?php
      $days7 = [];
      $max = 1;
      for ($i = 6; $i >= 0; $i--) {
          $d = date('Y-m-d', strtotime("-$i days"));
          $val = (float)($weekRevenue[$d] ?? 0);
          $days7[$d] = $val;
          if ($val > $max) $max = $val;
      }
      ?>
      <div class="chart-container" style="padding-bottom:2rem">
        <?php foreach ($days7 as $d => $val): ?>
          <div class="chart-bar" style="height:<?= $max > 0 ? round(($val/$max)*100) : 0 ?>%;position:relative" title="<?= date('M d',strtotime($d)) ?>: ₱<?= number_format($val,0) ?>">
            <div class="chart-bar-value">₱<?= $val >= 1000 ? number_format($val/1000,1).'K' : number_format($val,0) ?></div>
            <div class="chart-bar-label"><?= date('M d',strtotime($d)) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Order Status Donut-like -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📊 Order Status</span>
    </div>
    <div class="card-body">
      <?php
      $statusConfig = [
        'pending'=>['🟡','#f59e0b'],'confirmed'=>['🔵','#3b82f6'],
        'processing'=>['🟣','#8b5cf6'],'shipped'=>['🩵','#06b6d4'],
        'delivered'=>['🟢','#10b981'],'cancelled'=>['🔴','#ef4444'],
        'returned'=>['⚫','#6b7280']
      ];
      $totalOrd = array_sum($statusMap) ?: 1;
      foreach ($statusConfig as $s => [$icon,$color]):
        $cnt = $statusMap[$s] ?? 0;
        $pct = round(($cnt/$totalOrd)*100);
      ?>
        <div style="margin-bottom:0.8rem">
          <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:0.2rem">
            <span><?= $icon ?> <?= ucfirst($s) ?></span>
            <span style="font-weight:700"><?= $cnt ?></span>
          </div>
          <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden">
            <div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:3px;transition:width 0.5s"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- RECENT ORDERS + TOP PRODUCTS -->
<div style="display:grid;grid-template-columns:3fr 2fr;gap:1.5rem;margin-bottom:1.5rem">
  <!-- Recent Orders Table -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📦 Recent Orders</span>
      <a href="/joesfit/admin/pages/orders.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Tracking</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td>
                <a href="/joesfit/admin/pages/orders.php?id=<?= $o['id'] ?>" style="color:var(--accent);font-family:var(--font-mono);font-size:0.8rem">
                  <?= htmlspecialchars($o['tracking_code']) ?>
                </a>
              </td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td style="font-weight:700;color:var(--accent)">₱<?= number_format($o['total'],2) ?></td>
              <td><span class="badge badge-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
              <td style="color:var(--text-muted);font-size:0.8rem"><?= date('M d, g:i A', strtotime($o['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Products -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">🏆 Top Products</span>
    </div>
    <div class="card-body" style="padding:0">
      <?php foreach ($topProducts as $idx => $tp): ?>
        <div style="display:flex;align-items:center;gap:1rem;padding:0.9rem 1.2rem;border-bottom:1px solid var(--border)">
          <div style="font-family:var(--font-display);font-size:1.3rem;color:var(--text-light);width:24px;text-align:center">
            <?= $idx === 0 ? '🥇' : ($idx === 1 ? '🥈' : ($idx === 2 ? '🥉' : ($idx+1))) ?>
          </div>
          <div style="width:40px;height:50px;border-radius:6px;background:var(--border);overflow:hidden;flex-shrink:0">
            <?php if ($tp['image']): ?>
              <img src="/joesfit/uploads/products/<?= htmlspecialchars($tp['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">🧥</div>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($tp['name']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted)"><?= $tp['total_sold'] ?> sold · ₱<?= number_format($tp['total_revenue'],0) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($topProducts)): ?>
        <div style="text-align:center;padding:2rem;color:var(--text-muted);font-size:0.85rem">No sales data yet</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- RECENT ACTIVITY -->
<div class="card">
  <div class="card-header">
    <span class="card-title">🕐 Recent Activity</span>
  </div>
  <div class="card-body" style="padding:0">
    <?php foreach ($recentActivity as $act): ?>
      <div style="display:flex;align-items:center;gap:1rem;padding:0.8rem 1.2rem;border-bottom:1px solid var(--border)">
        <div style="width:36px;height:36px;border-radius:50%;background:rgba(232,50,26,0.15);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0">
          📦
        </div>
        <div style="flex:1">
          <span style="font-weight:600;font-size:0.85rem"><?= htmlspecialchars($act['customer_name']) ?></span>
          <span style="color:var(--text-muted);font-size:0.82rem"> — order </span>
          <a href="/joesfit/admin/pages/orders.php?id=<?= $act['order_id'] ?>" style="color:var(--accent);font-family:var(--font-mono);font-size:0.8rem">
            <?= htmlspecialchars($act['tracking_code']) ?>
          </a>
          <span style="color:var(--text-muted);font-size:0.82rem"> changed to </span>
          <span class="badge badge-<?= $act['status'] ?>"><?= $act['status'] ?></span>
        </div>
        <div style="font-size:0.75rem;color:var(--text-light);white-space:nowrap"><?= timeAgo($act['created_at']) ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($recentActivity)): ?>
      <div style="text-align:center;padding:2rem;color:var(--text-muted)">No activity yet</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
