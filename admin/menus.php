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
                <?php if ($m['image'] && file_exists(ROOT_PATH . $m['image'])): ?>
                  <img src="<?= APP_URL . '/' . e($m['image']) ?>" style="width:52px;height:52px;object-fit:cover;border-radius:10px">
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
                <button class="action-btn edit me-1" title="Edit"
                  onclick="openEditMenu(<?= htmlspecialchars(json_encode($m)) ?>)">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button class="action-btn delete" title="Hapus" onclick="deleteMenu(<?= $m['id'] ?>)">
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
              <input type="number" class="admin-input" name="price" id="menuPrice" required min="0" placeholder="0">
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Stok <span class="text-danger">*</span></label>
              <input type="number" class="admin-input" name="stock" id="menuStock" required min="0" placeholder="0">
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
  document.getElementById('menuAction').value = 'add';
  document.getElementById('menuId').value = '';
  document.getElementById('menuForm').reset();
  document.getElementById('imagePreviewWrap').style.display = 'none';
}

function openEditMenu(menu) {
  document.getElementById('menuModalTitle').textContent = 'Edit Menu';
  document.getElementById('menuAction').value  = 'edit';
  document.getElementById('menuId').value      = menu.id;
  document.getElementById('menuName').value    = menu.name;
  document.getElementById('menuCategory').value= menu.category_id;
  document.getElementById('menuPrice').value   = menu.price;
  document.getElementById('menuStock').value   = menu.stock;
  document.getElementById('menuDesc').value    = menu.description || '';
  document.getElementById('menuStatus').value  = menu.status;
  if (menu.image) {
    document.getElementById('imagePreview').src = APP_URL + '/' + menu.image;
    document.getElementById('imagePreviewWrap').style.display = 'block';
  } else {
    document.getElementById('imagePreviewWrap').style.display = 'none';
  }
  new bootstrap.Modal(document.getElementById('menuModal')).show();
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
    .then(r => r.json())
    .then(res => {
      btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Menu';
      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('menuModal'))?.hide();
        alertSuccess(res.message || 'Menu disimpan!');
        setTimeout(() => location.reload(), 1200);
      } else alertError(res.message || 'Gagal menyimpan menu.');
    });
});

initDataTable('menuTable');
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
