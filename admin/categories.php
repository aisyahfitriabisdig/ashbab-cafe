<?php
/**
 * ASHBAB CAFE - Admin Categories
 * File: admin/categories.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle  = 'Manajemen Kategori';
$categories = db()->fetchAll(
    "SELECT c.*, COUNT(m.id) as menu_count
     FROM categories c LEFT JOIN menus m ON c.id = m.category_id
     GROUP BY c.id ORDER BY c.name"
);

$icons = [
    'fa-utensils','fa-mug-hot','fa-cup-straw','fa-cookie-bite',
    'fa-ice-cream','fa-glass-water','fa-burger','fa-pizza-slice',
    'fa-bowl-rice','fa-drumstick-bite','fa-fish','fa-leaf'
];

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Manajemen Kategori</h1>
    <div class="topbar-right">
      <button class="btn-primary-cafe py-2 px-3" data-bs-toggle="modal" data-bs-target="#catModal" onclick="openAddModal()">
        <i class="fa-solid fa-plus me-2"></i>Tambah Kategori
      </button>
    </div>
  </div>

  <div class="page-content">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-tags me-2" style="color:var(--primary)"></i>Daftar Kategori</h2>
        <span class="badge bg-secondary rounded-pill"><?= count($categories) ?> kategori</span>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table" id="catTable">
          <thead>
            <tr><th>#</th><th>Icon</th><th>Nama Kategori</th><th>Jumlah Menu</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $i => $cat): ?>
            <tr data-cat-id="<?= $cat['id'] ?>">
              <td><?= $i+1 ?></td>
              <td><i class="fa-solid <?= e($cat['icon']) ?> fa-lg" style="color:var(--primary)"></i></td>
              <td class="fw-600"><?= e($cat['name']) ?></td>
              <td><span class="badge bg-info"><?= $cat['menu_count'] ?> menu</span></td>
              <td>
                <button class="action-btn edit me-1" title="Edit"
                  onclick="openEditModal(<?= $cat['id'] ?>, '<?= e($cat['name']) ?>', '<?= e($cat['icon']) ?>')">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button class="action-btn delete" title="Hapus" onclick="deleteCategory(<?= $cat['id'] ?>)">
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

<!-- Category Modal -->
<div class="modal fade" id="catModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-800" id="catModalTitle">Tambah Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-3">
        <form id="catForm">
          <?= csrfField() ?>
          <input type="hidden" name="action" id="catAction" value="add">
          <input type="hidden" name="id" id="catId">

          <div class="mb-3">
            <label class="admin-form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text" class="admin-input" name="name" id="catName" required placeholder="Contoh: Makanan">
          </div>

          <div class="mb-4">
            <label class="admin-form-label">Icon Font Awesome</label>
            <div class="row g-2" id="iconPicker">
              <?php foreach ($icons as $icon): ?>
              <div class="col-3">
                <div class="icon-opt text-center p-2 rounded-3 border" style="cursor:pointer;transition:all .2s"
                     onclick="selectIcon('<?= $icon ?>', this)" data-icon="<?= $icon ?>">
                  <i class="fa-solid <?= $icon ?> fa-lg"></i>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="icon" id="catIcon" value="fa-utensils">
            <small class="text-muted">Dipilih: <span id="selectedIconLabel">fa-utensils</span></small>
          </div>

          <button type="submit" class="btn-primary-cafe w-100 justify-content-center d-flex">
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

function selectIcon(icon, el) {
  document.querySelectorAll('.icon-opt').forEach(e => e.classList.remove('active','border-warning'));
  el.classList.add('active');
  el.style.background = 'rgba(139,69,19,.1)';
  el.style.borderColor = '#8B4513';
  document.getElementById('catIcon').value   = icon;
  document.getElementById('selectedIconLabel').textContent = icon;
}

function openAddModal() {
  document.getElementById('catModalTitle').textContent = 'Tambah Kategori';
  document.getElementById('catAction').value = 'add';
  document.getElementById('catId').value = '';
  document.getElementById('catName').value = '';
  document.getElementById('catIcon').value = 'fa-utensils';
  document.getElementById('selectedIconLabel').textContent = 'fa-utensils';
  document.querySelectorAll('.icon-opt').forEach(e => { e.style.background=''; e.style.borderColor=''; });
}

function openEditModal(id, name, icon) {
  document.getElementById('catModalTitle').textContent = 'Edit Kategori';
  document.getElementById('catAction').value = 'edit';
  document.getElementById('catId').value   = id;
  document.getElementById('catName').value = name;
  document.getElementById('catIcon').value = icon;
  document.getElementById('selectedIconLabel').textContent = icon;
  document.querySelectorAll('.icon-opt').forEach(el => {
    if (el.dataset.icon === icon) {
      el.style.background = 'rgba(139,69,19,.1)';
      el.style.borderColor = '#8B4513';
    } else {
      el.style.background = '';
      el.style.borderColor = '';
    }
  });
  new bootstrap.Modal(document.getElementById('catModal')).show();
}

document.getElementById('catForm').addEventListener('submit', function(e) {
  e.preventDefault();
  saveCategory(this);
});

initDataTable('catTable', { searching:false, paging:false, info:false });
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
