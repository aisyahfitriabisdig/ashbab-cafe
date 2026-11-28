<?php
/**
 * ASHBAB CAFE - Cashier Notifications
 * File: cashier/notifications.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('cashier', 'admin');

$pageTitle = 'Notifikasi';
ensureOrderSubmittedColumn();

// Mark all read on visit
db()->execute("UPDATE notifications SET status='read'");

$notifications = db()->fetchAll(
    "SELECT n.*, o.order_number, o.customer_name, o.payment_status, o.order_status, COALESCE(o.is_submitted, 1) as is_submitted
     FROM notifications n
     LEFT JOIN orders o ON n.order_id = o.id
     ORDER BY n.created_at DESC LIMIT 50"
);

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/cashier.css?v=5">
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

      <div id="cashierNotificationsList">
      <?php if (empty($notifications)): ?>
      <div class="text-center py-5 text-muted">
        <i class="fa-solid fa-bell-slash fa-3x mb-3 d-block opacity-25"></i>
        <p>Belum ada notifikasi</p>
      </div>
      <?php else: ?>
      <div>
        <?php foreach ($notifications as $n):
          $orderId      = (int)($n['order_id'] ?? 0);
          $payStatus    = (string)($n['payment_status'] ?? '');
          $isSubmitted  = (int)($n['is_submitted'] ?? 1) === 1;
          $isPendingPay = ($payStatus === 'pending');
          $isPayNotif   = $isPendingPay
                          || !$isSubmitted
                          || stripos((string)($n['title'] ?? ''), 'QRIS') !== false
                          || stripos((string)($n['title'] ?? ''), 'Pembayaran') !== false;

          if ($isPendingPay) {
              $targetUrl   = APP_URL . '/cashier/payments.php?payment_filter=pending' . ($orderId > 0 ? '&order_id=' . $orderId : '');
              $actionLabel = !empty($n['order_number']) ? 'Konfirmasi Pembayaran #' . $n['order_number'] : 'Buka Kelola Pembayaran';
          } elseif ($isPayNotif) {
              $targetUrl   = APP_URL . '/cashier/payments.php?payment_filter=all' . ($orderId > 0 ? '&order_id=' . $orderId : '');
              $actionLabel = !empty($n['order_number']) ? 'Lihat Pembayaran #' . $n['order_number'] : 'Buka Kelola Pembayaran';
          } else {
              $targetUrl   = APP_URL . '/cashier/orders.php' . ($orderId > 0 ? '?order_id=' . $orderId : '');
              $actionLabel = !empty($n['order_number']) ? 'Lihat Pesanan #' . $n['order_number'] : 'Buka Kelola Pesanan';
          }
        ?>
        <div class="notif-item" onclick="window.location='<?= e($targetUrl) ?>'"
             style="cursor:pointer;border-radius:12px;border:1px solid <?= $isPendingPay ? 'var(--primary)' : 'rgba(255,255,255,.08)' ?>;margin-bottom:10px;padding:16px 20px;display:flex;gap:14px;align-items:flex-start;transition:all .2s ease">
          <div class="notif-item-icon" style="width:42px;height:42px;border-radius:10px;background:rgba(245,197,24,.15);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa-solid <?= $isPayNotif ? 'fa-credit-card' : 'fa-receipt' ?>"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <div class="fw-700" style="font-size:.92rem"><?= e($n['title']) ?></div>
              <?php if ($isPendingPay): ?>
                <span class="badge bg-warning text-dark" style="font-size:.7rem">Menunggu Konfirmasi Pembayaran</span>
              <?php endif; ?>
            </div>
            <div class="text-muted" style="font-size:.85rem;margin-top:3px"><?= e($n['message']) ?></div>
            <?php if ($n['order_number']): ?>
            <a href="<?= e($targetUrl) ?>" onclick="event.stopPropagation()" class="d-inline-flex align-items-center gap-1 mt-2 text-decoration-none"
               style="font-size:.82rem;color:var(--primary);font-weight:700">
              <i class="fa-solid <?= $isPayNotif ? 'fa-credit-card' : 'fa-receipt' ?>"></i>
              <span><?= e($actionLabel) ?></span>
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/cashier.js?v=<?= APP_VERSION ?>"></script>
