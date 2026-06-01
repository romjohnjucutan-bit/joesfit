<?php
$pageTitle = 'Inventory Management';
require_once '../includes/admin_header.php';

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_stock') {
        $id    = (int)$_POST['product_id'];
        $stock = (int)$_POST['stock'];
        $pdo->prepare("UPDATE products SET stock=?, updated_at=NOW() WHERE id=?")->execute([$stock, $id]);
        // Remove low-stock notification if now OK
        if ($stock > 5) {
            $prod = $pdo->prepare("SELECT name FROM products WHERE id=?");
            $prod->execute([$id]);
            $prod = $prod->fetch();
        }
        header('Location: /joesfit/admin/pages/inventory.php?updated=1');
        exit;
    }
    if ($_POST['action'] === 'bulk_update') {
        foreach ($_POST['stocks'] as $pid => $qty) {
            $pdo->prepare("UPDATE products SET stock=?, updated_at=NOW() WHERE id=?")->execute([(int)$qty, (int)$pid]);
        }
        header('Location: /joesfit/admin/pages/inventory.php?updated=1');
        exit;
    }
}

// Filters
$filter = sanitize($_GET['filter'] ?? 'all');
$search = sanitize($_GET['search'] ?? '');
$where  = ['p.is_active=1'];
$params = [];
if ($filter === 'low')  { $where[] = 'p.stock<=5 AND p.stock>0'; }
if ($filter === 'out')  { $where[] = 'p.stock=0'; }
if ($filter === 'ok')   { $where[] = 'p.stock>5'; }
if ($search) { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$products = $pdo->prepare("
  SELECT p.*, c.name as cat_name
  FROM products p JOIN categories c ON p.category_id=c.id
  WHERE " . implode(' AND ',$where) . "
  ORDER BY p.stock ASC, p.name ASC
");
$products->execute($params);
$products = $products->fetchAll();

$lowCount = $pdo->query("SELECT COUNT(*) FROM products WHERE stock<=5 AND stock>0 AND is_active=1")->fetchColumn();
$outCount = $pdo->query("SELECT COUNT(*) FROM products WHERE stock=0 AND is_active=1")->fetchColumn();
$okCount  = $pdo->query("SELECT COUNT(*) FROM products WHERE stock>5 AND is_active=1")->fetchColumn();
?>

<?php if (isset($_GET['updated'])): ?>
  <div style="background:rgba(16,185,129,0.1);border:1px solid #10b981;border-radius:8px;padding:0.8rem 1rem;color:#10b981;margin-bottom:1.5rem">✅ Stock updated!</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem">
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div class="stat-value" style="color:var(--green)"><?= $okCount ?></div>
    <div class="stat-label">In Stock (Good)</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⚠️</div>
    <div class="stat-value" style="color:#f59e0b"><?= $lowCount ?></div>
    <div class="stat-label">Low Stock (≤5)</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">❌</div>
    <div class="stat-value" style="color:#ef4444"><?= $outCount ?></div>
    <div class="stat-label">Out of Stock</div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem">
    <span class="card-title">Inventory List</span>
    <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
      <div style="display:flex;gap:0.3rem">
        <?php foreach (['all'=>'All','low'=>"⚠️ Low ($lowCount)",'out'=>"❌ Out ($outCount)",'ok'=>"✅ Good ($okCount)"] as $f=>$l): ?>
          <a href="?filter=<?= $f ?><?= $search?'&search='.urlencode($search):'' ?>"
             style="padding:0.4rem 0.8rem;border-radius:6px;border:1px solid var(--border);font-size:0.8rem;<?= $filter===$f?'background:var(--accent);border-color:var(--accent);color:white':'' ?>">
            <?= $l ?>
          </a>
        <?php endforeach; ?>
      </div>
      <form method="GET">
        <input type="hidden" name="filter" value="<?= $filter ?>">
        <div class="search-bar">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        </div>
      </form>
    </div>
  </div>

  <form method="POST">
    <input type="hidden" name="action" value="bulk_update">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Product</th><th>Category</th><th>SKU</th><th>Current Stock</th><th>Update Stock</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:0.7rem">
                  <div style="width:40px;height:50px;border-radius:6px;background:var(--bg-3);overflow:hidden;flex-shrink:0">
                    <?php if ($p['image']): ?>
                      <img src="/joesfit/uploads/products/<?= htmlspecialchars($p['image']) ?>" style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">🧥</div>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($p['name']) ?></div>
                    <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($p['sizes'] ?? '') ?></div>
                  </div>
                </div>
              </td>
              <td style="font-size:0.85rem"><?= htmlspecialchars($p['cat_name']) ?></td>
              <td><code style="font-size:0.78rem;color:var(--accent)"><?= $p['sku'] ?: '—' ?></code></td>
              <td>
                <span style="font-size:1.1rem;font-weight:700;color:<?= $p['stock']==0?'#ef4444':($p['stock']<=5?'#f59e0b':'var(--green)') ?>">
                  <?= $p['stock'] ?>
                </span>
              </td>
              <td>
                <input type="number" name="stocks[<?= $p['id'] ?>]" value="<?= $p['stock'] ?>" min="0"
                       class="form-control" style="width:100px;padding:0.4rem 0.7rem">
              </td>
              <td>
                <?php if ($p['stock']==0): ?>
                  <span class="badge badge-cancelled">Out of Stock</span>
                <?php elseif ($p['stock']<=5): ?>
                  <span class="badge badge-pending">Low Stock</span>
                <?php else: ?>
                  <span class="badge badge-active">In Stock</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($products)): ?>
            <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted)">No products found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($products)): ?>
      <div style="padding:1rem 1.5rem;border-top:1px solid var(--border)">
        <button type="submit" class="btn btn-primary">💾 Save All Stock Changes</button>
      </div>
    <?php endif; ?>
  </form>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
