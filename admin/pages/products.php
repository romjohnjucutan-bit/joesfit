<?php
$pageTitle = 'Products Management';
require_once '../includes/admin_header.php';

$msg = '';
// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_product') {
        $id          = (int)($_POST['id'] ?? 0);
        $categoryId  = (int)$_POST['category_id'];
        $name        = sanitize($_POST['name']);
        $slug        = sanitize($_POST['slug'] ?: strtolower(preg_replace('/[^a-z0-9]+/','-',$_POST['name'])));
        $description = sanitize($_POST['description'] ?? '');
        $price       = (float)$_POST['price'];
        $salePrice   = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
        $stock       = (int)$_POST['stock'];
        $sku         = sanitize($_POST['sku'] ?? '');
        $sizes       = sanitize($_POST['sizes'] ?? '');
        $colors      = sanitize($_POST['colors'] ?? '');
        $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        // Handle image upload
        $imageName = $_POST['existing_image'] ?? null;
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $imageName = uniqid('prod_') . '.' . $ext;
                $dest = UPLOAD_PATH . $imageName;
                if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
                move_uploaded_file($_FILES['image']['tmp_name'], $dest);
            }
        }

        if ($id) {
            $pdo->prepare("UPDATE products SET category_id=?,name=?,slug=?,description=?,price=?,sale_price=?,stock=?,sku=?,sizes=?,colors=?,is_featured=?,is_active=?,image=COALESCE(?,image),updated_at=NOW() WHERE id=?")
                ->execute([$categoryId,$name,$slug,$description,$price,$salePrice,$stock,$sku,$sizes,$colors,$isFeatured,$isActive,$imageName,$id]);
            $msg = 'Product updated successfully!';
        } else {
            $pdo->prepare("INSERT INTO products (category_id,name,slug,description,price,sale_price,stock,sku,sizes,colors,is_featured,is_active,image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$categoryId,$name,$slug,$description,$price,$salePrice,$stock,$sku,$sizes,$colors,$isFeatured,$isActive,$imageName]);
            $msg = 'Product added successfully!';
        }
    }

    if ($action === 'delete_product') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([$id]);
        $msg = 'Product removed.';
    }

    if ($action === 'toggle_active') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id=?")->execute([$id]);
    }
}

// Edit product
$editProduct = null;
if (isset($_GET['edit'])) {
    $ep = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $ep->execute([(int)$_GET['edit']]);
    $editProduct = $ep->fetch();
}

// Fetch products
$search = sanitize($_GET['search'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$where = ['1=1'];
$params = [];
if ($search) { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter) { $where[] = 'p.category_id=?'; $params[] = $catFilter; }
$whereSQL = implode(' AND ', $where);

$products = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id WHERE $whereSQL ORDER BY p.created_at DESC");
$products->execute($params);
$products = $products->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();
?>

<?php if ($msg): ?>
  <div style="background:rgba(16,185,129,0.1);border:1px solid #10b981;border-radius:8px;padding:0.8rem 1rem;color:#10b981;margin-bottom:1.5rem">
    ✅ <?= $msg ?>
  </div>
<?php endif; ?>

<!-- ADD/EDIT FORM -->
<div class="card" style="margin-bottom:1.5rem">
  <div class="card-header">
    <span class="card-title"><?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?></span>
    <?php if ($editProduct): ?><a href="/joesfit/admin/pages/products.php" class="btn btn-outline btn-sm">Cancel Edit</a><?php endif; ?>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_product">
      <?php if ($editProduct): ?><input type="hidden" name="id" value="<?= $editProduct['id'] ?>"><?php endif; ?>
      <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editProduct['image'] ?? '') ?>">

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Product Name *</label>
          <input type="text" name="name" class="form-control" required
                 value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>"
                 oninput="this.form.slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')">
        </div>
        <div class="form-group">
          <label class="form-label">Category *</label>
          <select name="category_id" class="form-control" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($editProduct['category_id']??0)===$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">URL Slug *</label>
          <input type="text" name="slug" class="form-control" required value="<?= htmlspecialchars($editProduct['slug'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">SKU</label>
          <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($editProduct['sku'] ?? '') ?>" placeholder="JF-CAT-001">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
      </div>

      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Price (₱) *</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0" required value="<?= $editProduct['price'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Sale Price (₱)</label>
          <input type="number" name="sale_price" class="form-control" step="0.01" min="0" value="<?= $editProduct['sale_price'] ?? '' ?>" placeholder="Leave blank if none">
        </div>
        <div class="form-group">
          <label class="form-label">Stock Quantity *</label>
          <input type="number" name="stock" class="form-control" min="0" required value="<?= $editProduct['stock'] ?? 0 ?>">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Available Sizes (comma-separated)</label>
          <input type="text" name="sizes" class="form-control" value="<?= htmlspecialchars($editProduct['sizes'] ?? '') ?>" placeholder="S,M,L,XL,XXL">
          <div class="form-hint">e.g. XS,S,M,L,XL,XXL,3XL</div>
        </div>
        <div class="form-group">
          <label class="form-label">Available Colors (comma-separated)</label>
          <input type="text" name="colors" class="form-control" value="<?= htmlspecialchars($editProduct['colors'] ?? '') ?>" placeholder="Black,Navy,Red">
          <div class="form-hint">e.g. Black/White,Navy/Gold,Red/Black</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Product Image</label>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($editProduct['image'])): ?>
          <div style="margin-top:0.5rem;display:flex;align-items:center;gap:0.5rem">
            <img src="/joesfit/uploads/products/<?= htmlspecialchars($editProduct['image']) ?>" class="img-preview" alt="">
            <span style="font-size:0.78rem;color:var(--text-muted)">Current image (upload new to replace)</span>
          </div>
        <?php endif; ?>
      </div>

      <div style="display:flex;gap:2rem;margin-bottom:1.5rem">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.88rem">
          <input type="checkbox" name="is_featured" value="1" <?= ($editProduct['is_featured']??0)?'checked':'' ?>> Featured Product
        </label>
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.88rem">
          <input type="checkbox" name="is_active" value="1" <?= ($editProduct['is_active']??1)?'checked':'' ?>> Active / Visible
        </label>
      </div>

      <button type="submit" class="btn btn-primary"><?= $editProduct ? '💾 Update Product' : '➕ Add Product' ?></button>
    </form>
  </div>
</div>

<!-- PRODUCTS LIST -->
<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem">
    <span class="card-title">All Products (<?= count($products) ?>)</span>
    <form method="GET" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
      <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <select name="cat" class="form-control" style="width:auto">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catFilter===$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <div style="width:45px;height:55px;border-radius:6px;background:var(--bg-3);overflow:hidden">
                <?php if ($p['image']): ?>
                  <img src="/joesfit/uploads/products/<?= htmlspecialchars($p['image']) ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">🧥</div>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($p['name']) ?></div>
              <div style="font-size:0.75rem;color:var(--text-muted);font-family:var(--font-mono)"><?= $p['sku'] ?></div>
              <?php if ($p['is_featured']): ?><span class="badge badge-paid" style="font-size:0.65rem;margin-top:0.2rem">Featured</span><?php endif; ?>
            </td>
            <td style="font-size:0.85rem"><?= htmlspecialchars($p['cat_name']) ?></td>
            <td>
              <div style="font-weight:700;color:var(--accent)">₱<?= number_format($p['sale_price']?:$p['price'],2) ?></div>
              <?php if ($p['sale_price']): ?><div style="font-size:0.75rem;color:var(--text-muted);text-decoration:line-through">₱<?= number_format($p['price'],2) ?></div><?php endif; ?>
            </td>
            <td>
              <span style="font-weight:700;color:<?= $p['stock']==0?'#ef4444':($p['stock']<=5?'#f59e0b':'var(--green)') ?>">
                <?= $p['stock'] ?>
              </span>
              <?php if ($p['stock']==0): ?>
                <div style="font-size:0.72rem;color:#ef4444">Out of stock</div>
              <?php elseif ($p['stock']<=5): ?>
                <div style="font-size:0.72rem;color:#f59e0b">Low stock</div>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="badge <?= $p['is_active']?'badge-active':'badge-inactive' ?>" style="cursor:pointer">
                  <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                </button>
              </form>
            </td>
            <td>
              <div style="display:flex;gap:0.4rem;flex-wrap:wrap">
                <a href="?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
                <a href="/joesfit/product.php?slug=<?= $p['slug'] ?>" target="_blank" class="btn btn-outline btn-sm">👁</a>
                <form method="POST" onsubmit="return confirm('Delete this product?')">
                  <input type="hidden" name="action" value="delete_product">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted)">No products found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
