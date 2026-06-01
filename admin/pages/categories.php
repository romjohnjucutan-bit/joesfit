<?php
$pageTitle = 'Categories Management';
require_once '../includes/admin_header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_category') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = sanitize($_POST['name']);
        $slug     = sanitize($_POST['slug'] ?: strtolower(preg_replace('/[^a-z0-9]+/','-',$_POST['name'])));
        $desc     = sanitize($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id) {
            $pdo->prepare("UPDATE categories SET name=?,slug=?,description=?,is_active=? WHERE id=?")->execute([$name,$slug,$desc,$isActive,$id]);
            $msg = 'Category updated!';
        } else {
            $pdo->prepare("INSERT INTO categories (name,slug,description,is_active) VALUES (?,?,?,?)")->execute([$name,$slug,$desc,$isActive]);
            $msg = 'Category added!';
        }
    }

    if ($action === 'delete_category') {
        $id = (int)$_POST['id'];
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
        $inUse->execute([$id]);
        if ($inUse->fetchColumn() > 0) {
            $msg = 'Cannot delete: category has products.';
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
            $msg = 'Category deleted.';
        }
    }
}

$editCat = null;
if (isset($_GET['edit'])) {
    $ec = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $ec->execute([(int)$_GET['edit']]);
    $editCat = $ec->fetch();
}

$cats = $pdo->query("
  SELECT c.*, COUNT(p.id) as product_count
  FROM categories c LEFT JOIN products p ON c.id=p.category_id AND p.is_active=1
  GROUP BY c.id ORDER BY c.name
")->fetchAll();
?>

<?php if ($msg): ?>
  <div style="background:rgba(16,185,129,0.1);border:1px solid #10b981;border-radius:8px;padding:0.8rem 1rem;color:#10b981;margin-bottom:1.5rem">✅ <?= $msg ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <!-- FORM -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><?= $editCat ? '✏️ Edit Category' : '➕ New Category' ?></span>
      <?php if ($editCat): ?><a href="/joesfit/admin/pages/categories.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="save_category">
        <?php if ($editCat): ?><input type="hidden" name="id" value="<?= $editCat['id'] ?>"><?php endif; ?>
        <div class="form-group">
          <label class="form-label">Category Name *</label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editCat['name'] ?? '') ?>"
                 oninput="this.form.slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')">
        </div>
        <div class="form-group">
          <label class="form-label">URL Slug *</label>
          <input type="text" name="slug" class="form-control" required value="<?= htmlspecialchars($editCat['slug'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editCat['description'] ?? '') ?></textarea>
        </div>
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.88rem;margin-bottom:1.5rem">
          <input type="checkbox" name="is_active" value="1" <?= ($editCat['is_active']??1)?'checked':'' ?>> Active
        </label>
        <button type="submit" class="btn btn-primary btn-full"><?= $editCat ? '💾 Update' : '➕ Add Category' ?></button>
      </form>
    </div>
  </div>

  <!-- LIST -->
  <div class="card">
    <div class="card-header"><span class="card-title">All Categories (<?= count($cats) ?>)</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($cats as $c): ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($c['name']) ?></div>
                <?php if ($c['description']): ?><div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars(substr($c['description'],0,60)) ?>...</div><?php endif; ?>
              </td>
              <td><code style="font-size:0.78rem;color:var(--accent)"><?= htmlspecialchars($c['slug']) ?></code></td>
              <td style="text-align:center">
                <a href="/joesfit/admin/pages/products.php?cat=<?= $c['id'] ?>" style="color:var(--accent);font-weight:700"><?= $c['product_count'] ?></a>
              </td>
              <td><span class="badge <?= $c['is_active']?'badge-active':'badge-inactive' ?>"><?= $c['is_active']?'Active':'Inactive' ?></span></td>
              <td>
                <div style="display:flex;gap:0.4rem">
                  <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
                  <a href="/joesfit/shop.php?category=<?= $c['slug'] ?>" target="_blank" class="btn btn-outline btn-sm">👁</a>
                  <form method="POST" onsubmit="return confirm('Delete this category?')">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
