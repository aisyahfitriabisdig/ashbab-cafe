<?php
/**
 * ASHBAB CAFE - Cashier Dashboard
 * File: cashier/dashboard.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('cashier', 'admin');

$pageTitle = 'Dashboard Kasir';

$counts = [
    'waiting'    => db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status='waiting'")['c'] ?? 0,
    'processing' => db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status IN ('accepted','processing')")['c'] ?? 0,
    'completed'  => db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status='completed' AND DATE(created_at)=CURRENT_DATE")['c'] ?? 0,
];
$todayIncome = db()->fetch(
    "SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE order_status='completed' AND DATE(created_at)=CURRENT_DATE"
)['c'] ?? 0;

$recentOrders = db()->fetchAll(
    "SELECT * FROM orders WHERE order_status NOT IN ('completed','cancelled') ORDER BY created_at DESC LIMIT 10"
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
    <h1 class="topbar-title">Dashboard Kasir</h1>
    <div class="topbar-right">
      <button class="topbar-notif" id="notifBell" onclick="window.location='<?= APP_URL ?>/cashier/notifications.php'">
        <i class="fa-solid fa-bell"></i>
        <span class="notif-badge" id="notifBadge" style="display:none">0</span>
      </button>
      <div class="topbar-user">
        <i class="fa-solid fa-user-circle fa-lg" style="color:var(--primary)"></i>
        <div>
          <div class="topbar-user-name"><?= e(currentUser()['name']) ?></div>
          <div class="topbar-user-role">Kasir</div>
        </div>
      </div>
    </div>
  </div>

  <div class="page-content">
    <!-- Stat Cards -->
    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget warning">
          <div class="stat-icon warning"><i class="fa-solid fa-clock"></i></div>
          <div class="stat-value" id="count_waiting"><?= $counts['waiting'] ?></div>
          <div class="stat-label">Pesanan Baru</div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget primary">
          <div class="stat-icon primary"><i class="fa-solid fa-fire-burner"></i></div>
          <div class="stat-value" id="count_processing"><?= $counts['processing'] ?></div>
          <div class="stat-label">Sedang Diproses</div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget success">
          <div class="stat-icon success"><i class="fa-solid fa-check-circle"></i></div>
          <div class="stat-value" id="count_completed"><?= $counts['completed'] ?></div>
          <div class="stat-label">Selesai Hari Ini</div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget info">
          <div class="stat-icon info"><i class="fa-solid fa-coins"></i></div>
          <div class="stat-value" style="font-size:1.2rem"><?= formatRupiah($todayIncome) ?></div>
          <div class="stat-label">Pemasukan Hari Ini</div>
        </div>
      </div>
    </div>

    <!-- Active Orders -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-receipt me-2" style="color:var(--primary)"></i>Pesanan Aktif</h2>
        <a href="<?= APP_URL ?>/cashier/orders.php" class="btn btn-sm btn-outline-secondary rounded-pill">Kelola Semua</a>
      </div>

      <?php if (empty($recentOrders)): ?>
      <div class="text-center py-5 text-muted">
        <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
        <p>Tidak ada pesanan aktif saat ini</p>
      </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($recentOrders as $o): ?>
        <div class="col-md-6 col-lg-4">
          <div class="order-card">
            <div class="order-card-header">
              <div>
                <div class="order-number"><?= e($o['order_number']) ?></div>
                <div class="order-time"><?= formatDate($o['created_at'],'H:i - d M') ?></div>
              </div>
              <?= orderStatusBadge($o['order_status']) ?>
            </div>
            <div class="order-card-body">
              <div class="order-customer"><?= e($o['customer_name']) ?></div>
              <?php if ($o['table_number']): ?>
              <div class="order-table"><i class="fa-solid fa-chair me-1"></i>Meja <?= e($o['table_number']) ?></div>
              <?php endif; ?>
              <div class="order-total"><?= formatRupiah($o['total_amount']) ?></div>
            </div>
            <div class="order-card-footer flex-wrap gap-2">
              <?php if ($o['payment_status']==='pending' && !empty($o['payment_proof'])): ?>
              <button class="btn btn-success btn-sm rounded-pill flex-fill" onclick="confirmPayment(<?= $o['id'] ?>)">
                <i class="fa-solid fa-check me-1"></i>Konfirmasi Bayar
              </button>
              <?php endif; ?>
              
              <?php if ($o['order_status']==='waiting'): ?>
              <button class="btn btn-outline-success btn-sm rounded-pill flex-fill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'accepted')">
                <i class="fa-solid fa-thumbs-up me-1"></i>Terima
              </button>
              <?php elseif ($o['order_status']==='accepted'): ?>
              <button class="btn btn-outline-primary btn-sm rounded-pill flex-fill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'processing')">
                <i class="fa-solid fa-fire-burner me-1"></i>Proses
              </button>
              <?php elseif ($o['order_status']==='processing'): ?>
              <button class="btn btn-outline-success btn-sm rounded-pill flex-fill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'completed')">
                <i class="fa-solid fa-star me-1"></i>Selesai
              </button>
              <?php endif; ?>
              
              <?php if ($o['payment_status'] === 'confirmed'): ?>
              <a href="<?= APP_URL ?>/cashier/receipt.php?order_id=<?= $o['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill ms-auto">
                <i class="fa-solid fa-print"></i>
              </a>
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
<script src="<?= APP_URL ?>/assets/js/cashier.js?v=2"></script>
