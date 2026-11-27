<?php
/**
 * ASHBAB CAFE - Admin Menu Management
 * File: admin/menus.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle  = 'Manajemen Menu';
$categories = db()->fetchAll("SELECT * FROM categories ORDER BY name");
$menus      = db()->fetchAll(
    "SELECT m.*, c.name as cat_name FROM menus m
     JOIN categories c ON m.category_id = c.id ORDER BY c.name, m.name"
);

// Edit mode?
$editMenu = null;
if (isset($_GET['edit'])) {
    $editMenu = db()->fetch("SELECT * FROM menus WHERE id=?", [(int)$_GET['edit']]);
}

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Manajemen Menu</h1>
    <div class="topbar-right">
      <button class="btn-primary-cafe py-2 px-3" data-bs-toggle="modal" data-bs-target="#menuModal" onclick="resetMenuForm()">
        <i class="fa-solid fa-plus me-2"></i>Tambah Menu
      </button>
    </div>
  </div>

  <div class="page-content">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-utensils me-2" style="color:var(--primary)"></i>Daftar Menu</h2>
        <span class="badge bg-secondary rounded-pill"><?= count($menus) ?> menu</span>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table" id="menuTable">
          <thead>
            <tr><th>#</th><th>Foto</th><th>Nama Menu</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($menus as $i => $m): ?>
            <tr data-menu-id="<?= $m['id'] ?>">
              <td><?= $i+1 ?></td>
              <td>
                <?php if (assetExists($m['image'])): ?>
                  <img src="<?= e(assetUrl($m['image'])) ?>" style="width:52px;height:52px;object-fit:cover;border-radius:10px">
                <?php else: ?>
                  <div style="width:52px;height:52px;background:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.4rem">🍽️</div>
                <?php endif; ?>
              </td>
              <td class="fw-700"><?= e($m['name']) ?></td>
              <td><span class="badge bg-light text-dark border"><?= e($m['cat_name']) ?></span></td>
              <td class="fw-700" style="color:var(--primary)"><?= formatRupiah($m['price']) ?></td>
              <td><?= $m['stock'] ?></td>
              <td>
                <button class="btn btn-sm rounded-pill px-3 <?= $m['status']==='available'?'btn-success':'btn-secondary' ?>"
                        onclick="toggleMenuStatus(<?= $m['id'] ?>, '<?= $m['status'] ?>')">
                  <?= $m['status']==='available'?'Tersedia':'Tidak Tersedia' ?>
                </button>
              </td>
              <td>
                <button type="button" class="action-btn edit me-1" title="Edit"
                  onclick="openEditMenu(<?= htmlspecialchars(json_encode($m, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button type="button" class="action-btn delete" title="Hapus" onclick="deleteMenu(<?= $m['id'] ?>)">
                  <i class="fa-solid fa-trash"></i>
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

<!-- Menu Modal -->
<div class="modal fade" id="menuModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-800" id="menuModalTitle">Tambah Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="menuForm" enctype="multipart/form-data"
              action="<?= APP_URL ?>/ajax/menus.php" method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" id="menuAction" value="add">
          <input type="hidden" name="id" id="menuId">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="admin-form-label">Nama Menu <span class="text-danger">*</span></label>
              <input type="text" class="admin-input" name="name" id="menuName" required placeholder="Nama menu">
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Kategori <span class="text-danger">*</span></label>
              <select class="admin-input" name="category_id" id="menuCategory" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Harga (Rp) <span class="text-danger">*</span></label>
              <input type="number" class="admin-input" name="price" id="menuPrice" required min="0" step="any" placeholder="0">
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Stok <span class="text-danger">*</span></label>
              <input type="number" class="admin-input" name="stock" id="menuStock" required min="0" step="1" placeholder="0">
            </div>
            <div class="col-12">
              <label class="admin-form-label">Deskripsi</label>
              <textarea class="admin-input" name="description" id="menuDesc" rows="3" placeholder="Deskripsi menu..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Status</label>
              <select class="admin-input" name="status" id="menuStatus">
                <option value="available">Tersedia</option>
                <option value="unavailable">Tidak Tersedia</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Foto Menu</label>
              <input type="file" class="admin-input" name="image" id="menuImage" accept="image/*">
              <small class="text-muted d-block mt-1" id="menuImageHint" style="display:none;font-size:.75rem">Kosongkan jika tidak ingin mengubah foto saat ini.</small>
            </div>
            <div class="col-12 text-center" id="imagePreviewWrap" style="display:none">
              <img id="imagePreview" src="" alt="Preview" style="max-height:160px;border-radius:12px;object-fit:cover">
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn-primary-cafe w-100 justify-content-center d-flex">
              <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Menu
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';

function resetMenuForm() {
  document.getElementById('menuModalTitle').textContent = 'Tambah Menu';
  document.getElementById('menuForm').reset();
  document.getElementById('menuAction').value = 'add';
  document.getElementById('menuId').value = '';
  document.getElementById('menuImage').value = '';
  document.getElementById('imagePreview').src = '';
  document.getElementById('imagePreviewWrap').style.display = 'none';
  const hint = document.getElementById('menuImageHint');
  if (hint) hint.style.display = 'none';
}

function openEditMenu(menu) {
  document.getElementById('menuForm').reset();
  document.getElementById('menuImage').value = '';
  document.getElementById('menuModalTitle').textContent = 'Edit Menu';
  document.getElementById('menuAction').value   = 'edit';
  document.getElementById('menuId').value       = menu.id;
  document.getElementById('menuName').value     = menu.name || '';
  document.getElementById('menuCategory').value = String(menu.category_id || '');
  const priceNum = Number(menu.price);
  document.getElementById('menuPrice').value    = Number.isFinite(priceNum) ? priceNum : (menu.price || 0);
  document.getElementById('menuStock').value    = menu.stock ?? 0;
  document.getElementById('menuDesc').value     = menu.description || '';
  document.getElementById('menuStatus').value   = menu.status || 'available';

  const hint = document.getElementById('menuImageHint');
  if (hint) hint.style.display = 'block';

  if (menu.image) {
    const imgSrc = /^(https?:\/\/|data:)/i.test(menu.image) ? menu.image : `${APP_URL}/${menu.image.replace(/^\/+/, '')}`;
    document.getElementById('imagePreview').src = imgSrc;
    document.getElementById('imagePreviewWrap').style.display = 'block';
  } else {
    document.getElementById('imagePreview').src = '';
    document.getElementById('imagePreviewWrap').style.display = 'none';
  }
  bootstrap.Modal.getOrCreateInstance(document.getElementById('menuModal')).show();
}

// Image preview
document.getElementById('menuImage').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('imagePreview').src = e.target.result;
    document.getElementById('imagePreviewWrap').style.display = 'block';
  };
  reader.readAsDataURL(file);
});

// Form submit via AJAX
document.getElementById('menuForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = this.querySelector('button[type=submit]');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Menyimpan...';

  fetch(APP_URL + '/ajax/menus.php', { method:'POST', body: new FormData(this) })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(res => {
      btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Menu';
      if (res.success) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('menuModal')).hide();
        alertSuccess(res.message || 'Menu disimpan!');
        setTimeout(() => location.reload(), 1000);
      } else {
        alertError(res.message || 'Gagal menyimpan menu.');
      }
    })
    .catch(() => {
      btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Menu';
      alertError('Terjadi kesalahan jaringan/server saat menyimpan menu. Silakan coba lagi.');
    });
});

initDataTable('menuTable');
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
