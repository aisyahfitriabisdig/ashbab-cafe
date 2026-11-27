<?php
/**
 * ASHBAB CAFE - Cashier Orders
 * File: cashier/orders.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('cashier', 'admin');

$pageTitle     = 'Kelola Pesanan';
$filterStatus  = $_GET['status'] ?? '';

$sql    = "SELECT * FROM orders WHERE 1=1";
$params = [];
if ($filterStatus) { $sql .= " AND order_status=?"; $params[] = $filterStatus; }
$sql   .= " ORDER BY created_at DESC";
$orders = db()->fetchAll($sql, $params);

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
    <h1 class="topbar-title">Kelola Pesanan</h1>
    <div class="topbar-right">
      <button class="topbar-notif" id="notifBell" onclick="window.location='<?= APP_URL ?>/cashier/notifications.php'">
        <i class="fa-solid fa-bell"></i>
        <span class="notif-badge" id="notifBadge" style="display:none">0</span>
      </button>
    </div>
  </div>

  <div class="page-content">
    <!-- Status Tabs -->
    <div class="status-tabs mb-4">
      <a href="<?= APP_URL ?>/cashier/orders.php" class="status-tab <?= !$filterStatus?'active':'' ?>">
        <i class="fa-solid fa-list me-1"></i>Semua
      </a>
      <?php
      $tabs = ['waiting'=>['Menunggu','warning'],'accepted'=>['Diterima','info'],
               'processing'=>['Diproses','primary'],'completed'=>['Selesai','success'],'cancelled'=>['Dibatalkan','danger']];
      foreach ($tabs as $v => [$l,$c]): ?>
      <a href="?status=<?= $v ?>" class="status-tab <?= $filterStatus===$v?'active':'' ?>">
        <?= $l ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Orders Grid -->
    <?php if (empty($orders)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
      <p>Tidak ada pesanan<?= $filterStatus?" dengan status \"$filterStatus\"":'' ?></p>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($orders as $o): ?>
      <div class="col-md-6 col-lg-4">
        <div class="order-card">
          <div class="order-card-header">
            <div>
              <div class="order-number"><?= e($o['order_number']) ?></div>
              <div class="order-time"><i class="fa-regular fa-clock me-1"></i><?= formatDate($o['created_at'],'H:i d M') ?></div>
            </div>
            <?= orderStatusBadge($o['order_status']) ?>
          </div>
          <div class="order-card-body">
            <div class="order-customer"><i class="fa-solid fa-user me-2" style="color:var(--primary)"></i><?= e($o['customer_name']) ?></div>
            <div class="order-table text-muted small mt-1">
              <i class="fa-solid fa-phone me-1"></i><?= e($o['phone']) ?>
              <?php if ($o['table_number']): ?>
               &nbsp;·&nbsp;<i class="fa-solid fa-chair me-1"></i>Meja <?= e($o['table_number']) ?>
              <?php endif; ?>
            </div>
            <?php if ($o['notes']): ?>
            <div class="mt-2 p-2 rounded-2 small" style="background:#fff8f0;border-left:3px solid var(--secondary)">
              <i class="fa-solid fa-note-sticky me-1" style="color:var(--secondary)"></i><?= e($o['notes']) ?>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
              <div>
                <div class="text-muted small">Pembayaran</div>
                <div><?= paymentStatusBadge($o['payment_status']) ?></div>
              </div>
              <div class="text-end">
                <div class="text-muted small"><?= strtoupper(e($o['payment_method'])) ?></div>
                <div class="order-total"><?= formatRupiah($o['total_amount']) ?></div>
              </div>
            </div>
          </div>
          <div class="order-card-footer flex-wrap">
            <!-- Payment actions -->
            <?php if ($o['payment_status']==='pending' && !empty($o['payment_proof'])): ?>
            <button class="btn btn-success btn-sm rounded-pill" onclick="confirmPayment(<?= $o['id'] ?>)">
              <i class="fa-solid fa-check me-1"></i>Konfirmasi Bayar
            </button>
            <?php endif; ?>
            <!-- Order status actions -->
            <?php if ($o['order_status']==='waiting'): ?>
            <button class="btn btn-outline-success btn-sm rounded-pill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'accepted')">
              <i class="fa-solid fa-thumbs-up me-1"></i>Terima
            </button>
            <?php elseif ($o['order_status']==='accepted'): ?>
            <button class="btn btn-outline-primary btn-sm rounded-pill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'processing')">
              <i class="fa-solid fa-fire-burner me-1"></i>Proses
            </button>
            <?php elseif ($o['order_status']==='processing'): ?>
            <button class="btn btn-outline-success btn-sm rounded-pill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'completed')">
              <i class="fa-solid fa-star me-1"></i>Selesai
            </button>
            <?php endif; ?>
            <?php if (!in_array($o['order_status'],['completed','cancelled'])): ?>
            <button class="btn btn-outline-danger btn-sm rounded-pill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'cancelled')">
              <i class="fa-solid fa-ban me-1"></i>Batalkan
            </button>
            <?php endif; ?>
            <!-- View proof -->
            <?php if (!empty($o['payment_proof'])): ?>
            <button class="btn btn-outline-secondary btn-sm rounded-pill"
              onclick="window.open('<?= APP_URL . '/' . $o['payment_proof'] ?>','_blank')">
              <i class="fa-solid fa-image me-1"></i>Bukti
            </button>
            <?php endif; ?>
            <!-- Print -->
            <?php if ($o['payment_status'] === 'confirmed'): ?>
            <a href="<?= APP_URL ?>/cashier/receipt.php?order_id=<?= $o['id'] ?>"
               class="btn btn-outline-dark btn-sm rounded-pill ms-auto">
              <i class="fa-solid fa-print me-1"></i>Struk
            </a>
            <?php else: ?>
            <div class="ms-auto text-muted small"><i class="fa-solid fa-circle-exclamation me-1"></i>Bayar dulu</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/cashier.js?v=2"></script>
