<?php
/**
 * ASHBAB CAFE - Admin Pemasukan (Income)
 * File: admin/income.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Manajemen Pemasukan';

$filterMonth    = $_GET['month'] ?? date('Y-m');
$monthStart     = $filterMonth . '-01';
$nextMonthStart = date('Y-m-d', strtotime($monthStart . ' +1 month'));
$yearStart      = date('Y-01-01');
$nextYearStart  = date('Y-01-01', strtotime('+1 year'));

// Income = completed orders
$incomes = db()->fetchAll(
    "SELECT o.*, 
            COUNT(oi.id) as item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.order_status = 'completed'
       AND o.created_at >= ? AND o.created_at < ?
     GROUP BY o.id
     ORDER BY o.created_at DESC",
    [$monthStart, $nextMonthStart]
);

$todayIncome = db()->fetch(
    "SELECT COALESCE(SUM(total_amount),0) as total FROM orders
     WHERE order_status='completed' AND DATE(created_at)=CURRENT_DATE"
)['total'] ?? 0;

$monthIncome = db()->fetch(
    "SELECT COALESCE(SUM(total_amount),0) as total FROM orders
     WHERE order_status='completed' AND created_at >= ? AND created_at < ?",
    [$monthStart, $nextMonthStart]
)['total'] ?? 0;

$yearIncome = db()->fetch(
    "SELECT COALESCE(SUM(total_amount),0) as total FROM orders
     WHERE order_status='completed' AND created_at >= ? AND created_at < ?",
    [$yearStart, $nextYearStart]
)['total'] ?? 0;

$totalAll = db()->fetch(
    "SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE order_status='completed'"
)['total'] ?? 0;

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Manajemen Pemasukan</h1>
  </div>

  <div class="page-content">

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget success">
          <div class="stat-icon success"><i class="fa-solid fa-sun"></i></div>
          <div class="stat-value" style="font-size:1.1rem"><?= formatRupiah($todayIncome) ?></div>
          <div class="stat-label">Pemasukan Hari Ini</div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget primary">
          <div class="stat-icon primary"><i class="fa-solid fa-calendar-days"></i></div>
          <div class="stat-value" style="font-size:1.1rem"><?= formatRupiah($monthIncome) ?></div>
          <div class="stat-label">Pemasukan Bulan Ini</div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget warning">
          <div class="stat-icon warning"><i class="fa-solid fa-calendar"></i></div>
          <div class="stat-value" style="font-size:1.1rem"><?= formatRupiah($yearIncome) ?></div>
          <div class="stat-label">Pemasukan Tahun Ini</div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget info">
          <div class="stat-icon info"><i class="fa-solid fa-piggy-bank"></i></div>
          <div class="stat-value" style="font-size:1.1rem"><?= formatRupiah($totalAll) ?></div>
          <div class="stat-label">Total Semua Pemasukan</div>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="admin-card mb-4">
      <form method="GET" class="d-flex gap-3 align-items-end flex-wrap">
        <div>
          <label class="admin-form-label">Filter Bulan</label>
          <input type="month" class="admin-input" name="month" value="<?= e($filterMonth) ?>">
        </div>
        <button type="submit" class="btn-primary-cafe py-2 px-4 d-flex justify-content-center">
          <i class="fa-solid fa-filter me-2"></i>Filter
        </button>
        <a href="<?= APP_URL ?>/admin/income.php" class="btn btn-outline-secondary rounded-3 py-2 px-3">Reset</a>
      </form>
    </div>

    <!-- Income Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title">
          <i class="fa-solid fa-sack-dollar me-2" style="color:#10b981"></i>
          Riwayat Pemasukan
        </h2>
        <span class="badge bg-success rounded-pill"><?= count($incomes) ?> transaksi</span>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table" id="incomeTable">
          <thead>
            <tr>
              <th>#</th>
              <th>No. Pesanan</th>
              <th>Pelanggan</th>
              <th>Jumlah Item</th>
              <th>Metode Bayar</th>
              <th>Total</th>
              <th>Tanggal Selesai</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($incomes as $i => $inc): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td>
                <span class="fw-700" style="color:var(--primary)"><?= e($inc['order_number']) ?></span>
              </td>
              <td>
                <div class="fw-600"><?= e($inc['customer_name']) ?></div>
                <small class="text-muted"><?= e($inc['phone']) ?></small>
              </td>
              <td>
                <span class="badge bg-info"><?= $inc['item_count'] ?> item</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  <i class="fa-solid <?= $inc['payment_method']==='qris'?'fa-qrcode':'fa-building-columns' ?> me-1"></i>
                  <?= strtoupper(e($inc['payment_method'])) ?>
                </span>
              </td>
              <td class="fw-800 text-success"><?= formatRupiah($inc['total_amount']) ?></td>
              <td class="text-muted small"><?= formatDate($inc['updated_at'] ?? $inc['created_at'], 'd M Y H:i') ?></td>
              <td>
                <button class="action-btn view" title="Detail"
                  onclick="viewOrderIncome(<?= $inc['id'] ?>)">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if (!empty($incomes)): ?>
          <tfoot>
            <tr class="fw-800">
              <td colspan="5" class="text-end">Total Pemasukan Bulan Ini:</td>
              <td class="text-success fs-6"><?= formatRupiah($monthIncome) ?></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="incomeDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-800">Detail Transaksi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="incomeDetailContent">
        <div class="text-center p-4"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';

function viewOrderIncome(id) {
  const modal   = new bootstrap.Modal(document.getElementById('incomeDetailModal'));
  const content = document.getElementById('incomeDetailContent');
  content.innerHTML = '<div class="text-center p-4"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
  modal.show();

  fetch(APP_URL + '/ajax/get_order.php?id=' + id)
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        content.innerHTML = '<p class="p-4 text-center text-muted">Gagal memuat data.</p>';
        return;
      }
      const o = res.order;
      const itemRows = res.items.map(it =>
        `<tr>
          <td>${it.name}</td>
          <td class="text-center">${it.quantity}</td>
          <td class="text-end">Rp ${Number(it.price).toLocaleString('id-ID')}</td>
          <td class="text-end fw-700 text-success">Rp ${Number(it.subtotal).toLocaleString('id-ID')}</td>
        </tr>`
      ).join('');

      content.innerHTML = `
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="text-muted small mb-1">Nomor Pesanan</div>
            <div class="fw-800 fs-5" style="color:var(--primary)">${o.order_number}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Tanggal</div>
            <div class="fw-600">${o.created_at}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Pelanggan</div>
            <div class="fw-700">${o.customer_name}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">No. HP</div>
            <div class="fw-600">${o.phone}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">No. Meja</div>
            <div class="fw-600">${o.table_number || '-'}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Metode Bayar</div>
            <div class="fw-600">${o.payment_method.toUpperCase()}</div>
          </div>
        </div>

        <table class="table table-sm">
          <thead class="table-light">
            <tr>
              <th>Menu</th>
              <th class="text-center">Qty</th>
              <th class="text-end">Harga</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>${itemRows}</tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="text-end fw-700">Total</td>
              <td class="text-end fw-800 text-success fs-6">Rp ${Number(o.total_amount).toLocaleString('id-ID')}</td>
            </tr>
          </tfoot>
        </table>

        <div class="mt-3 d-flex gap-2">
          <span class="badge bg-success p-2">
            <i class="fa-solid fa-check me-1"></i>Pembayaran Dikonfirmasi
          </span>
          <span class="badge bg-success p-2">
            <i class="fa-solid fa-star me-1"></i>Pesanan Selesai
          </span>
        </div>`;
    });
}

initDataTable('incomeTable');
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
