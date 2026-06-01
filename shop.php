<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
$pageTitle = "Shop All Jackets";

// Filters
$category = sanitize($_GET['category'] ?? '');
$search   = sanitize($_GET['search'] ?? '');
$sort     = sanitize($_GET['sort'] ?? 'featured');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Build query
$where = ["p.is_active = 1"];
$params = [];

if ($category) {
    $where[] = "c.slug = ?";
    $params[] = $category;
}
if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = implode(' AND ', $where);

$orderSQL = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest'     => 'p.created_at DESC',
    'name'       => 'p.name ASC',
    default      => 'p.is_featured DESC, p.created_at DESC'
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id WHERE $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("
  SELECT p.*, c.name as category_name, c.slug as category_slug
  FROM products p JOIN categories c ON p.category_id = c.id 
  WHERE $whereSQL ORDER BY $orderSQL LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT * FROM categories WHERE is_active = 1")->fetchAll();
$activeCat = $category ? $pdo->prepare("SELECT name FROM categories WHERE slug = ?") : null;
if ($activeCat) { $activeCat->execute([$category]); $activeCat = $activeCat->fetchColumn(); }

include 'includes/header.php';
?>

<div class="section">
  <div style="margin-bottom:2rem">
    <h1 style="font-family:var(--font-display);font-size:clamp(2rem,4vw,3.5rem);letter-spacing:1px">
      <?= $search ? "SEARCH: <span style='color:var(--accent)'>" . strtoupper(htmlspecialchars($search)) . "</span>" : 
         ($activeCat ? strtoupper(htmlspecialchars($activeCat)) : "ALL JACKETS") ?>
    </h1>
    <p style="color:var(--text-muted);font-size:0.88rem"><?= $total ?> product<?= $total !== 1 ? 's' : '' ?> found</p>
  </div>

  <div class="shop-layout">
    <!-- FILTER PANEL -->
    <aside class="filter-panel">
      <div class="filter-title">🔍 Filters</div>

      <div class="filter-group">
        <div class="filter-group-label">Categories</div>
        <a href="/joesfit/shop.php<?= $search ? '?search='.urlencode($search) : '' ?>" 
           class="check-option" style="<?= !$category ? 'color:var(--accent)' : '' ?>">
          <span>All Categories</span>
        </a>
        <?php foreach ($cats as $cat): ?>
          <a href="/joesfit/shop.php?category=<?= $cat['slug'] ?><?= $search ? '&search='.urlencode($search) : '' ?>" 
             class="check-option" style="<?= $category === $cat['slug'] ? 'color:var(--accent)' : '' ?>">
            <?= htmlspecialchars($cat['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="filter-group">
        <div class="filter-group-label">Price Range</div>
        <label class="check-option">
          <input type="radio" name="price" value="0-1000"> Under ₱1,000
        </label>
        <label class="check-option">
          <input type="radio" name="price" value="1000-2000"> ₱1,000 – ₱2,000
        </label>
        <label class="check-option">
          <input type="radio" name="price" value="2000-3000"> ₱2,000 – ₱3,000
        </label>
        <label class="check-option">
          <input type="radio" name="price" value="3000+"> ₱3,000+
        </label>
      </div>

      <div class="filter-group">
        <div class="filter-group-label">Sizes</div>
        <?php foreach (['XS','S','M','L','XL','XXL','3XL'] as $sz): ?>
          <label class="check-option">
            <input type="checkbox" name="size[]" value="<?= $sz ?>"> <?= $sz ?>
          </label>
        <?php endforeach; ?>
      </div>

      <?php if ($category || $search): ?>
        <a href="/joesfit/shop.php" class="btn btn-outline btn-sm btn-full" style="margin-top:1rem">Clear Filters</a>
      <?php endif; ?>
    </aside>

    <!-- PRODUCTS -->
    <div>
      <!-- Sort bar -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
          <?php $chips = ['all'=>'All','varsity-jackets'=>'Varsity','bomber-jackets'=>'Bomber','windbreakers'=>'Wind','hooded-jackets'=>'Hooded','leather-jackets'=>'Leather']; ?>
          <?php foreach ($chips as $slug => $label): ?>
            <a href="/joesfit/shop.php<?= $slug !== 'all' ? '?category='.$slug : '' ?>" 
               class="cat-chip" style="border-color:var(--border-dark);color:var(--text-muted)<?= ($category === $slug || ($slug==='all' && !$category)) ? ';background:var(--accent);color:white;border-color:var(--accent)' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </div>
        <form method="GET" action="/joesfit/shop.php" style="display:flex;gap:0.5rem;align-items:center">
          <?php if ($category): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
          <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
          <label style="font-size:0.8rem;color:var(--text-muted)">Sort by</label>
          <select name="sort" class="form-control" style="width:auto;font-size:0.85rem" onchange="this.form.submit()">
            <option value="featured" <?= $sort==='featured'?'selected':'' ?>>Featured</option>
            <option value="newest"   <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High to Low</option>
            <option value="name" <?= $sort==='name'?'selected':'' ?>>Name A-Z</option>
          </select>
        </form>
      </div>

      <?php if (empty($products)): ?>
        <div style="text-align:center;padding:5rem 2rem;color:var(--text-muted)">
          <div style="font-size:3rem;margin-bottom:1rem">😕</div>
          <h3 style="margin-bottom:0.5rem">No products found</h3>
          <p>Try a different search or browse all categories</p>
          <a href="/joesfit/shop.php" class="btn btn-primary mt-2">View All Jackets</a>
        </div>
      <?php else: ?>
        <div class="products-grid">
          <?php foreach ($products as $p): ?>
            <?php $displayPrice = $p['sale_price'] ?: $p['price']; ?>
            <div class="product-card">
              <a href="/joesfit/product.php?slug=<?= $p['slug'] ?>">
                <div class="product-img-wrap">
                  <?php if ($p['image']): ?>
                    <img src="/joesfit/uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                  <?php else: ?>
                    <div class="img-placeholder"><span style="font-size:3rem">🧥</span><span><?= htmlspecialchars($p['name']) ?></span></div>
                  <?php endif; ?>
                  <?php if ($p['sale_price']): ?><span class="product-badge badge-sale">SALE</span><?php endif; ?>
                  <div class="product-actions-overlay">
                    <span class="btn btn-primary btn-sm" onclick="event.preventDefault();window.location='/joesfit/product.php?slug=<?= $p['slug'] ?>'">View Details</span>
                  </div>
                </div>
              </a>
              <div class="product-info">
                <div class="product-category"><?= htmlspecialchars($p['category_name']) ?></div>
                <a href="/joesfit/product.php?slug=<?= $p['slug'] ?>">
                  <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                </a>
                <div class="product-price">
                  <span class="price-current"><?= formatPrice($displayPrice) ?></span>
                  <?php if ($p['sale_price']): ?>
                    <span class="price-original"><?= formatPrice($p['price']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($p['stock'] <= 5 && $p['stock'] > 0): ?>
                  <div style="font-size:0.75rem;color:var(--accent);margin-top:0.3rem;font-weight:600">⚡ Only <?= $p['stock'] ?> left!</div>
                <?php elseif ($p['stock'] == 0): ?>
                  <div style="font-size:0.75rem;color:#999;margin-top:0.3rem">Out of Stock</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" class="page-btn">‹</a>
            <?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" class="page-btn">›</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
