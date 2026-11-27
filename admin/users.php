<?php
/**
 * ASHBAB CAFE - Admin User Management
 * File: admin/users.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Manajemen Pengguna';
$users     = db()->fetchAll("SELECT id, name, username, role, created_at FROM users ORDER BY role, name");

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Manajemen Pengguna</h1>
    <div class="topbar-right">
      <button class="btn-primary-cafe py-2 px-3" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserForm()">
        <i class="fa-solid fa-plus me-2"></i>Tambah User
      </button>
    </div>
  </div>

  <div class="page-content">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-users me-2" style="color:var(--primary)"></i>Daftar Pengguna</h2>
        <span class="badge bg-secondary rounded-pill"><?= count($users) ?> pengguna</span>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table" id="usersTable">
          <thead>
            <tr><th>#</th><th>Nama</th><th>Username</th><th>Role</th><th>Bergabung</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u): ?>
            <tr data-user-id="<?= $u['id'] ?>">
              <td><?= $i+1 ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem">
                    <?= strtoupper(substr($u['name'],0,1)) ?>
                  </div>
                  <span class="fw-600"><?= e($u['name']) ?></span>
                </div>
              </td>
              <td><code><?= e($u['username']) ?></code></td>
              <td>
                <span class="badge <?= $u['role']==='admin'?'bg-danger':'bg-info' ?>">
                  <i class="fa-solid <?= $u['role']==='admin'?'fa-shield-halved':'fa-cash-register' ?> me-1"></i>
                  <?= ucfirst($u['role']) ?>
                </span>
              </td>
              <td class="text-muted small"><?= formatDate($u['created_at'],'d M Y') ?></td>
              <td>
                <button class="action-btn edit me-1" title="Edit"
                  onclick="editUser(<?= $u['id'] ?>, '<?= e($u['name']) ?>', '<?= e($u['username']) ?>', '<?= $u['role'] ?>')">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <?php if ($u['id'] !== (int)(currentUser()['id'])): ?>
                <button class="action-btn delete" title="Hapus" onclick="deleteUser(<?= $u['id'] ?>)">
                  <i class="fa-solid fa-trash"></i>
                </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-800" id="userModalTitle">Tambah Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="userForm">
          <?= csrfField() ?>
          <input type="hidden" name="action" id="userAction" value="add">
          <input type="hidden" name="id" id="userId">
          <div class="row g-3">
            <div class="col-12">
              <label class="admin-form-label">Nama Lengkap <span class="text-danger">*</span></label>
              <input type="text" class="admin-input" name="name" id="userName" required placeholder="Nama pengguna">
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Username <span class="text-danger">*</span></label>
              <input type="text" class="admin-input" name="username" id="userUsername" required placeholder="username">
            </div>
            <div class="col-md-6">
              <label class="admin-form-label">Role <span class="text-danger">*</span></label>
              <select class="admin-input" name="role" id="userRole" required>
                <option value="cashier">Kasir</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="col-12">
              <label class="admin-form-label">Password <span class="text-danger" id="passRequired">*</span></label>
              <input type="password" class="admin-input" name="password" id="userPassword"
                     placeholder="Kosongkan jika tidak ingin mengubah">
              <small class="text-muted" id="passHint" style="display:none">Kosongkan jika tidak ingin mengubah password</small>
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

function resetUserForm() {
  document.getElementById('userModalTitle').textContent = 'Tambah Pengguna';
  document.getElementById('userAction').value = 'add';
  document.getElementById('userId').value = '';
  document.getElementById('userForm').reset();
  document.getElementById('passRequired').style.display = 'inline';
  document.getElementById('passHint').style.display = 'none';
  document.getElementById('userPassword').required = true;
}

function editUser(id, name, username, role) {
  document.getElementById('userModalTitle').textContent = 'Edit Pengguna';
  document.getElementById('userAction').value    = 'edit';
  document.getElementById('userId').value        = id;
  document.getElementById('userName').value      = name;
  document.getElementById('userUsername').value  = username;
  document.getElementById('userRole').value      = role;
  document.getElementById('userPassword').value  = '';
  document.getElementById('userPassword').required = false;
  document.getElementById('passRequired').style.display = 'none';
  document.getElementById('passHint').style.display = 'inline';
  new bootstrap.Modal(document.getElementById('userModal')).show();
}

document.getElementById('userForm').addEventListener('submit', function(e) {
  e.preventDefault();
  ajaxPost(APP_URL + '/ajax/users.php', new FormData(this), res => {
    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById('userModal'))?.hide();
      alertSuccess(res.message || 'Pengguna disimpan!');
      setTimeout(() => location.reload(), 1200);
    } else alertError(res.message || 'Gagal menyimpan.');
  });
});

initDataTable('usersTable', { searching:false, paging:false, info:false });
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
