<?php
require_once dirname(__DIR__) . '/../includes/config.php';
require_once __DIR__ . '/admin_auth.php';
requireAdmin();

$admin = getAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Validate database connection
if (!$pdo) {
    die('Database connection failed. Please check your configuration.');
}

// Unread notifications count
$unreadCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn() ?? 0;

// Pending orders count
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?? 0;

// Low stock count
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND is_active = 1")->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? $pageTitle . ' — Admin | ' . SITE_NAME : 'Admin Panel | ' . SITE_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="/joesfit/admin/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="adminSidebar">
    <div class="sidebar-logo">
      <div>
        <div class="brand">JOE'S<span>FIT</span></div>
      </div>
      <span class="badge">Admin</span>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">Overview</div>
      <a href="/joesfit/admin/index.php" class="nav-item <?= $currentPage==='index'?'active':'' ?>">
        <span class="nav-icon">📊</span> Dashboard
      </a>

      <div class="nav-section">Orders</div>
      <a href="/joesfit/admin/pages/orders.php" class="nav-item <?= $currentPage==='orders'?'active':'' ?>">
        <span class="nav-icon">📦</span> All Orders
        <?php if ($pendingOrders): ?>
          <span class="nav-badge"><?= $pendingOrders ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-section">Catalog</div>
      <a href="/joesfit/admin/pages/products.php" class="nav-item <?= $currentPage==='products'?'active':'' ?>">
        <span class="nav-icon">🧥</span> Products
        <?php if ($lowStock): ?>
          <span class="nav-badge" style="background:var(--yellow)"><?= $lowStock ?></span>
        <?php endif; ?>
      </a>
      <a href="/joesfit/admin/pages/categories.php" class="nav-item <?= $currentPage==='categories'?'active':'' ?>">
        <span class="nav-icon">🏷️</span> Categories
      </a>
      <a href="/joesfit/admin/pages/inventory.php" class="nav-item <?= $currentPage==='inventory'?'active':'' ?>">
        <span class="nav-icon">📋</span> Inventory
      </a>

      <div class="nav-section">Customers & Reviews</div>
      <a href="/joesfit/admin/pages/reviews.php" class="nav-item <?= $currentPage==='reviews'?'active':'' ?>">
        <span class="nav-icon">⭐</span> Reviews
      </a>

      <?php if (isAdmin()): ?>
      <div class="nav-section">Management</div>
      <a href="/joesfit/admin/pages/staff.php" class="nav-item <?= $currentPage==='staff'?'active':'' ?>">
        <span class="nav-icon">👥</span> Staff
      </a>
      <a href="/joesfit/admin/pages/reports.php" class="nav-item <?= $currentPage==='reports'?'active':'' ?>">
        <span class="nav-icon">📈</span> Reports
      </a>
      <a href="/joesfit/admin/pages/coupons.php" class="nav-item <?= $currentPage==='coupons'?'active':'' ?>">
        <span class="nav-icon">🎟️</span> Coupons
      </a>
      <?php endif; ?>

      <div class="nav-section">Notifications</div>
      <a href="/joesfit/admin/pages/notifications.php" class="nav-item <?= $currentPage==='notifications'?'active':'' ?>">
        <span class="nav-icon">🔔</span> Notifications
        <?php if ($unreadCount): ?>
          <span class="nav-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-section" style="margin-top:auto"></div>
      <a href="/joesfit/" target="_blank" class="nav-item">
        <span class="nav-icon">🌐</span> View Store
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="/joesfit/admin/logout.php" style="display:flex;align-items:center;gap:0.8rem;padding:0.7rem;border-radius:8px;transition:background 0.2s;color:var(--text-muted)" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background=''">
        <div class="admin-avatar"><?= strtoupper(substr($admin['name'], 0, 1)) ?></div>
        <div>
          <div class="admin-name"><?= htmlspecialchars($admin['name']) ?></div>
          <div class="admin-role"><?= $admin['role'] ?> · Sign out</div>
        </div>
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <!-- TOPBAR -->
    <header class="topbar">
      <button onclick="document.getElementById('adminSidebar').classList.toggle('mobile-open')" 
              style="display:none;width:36px;height:36px;border-radius:8px;background:var(--bg-3);border:1px solid var(--border);align-items:center;justify-content:center;font-size:1.1rem"
              id="mobileMenuBtn">☰</button>
      <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
      <div class="topbar-right">
        <span class="topbar-date"><?= date('D, M d Y · g:i A') ?></span>
        <button class="notif-btn" id="notifToggle" title="Notifications">
          🔔
          <?php if ($unreadCount): ?><div class="notif-dot"></div><?php endif; ?>
        </button>
      </div>
    </header>

    <!-- NOTIFICATION PANEL -->
    <div class="notif-panel" id="notifPanel">
      <div style="padding:1rem 1.2rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
        <strong style="font-size:0.9rem">Notifications</strong>
        <a href="/joesfit/admin/api/mark_read.php" style="font-size:0.75rem;color:var(--accent)">Mark all read</a>
      </div>
      <?php
      $notifs = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 15")->fetchAll() ?? [];
      foreach ($notifs as $n):
        $icons = ['new_order'=>'📦','low_stock'=>'⚠️','review'=>'⭐','payment'=>'💳','other'=>'📢'];
        $notifLink = $n['link'] ?? '#';
        // Fix relative links to include full path
        if ($notifLink !== '#' && strpos($notifLink, 'http') === false && strpos($notifLink, '/joesfit') === false) {
          $notifLink = '/joesfit' . $notifLink;
        }
      ?>
        <a href="<?= $notifLink ?>" class="notif-item <?= !$n['is_read']?'unread':'' ?>">
          <?php if (!$n['is_read']): ?><div class="notif-dot-indicator"></div><?php else: ?><div style="width:8px"></div><?php endif; ?>
          <div class="notif-text">
            <div class="notif-title"><?= $icons[$n['type']] ?? '📢' ?> <?= htmlspecialchars($n['title']) ?></div>
            <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
            <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if (empty($notifs)): ?>
        <div style="text-align:center;padding:3rem;color:var(--text-muted);font-size:0.85rem">No notifications</div>
      <?php endif; ?>
    </div>

    <div class="page-content">
