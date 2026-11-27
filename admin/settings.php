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
              <div class="col-md-6 text-center" id="qrisPreviewWrap" style="<?= empty($settings['qris_image']) ? 'display:none' : '' ?>">
                <label class="admin-form-label">QRIS Saat Ini</label><br>
                <img id="qrisPreviewImg" src="<?= e(assetUrl($settings['qris_image'] ?? '')) ?>" alt="QRIS" style="max-width:160px;border-radius:12px">
              </div>
            </div>
          </div>

          <!-- Pengaturan Background Section Menu -->
          <?php $menuBgCfg = getMenuBgConfig(); ?>
          <div class="admin-card mb-4">
            <div class="admin-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h2 class="admin-card-title mb-0"><i class="fa-solid fa-panorama me-2" style="color:var(--primary)"></i>Pengaturan Background Section Menu</h2>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" id="menuBgEnabled" name="menu_bg_enabled" value="1" <?= $menuBgCfg['enabled'] ? 'checked' : '' ?> style="cursor:pointer;transform:scale(1.15)">
                <label class="form-check-label fw-600 ms-2" for="menuBgEnabled" id="menuBgEnabledLabel">
                  <?= $menuBgCfg['enabled'] ? 'Aktif' : 'Nonaktif' ?>
                </label>
              </div>
            </div>

            <p class="text-muted small mb-3">
              Kelola gambar latar belakang untuk <b>Section Menu Favorit Pelanggan (Beranda)</b> dan <b>Halaman Menu Lengkap</b>. Seluruh kartu menu, label, harga, dan tombol tetap berada di lapisan depan.
            </p>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="admin-form-label">Mode Penggunaan Background</label>
                <select class="admin-input" name="menu_bg_mode" id="menuBgMode" onchange="toggleMenuBgMode(this.value)">
                  <option value="same" <?= $menuBgCfg['mode'] === 'same' ? 'selected' : '' ?>>Gunakan Background yang Sama (Beranda & Halaman Menu)</option>
                  <option value="separate" <?= $menuBgCfg['mode'] === 'separate' ? 'selected' : '' ?>>Gunakan Background Berbeda (Beranda vs Halaman Menu)</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="admin-form-label">Posisi Gambar Background</label>
                <select class="admin-input" name="menu_bg_position">
                  <?php
                  $posOptions = [
                    'center center' => 'Tengah (Center)',
                    'top center'    => 'Atas (Top)',
                    'bottom center' => 'Bawah (Bottom)',
                    'left center'   => 'Kiri (Left)',
                    'right center'  => 'Kanan (Right)',
                  ];
                  foreach ($posOptions as $val => $lbl): ?>
                  <option value="<?= e($val) ?>" <?= $menuBgCfg['position'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="admin-form-label">Ukuran Tampilan</label>
                <select class="admin-input" name="menu_bg_size">
                  <option value="cover" <?= $menuBgCfg['size'] === 'cover' ? 'selected' : '' ?>>Cover (Penuh Proporsional)</option>
                  <option value="contain" <?= $menuBgCfg['size'] === 'contain' ? 'selected' : '' ?>>Contain (Tampil Utuh)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="admin-form-label">Perilaku Saat Scroll</label>
                <select class="admin-input" name="menu_bg_attachment">
                  <option value="scroll" <?= $menuBgCfg['attachment'] === 'scroll' ? 'selected' : '' ?>>Ikut Scroll (Menyesuaikan Tinggi)</option>
                  <option value="fixed" <?= $menuBgCfg['attachment'] === 'fixed' ? 'selected' : '' ?>>Tetap / Parallax (Fixed)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="admin-form-label d-flex justify-content-between">
                  <span>Kegelapan Overlay</span>
                  <strong id="menuBgOpacityVal" style="color:var(--primary)"><?= (int)$menuBgCfg['overlay_opacity'] ?>%</strong>
                </label>
                <input type="range" class="form-range mt-1" name="menu_bg_opacity" min="20" max="95" step="5"
                       value="<?= (int)$menuBgCfg['overlay_opacity'] ?>"
                       oninput="document.getElementById('menuBgOpacityVal').textContent = this.value + '%'">
              </div>
            </div>

            <?php
            $menuBgBuckets = [
              'shared' => [
                'title' => 'Gambar Background Menu (Digunakan di Beranda & Halaman Menu)',
                'desc'  => 'Unggah 1 gambar untuk background statis, atau beberapa gambar untuk slideshow halus otomatis.',
                'items' => $menuBgCfg['images_shared'],
              ],
              'home' => [
                'title' => 'A. Background Section Menu Favorit (Halaman Beranda)',
                'desc'  => 'Khusus untuk area Menu Favorit Pelanggan di halaman Beranda.',
                'items' => $menuBgCfg['images_home'],
              ],
              'menu' => [
                'title' => 'B. Background Halaman Menu Lengkap',
                'desc'  => 'Khusus untuk area daftar makanan & minuman di halaman Menu.',
                'items' => $menuBgCfg['images_menu'],
              ],
            ];
            foreach ($menuBgBuckets as $bKey => $bMeta):
              $isHidden = ($bKey === 'shared' && $menuBgCfg['mode'] === 'separate') || ($bKey !== 'shared' && $menuBgCfg['mode'] === 'same');
            ?>
            <div class="p-3 rounded-3 border mb-3 menu-bg-bucket-box" id="menuBgBox_<?= $bKey ?>" data-bucket="<?= $bKey ?>" style="<?= $isHidden ? 'display:none;' : '' ?>background:rgba(0,0,0,0.02)">
              <div class="fw-700 mb-1"><i class="fa-solid fa-images me-2" style="color:var(--primary)"></i><?= e($bMeta['title']) ?></div>
              <small class="text-muted d-block mb-3"><?= e($bMeta['desc']) ?></small>

              <div class="row g-2 mb-3 menu-bg-list-container" id="menuBgList_<?= $bKey ?>" data-bucket="<?= $bKey ?>">
                <?php foreach ($bMeta['items'] as $idx => $imgPath): ?>
                <div class="col-6 col-md-4 col-lg-3 position-relative menu-bg-manage-item" data-path="<?= e($imgPath) ?>" data-bucket="<?= $bKey ?>">
                  <input type="hidden" name="menu_bg_<?= $bKey ?>_order[]" value="<?= e($imgPath) ?>" class="menu-bg-order-input">
                  <input type="file" name="menu_bg_<?= $bKey ?>_replace[]" class="d-none menu-bg-replace-input" accept="image/*" onchange="previewReplaceMenuBg(this)">
                  <div class="position-relative rounded-3 overflow-hidden border" style="height:105px;background:#111">
                    <img src="<?= e(assetUrl($imgPath)) ?>" class="menu-bg-thumb-img" style="width:100%;height:100%;object-fit:cover">
                    <span class="badge bg-dark bg-opacity-75 position-absolute top-0 start-0 m-1 menu-bg-order-badge">#<?= $idx + 1 ?></span>
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 p-1 lh-1" onclick="deleteMenuBgImage(this)" title="Hapus Gambar">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div class="position-absolute bottom-0 start-0 end-0 p-1 d-flex justify-content-between align-items-center" style="background:rgba(0,0,0,0.68)">
                      <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-dark py-0 px-1" onclick="moveMenuBgImage(this, -1)" title="Geser Maju">
                          <i class="fa-solid fa-chevron-left" style="font-size:.7rem"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-dark py-0 px-1" onclick="moveMenuBgImage(this, 1)" title="Geser Mundur">
                          <i class="fa-solid fa-chevron-right" style="font-size:.7rem"></i>
                        </button>
                      </div>
                      <button type="button" class="btn btn-sm btn-warning py-0 px-2 fw-600" style="font-size:.7rem" onclick="this.closest('.menu-bg-manage-item').querySelector('.menu-bg-replace-input').click()" title="Ganti Gambar Ini">
                        <i class="fa-solid fa-pen me-1"></i>Ganti
                      </button>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

              <label class="admin-form-label small text-muted mb-1">Tambah Gambar Background Baru (Satu atau beberapa sekaligus)</label>
              <input type="file" class="admin-input menu-bg-new-input" name="menu_bg_<?= $bKey ?>[]" data-bucket="<?= $bKey ?>" multiple accept="image/*">
              <div class="row g-2 mt-2" id="menuBgNewPreview_<?= $bKey ?>" style="display:none"></div>
            </div>
            <?php endforeach; ?>
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
                <img src="<?= e(assetUrl($settings['logo'])) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover">
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
                <img src="<?= e(assetUrl($settings['about_image'])) ?>" style="width:100%;border-radius:10px;height:120px;object-fit:cover">
              </div>
              <?php endif; ?>
              <input type="file" class="admin-input" name="about_image" accept="image/*">
            </div>

            <!-- Banner Hero Background -->
            <div class="mb-4">
              <label class="admin-form-label">Gambar Background Hero (Slideshow)</label>
              <small class="text-muted d-block mb-2" style="font-size:.78rem">
                Unggah 1 gambar untuk background statis, atau beberapa gambar untuk slideshow otomatis (5–6 detik). Gunakan tombol panah untuk mengatur urutan, ikon pensil untuk mengganti gambar, dan ikon silang untuk menghapus.
              </small>
              <?php 
              $banners = [];
              if (!empty($settings['banner'])) {
                  $decoded = json_decode($settings['banner'], true);
                  $banners = is_array($decoded) ? array_values(array_filter($decoded)) : [$settings['banner']];
              }
              ?>
              <?php if (!empty($banners)): ?>
              <div class="row g-2 mb-3" id="bannerListContainer">
                <?php foreach($banners as $index => $b): ?>
                <div class="col-6 position-relative banner-manage-item" id="banner-<?= $index ?>" data-banner-path="<?= e($b) ?>">
                  <input type="hidden" name="banner_order[]" value="<?= e($b) ?>" class="banner-order-input">
                  <input type="file" name="banner_replace[]" class="d-none banner-replace-input" accept="image/*" onchange="previewReplaceBanner(this)">
                  <div class="position-relative rounded-3 overflow-hidden border" style="height:96px;background:#111">
                    <img src="<?= e(assetUrl($b)) ?>" class="banner-thumb-img" style="width:100%;height:100%;object-fit:cover">
                    <span class="badge bg-dark bg-opacity-75 position-absolute top-0 start-0 m-1 banner-order-badge">#<?= $index + 1 ?></span>
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 p-1 lh-1" onclick="deleteBanner(this)" title="Hapus Gambar">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div class="position-absolute bottom-0 start-0 end-0 p-1 d-flex justify-content-between align-items-center" style="background:rgba(0,0,0,0.65)">
                      <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-dark py-0 px-1" onclick="moveBanner(this, -1)" title="Geser Maju">
                          <i class="fa-solid fa-chevron-left" style="font-size:.7rem"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-dark py-0 px-1" onclick="moveBanner(this, 1)" title="Geser Mundur">
                          <i class="fa-solid fa-chevron-right" style="font-size:.7rem"></i>
                        </button>
                      </div>
                      <button type="button" class="btn btn-sm btn-warning py-0 px-2 fw-600" style="font-size:.7rem" onclick="triggerReplaceBanner(this)" title="Ubah/Ganti Gambar Ini">
                        <i class="fa-solid fa-pen me-1"></i>Ganti
                      </button>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <label class="admin-form-label small text-muted mb-1">Tambah Gambar Hero Baru (Bisa pilih beberapa sekaligus)</label>
              <input type="file" class="admin-input" name="banner[]" id="newBannerInput" multiple accept="image/*">
              <div class="row g-2 mt-2" id="newBannerPreviewWrap" style="display:none"></div>
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

document.getElementById('qrisInput')?.addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('qrisPreviewImg');
    const wrap = document.getElementById('qrisPreviewWrap');
    if (img && wrap) {
      img.src = e.target.result;
      wrap.style.display = 'block';
    }
  };
  reader.readAsDataURL(file);
});

document.getElementById('settingsForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const btn = this.querySelector('button[type=submit]');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Menyimpan...';
  fetch(APP_URL + '/ajax/settings.php', { method:'POST', body: new FormData(this) })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(res => {
      btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Pengaturan';
      if (res.success) {
        if (res.qris_url) {
          const img = document.getElementById('qrisPreviewImg');
          const wrap = document.getElementById('qrisPreviewWrap');
          if (img && wrap) {
            img.src = res.qris_url;
            wrap.style.display = 'block';
          }
        }
        alertSuccess(res.message || 'Pengaturan disimpan!');
        setTimeout(() => window.location.reload(), 1500);
      }
      else alertError(res.message || 'Gagal menyimpan.');
    })
    .catch(() => {
      btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Pengaturan';
      alertError('Terjadi kesalahan saat menyimpan pengaturan. Silakan coba lagi.');
    });
});

function refreshBannerBadges() {
  const items = document.querySelectorAll('#bannerListContainer .banner-manage-item');
  items.forEach((el, idx) => {
    const badge = el.querySelector('.banner-order-badge');
    if (badge) badge.textContent = '#' + (idx + 1);
  });
}

function saveBannerOrderAjax() {
  const inputs = document.querySelectorAll('#bannerListContainer .banner-order-input');
  const order = Array.from(inputs).map(inp => inp.value);
  fetch(APP_URL + '/ajax/delete_banner.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'csrf_token=' + encodeURIComponent(CSRF_TOKEN) + '&action=reorder&order=' + encodeURIComponent(JSON.stringify(order))
  }).then(r => r.json()).then(res => {
    if (res.success) {
      showToast('Urutan gambar Hero diperbarui.', 'success');
    }
  }).catch(() => {});
}

function moveBanner(btn, direction) {
  const item = btn.closest('.banner-manage-item');
  const container = document.getElementById('bannerListContainer');
  if (!item || !container) return;

  if (direction < 0 && item.previousElementSibling) {
    container.insertBefore(item, item.previousElementSibling);
  } else if (direction > 0 && item.nextElementSibling) {
    container.insertBefore(item.nextElementSibling, item);
  } else {
    return;
  }
  refreshBannerBadges();
  saveBannerOrderAjax();
}

function triggerReplaceBanner(btn) {
  const item = btn.closest('.banner-manage-item');
  const fileInput = item?.querySelector('.banner-replace-input');
  fileInput?.click();
}

function previewReplaceBanner(input) {
  const file = input.files?.[0];
  if (!file) return;
  const item = input.closest('.banner-manage-item');
  const img  = item?.querySelector('.banner-thumb-img');
  if (!img) return;
  const reader = new FileReader();
  reader.onload = e => {
    img.src = e.target.result;
    showToast('Gambar pengganti dipilih. Klik Simpan Pengaturan untuk menyimpan.', 'info');
  };
  reader.readAsDataURL(file);
}

function deleteBanner(btn) {
  const item = btn.closest('.banner-manage-item');
  const path = item?.getAttribute('data-banner-path') || item?.querySelector('.banner-order-input')?.value || '';
  if (!item || !path) return;

  if (confirm('Yakin ingin menghapus gambar Hero ini?')) {
    fetch(APP_URL + '/ajax/delete_banner.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF_TOKEN) + '&path=' + encodeURIComponent(path)
    }).then(r => r.json()).then(res => {
      if (res.success) {
        item.remove();
        refreshBannerBadges();
        alertSuccess('Gambar Hero berhasil dihapus.');
      } else {
        alertError(res.message || 'Gagal menghapus gambar Hero.');
      }
    });
  }
}

document.getElementById('newBannerInput')?.addEventListener('change', function() {
  const wrap = document.getElementById('newBannerPreviewWrap');
  if (!wrap) return;
  wrap.innerHTML = '';
  const files = Array.from(this.files || []);
  if (files.length === 0) {
    wrap.style.display = 'none';
    return;
  }
  wrap.style.display = 'flex';
  files.forEach((file, idx) => {
    const col = document.createElement('div');
    col.className = 'col-4';
    const reader = new FileReader();
    reader.onload = e => {
      col.innerHTML = `<div class="position-relative rounded-3 overflow-hidden border" style="height:64px">
        <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
        <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-1" style="font-size:.65rem">Baru #${idx + 1}</span>
      </div>`;
    };
    reader.readAsDataURL(file);
    wrap.appendChild(col);
  });
});
document.getElementById('menuBgEnabled')?.addEventListener('change', function() {
  const lbl = document.getElementById('menuBgEnabledLabel');
  if (lbl) lbl.textContent = this.checked ? 'Aktif' : 'Nonaktif';
});

function toggleMenuBgMode(mode) {
  const boxShared = document.getElementById('menuBgBox_shared');
  const boxHome   = document.getElementById('menuBgBox_home');
  const boxMenu   = document.getElementById('menuBgBox_menu');
  if (mode === 'separate') {
    if (boxShared) boxShared.style.display = 'none';
    if (boxHome)   boxHome.style.display   = 'block';
    if (boxMenu)   boxMenu.style.display   = 'block';
  } else {
    if (boxShared) boxShared.style.display = 'block';
    if (boxHome)   boxHome.style.display   = 'none';
    if (boxMenu)   boxMenu.style.display   = 'none';
  }
}

function refreshMenuBgBadges(bucket) {
  const items = document.querySelectorAll('#menuBgList_' + bucket + ' .menu-bg-manage-item');
  items.forEach((el, idx) => {
    const badge = el.querySelector('.menu-bg-order-badge');
    if (badge) badge.textContent = '#' + (idx + 1);
  });
}

function saveMenuBgOrderAjax(bucket) {
  const inputs = document.querySelectorAll('#menuBgList_' + bucket + ' .menu-bg-order-input');
  const order  = Array.from(inputs).map(inp => inp.value);
  fetch(APP_URL + '/ajax/delete_banner.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'csrf_token=' + encodeURIComponent(CSRF_TOKEN)
        + '&action=menu_bg_reorder'
        + '&bucket=' + encodeURIComponent(bucket)
        + '&order=' + encodeURIComponent(JSON.stringify(order))
  }).then(r => r.json()).then(res => {
    if (res.success) {
      showToast('Urutan background menu diperbarui.', 'success');
    }
  }).catch(() => {});
}

function moveMenuBgImage(btn, direction) {
  const item      = btn.closest('.menu-bg-manage-item');
  const container = item?.closest('.menu-bg-list-container');
  const bucket    = container?.getAttribute('data-bucket') || 'shared';
  if (!item || !container) return;

  if (direction < 0 && item.previousElementSibling) {
    container.insertBefore(item, item.previousElementSibling);
  } else if (direction > 0 && item.nextElementSibling) {
    container.insertBefore(item.nextElementSibling, item);
  } else {
    return;
  }
  refreshMenuBgBadges(bucket);
  saveMenuBgOrderAjax(bucket);
}

function previewReplaceMenuBg(input) {
  const file = input.files?.[0];
  if (!file) return;
  const item = input.closest('.menu-bg-manage-item');
  const img  = item?.querySelector('.menu-bg-thumb-img');
  if (!img) return;
  const reader = new FileReader();
  reader.onload = e => {
    img.src = e.target.result;
    showToast('Gambar pengganti dipilih. Klik Simpan Pengaturan untuk menyimpan.', 'info');
  };
  reader.readAsDataURL(file);
}

function deleteMenuBgImage(btn) {
  const item   = btn.closest('.menu-bg-manage-item');
  const bucket = item?.getAttribute('data-bucket') || 'shared';
  const path   = item?.getAttribute('data-path') || item?.querySelector('.menu-bg-order-input')?.value || '';
  if (!item || !path) return;

  if (confirm('Yakin ingin menghapus gambar background menu ini?')) {
    fetch(APP_URL + '/ajax/delete_banner.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF_TOKEN)
          + '&action=menu_bg_delete'
          + '&bucket=' + encodeURIComponent(bucket)
          + '&path=' + encodeURIComponent(path)
    }).then(r => r.json()).then(res => {
      if (res.success) {
        item.remove();
        refreshMenuBgBadges(bucket);
        alertSuccess('Gambar background menu berhasil dihapus.');
      } else {
        alertError(res.message || 'Gagal menghapus gambar background menu.');
      }
    });
  }
}

document.querySelectorAll('.menu-bg-new-input').forEach(inp => {
  inp.addEventListener('change', function() {
    const bucket = this.getAttribute('data-bucket') || 'shared';
    const wrap   = document.getElementById('menuBgNewPreview_' + bucket);
    if (!wrap) return;
    wrap.innerHTML = '';
    const files = Array.from(this.files || []);
    if (files.length === 0) {
      wrap.style.display = 'none';
      return;
    }
    wrap.style.display = 'flex';
    files.forEach((file, idx) => {
      const col = document.createElement('div');
      col.className = 'col-4 col-md-3';
      const reader = new FileReader();
      reader.onload = e => {
        col.innerHTML = `<div class="position-relative rounded-3 overflow-hidden border" style="height:80px">
          <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">
          <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-1" style="font-size:.65rem">Baru #${idx + 1}</span>
        </div>`;
      };
      reader.readAsDataURL(file);
      wrap.appendChild(col);
    });
  });
});
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js"></script>
