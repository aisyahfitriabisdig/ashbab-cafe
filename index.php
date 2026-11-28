<?php
/**
 * ASHBAB CAFE - Landing Page
 * File: index.php
 */
require_once __DIR__ . '/includes/functions.php';

$settings  = getSettings();
$cafeName  = $settings['cafe_name'] ?? 'ASHBAB CAFE';
$pageTitle = 'Beranda';

// Get featured menus
$randFn = DB_DRIVER === 'pgsql' ? 'RANDOM()' : 'RAND()';
$featuredMenus = db()->fetchAll(
    "SELECT m.*, c.name as category_name
     FROM menus m JOIN categories c ON m.category_id = c.id
     WHERE m.status = 'available' ORDER BY {$randFn} LIMIT 6"
);

// Get best seller (most ordered)
$bestSellers = db()->fetchAll(
    "SELECT m.*, c.name as category_name, COALESCE(SUM(oi.quantity),0) as total_sold
     FROM menus m
     JOIN categories c ON m.category_id = c.id
     LEFT JOIN order_items oi ON m.id = oi.menu_id
     WHERE m.status = 'available'
     GROUP BY m.id, c.name ORDER BY total_sold DESC LIMIT 4"
);

require_once __DIR__ . '/includes/header.php';
?>
</head>
<body>
<!-- Page Loader -->
<div id="pageLoader">
    <div class="text-center">
        <?php if (!empty($settings['logo'])): ?>
            <img src="<?= e(assetUrl($settings['logo'])) ?>" alt="Logo" class="loader-logo">
        <?php else: ?>
            <img src="<?= APP_URL ?>/assets/uploads/ashbab_logo.png" alt="Logo" class="loader-logo">
        <?php endif; ?>
        <div class="text-white mt-3 fw-600">Memuat...</div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<?php
$banners = [];
if (!empty($settings['banner'])) {
    $decoded = json_decode($settings['banner'], true);
    $banners = is_array($decoded) ? array_values(array_filter($decoded)) : [$settings['banner']];
}
$bannerCount = count($banners);
?>
<section class="hero-section <?= $bannerCount > 0 ? 'has-hero-bg' : '' ?>" id="home">
    <!-- Full Background Slideshow / Static Background -->
    <div class="hero-bg-wrapper" aria-hidden="true">
        <?php if ($bannerCount > 0): ?>
            <?php foreach ($banners as $idx => $b): ?>
                <div class="hero-bg-slide <?= $idx === 0 ? 'active' : '' ?>"
                     style="background-image: url('<?= e(assetUrl($b)) ?>');"></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="hero-bg-overlay"></div>
    </div>

    <div class="container position-relative" style="z-index:3">
        <div class="row align-items-center min-vh-100 py-5">
            <div class="col-lg-8 col-xl-7 hero-text hero-noise-surface" data-aos="fade-right">
                <div class="hero-badge hero-grain-item">
                    <i class="fa-solid fa-mug-hot me-2"></i>Selamat Datang di <?= e($cafeName) ?>
                </div>
                <h1 class="hero-title hero-grain-text">
                    Nikmati Kopi <span>Terbaik</span><br>Setiap Hari
                </h1>
                <p class="hero-subtitle">
                    Pesan menu favorit Anda langsung dari meja. Cepat, mudah, dan lezat. Pengalaman kafe yang sesungguhnya.
                </p>
                <div class="d-flex gap-3 flex-wrap hero-cta-group">
                    <a href="<?= APP_URL ?>/menu.php" class="btn-primary-cafe hero-grain-item">
                        <i class="fa-solid fa-utensils"></i> Pesan Sekarang
                    </a>
                    <a href="#menu" class="btn-outline-cafe hero-grain-item">
                        <i class="fa-solid fa-eye"></i> Lihat Menu
                    </a>
                </div>
                <!-- Stats row -->
                <div class="row mt-5 g-3 hero-stats-row">
                    <div class="col-4 col-sm-3">
                        <div class="hero-stat-item text-center text-sm-start">
                            <div class="fw-800 text-white fs-3 hero-grain-text">50+</div>
                            <div class="text-white-50 small">Menu Pilihan</div>
                        </div>
                    </div>
                    <div class="col-4 col-sm-3">
                        <div class="hero-stat-item text-center text-sm-start">
                            <div class="fw-800 text-white fs-3 hero-grain-text">1K+</div>
                            <div class="text-white-50 small">Pelanggan Puas</div>
                        </div>
                    </div>
                    <div class="col-4 col-sm-3">
                        <div class="hero-stat-item text-center text-sm-start">
                            <div class="fw-800 text-white fs-3 hero-grain-text">5★</div>
                            <div class="text-white-50 small">Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($bannerCount > 1): ?>
    <!-- Slideshow Pagination Dots -->
    <div class="hero-slide-dots" id="heroSlideDots" role="tablist" aria-label="Pilih slide gambar hero">
        <?php foreach ($banners as $idx => $b): ?>
            <button type="button"
                    class="hero-slide-dot <?= $idx === 0 ? 'active' : '' ?>"
                    data-slide-index="<?= $idx ?>"
                    aria-label="Slide <?= $idx + 1 ?>"
                    aria-selected="<?= $idx === 0 ? 'true' : 'false' ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Decorative wave -->
    <div style="position:absolute;bottom:0;left:0;right:0;z-index:3;pointer-events:none">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg"><path fill="#ffffff" d="M0,40 C360,80 1080,0 1440,40 L1440,60 L0,60 Z"/></svg>
    </div>
</section>

<?php if ($bannerCount > 1): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-bg-slide');
    const dots   = document.querySelectorAll('.hero-slide-dot');
    if (slides.length <= 1) return;

    let currentSlide = 0;
    let slideTimer   = null;
    const SLIDE_INTERVAL = 5500; // 5.5 detik

    function goToSlide(index) {
        slides[currentSlide].classList.remove('active');
        if (dots[currentSlide]) {
            dots[currentSlide].classList.remove('active');
            dots[currentSlide].setAttribute('aria-selected', 'false');
        }
        currentSlide = (index + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) {
            dots[currentSlide].classList.add('active');
            dots[currentSlide].setAttribute('aria-selected', 'true');
        }
    }

    function startTimer() {
        stopTimer();
        slideTimer = setInterval(function() {
            goToSlide(currentSlide + 1);
        }, SLIDE_INTERVAL);
    }

    function stopTimer() {
        if (slideTimer) {
            clearInterval(slideTimer);
            slideTimer = null;
        }
    }

    dots.forEach(function(dot) {
        dot.addEventListener('click', function() {
            const idx = parseInt(this.getAttribute('data-slide-index'), 10);
            if (!isNaN(idx) && idx !== currentSlide) {
                goToSlide(idx);
                startTimer();
            }
        });
    });

    startTimer();
});
</script>
<?php endif; ?>

<!-- ============================================================
     BEST SELLER SECTION
     ============================================================ -->
<?php $homeMenuBgHtml = renderMenuSectionBg('home'); ?>
<section class="menu-section <?= $homeMenuBgHtml !== '' ? 'has-custom-menu-bg' : '' ?>" id="menu">
    <?= $homeMenuBgHtml ?>
    <div class="container position-relative" style="z-index:2">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="hero-badge" style="background:rgba(139,69,19,.08);border-color:rgba(139,69,19,.2);color:var(--primary)">
                <i class="fa-solid fa-star me-2"></i>Terlaris
            </div>
            <h2 class="section-title">Menu Favorit Pelanggan</h2>
            <div class="section-divider mx-auto"></div>
            <p class="section-subtitle">Menu-menu yang paling banyak dipesan oleh pelanggan setia kami</p>
        </div>

        <div class="row g-4">
            <?php foreach ($bestSellers as $menu): ?>
            <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $loop_i++ * 100 ?? 0 ?>">
                <div class="menu-card position-relative">
                    <?php if (assetExists($menu['image'])): ?>
                        <img src="<?= e(assetUrl($menu['image'])) ?>" alt="<?= e($menu['name']) ?>" class="menu-card-img">
                    <?php else: ?>
                        <div class="menu-card-img-placeholder">🍽️</div>
                    <?php endif; ?>

                    <?php if ($menu['total_sold'] > 0): ?>
                    <span style="position:absolute;top:12px;left:12px;background:var(--primary);color:white;padding:4px 10px;border-radius:50px;font-size:.72rem;font-weight:700">
                        🔥 <?= $menu['total_sold'] ?>x terjual
                    </span>
                    <?php endif; ?>

                    <div class="menu-card-body">
                        <div class="menu-card-category"><?= e($menu['category_name']) ?></div>
                        <div class="menu-card-name"><?= e($menu['name']) ?></div>
                        <div class="menu-card-desc"><?= e($menu['description']) ?></div>
                        <div class="menu-card-price"><?= formatRupiah($menu['price']) ?></div>
                        <div class="text-muted small mb-2"><i class="fa-solid fa-box me-1"></i>Stok: <?= $menu['stock'] ?></div>
                        <div class="menu-card-actions">
                            <button class="btn-add-cart" data-id="<?= $menu['id'] ?>" <?= $menu['stock'] == 0 ? 'disabled' : '' ?>>
                                <?php if ($menu['stock'] == 0): ?>
                                    <i class="fa-solid fa-ban me-1"></i> Habis
                                <?php else: ?>
                                    <i class="fa-solid fa-cart-plus me-1"></i> Tambah
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5" data-aos="fade-up">
            <a href="<?= APP_URL ?>/menu.php" class="btn-primary-cafe">
                <i class="fa-solid fa-utensils me-2"></i>Lihat Semua Menu
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     PROMO SECTION
     ============================================================ -->
<section class="promo-section">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="promo-card">
                    <div class="hero-badge mb-3" style="background:rgba(0,0,0,.1);border-color:rgba(0,0,0,.2);color:var(--dark)">
                        <i class="fa-solid fa-percent me-2"></i>Promo Spesial
                    </div>
                    <h3 class="fw-800 fs-2 mb-3">Hemat Lebih Banyak<br>Setiap Hari!</h3>
                    <p class="mb-4" style="color:rgba(0,0,0,.8)">
                        Nikmati berbagai promo menarik setiap hari. Pesan sekarang dan dapatkan pengalaman kafe terbaik.
                    </p>
                    <a href="<?= APP_URL ?>/menu.php" class="btn btn-dark fw-700 rounded-pill px-4 py-2">
                        <i class="fa-solid fa-arrow-right me-2"></i>Pesan Sekarang
                    </a>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="admin-card h-100 text-center p-4">
                            <img src="<?= APP_URL ?>/assets/uploads/coffee_special.png" alt="Coffee Spesial" class="img-fluid rounded-circle mb-3 shadow" style="width: 80px; height: 80px; object-fit: cover;">
                            <h6 class="fw-700">Coffee Spesial</h6>
                            <p class="text-muted small mb-0">Pilihan kopi premium dari biji terbaik</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="admin-card h-100 text-center p-4">
                            <img src="<?= APP_URL ?>/assets/uploads/dessert_lezat.png" alt="Dessert Lezat" class="img-fluid rounded-circle mb-3 shadow" style="width: 80px; height: 80px; object-fit: cover;">
                            <h6 class="fw-700">Dessert Lezat</h6>
                            <p class="text-muted small mb-0">Hidangan penutup yang manis dan segar</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="admin-card h-100 text-center p-4">
                            <img src="<?= APP_URL ?>/assets/uploads/makanan_berat.png" alt="Makanan Berat" class="img-fluid rounded-circle mb-3 shadow" style="width: 80px; height: 80px; object-fit: cover;">
                            <h6 class="fw-700">Makanan Berat</h6>
                            <p class="text-muted small mb-0">Sajian utama yang mengenyangkan</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="admin-card h-100 text-center p-4">
                            <img src="<?= APP_URL ?>/assets/uploads/minuman_segar.png" alt="Minuman Segar" class="img-fluid rounded-circle mb-3 shadow" style="width: 80px; height: 80px; object-fit: cover;">
                            <h6 class="fw-700">Minuman Segar</h6>
                            <p class="text-muted small mb-0">Pilihan minuman non-kopi yang menyegarkan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     ABOUT SECTION
     ============================================================ -->
<section class="about-section" id="about">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 text-center" data-aos="zoom-in">
                <?php if (!empty($settings['about_image'])): ?>
                    <img src="<?= e(assetUrl($settings['about_image'])) ?>" alt="Tentang Kami" class="img-fluid rounded-4 shadow-lg mb-4" style="max-height: 350px; object-fit: cover;">
                <?php else: ?>
                    <div style="font-size:10rem;line-height:1">☕</div>
                <?php endif; ?>
                <h3 class="fw-bold mt-3 text-white"><?= e($cafeName) ?></h3>
                <p class="text-muted">Tempat yang nyaman untuk bersantai</p>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="hero-badge" style="background:rgba(139,69,19,.08);border-color:rgba(139,69,19,.2);color:var(--primary)">
                    <i class="fa-solid fa-circle-info me-2"></i>Tentang Kami
                </div>
                <h2 class="section-title mt-2">Kafe dengan Cita Rasa Terbaik</h2>
                <div class="section-divider"></div>
                <p class="text-muted lh-lg mb-4">
                    <?= e($cafeName) ?> adalah kafe modern yang menghadirkan pengalaman minum kopi terbaik dengan suasana yang nyaman dan elegan. Kami berkomitmen menyajikan menu berkualitas dengan bahan-bahan pilihan terbaik.
                </p>
                <div class="row g-3 mb-4">
                    <?php
                    $features = [
                        ['fa-leaf','Bahan Segar','Hanya menggunakan bahan-bahan segar dan berkualitas tinggi'],
                        ['fa-bolt','Layanan Cepat','Pesanan diproses dengan cepat dan efisien'],
                        ['fa-wifi','Free WiFi','Koneksi internet gratis untuk semua pengunjung'],
                        ['fa-snowflake','AC Sejuk','Ruangan ber-AC yang nyaman dan segar'],
                    ];
                    foreach ($features as $f): ?>
                    <div class="col-sm-6">
                        <div class="d-flex gap-3 align-items-start">
                            <div style="width:42px;height:42px;border-radius:10px;background:rgba(139,69,19,.1);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fa-solid <?= $f[0] ?>"></i>
                            </div>
                            <div>
                                <div class="fw-700 small"><?= $f[1] ?></div>
                                <div class="text-muted" style="font-size:.82rem"><?= $f[2] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     CONTACT SECTION
     ============================================================ -->
<section class="contact-section" id="contact">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-800 text-white">Hubungi Kami</h2>
            <div class="section-divider mx-auto" style="background:var(--accent)"></div>
        </div>
        <style>
        .contact-card:hover {
            transform: translateY(-10px);
            background: rgba(255,255,255,0.08) !important;
            border-color: rgba(255,215,0,0.3) !important;
        }
        .contact-icon-circle,
        a .contact-icon-circle,
        a:hover .contact-icon-circle,
        a:focus .contact-icon-circle,
        a:visited .contact-icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
            color: var(--dark) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 16px;
            box-shadow: 0 10px 20px rgba(255,215,0,0.2) !important;
        }
        .contact-icon-circle i {
            color: var(--dark) !important;
        }
        </style>
        <div class="row g-4 justify-content-center">
            <!-- Alamat -->
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="contact-card h-100 text-center p-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; transition: all 0.3s ease;">
                    <div class="contact-icon-circle">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h6 class="text-white fw-700 mb-3">Alamat</h6>
                    <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.9rem;line-height:1.6"><?= e($settings['address'] ?? '-') ?></p>
                </div>
            </div>
            
            <!-- Telepon -->
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="contact-card h-100 text-center p-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; transition: all 0.3s ease;">
                    <div class="contact-icon-circle">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <h6 class="text-white fw-700 mb-3">Telepon</h6>
                    <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.9rem;line-height:1.6"><?= e($settings['phone'] ?? '-') ?></p>
                </div>
            </div>

            <!-- Email -->
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="contact-card h-100 text-center p-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; transition: all 0.3s ease;">
                    <div class="contact-icon-circle">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <h6 class="text-white fw-700 mb-3">Email</h6>
                    <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.9rem;line-height:1.6"><?= e($settings['email'] ?? '-') ?></p>
                </div>
            </div>

            <!-- Instagram -->
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
                <a href="https://www.instagram.com/ashbab.coffee?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" rel="noopener" class="text-decoration-none d-block h-100">
                    <div class="contact-card h-100 text-center p-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; transition: all 0.3s ease; cursor: pointer;">
                        <div class="contact-icon-circle">
                            <i class="fa-brands fa-instagram"></i>
                        </div>
                        <h6 class="text-white fw-700 mb-3">Instagram</h6>
                        <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.9rem;line-height:1.6">@ashbab.coffee</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="<?= APP_URL ?>/menu.php" class="btn-primary-cafe">
                <i class="fa-solid fa-utensils me-2"></i>Pesan Sekarang
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<?= renderSiteFooter() ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
    const APP_URL   = '<?= APP_URL ?>';
    const CSRF_TOKEN = '<?= generateCSRF() ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/cart.js"></script>
