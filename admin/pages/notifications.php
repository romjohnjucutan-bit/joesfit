<?php
require_once '../../includes/config.php';
require_once '../includes/admin_auth.php';

// Handle actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);
    }
    if ($action === 'mark_all_read') {
        $pdo->query("UPDATE notifications SET is_read=1");
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM notifications WHERE id=?")->execute([$id]);
    }
    if ($action === 'clear_all') {
        $pdo->query("DELETE FROM notifications WHERE is_read=1");
    }
    header('Location: /joesfit/admin/pages/notifications.php');
    exit;
}

$pageTitle = 'Notifications';
require_once '../includes/admin_header.php';

$filter = sanitize($_GET['filter'] ?? 'all');
$where  = [];
if ($filter === 'unread') $where[] = 'is_read=0';
if ($filter === 'read')   $where[] = 'is_read=1';
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$notifications = $pdo->query("SELECT * FROM notifications $whereSQL ORDER BY created_at DESC")->fetchAll();
$unread = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();

$typeIcons = ['new_order'=>'📦','low_stock'=>'⚠️','review'=>'⭐','payment'=>'💳','other'=>'📢'];
$typeColors = ['new_order'=>'var(--blue)','low_stock'=>'#f59e0b','review'=>'#c9a84c','payment'=>'var(--green)','other'=>'var(--text-muted)'];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
  <div style="display:flex;gap:0.5rem">
    <?php foreach (['all'=>'All','unread'=>"Unread ($unread)",'read'=>'Read'] as $f=>$l): ?>
      <a href="?filter=<?= $f ?>" style="padding:0.5rem 1rem;border-radius:8px;border:1px solid var(--border);font-size:0.85rem;<?= $filter===$f?'background:var(--accent);border-color:var(--accent);color:white':'color:var(--text-muted)' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <div style="display:flex;gap:0.5rem">
    <form method="POST">
      <input type="hidden" name="action" value="mark_all_read">
      <button type="submit" class="btn btn-outline btn-sm">✅ Mark All Read</button>
    </form>
    <form method="POST" onsubmit="return confirm('Clear all read notifications?')">
      <input type="hidden" name="action" value="clear_all">
      <button type="submit" class="btn btn-danger btn-sm">🗑 Clear Read</button>
    </form>
  </div>
</div>

<div style="display:flex;flex-direction:column;gap:0.8rem">
  <?php if (empty($notifications)): ?>
    <div class="card" style="text-align:center;padding:4rem;color:var(--text-muted)">
      <div style="font-size:3rem;margin-bottom:1rem">🔔</div>
      <p>No notifications found</p>
    </div>
  <?php endif; ?>
  <?php foreach ($notifications as $n): ?>
    <div class="card" style="<?= !$n['is_read']?'border-color:rgba(232,50,26,0.3);background:rgba(232,50,26,0.03)':'' ?>">
      <div style="padding:1rem 1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
        <div style="width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">
          <?= $typeIcons[$n['type']] ?? '📢' ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:0.9rem;margin-bottom:0.2rem;display:flex;align-items:center;gap:0.5rem">
            <?= htmlspecialchars($n['title']) ?>
            <?php if (!$n['is_read']): ?>
              <span style="width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0"></span>
            <?php endif; ?>
          </div>
          <div style="font-size:0.83rem;color:var(--text-muted)"><?= htmlspecialchars($n['message']) ?></div>
          <div style="font-size:0.75rem;color:var(--text-light);margin-top:0.2rem"><?= timeAgo($n['created_at']) ?> — <?= date('M d, Y g:i A',strtotime($n['created_at'])) ?></div>
        </div>
        <div style="display:flex;gap:0.5rem;flex-shrink:0">
          <?php if ($n['link']): ?>
            <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-outline btn-sm">View →</a>
          <?php endif; ?>
          <?php if (!$n['is_read']): ?>
            <form method="POST">
              <input type="hidden" name="action" value="mark_read">
              <input type="hidden" name="id" value="<?= $n['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm">✓ Read</button>
            </form>
          <?php endif; ?>
          <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $n['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">🗑</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
