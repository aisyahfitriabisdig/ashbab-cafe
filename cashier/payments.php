<?php
/**
 * ASHBAB CAFE - Cashier Payment Management (Pra-Pesanan & Verifikasi Pembayaran QRIS)
 * File: cashier/payments.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('cashier', 'admin');

ensureOrderSubmittedColumn();

$pageTitle     = 'Kelola Pembayaran';
$paymentFilter = clean($_GET['payment_filter'] ?? 'all');
$focusOrderId  = (int)($_GET['order_id'] ?? 0);
if (!in_array($paymentFilter, ['all', 'pending', 'confirmed', 'rejected'], true)) {
    $paymentFilter = 'all';
}

$payCounts = [
    'pending'        => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE payment_status='pending'")['c'] ?? 0),
    'waiting_submit' => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE payment_status='confirmed' AND COALESCE(is_submitted,1)=0")['c'] ?? 0),
    'submitted'      => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE payment_status='confirmed' AND COALESCE(is_submitted,1)=1")['c'] ?? 0),
    'rejected'       => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE payment_status='rejected'")['c'] ?? 0),
];

$sql    = "SELECT * FROM orders WHERE 1=1";
$params = [];
if ($paymentFilter !== 'all') {
    if ($focusOrderId > 0) {
        $sql .= " AND (payment_status=? OR id=?)";
        $params[] = $paymentFilter;
        $params[] = $focusOrderId;
    } else {
        $sql .= " AND payment_status=?";
        $params[] = $paymentFilter;
    }
}
if ($focusOrderId > 0) {
    $sql .= " ORDER BY CASE WHEN id=? THEN -1 WHEN payment_status='pending' THEN 0 WHEN COALESCE(is_submitted,1)=0 THEN 1 ELSE 2 END, updated_at DESC, id DESC LIMIT 60";
    $params[] = $focusOrderId;
} else {
    $sql .= " ORDER BY CASE WHEN payment_status='pending' THEN 0 WHEN COALESCE(is_submitted,1)=0 THEN 1 ELSE 2 END, updated_at DESC, id DESC LIMIT 60";
}
$payments = db()->fetchAll($sql, $params);

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
    <h1 class="topbar-title">Kelola Pembayaran</h1>
    <div class="topbar-right">
      <button class="topbar-notif" id="notifBell" onclick="window.location='<?= APP_URL ?>/cashier/notifications.php'">
        <i class="fa-solid fa-bell"></i>
        <span class="notif-badge" id="notifBadge" style="display:none">0</span>
      </button>
    </div>
  </div>

  <div class="page-content">
    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat-widget warning">
          <div class="stat-icon warning"><i class="fa-solid fa-hourglass-half"></i></div>
          <div class="stat-value" id="pay_count_pending"><?= $payCounts['pending'] ?></div>
          <div class="stat-label">Belum Dikonfirmasi</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-widget info">
          <div class="stat-icon info"><i class="fa-solid fa-mobile-screen-button"></i></div>
          <div class="stat-value" id="pay_count_waiting_submit"><?= $payCounts['waiting_submit'] ?></div>
          <div class="stat-label">Menunggu Buat Pesanan</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-widget success">
          <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-value" id="pay_count_submitted"><?= $payCounts['submitted'] ?></div>
          <div class="stat-label">Sudah Jadi Pesanan</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-widget danger">
          <div class="stat-icon danger"><i class="fa-solid fa-circle-xmark"></i></div>
          <div class="stat-value" id="pay_count_rejected"><?= $payCounts['rejected'] ?></div>
          <div class="stat-label">Pembayaran Ditolak</div>
        </div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="status-tabs mb-4">
      <a href="<?= APP_URL ?>/cashier/payments.php?payment_filter=all" class="status-tab <?= $paymentFilter==='all'?'active':'' ?>">
        <i class="fa-solid fa-list me-1"></i>Semua Pembayaran
      </a>
      <a href="<?= APP_URL ?>/cashier/payments.php?payment_filter=pending" class="status-tab <?= $paymentFilter==='pending'?'active':'' ?>">
        Belum Bayar / Menunggu Konfirmasi
      </a>
      <a href="<?= APP_URL ?>/cashier/payments.php?payment_filter=confirmed" class="status-tab <?= $paymentFilter==='confirmed'?'active':'' ?>">
        Sudah Bayar / Dikonfirmasi
      </a>
      <a href="<?= APP_URL ?>/cashier/payments.php?payment_filter=rejected" class="status-tab <?= $paymentFilter==='rejected'?'active':'' ?>">
        Ditolak
      </a>
    </div>

    <!-- Payments Grid -->
    <div id="cashierPaymentsGrid">
    <?php if (empty($payments)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-credit-card fa-3x mb-3 d-block opacity-25"></i>
      <p>Belum ada data pembayaran pada kategori ini</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($payments as $p):
        $isSubmitted = (int)($p['is_submitted'] ?? 1) === 1;
        $isFocused   = ($focusOrderId > 0 && (int)$p['id'] === $focusOrderId);
        $pItems = db()->fetchAll(
            "SELECT oi.quantity, m.name FROM order_items oi JOIN menus m ON oi.menu_id=m.id WHERE oi.order_id=?",
            [$p['id']]
        );
      ?>
      <div class="col-md-6 col-lg-4" id="payment-card-<?= (int)$p['id'] ?>">
        <div class="order-card" style="<?= $isFocused ? 'border:2px solid var(--primary);box-shadow:0 0 0 4px rgba(245,197,24,.22);' : '' ?>">
          <div class="order-card-header">
            <div>
              <div class="order-number">
                <?= e($p['order_number']) ?>
                <?php if ($isFocused): ?>
                  <span class="badge bg-warning text-dark ms-1" style="font-size:.68rem">Dari Notifikasi</span>
                <?php endif; ?>
              </div>
              <div class="order-time"><i class="fa-regular fa-clock me-1"></i><?= formatDate($p['updated_at'] ?? $p['created_at'],'H:i - d M') ?></div>
            </div>
            <?= paymentStatusBadge($p['payment_status']) ?>
          </div>
          <div class="order-card-body">
            <div class="order-customer"><i class="fa-solid fa-user me-2" style="color:var(--primary)"></i><?= e($p['customer_name']) ?></div>
            <div class="order-table text-muted small mt-1">
              <i class="fa-solid fa-phone me-1"></i><?= e($p['phone']) ?>
              <?php if ($p['table_number']): ?>
               &nbsp;·&nbsp;<i class="fa-solid fa-chair me-1"></i>Meja <?= e($p['table_number']) ?>
              <?php endif; ?>
            </div>
            <?php if (!empty($pItems)): ?>
            <div class="mt-2 pt-2 border-top small text-muted">
              <?php foreach ($pItems as $pi): ?>
                <div>• <?= e($pi['name']) ?> <strong class="text-white">x<?= (int)$pi['quantity'] ?></strong></div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
              <div>
                <div class="text-muted small">Tahap Pelanggan</div>
                <?php if ($p['payment_status'] === 'pending'): ?>
                  <span class="badge bg-warning text-dark">Menunggu Konfirmasi Kasir</span>
                <?php elseif ($p['payment_status'] === 'confirmed' && !$isSubmitted): ?>
                  <span class="badge bg-info text-dark">Menunggu Klik Buat Pesanan</span>
                <?php elseif ($p['payment_status'] === 'confirmed' && $isSubmitted): ?>
                  <span class="badge bg-success">Pesanan Sudah Dibuat</span>
                <?php else: ?>
                  <span class="badge bg-danger">Ditolak</span>
                <?php endif; ?>
              </div>
              <div class="text-end">
                <div class="text-muted small"><?= strtoupper(e($p['payment_method'])) ?></div>
                <div class="order-total mb-0"><?= formatRupiah((float)$p['total_amount']) ?></div>
              </div>
            </div>
          </div>
          <div class="order-card-footer flex-wrap gap-2">
            <?php if ($p['payment_status'] === 'pending'): ?>
              <button class="btn btn-success btn-sm rounded-pill flex-fill" onclick="confirmPayment(<?= $p['id'] ?>)">
                <i class="fa-solid fa-check me-1"></i>Konfirmasi Pembayaran
              </button>
              <button class="btn btn-outline-danger btn-sm rounded-pill" onclick="rejectPaymentCashier(<?= $p['id'] ?>)">
                <i class="fa-solid fa-xmark me-1"></i>Tolak
              </button>
            <?php elseif ($p['payment_status'] === 'confirmed' && !$isSubmitted): ?>
              <div class="w-100 text-center small text-warning py-1">
                <i class="fa-solid fa-spinner fa-spin me-1"></i>Menunggu pelanggan menekan tombol <b>Buat Pesanan</b>...
              </div>
            <?php elseif ($p['payment_status'] === 'confirmed' && $isSubmitted): ?>
              <a href="<?= APP_URL ?>/cashier/orders.php" class="btn btn-outline-success btn-sm rounded-pill w-100">
                <i class="fa-solid fa-receipt me-1"></i>Sudah Masuk ke Kelola Pesanan
              </a>
            <?php else: ?>
              <div class="w-100 text-center small text-danger py-1">Pembayaran telah ditolak</div>
            <?php endif; ?>
          </div>
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
