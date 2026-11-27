<?php
/**
 * ASHBAB CAFE - Admin Orders
 * File: admin/orders.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Manajemen Pesanan';

$filterStatus = $_GET['status'] ?? '';
$filterDate   = $_GET['date']   ?? '';

$sql    = "SELECT * FROM orders WHERE 1=1";
$params = [];
if ($filterStatus) { $sql .= " AND order_status=?";             $params[] = $filterStatus; }
if ($filterDate)   { $sql .= " AND DATE(created_at)=?";         $params[] = $filterDate; }
$sql .= " ORDER BY created_at DESC";
$orders = db()->fetchAll($sql, $params);

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Manajemen Pesanan</h1>
  </div>

  <div class="page-content">
    <!-- Filter Bar -->
    <div class="admin-card mb-4">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="admin-form-label">Status Pesanan</label>
          <select class="admin-input" name="status">
            <option value="">-- Semua Status --</option>
            <?php foreach (['waiting'=>'Menunggu','accepted'=>'Diterima','processing'=>'Diproses','completed'=>'Selesai','cancelled'=>'Dibatalkan'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $filterStatus===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="admin-form-label">Tanggal</label>
          <input type="date" class="admin-input" name="date" value="<?= e($filterDate) ?>">
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button type="submit" class="btn-primary-cafe py-2 px-4 flex-fill justify-content-center d-flex">
            <i class="fa-solid fa-filter me-2"></i>Filter
          </button>
          <a href="<?= APP_URL ?>/admin/orders.php" class="btn btn-outline-secondary py-2 px-3 rounded-3">Reset</a>
        </div>
      </form>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-receipt me-2" style="color:var(--primary)"></i>Daftar Pesanan</h2>
        <span class="badge bg-secondary rounded-pill"><?= count($orders) ?> pesanan</span>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table" id="ordersTable">
          <thead>
            <tr><th>#</th><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Bayar</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $i => $o): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><span class="fw-700" style="color:var(--primary)"><?= e($o['order_number']) ?></span></td>
              <td>
                <div class="fw-600"><?= e($o['customer_name']) ?></div>
                <small class="text-muted"><?= e($o['phone']) ?></small>
              </td>
              <td class="fw-700"><?= formatRupiah($o['total_amount']) ?></td>
              <td><?= strtoupper(e($o['payment_method'])) ?></td>
              <td><?= paymentStatusBadge($o['payment_status']) ?></td>
              <td><?= orderStatusBadge($o['order_status']) ?></td>
              <td class="text-muted small"><?= formatDate($o['created_at'],'d M Y H:i') ?></td>
              <td>
                <button class="action-btn view me-1" title="Detail"
                  onclick="viewOrder(<?= $o['id'] ?>)"><i class="fa-solid fa-eye"></i>
                </button>

              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-800">Detail Pesanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="orderDetailContent">
        <div class="text-center p-4"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';

function viewOrder(id) {
  const modal = new bootstrap.Modal(document.getElementById('orderModal'));
  const content = document.getElementById('orderDetailContent');
  content.innerHTML = '<div class="text-center p-4"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
  modal.show();

  fetch(APP_URL + '/ajax/get_order.php?id=' + id)
    .then(r => r.json())
    .then(res => {
      if (!res.success) { content.innerHTML = '<p class="text-center text-muted p-4">Gagal memuat data.</p>'; return; }
      const o = res.order;
      const itemsHtml = res.items.map(it =>
        `<tr><td>${it.name}</td><td class="text-center">${it.quantity}</td>
         <td class="text-end">Rp ${Number(it.price).toLocaleString('id-ID')}</td>
         <td class="text-end fw-700">Rp ${Number(it.subtotal).toLocaleString('id-ID')}</td></tr>`
      ).join('');

      const proofSrc = o.payment_proof
        ? (/^(https?:\/\/|data:)/i.test(o.payment_proof) ? o.payment_proof : `${APP_URL}/${o.payment_proof.replace(/^\/+/, '')}`)
        : '';
      const proofHtml = proofSrc
        ? `<div class="mt-3"><label class="admin-form-label">Bukti Pembayaran</label><br>
           <img src="${proofSrc}" style="max-width:200px;border-radius:12px;cursor:pointer"
                onclick="window.open('${proofSrc}','_blank')"></div>` : '';

      content.innerHTML = `
        <div class="row g-3 mb-3">
          <div class="col-md-6"><div class="text-muted small">No. Pesanan</div><div class="fw-800" style="color:var(--primary)">${o.order_number}</div></div>
          <div class="col-md-6"><div class="text-muted small">Tanggal</div><div class="fw-600">${o.created_at}</div></div>
          <div class="col-md-6"><div class="text-muted small">Pelanggan</div><div class="fw-700">${o.customer_name}</div></div>
          <div class="col-md-6"><div class="text-muted small">No. HP</div><div class="fw-600">${o.phone}</div></div>
          <div class="col-md-6"><div class="text-muted small">No. Meja</div><div class="fw-600">${o.table_number||'-'}</div></div>
          <div class="col-md-6"><div class="text-muted small">Metode</div><div class="fw-600">${o.payment_method.toUpperCase()}</div></div>
        </div>
        ${o.notes ? `<div class="mb-3 p-3 rounded-3 bg-light"><i class="fa-solid fa-note-sticky me-2"></i>${o.notes}</div>` : ''}
        <table class="table table-sm mb-3"><thead class="table-light"><tr><th>Menu</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead><tbody>${itemsHtml}</tbody>
        <tfoot><tr><td colspan="3" class="text-end fw-700">Total</td><td class="text-end fw-800" style="color:var(--primary)">Rp ${Number(o.total_amount).toLocaleString('id-ID')}</td></tr></tfoot></table>
        <div class="d-flex gap-2 flex-wrap">
          <div>Status Bayar: <span class="badge bg-${o.payment_status==='confirmed'?'success':o.payment_status==='rejected'?'danger':'warning'}">${o.payment_status}</span></div>
          <div>Status Pesanan: <span class="badge bg-${o.order_status==='completed'?'success':o.order_status==='cancelled'?'danger':'primary'}">${o.order_status}</span></div>
        </div>
        ${proofHtml}
        <div class="mt-4 d-flex gap-2 flex-wrap">
          ${o.order_status==='waiting'?`<button class="btn btn-success btn-sm rounded-pill px-3" onclick="updateOrderStatus(${o.id},'accepted')"><i class="fa-solid fa-check me-1"></i>Terima</button>`:''}
          ${o.order_status==='accepted'?`<button class="btn btn-primary btn-sm rounded-pill px-3" onclick="updateOrderStatus(${o.id},'processing')"><i class="fa-solid fa-fire-burner me-1"></i>Proses</button>`:''}
          ${o.order_status==='processing'?`<button class="btn btn-success btn-sm rounded-pill px-3" onclick="updateOrderStatus(${o.id},'completed')"><i class="fa-solid fa-star me-1"></i>Selesai</button>`:''}
          ${!['completed','cancelled'].includes(o.order_status)?`<button class="btn btn-danger btn-sm rounded-pill px-3" onclick="updateOrderStatus(${o.id},'cancelled')"><i class="fa-solid fa-ban me-1"></i>Batalkan</button>`:''}
        </div>`;
    });
}

initDataTable('ordersTable');
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
