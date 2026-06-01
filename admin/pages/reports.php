<?php
$pageTitle = 'Reports & Analytics';
require_once '../includes/admin_header.php';
requireSuperAdmin();

$period = sanitize($_GET['period'] ?? 'month');
$dateCondition = match($period) {
    'today'  => "DATE(o.created_at) = CURDATE()",
    'week'   => "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    'month'  => "DATE_FORMAT(o.created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')",
    'year'   => "YEAR(o.created_at) = YEAR(NOW())",
    default  => "DATE_FORMAT(o.created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"
};

// Revenue stats
$revStats = $pdo->query("
  SELECT
    COUNT(*) as total_orders,
    COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','returned') THEN total ELSE 0 END),0) as revenue,
    COALESCE(SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END),0) as cancelled,
    COALESCE(SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END),0) as delivered,
    COALESCE(AVG(CASE WHEN status NOT IN ('cancelled','returned') THEN total ELSE NULL END),0) as avg_order
  FROM orders o WHERE $dateCondition
")->fetch();

// Revenue by day (last 30 days for month, last 12 months for year)
if ($period === 'year') {
    $revByTime = $pdo->query("
        SELECT DATE_FORMAT(created_at,'%Y-%m') as period, SUM(total) as rev, COUNT(*) as orders
        FROM orders WHERE YEAR(created_at)=YEAR(NOW()) AND status NOT IN ('cancelled','returned')
        GROUP BY period ORDER BY period
    ")->fetchAll();
} else {
    $revByTime = $pdo->query("
        SELECT DATE(created_at) as period, SUM(total) as rev, COUNT(*) as orders
        FROM orders WHERE $dateCondition AND status NOT IN ('cancelled','returned')
        GROUP BY DATE(created_at) ORDER BY period
    ")->fetchAll();
}

// Top products by revenue
$topProducts = $pdo->query("
    SELECT p.name, p.sku, p.image, SUM(oi.quantity) as units, SUM(oi.subtotal) as revenue
    FROM order_items oi JOIN products p ON oi.product_id=p.id
    JOIN orders o ON oi.order_id=o.id
    WHERE $dateCondition AND o.status NOT IN ('cancelled','returned')
    GROUP BY p.id ORDER BY revenue DESC LIMIT 10
")->fetchAll();

// Revenue by category
$revByCat = $pdo->query("
    SELECT c.name, SUM(oi.subtotal) as revenue, SUM(oi.quantity) as units
    FROM order_items oi JOIN products p ON oi.product_id=p.id
    JOIN categories c ON p.category_id=c.id
    JOIN orders o ON oi.order_id=o.id
    WHERE $dateCondition AND o.status NOT IN ('cancelled','returned')
    GROUP BY c.id ORDER BY revenue DESC
")->fetchAll();

// Payment breakdown
$payBreakdown = $pdo->query("
    SELECT payment_method, COUNT(*) as cnt, SUM(total) as rev
    FROM orders o WHERE $dateCondition AND status NOT IN ('cancelled','returned')
    GROUP BY payment_method
")->fetchAll();

// Delivery breakdown
$delivBreakdown = $pdo->query("
    SELECT delivery_method, COUNT(*) as cnt FROM orders o
    WHERE $dateCondition GROUP BY delivery_method
")->fetchAll();

$maxRev = max(array_column($revByTime, 'rev') + [1]);
?>

<!-- Period Selector -->
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
  <?php foreach (['today'=>'Today','week'=>'Last 7 Days','month'=>'This Month','year'=>'This Year'] as $p=>$l): ?>
    <a href="?period=<?= $p ?>" style="padding:0.5rem 1rem;border-radius:8px;border:1px solid var(--border);font-size:0.85rem;<?= $period===$p?'background:var(--accent);border-color:var(--accent);color:white':'color:var(--text-muted)' ?>">
      <?= $l ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- Summary Stats -->
<div class="stats-grid" style="margin-bottom:1.5rem">
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-value">₱<?= number_format($revStats['revenue']/1000,1) ?>K</div>
    <div class="stat-label">Revenue</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-value"><?= $revStats['total_orders'] ?></div>
    <div class="stat-label">Total Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div class="stat-value"><?= $revStats['delivered'] ?></div>
    <div class="stat-label">Delivered</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">❌</div>
    <div class="stat-value"><?= $revStats['cancelled'] ?></div>
    <div class="stat-label">Cancelled</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📊</div>
    <div class="stat-value">₱<?= number_format($revStats['avg_order'],0) ?></div>
    <div class="stat-label">Avg. Order Value</div>
  </div>
</div>

<!-- Revenue Chart -->
<?php if (!empty($revByTime)): ?>
<div class="card" style="margin-bottom:1.5rem">
  <div class="card-header"><span class="card-title">📈 Revenue Chart</span></div>
  <div class="card-body">
    <div class="chart-container" style="padding-bottom:2.5rem;height:200px">
      <?php foreach ($revByTime as $r): ?>
        <?php $h = $maxRev > 0 ? round(($r['rev']/$maxRev)*100) : 0; ?>
        <div class="chart-bar" style="height:<?= $h ?>%" title="<?= $r['period'] ?>: ₱<?= number_format($r['rev'],0) ?> (<?= $r['orders'] ?> orders)">
          <div class="chart-bar-value" style="font-size:0.6rem">₱<?= number_format($r['rev']/1000,1) ?>K</div>
          <div class="chart-bar-label" style="font-size:0.6rem"><?= date('M d', strtotime($r['period'].'-01')) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
  <!-- Top Products -->
  <div class="card">
    <div class="card-header"><span class="card-title">🏆 Top Products by Revenue</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php foreach ($topProducts as $i => $tp): ?>
            <tr>
              <td style="font-weight:700;color:var(--text-muted)"><?= $i+1 ?></td>
              <td>
                <div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($tp['name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= $tp['sku'] ?></div>
              </td>
              <td style="text-align:center;font-weight:700"><?= $tp['units'] ?></td>
              <td style="font-weight:700;color:var(--accent)">₱<?= number_format($tp['revenue'],2) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($topProducts)): ?>
            <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-muted)">No data</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Breakdowns -->
  <div style="display:flex;flex-direction:column;gap:1.5rem">
    <!-- Revenue by Category -->
    <div class="card">
      <div class="card-header"><span class="card-title">📂 By Category</span></div>
      <div class="card-body" style="padding:1rem">
        <?php
        $totalCatRev = array_sum(array_column($revByCat,'revenue')) ?: 1;
        $catColors = ['#e8321a','#f59e0b','#10b981','#3b82f6','#8b5cf6'];
        foreach ($revByCat as $i=>$c):
          $pct = round(($c['revenue']/$totalCatRev)*100);
        ?>
          <div style="margin-bottom:0.8rem">
            <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:0.2rem">
              <span><?= htmlspecialchars($c['name']) ?></span>
              <span style="font-weight:700">₱<?= number_format($c['revenue'],0) ?></span>
            </div>
            <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden">
              <div style="width:<?= $pct ?>%;height:100%;background:<?= $catColors[$i%count($catColors)] ?>;border-radius:3px"></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($revByCat)): ?><div style="text-align:center;color:var(--text-muted);font-size:0.85rem">No data</div><?php endif; ?>
      </div>
    </div>

    <!-- Payment Methods -->
    <div class="card">
      <div class="card-header"><span class="card-title">💳 Payment Methods</span></div>
      <div class="card-body" style="padding:1rem;font-size:0.85rem">
        <?php
        $icons = ['cod'=>'💵','gcash'=>'📱','maya'=>'💜','card'=>'🏦'];
        foreach ($payBreakdown as $p):
        ?>
          <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid var(--border)">
            <span><?= $icons[$p['payment_method']]??'💳' ?> <?= strtoupper($p['payment_method']) ?></span>
            <span style="font-weight:700"><?= $p['cnt'] ?> orders · ₱<?= number_format($p['rev'],0) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
