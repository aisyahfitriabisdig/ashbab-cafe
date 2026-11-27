<?php
/**
 * ASHBAB CAFE - Admin Settings
 * File: admin/settings.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Pengaturan Website';
$settings  = getSettings();
$flash     = getFlash();

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content" id="adminContent">

  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Pengaturan Website</h1>
  </div>

  <div class="page-content">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> rounded-3 mb-4">
      <i class="fa-solid fa-<?= $flash['type']==='success'?'circle-check':'circle-xmark' ?> me-2"></i>
      <?= e($flash['message']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/ajax/settings.php" enctype="multipart/form-data" id="settingsForm">
      <?= csrfField() ?>
      <div class="row g-4">

        <!-- General Info -->
        <div class="col-lg-8">
          <div class="admin-card mb-4">
            <div class="admin-card-header">
              <h2 class="admin-card-title"><i class="fa-solid fa-store me-2" style="color:var(--primary)"></i>Informasi Cafe</h2>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="admin-form-label">Nama Cafe</label>
                <input type="text" class="admin-input" name="cafe_name" value="<?= e($settings['cafe_name']??'') ?>">
              </div>
              <div class="col-md-6">
                <label class="admin-form-label">No. Telepon</label>
                <input type="text" class="admin-input" name="phone" value="<?= e($settings['phone']??'') ?>">
              </div>
              <div class="col-md-6">
                <label class="admin-form-label">Email</label>
                <input type="email" class="admin-input" name="email" value="<?= e($settings['email']??'') ?>">
              </div>
              <div class="col-md-6">
                <label class="admin-form-label">Pajak (%)</label>
                <input type="number" class="admin-input" name="tax_percent" min="0" max="100" step="0.01"
                       value="<?= e($settings['tax_percent']??'10') ?>">
              </div>
              <div class="col-12">
                <label class="admin-form-label">Alamat</label>
                <textarea class="admin-input" name="address" rows="2"><?= e($settings['address']??'') ?></textarea>
              </div>
              <div class="col-12">
                <label class="admin-form-label">Teks Footer</label>
                <textarea class="admin-input" name="footer_text" rows="2"><?= e($settings['footer_text']??'') ?></textarea>
              </div>
            </div>
          </div>

          <!-- Payment Info -->
          <div class="admin-card mb-4">
            <div class="admin-card-header">
              <h2 class="admin-card-title"><i class="fa-solid fa-credit-card me-2" style="color:var(--primary)"></i>Informasi Pembayaran</h2>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="admin-form-label">Upload QRIS Image</label>
                <input type="file" class="admin-input" name="qris_image" id="qrisInput" accept="image/*">
              </div>
              <?php if (!empty($settings['qris_image'])): ?>
              <div class="col-md-6 text-center">
                <label class="admin-form-label">QRIS Saat Ini</label><br>
                <img src="<?= APP_URL . '/' . $settings['qris_image'] ?>" style="max-width:160px;border-radius:12px">
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Media Upload -->
        <div class="col-lg-4">
          <div class="admin-card mb-4">
            <div class="admin-card-header">
              <h2 class="admin-card-title"><i class="fa-solid fa-image me-2" style="color:var(--primary)"></i>Media</h2>
            </div>

            <!-- Logo -->
            <div class="mb-4">
              <label class="admin-form-label">Logo Cafe</label>
              <?php if (!empty($settings['logo'])): ?>
              <div class="text-center mb-2">
                <img src="<?= APP_URL . '/' . $settings['logo'] ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover">
              </div>
              <?php endif; ?>
              <input type="file" class="admin-input" name="logo" accept="image/*">
            </div>

            <!-- Favicon -->
            <div class="mb-4">
              <label class="admin-form-label">Favicon</label>
              <input type="file" class="admin-input" name="favicon" accept="image/*">
            </div>

            <!-- About Image -->
            <div class="mb-4">
              <label class="admin-form-label">Gambar Tentang Kami</label>
              <?php if (!empty($settings['about_image'])): ?>
              <div class="mb-2">
                <img src="<?= APP_URL . '/' . $settings['about_image'] ?>" style="width:100%;border-radius:10px;height:120px;object-fit:cover">
              </div>
              <?php endif; ?>
              <input type="file" class="admin-input" name="about_image" accept="image/*">
            </div>

            <!-- Banner -->
            <div class="mb-4">
              <label class="admin-form-label">Banner Hero (Bisa lebih dari 1)</label>
              <?php 
              $banners = [];
              if (!empty($settings['banner'])) {
                  $decoded = json_decode($settings['banner'], true);
                  $banners = is_array($decoded) ? $decoded : [$settings['banner']];
              }
              if (!empty($banners)): ?>
              <div class="row g-2 mb-3">
                <?php foreach($banners as $index => $b): ?>
                <div class="col-6 position-relative" id="banner-<?= $index ?>">
                  <img src="<?= APP_URL . '/' . $b ?>" style="width:100%;border-radius:10px;height:80px;object-fit:cover">
                  <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 p-1" onclick="deleteBanner('<?= e($b) ?>', <?= $index ?>)" title="Hapus">
                      <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <input type="file" class="admin-input" name="banner[]" multiple accept="image/*">
            </div>
          </div>
        </div>

        <!-- Save Button -->
        <div class="col-12">
          <button type="submit" class="btn-primary-cafe px-5 py-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-floppy-disk"></i>Simpan Pengaturan
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';

document.getElementById('settingsForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = this.querySelector('button[type=submit]');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Menyimpan...';
  fetch(APP_URL + '/ajax/settings.php', { method:'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Pengaturan';
      if (res.success) {
        alertSuccess(res.message || 'Pengaturan disimpan!');
        setTimeout(() => window.location.reload(), 1500);
      }
      else alertError(res.message || 'Gagal menyimpan.');
    });
});

function deleteBanner(path, index) {
  if (confirm('Yakin ingin menghapus banner ini?')) {
    fetch(APP_URL + '/ajax/delete_banner.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + CSRF_TOKEN + '&path=' + encodeURIComponent(path)
    }).then(r => r.json()).then(res => {
      if (res.success) {
        document.getElementById('banner-' + index).remove();
        alertSuccess('Banner berhasil dihapus.');
      } else {
        alertError(res.message || 'Gagal menghapus banner.');
      }
    });
  }
}
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
