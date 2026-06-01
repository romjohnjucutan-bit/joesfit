<?php
$pageTitle = 'Coupons Management';
require_once '../includes/admin_header.php';
requireSuperAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_coupon') {
        $id       = (int)($_POST['id'] ?? 0);
        $code     = strtoupper(sanitize($_POST['code']));
        $type     = in_array($_POST['type'],['percent','fixed']) ? $_POST['type'] : 'percent';
        $value    = (float)$_POST['value'];
        $minOrder = (float)($_POST['min_order'] ?? 0);
        $maxUses  = $_POST['max_uses'] !== '' ? (int)$_POST['max_uses'] : null;
        $expires  = $_POST['expires_at'] ?: null;
        $active   = isset($_POST['is_active']) ? 1 : 0;

        if ($id) {
            $pdo->prepare("UPDATE coupons SET code=?,type=?,value=?,min_order=?,max_uses=?,expires_at=?,is_active=? WHERE id=?")
                ->execute([$code,$type,$value,$minOrder,$maxUses,$expires,$active,$id]);
            $msg = 'Coupon updated!';
        } else {
            $pdo->prepare("INSERT INTO coupons (code,type,value,min_order,max_uses,expires_at,is_active) VALUES (?,?,?,?,?,?,?)")
                ->execute([$code,$type,$value,$minOrder,$maxUses,$expires,$active]);
            $msg = 'Coupon created!';
        }
    }

    if ($action === 'delete_coupon') {
        $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'Coupon deleted.';
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE coupons SET is_active=NOT is_active WHERE id=?")->execute([$id]);
    }
}

$editCoupon = null;
if (isset($_GET['edit'])) {
    $ec = $pdo->prepare("SELECT * FROM coupons WHERE id=?");
    $ec->execute([(int)$_GET['edit']]);
    $editCoupon = $ec->fetch();
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
?>

<?php if ($msg): ?>
  <div style="background:rgba(16,185,129,0.1);border:1px solid #10b981;border-radius:8px;padding:0.8rem 1rem;color:#10b981;margin-bottom:1.5rem">✅ <?= $msg ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <!-- FORM -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><?= $editCoupon ? '✏️ Edit Coupon' : '➕ New Coupon' ?></span>
      <?php if ($editCoupon): ?><a href="/joesfit/admin/pages/coupons.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="save_coupon">
        <?php if ($editCoupon): ?><input type="hidden" name="id" value="<?= $editCoupon['id'] ?>"><?php endif; ?>

        <div class="form-group">
          <label class="form-label">Coupon Code *</label>
          <input type="text" name="code" class="form-control" required
                 value="<?= htmlspecialchars($editCoupon['code'] ?? '') ?>"
                 placeholder="e.g. JOESFIT10"
                 style="text-transform:uppercase"
                 oninput="this.value=this.value.toUpperCase()">
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
              <option value="percent" <?= ($editCoupon['type']??'')==='percent'?'selected':'' ?>>Percent (%)</option>
              <option value="fixed"   <?= ($editCoupon['type']??'')==='fixed'?'selected':'' ?>>Fixed (₱)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Discount Value *</label>
            <input type="number" name="value" class="form-control" step="0.01" min="0" required value="<?= $editCoupon['value'] ?? '' ?>">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Min. Order (₱)</label>
            <input type="number" name="min_order" class="form-control" step="0.01" min="0" value="<?= $editCoupon['min_order'] ?? 0 ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Max Uses</label>
            <input type="number" name="max_uses" class="form-control" min="1" value="<?= $editCoupon['max_uses'] ?? '' ?>" placeholder="Unlimited">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Expiry Date</label>
          <input type="date" name="expires_at" class="form-control" value="<?= $editCoupon['expires_at'] ?? '' ?>">
        </div>
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.88rem;margin-bottom:1.5rem">
          <input type="checkbox" name="is_active" value="1" <?= ($editCoupon['is_active']??1)?'checked':'' ?>> Active
        </label>
        <button type="submit" class="btn btn-primary btn-full"><?= $editCoupon ? '💾 Update' : '➕ Create Coupon' ?></button>
      </form>
    </div>
  </div>

  <!-- LIST -->
  <div class="card">
    <div class="card-header"><span class="card-title">All Coupons (<?= count($coupons) ?>)</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Code</th><th>Discount</th><th>Min. Order</th><th>Used / Max</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($coupons as $c): ?>
            <tr>
              <td>
                <code style="font-family:var(--font-mono);font-weight:700;color:var(--accent);font-size:0.9rem"><?= htmlspecialchars($c['code']) ?></code>
              </td>
              <td style="font-weight:700">
                <?= $c['type']==='percent' ? $c['value'].'%' : '₱'.number_format($c['value'],2) ?>
              </td>
              <td>₱<?= number_format($c['min_order'],0) ?></td>
              <td style="font-size:0.85rem">
                <?= $c['used_count'] ?> / <?= $c['max_uses'] ?: '∞' ?>
                <div style="height:4px;background:var(--border);border-radius:2px;margin-top:0.2rem;width:80px">
                  <?php if ($c['max_uses']): ?>
                    <div style="width:<?= min(100,round(($c['used_count']/$c['max_uses'])*100)) ?>%;height:100%;background:var(--accent);border-radius:2px"></div>
                  <?php endif; ?>
                </div>
              </td>
              <td style="font-size:0.82rem;color:var(--text-muted)">
                <?= $c['expires_at'] ? date('M d, Y',strtotime($c['expires_at'])) : 'No expiry' ?>
                <?php if ($c['expires_at'] && $c['expires_at'] < date('Y-m-d')): ?>
                  <div style="font-size:0.72rem;color:#ef4444">Expired</div>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button type="submit" class="badge <?= $c['is_active']?'badge-active':'badge-inactive' ?>" style="cursor:pointer">
                    <?= $c['is_active']?'Active':'Inactive' ?>
                  </button>
                </form>
              </td>
              <td>
                <div style="display:flex;gap:0.4rem">
                  <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                  <form method="POST" onsubmit="return confirm('Delete this coupon?')">
                    <input type="hidden" name="action" value="delete_coupon">
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
