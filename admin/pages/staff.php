<?php
$pageTitle = 'Staff Management';
require_once '../includes/admin_header.php';
requireSuperAdmin();

$msg = ''; $msgType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_staff') {
        $id      = (int)($_POST['id'] ?? 0);
        $name    = sanitize($_POST['name']);
        $email   = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $role    = in_array($_POST['role'],['admin','staff']) ? $_POST['role'] : 'staff';
        $phone   = sanitize($_POST['phone'] ?? '');
        $active  = isset($_POST['is_active']) ? 1 : 0;
        $pw      = $_POST['password'] ?? '';

        if ($id) {
            if ($pw) {
                $hash = password_hash($pw, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE staff SET name=?,email=?,role=?,phone=?,is_active=?,password=? WHERE id=?")->execute([$name,$email,$role,$phone,$active,$hash,$id]);
            } else {
                $pdo->prepare("UPDATE staff SET name=?,email=?,role=?,phone=?,is_active=? WHERE id=?")->execute([$name,$email,$role,$phone,$active,$id]);
            }
            $msg = 'Staff member updated!';
        } else {
            if (!$pw) { $msg = 'Password is required for new staff.'; $msgType = 'error'; }
            else {
                $hash = password_hash($pw, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO staff (name,email,password,role,phone,is_active) VALUES (?,?,?,?,?,?)")->execute([$name,$email,$hash,$role,$phone,$active]);
                $msg = 'Staff member added!';
            }
        }
    }

    if ($action === 'delete_staff') {
        $id = (int)$_POST['id'];
        if ($id == $_SESSION['admin_id']) {
            $msg = 'You cannot delete your own account.'; $msgType = 'error';
        } else {
            $pdo->prepare("UPDATE staff SET is_active=0 WHERE id=?")->execute([$id]);
            $msg = 'Staff member deactivated.';
        }
    }
}

$editStaff = null;
if (isset($_GET['edit'])) {
    $es = $pdo->prepare("SELECT * FROM staff WHERE id=?");
    $es->execute([(int)$_GET['edit']]);
    $editStaff = $es->fetch();
}

$staffList = $pdo->query("SELECT * FROM staff ORDER BY role DESC, name ASC")->fetchAll();
?>

<?php if ($msg): ?>
  <div style="background:rgba(<?= $msgType==='error'?'239,68,68':'16,185,129' ?>,0.1);border:1px solid <?= $msgType==='error'?'#ef4444':'#10b981' ?>;border-radius:8px;padding:0.8rem 1rem;color:<?= $msgType==='error'?'#ef4444':'#10b981' ?>;margin-bottom:1.5rem">
    <?= $msgType==='error'?'⚠️':'✅' ?> <?= $msg ?>
  </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <!-- FORM -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><?= $editStaff ? '✏️ Edit Staff' : '➕ Add Staff' ?></span>
      <?php if ($editStaff): ?><a href="/joesfit/admin/pages/staff.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="save_staff">
        <?php if ($editStaff): ?><input type="hidden" name="id" value="<?= $editStaff['id'] ?>"><?php endif; ?>

        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editStaff['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($editStaff['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editStaff['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Role *</label>
          <select name="role" class="form-control">
            <option value="staff" <?= ($editStaff['role']??'')==='staff'?'selected':'' ?>>Staff</option>
            <option value="admin" <?= ($editStaff['role']??'')==='admin'?'selected':'' ?>>Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label"><?= $editStaff ? 'New Password (leave blank to keep)' : 'Password *' ?></label>
          <input type="password" name="password" class="form-control" <?= $editStaff?'':'required' ?> placeholder="••••••••">
        </div>
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.88rem;margin-bottom:1.5rem">
          <input type="checkbox" name="is_active" value="1" <?= ($editStaff['is_active']??1)?'checked':'' ?>> Active Account
        </label>
        <button type="submit" class="btn btn-primary btn-full"><?= $editStaff ? '💾 Update' : '➕ Add Staff Member' ?></button>
      </form>
    </div>
  </div>

  <!-- STAFF LIST -->
  <div class="card">
    <div class="card-header"><span class="card-title">Team (<?= count($staffList) ?>)</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($staffList as $s): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:0.7rem">
                  <div style="width:36px;height:36px;border-radius:50%;background:<?= $s['role']==='admin'?'var(--accent)':'var(--purple)' ?>;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">
                    <?= strtoupper(substr($s['name'],0,1)) ?>
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:0.88rem">
                      <?= htmlspecialchars($s['name']) ?>
                      <?php if ($s['id']==$_SESSION['admin_id']): ?>
                        <span style="font-size:0.7rem;color:var(--text-muted)">(you)</span>
                      <?php endif; ?>
                    </div>
                    <?php if ($s['phone']): ?><div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($s['phone']) ?></div><?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="font-size:0.85rem;color:var(--text-muted)"><?= htmlspecialchars($s['email']) ?></td>
              <td><span class="badge badge-<?= $s['role'] ?>"><?= ucfirst($s['role']) ?></span></td>
              <td style="font-size:0.8rem;color:var(--text-muted)"><?= $s['last_login'] ? date('M d, Y g:i A',strtotime($s['last_login'])) : 'Never' ?></td>
              <td><span class="badge <?= $s['is_active']?'badge-active':'badge-inactive' ?>"><?= $s['is_active']?'Active':'Inactive' ?></span></td>
              <td>
                <div style="display:flex;gap:0.4rem">
                  <a href="?edit=<?= $s['id'] ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
                  <?php if ($s['id'] != $_SESSION['admin_id']): ?>
                    <form method="POST" onsubmit="return confirm('Deactivate this staff member?')">
                      <input type="hidden" name="action" value="delete_staff">
                      <input type="hidden" name="id" value="<?= $s['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                    </form>
                  <?php endif; ?>
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
