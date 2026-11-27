<?php
/**
 * ASHBAB CAFE - Menu Page
 * File: menu.php
 */
require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Menu';
$settings   = getSettings();

$categories = db()->fetchAll("SELECT * FROM categories ORDER BY name");
$catId      = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$sql    = "SELECT m.*, c.name as category_name
           FROM menus m JOIN categories c ON m.category_id = c.id
           WHERE m.status = 'available'";
$params = [];
if ($catId > 0) { $sql .= " AND m.category_id = ?"; $params[] = $catId; }
$sql .= " ORDER BY c.name, m.name";

$menus = db()->fetchAll($sql, $params);

require_once __DIR__ . '/includes/header.php';
?>
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="menu-page-wrapper">
<div class="container py-5">

    <!-- Page Title -->
    <div class="text-center mb-5" data-aos="fade-up">
        <div class="hero-badge" style="background:rgba(139,69,19,.08);border-color:rgba(139,69,19,.2);color:var(--primary)">
            <i class="fa-solid fa-utensils me-2"></i>Menu Kami
        </div>
        <h2 class="section-title mt-2">Pilihan Menu Lengkap</h2>
        <div class="section-divider mx-auto"></div>
        <p class="section-subtitle">Temukan menu favorit Anda dari koleksi kami yang beragam</p>
    </div>

    <!-- Category Filter -->
    <div class="menu-filter text-center" data-aos="fade-up">
        <a href="<?= APP_URL ?>/menu.php" class="filter-btn <?= $catId===0?'active':'' ?> me-2 mb-2">
            <i class="fa-solid fa-border-all me-1"></i>Semua
        </a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= APP_URL ?>/menu.php?cat=<?= $cat['id'] ?>"
           class="filter-btn <?= $catId===$cat['id']?'active':'' ?> me-2 mb-2">
            <i class="fa-solid <?= e($cat['icon']) ?> me-1"></i><?= e($cat['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Menu Grid -->
    <div class="row g-4" id="menuGrid">
        <?php if (empty($menus)): ?>
        <div class="col-12 text-center py-5">
            <div style="font-size:4rem">🍽️</div>
            <h5 class="mt-3 text-muted">Belum ada menu tersedia</h5>
        </div>
        <?php endif; ?>

        <?php foreach ($menus as $i => $menu): ?>
        <div class="col-6 col-md-4 col-xl-3" data-aos="fade-up" data-aos-delay="<?= ($i % 8) * 60 ?>">
            <div class="menu-card h-100 position-relative">
                <?php if (assetExists($menu['image'])): ?>
                    <img src="<?= e(assetUrl($menu['image'])) ?>" alt="<?= e($menu['name']) ?>" class="menu-card-img">
                <?php else: ?>
                    <div class="menu-card-img-placeholder">🍽️</div>
                <?php endif; ?>

                <div class="menu-card-body">
                    <div class="menu-card-category"><?= e($menu['category_name']) ?></div>
                    <h6 class="menu-card-name"><?= e($menu['name']) ?></h6>
                    <p class="menu-card-desc"><?= e($menu['description'] ?? '') ?></p>
                    <div class="menu-card-price"><?= formatRupiah($menu['price']) ?></div>
                    <div class="text-muted small mb-2"><i class="fa-solid fa-box me-1"></i>Stok: <?= $menu['stock'] ?></div>
                    <div class="menu-card-actions">
                        <button class="btn-add-cart" data-id="<?= $menu['id'] ?>"
                                <?= $menu['stock'] == 0 ? 'disabled' : '' ?>>
                            <?php if ($menu['stock'] == 0): ?>
                                <i class="fa-solid fa-ban me-1"></i> Habis
                            <?php else: ?>
                                <i class="fa-solid fa-cart-plus me-1"></i> Tambah
                            <?php endif; ?>
                        </button>
                        <button class="action-btn view" onclick="showDetail(<?= $menu['id'] ?>)" title="Detail">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</div>

<!-- Menu Detail Modal -->
<div class="modal fade" id="menuDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-body p-0" id="menuDetailContent">
                <div class="text-center p-5"><div class="loader-coffee">☕</div></div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="site-footer">
    <p class="mb-0"><?= e($settings['footer_text'] ?? '© '.date('Y').' '.e($settings['cafe_name'] ?? 'ASHBAB CAFE')) ?></p>
</footer>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
    const APP_URL    = '<?= APP_URL ?>';
    const CSRF_TOKEN = '<?= generateCSRF() ?>';

    function showDetail(menuId) {
        const modal = new bootstrap.Modal(document.getElementById('menuDetailModal'));
        const content = document.getElementById('menuDetailContent');
        content.innerHTML = '<div class="text-center p-5"><img src="' + APP_URL + '/assets/uploads/ashbab_logo.png" alt="Logo" class="loader-logo"><p class="mt-3 text-muted fw-600">Memuat...</p></div>';
        modal.show();

        fetch(APP_URL + '/ajax/get_menu.php?id=' + menuId)
            .then(r => r.json())
            .then(res => {
                if (!res.success) { content.innerHTML = '<p class="p-4 text-center text-muted">Menu tidak ditemukan.</p>'; return; }
                const m = res.menu;
                const imgSrc = m.image
                    ? (/^(https?:\/\/|data:)/i.test(m.image) ? m.image : `${APP_URL}/${m.image.replace(/^\/+/, '')}`)
                    : '';
                const imgHtml = imgSrc
                    ? `<img src="${imgSrc}" class="w-100" style="height:240px;object-fit:cover" alt="${m.name}">`
                    : `<div style="height:200px;background:linear-gradient(135deg,var(--accent),#e8d5b0);display:flex;align-items:center;justify-content:center;font-size:5rem">🍽️</div>`;
                content.innerHTML = `
                    <div class="rounded-4 overflow-hidden">
                        ${imgHtml}
                        <div class="p-4">
                            <div class="text-muted small mb-1">${m.category_name}</div>
                            <h5 class="fw-700 mb-1">${m.name}</h5>
                            <p class="text-muted mb-3">${m.description || 'Tidak ada deskripsi.'}</p>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <span class="fs-4 fw-800" style="color:var(--primary)">Rp ${Number(m.price).toLocaleString('id-ID')}</span>
                                <span class="badge ${m.stock > 0 ? 'bg-success' : 'bg-danger'}">${m.stock > 0 ? 'Stok: '+m.stock : 'Stok Habis'}</span>
                            </div>
                            <button class="btn-add-cart w-100" data-id="${m.id}" ${m.stock==0?'disabled':''} onclick="bootstrap.Modal.getInstance(document.getElementById('menuDetailModal')).hide()">
                                <i class="fa-solid fa-cart-plus me-2"></i>${m.stock==0?'Stok Habis':'Tambah ke Keranjang'}
                            </button>
                        </div>
                    </div>`;
            });
    }
</script>
<script src="<?= APP_URL ?>/assets/js/cart.js"></script>
