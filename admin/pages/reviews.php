<?php
$pageTitle = 'Reviews Management';
require_once '../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)$_POST['id'];

    if ($action === 'approve')  $pdo->prepare("UPDATE reviews SET is_approved=1 WHERE id=?")->execute([$id]);
    if ($action === 'reject')   $pdo->prepare("UPDATE reviews SET is_approved=0 WHERE id=?")->execute([$id]);
    if ($action === 'delete')   $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$id]);
    header('Location: /joesfit/admin/pages/reviews.php');
    exit;
}

$filter = sanitize($_GET['filter'] ?? 'pending');
$where  = [];
if ($filter === 'pending')  $where[] = 'r.is_approved=0';
if ($filter === 'approved') $where[] = 'r.is_approved=1';
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$reviews = $pdo->query("
  SELECT r.*, p.name as product_name, p.slug as product_slug
  FROM reviews r JOIN products p ON r.product_id=p.id
  $whereSQL ORDER BY r.created_at DESC
")->fetchAll();

$pendingCount  = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved=0")->fetchColumn();
$approvedCount = $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved=1")->fetchColumn();
?>

<!-- Filter tabs -->
<div style="display:flex;gap:0.5rem;margin-bottom:1.5rem">
  <a href="?filter=pending"  style="padding:0.5rem 1rem;border-radius:8px;border:1px solid var(--border);font-size:0.85rem;<?= $filter==='pending'?'background:var(--accent);border-color:var(--accent);color:white':'color:var(--text-muted)' ?>">
    ⏳ Pending (<?= $pendingCount ?>)
  </a>
  <a href="?filter=approved" style="padding:0.5rem 1rem;border-radius:8px;border:1px solid var(--border);font-size:0.85rem;<?= $filter==='approved'?'background:var(--green);border-color:var(--green);color:white':'color:var(--text-muted)' ?>">
    ✅ Approved (<?= $approvedCount ?>)
  </a>
  <a href="?filter=all"      style="padding:0.5rem 1rem;border-radius:8px;border:1px solid var(--border);font-size:0.85rem;<?= $filter==='all'?'background:var(--blue);border-color:var(--blue);color:white':'color:var(--text-muted)' ?>">
    📋 All
  </a>
</div>

<div style="display:grid;gap:1rem">
  <?php if (empty($reviews)): ?>
    <div class="card" style="text-align:center;padding:3rem;color:var(--text-muted)">No reviews found</div>
  <?php endif; ?>
  <?php foreach ($reviews as $r): ?>
    <div class="card">
      <div style="padding:1.2rem 1.5rem;display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:1rem">
        <div style="flex:1">
          <div style="display:flex;align-items:center;gap:1rem;margin-bottom:0.8rem;flex-wrap:wrap">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--accent);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">
              <?= strtoupper(substr($r['customer_name'],0,1)) ?>
            </div>
            <div>
              <div style="font-weight:700"><?= htmlspecialchars($r['customer_name']) ?></div>
              <div style="font-size:0.78rem;color:var(--text-muted)"><?= date('M d, Y g:i A',strtotime($r['created_at'])) ?></div>
            </div>
            <div style="color:#c9a84c;font-size:1rem"><?= str_repeat('★',$r['rating']) . str_repeat('☆',5-$r['rating']) ?></div>
            <span class="badge <?= $r['is_approved']?'badge-active':'badge-pending' ?>"><?= $r['is_approved']?'Approved':'Pending' ?></span>
          </div>

          <div style="font-size:0.88rem;color:var(--text-muted);margin-bottom:0.3rem">
            Product: <a href="/joesfit/product.php?slug=<?= $r['product_slug'] ?>" target="_blank" style="color:var(--accent)"><?= htmlspecialchars($r['product_name']) ?></a>
          </div>

          <?php if ($r['title']): ?>
            <div style="font-weight:700;margin-bottom:0.4rem"><?= htmlspecialchars($r['title']) ?></div>
          <?php endif; ?>
          <?php if ($r['body']): ?>
            <div style="font-size:0.88rem;line-height:1.6;color:var(--text-muted)">"<?= htmlspecialchars($r['body']) ?>"</div>
          <?php endif; ?>
        </div>

        <div style="display:flex;gap:0.5rem;flex-shrink:0">
          <?php if (!$r['is_approved']): ?>
            <form method="POST">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <input type="hidden" name="action" value="approve">
              <button type="submit" class="btn btn-success btn-sm">✅ Approve</button>
            </form>
          <?php else: ?>
            <form method="POST">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <button type="submit" class="btn btn-warning btn-sm">⏸ Unpublish</button>
            </form>
          <?php endif; ?>
          <form method="POST" onsubmit="return confirm('Delete this review?')">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
