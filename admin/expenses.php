<?php
/**
 * ASHBAB CAFE - Admin Expenses
 * File: admin/expenses.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Manajemen Pengeluaran';

$filterMonth = $_GET['month'] ?? date('Y-m');
[$year, $month] = explode('-', $filterMonth . '-01');
$monthStart     = $filterMonth . '-01';
$nextMonthStart = date('Y-m-d', strtotime($monthStart . ' +1 month'));

$expenses     = db()->fetchAll(
    "SELECT * FROM expenses WHERE expense_date >= ? AND expense_date < ? ORDER BY expense_date DESC",
    [$monthStart, $nextMonthStart]
);
$totalMonth   = array_sum(array_column($expenses, 'amount'));
$totalAll     = db()->fetch("SELECT COALESCE(SUM(amount),0) as t FROM expenses")['t'] ?? 0;

$expCategories = ['Bahan Baku','Operasional','Gaji','Utilitas','Peralatan','Lainnya'];

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Manajemen Pengeluaran</h1>
    <div class="topbar-right">
      <button class="btn-primary-cafe py-2 px-3" data-bs-toggle="modal" data-bs-target="#expModal" onclick="resetExpForm()">
        <i class="fa-solid fa-plus me-2"></i>Tambah Pengeluaran
      </button>
    </div>
  </div>

  <div class="page-content">
    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
      <div class="col-sm-6">
        <div class="stat-widget danger">
          <div class="stat-icon danger"><i class="fa-solid fa-arrow-trend-down"></i></div>
          <div class="stat-value"><?= formatRupiah($totalMonth) ?></div>
          <div class="stat-label">Pengeluaran Bulan Ini</div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="stat-widget primary">
          <div class="stat-icon primary"><i class="fa-solid fa-coins"></i></div>
          <div class="stat-value"><?= formatRupiah($totalAll) ?></div>
          <div class="stat-label">Total Seluruh Pengeluaran</div>
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
      </form>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-money-bill-wave me-2" style="color:var(--primary)"></i>Daftar Pengeluaran</h2>
        <span class="badge bg-secondary rounded-pill"><?= count($expenses) ?> entri</span>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table" id="expTable">
          <thead>
            <tr><th>#</th><th>Nama</th><th>Kategori</th><th>Nominal</th><th>Tanggal</th><th>Keterangan</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($expenses as $i => $exp): ?>
            <tr data-exp-id="<?= $exp['id'] ?>">
              <td><?= $i+1 ?></td>
              <td class="fw-700"><?= e($exp['name']) ?></td>
              <td><span class="badge bg-warning text-dark"><?= e($exp['category']) ?></span></td>
              <td class="fw-700 text-danger"><?= formatRupiah($exp['amount']) ?></td>
              <td><?= formatDate($exp['expense_date'],'d M Y') ?></td>
              <td class="text-muted small"><?= e($exp['description'] ?? '-') ?></td>
              <td>
                <button class="action-btn edit me-1" title="Edit"
                  onclick="editExpense(<?= htmlspecialchars(json_encode($exp)) ?>)">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button class="action-btn delete" title="Hapus" onclick="deleteExpense(<?= $exp['id'] ?>)">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if (!empty($expenses)): ?>
          <tfoot>
            <tr class="fw-800">
              <td colspan="3" class="text-end">Total:</td>
              <td class="text-danger"><?= formatRupiah($totalMonth) ?></td>
              <td colspan="3"></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Expense Modal -->
<div class="modal fade" id="expModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-800" id="expModalTitle">Tambah Pengeluaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="expForm">
          <?= csrfField() ?>
          <input type="hidden" name="action" id="expAction" value="add">
          <input type="hidden" name="id" id="expId">
          <div class="row g-3">
            <div class="col-12">
              <label class="admin-form-label">Nama Pengeluaran <span class="text-danger">*</span></label>
              <input type="text" class="admin-input" name="name" id="expName" required placeholder="Contoh: Pembelian kopi">
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Kategori <span class="text-danger">*</span></label>
              <select class="admin-input" name="category" id="expCategory" required>
                <option value="">-- Pilih --</option>
                <?php foreach ($expCategories as $ec): ?>
                <option value="<?= $ec ?>"><?= $ec ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Nominal (Rp) <span class="text-danger">*</span></label>
              <input type="number" class="admin-input" name="amount" id="expAmount" required min="0">
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Tanggal <span class="text-danger">*</span></label>
              <input type="date" class="admin-input" name="expense_date" id="expDate" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-12">
              <label class="admin-form-label">Keterangan</label>
              <textarea class="admin-input" name="description" id="expDesc" rows="2" placeholder="Keterangan opsional..."></textarea>
            </div>
          </div>
          <button type="submit" class="btn-primary-cafe w-100 justify-content-center d-flex mt-4">
            <i class="fa-solid fa-floppy-disk me-2"></i>Simpan
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';

function resetExpForm() {
  document.getElementById('expModalTitle').textContent = 'Tambah Pengeluaran';
  document.getElementById('expAction').value = 'add';
  document.getElementById('expId').value = '';
  document.getElementById('expForm').reset();
  document.getElementById('expDate').value = '<?= date('Y-m-d') ?>';
}

function editExpense(exp) {
  document.getElementById('expModalTitle').textContent = 'Edit Pengeluaran';
  document.getElementById('expAction').value  = 'edit';
  document.getElementById('expId').value      = exp.id;
  document.getElementById('expName').value    = exp.name;
  document.getElementById('expCategory').value= exp.category;
  document.getElementById('expAmount').value  = exp.amount;
  document.getElementById('expDate').value    = exp.expense_date;
  document.getElementById('expDesc').value    = exp.description || '';
  new bootstrap.Modal(document.getElementById('expModal')).show();
}

document.getElementById('expForm').addEventListener('submit', function(e) {
  e.preventDefault();
  ajaxPost(APP_URL + '/ajax/expenses.php', new FormData(this), res => {
    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById('expModal'))?.hide();
      alertSuccess(res.message || 'Tersimpan!');
      setTimeout(() => location.reload(), 1200);
    } else alertError(res.message || 'Gagal menyimpan.');
  });
});

initDataTable('expTable', { searching:false, paging:false, info:false });
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
