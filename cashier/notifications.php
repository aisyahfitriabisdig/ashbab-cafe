<?php
/**
 * ASHBAB CAFE - Cashier Notifications
 * File: cashier/notifications.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('cashier', 'admin');

$pageTitle = 'Notifikasi';

// Mark all read on visit
db()->execute("UPDATE notifications SET status='read'");

$notifications = db()->fetchAll(
    "SELECT n.*, o.order_number, o.customer_name FROM notifications n
     LEFT JOIN orders o ON n.order_id = o.id
     ORDER BY n.created_at DESC LIMIT 50"
);

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=4">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/cashier.css?v=4">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Notifikasi</h1>
  </div>

  <div class="page-content">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-bell me-2" style="color:var(--primary)"></i>Semua Notifikasi</h2>
        <span class="badge bg-secondary rounded-pill"><?= count($notifications) ?></span>
      </div>

      <?php if (empty($notifications)): ?>
      <div class="text-center py-5 text-muted">
        <i class="fa-solid fa-bell-slash fa-3x mb-3 d-block opacity-25"></i>
        <p>Belum ada notifikasi</p>
      </div>
      <?php else: ?>
      <div>
        <?php foreach ($notifications as $n): ?>
        <div class="notif-item" style="border-radius:12px;border:1px solid #f1f5f9;margin-bottom:8px;padding:16px 20px;display:flex;gap:14px;align-items:flex-start">
          <div class="notif-item-icon" style="width:40px;height:40px;border-radius:10px;background:rgba(139,69,19,.1);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa-solid fa-receipt"></i>
          </div>
          <div class="flex-grow-1">
            <div class="fw-700" style="font-size:.9rem"><?= e($n['title']) ?></div>
            <div class="text-muted" style="font-size:.85rem;margin-top:2px"><?= e($n['message']) ?></div>
            <?php if ($n['order_number']): ?>
            <a href="<?= APP_URL ?>/cashier/orders.php" class="d-inline-block mt-2 text-decoration-none"
               style="font-size:.8rem;color:var(--primary);font-weight:600">
              Lihat Pesanan #<?= e($n['order_number']) ?>
            </a>
            <?php endif; ?>
          </div>
          <div class="text-muted" style="font-size:.75rem;white-space:nowrap">
            <?= formatDate($n['created_at'],'d M H:i') ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/cashier.js?v=<?= APP_VERSION ?>"></script>
