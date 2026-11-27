<?php
/**
 * ASHBAB CAFE - Admin Reports
 * File: admin/reports.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Laporan';

$filterType  = $_GET['type']       ?? 'monthly';
$filterStart = $_GET['start_date'] ?? date('Y-m-01');
$filterEnd   = $_GET['end_date']   ?? date('Y-m-d');

// Build date range
switch ($filterType) {
    case 'daily':   $filterStart = $filterEnd = date('Y-m-d'); break;
    case 'weekly':  $filterStart = date('Y-m-d', strtotime('monday this week')); $filterEnd = date('Y-m-d'); break;
    case 'monthly': $filterStart = date('Y-m-01'); $filterEnd = date('Y-m-d'); break;
    case 'yearly':  $filterStart = date('Y-01-01'); $filterEnd = date('Y-12-31'); break;
    case 'custom':  break; // Use GET params
}

$orders   = db()->fetchAll(
    "SELECT * FROM orders WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC",
    [$filterStart, $filterEnd]
);
$expenses = db()->fetchAll(
    "SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC",
    [$filterStart, $filterEnd]
);

$totalIncome   = array_sum(array_column(
    array_filter($orders, fn($o) => $o['order_status']==='completed'), 'total_amount'
));
$totalExpenses = array_sum(array_column($expenses, 'amount'));
$totalOrders   = count($orders);
$totalProfit   = $totalIncome - $totalExpenses;

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Laporan Keuangan</h1>
    <div class="topbar-right">
      <a href="<?= APP_URL ?>/exports/export_pdf.php?start=<?= $filterStart ?>&end=<?= $filterEnd ?>"
         target="_blank" class="btn btn-danger rounded-pill px-3 me-2">
        <i class="fa-solid fa-file-pdf me-2"></i>Export PDF
      </a>
      <a href="<?= APP_URL ?>/exports/export_excel.php?start=<?= $filterStart ?>&end=<?= $filterEnd ?>"
         class="btn btn-success rounded-pill px-3">
        <i class="fa-solid fa-file-excel me-2"></i>Export Excel
      </a>
    </div>
  </div>

  <div class="page-content">
    <!-- Filter -->
    <div class="admin-card mb-4">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="admin-form-label">Periode</label>
          <select class="admin-input" name="type" id="periodType" onchange="toggleCustom(this.value)">
            <?php foreach (['daily'=>'Hari Ini','weekly'=>'Minggu Ini','monthly'=>'Bulan Ini','yearly'=>'Tahun Ini','custom'=>'Custom'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $filterType===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3" id="startDateWrap" style="display:<?= $filterType==='custom'?'block':'none' ?>">
          <label class="admin-form-label">Dari Tanggal</label>
          <input type="date" class="admin-input" name="start_date" value="<?= e($filterStart) ?>">
        </div>
        <div class="col-md-3" id="endDateWrap" style="display:<?= $filterType==='custom'?'block':'none' ?>">
          <label class="admin-form-label">Sampai Tanggal</label>
          <input type="date" class="admin-input" name="end_date" value="<?= e($filterEnd) ?>">
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn-primary-cafe py-2 px-4 d-flex justify-content-center w-100">
            <i class="fa-solid fa-filter me-2"></i>Tampilkan
          </button>
        </div>
      </form>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
      <?php
      $cards = [
        ['Total Pesanan',   $totalOrders,   'fa-receipt',        'primary', true],
        ['Total Pemasukan', $totalIncome,   'fa-arrow-trend-up', 'success', false],
        ['Total Pengeluaran',$totalExpenses,'fa-arrow-trend-down','danger', false],
        ['Keuntungan',      $totalProfit,   'fa-coins',          $totalProfit>=0?'warning':'danger', false],
      ];
      foreach ($cards as [$label,$val,$icon,$type,$isCount]): ?>
      <div class="col-sm-6 col-lg-3">
        <div class="stat-widget <?= $type ?>">
          <div class="stat-icon <?= $type ?>"><i class="fa-solid <?= $icon ?>"></i></div>
          <div class="stat-value"><?= $isCount ? $val : formatRupiah($val) ?></div>
          <div class="stat-label"><?= $label ?></div>
          <div class="stat-change"><?= formatDate($filterStart,'d M') ?> – <?= formatDate($filterEnd,'d M Y') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Orders Table -->
    <div class="admin-card mb-4">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-receipt me-2" style="color:var(--primary)"></i>Data Pesanan</h2>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table" id="reportOrderTable">
          <thead>
            <tr><th>#</th><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Status Bayar</th><th>Status</th><th>Tanggal</th></tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $i => $o): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><span class="fw-700" style="color:var(--primary)"><?= e($o['order_number']) ?></span></td>
              <td><?= e($o['customer_name']) ?></td>
              <td class="fw-700"><?= formatRupiah($o['total_amount']) ?></td>
              <td><?= paymentStatusBadge($o['payment_status']) ?></td>
              <td><?= orderStatusBadge($o['order_status']) ?></td>
              <td class="text-muted small"><?= formatDate($o['created_at'],'d M Y H:i') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Expenses Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-money-bill-wave me-2" style="color:var(--primary)"></i>Data Pengeluaran</h2>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table" id="reportExpTable">
          <thead>
            <tr><th>#</th><th>Nama</th><th>Kategori</th><th>Nominal</th><th>Tanggal</th><th>Keterangan</th></tr>
          </thead>
          <tbody>
            <?php foreach ($expenses as $i => $exp): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td class="fw-600"><?= e($exp['name']) ?></td>
              <td><span class="badge bg-warning text-dark"><?= e($exp['category']) ?></span></td>
              <td class="text-danger fw-700"><?= formatRupiah($exp['amount']) ?></td>
              <td><?= formatDate($exp['expense_date'],'d M Y') ?></td>
              <td class="text-muted small"><?= e($exp['description'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';
function toggleCustom(val) {
  const show = val === 'custom';
  document.getElementById('startDateWrap').style.display = show ? 'block' : 'none';
  document.getElementById('endDateWrap').style.display   = show ? 'block' : 'none';
}
initDataTable('reportOrderTable');
initDataTable('reportExpTable', { searching:false, paging:false, info:false });
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
