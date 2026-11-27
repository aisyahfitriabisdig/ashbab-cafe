<?php
/**
 * ASHBAB CAFE - About Page
 * File: about.php
 */
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Tentang Kami';
$settings  = getSettings();

require_once __DIR__ . '/includes/header.php';
?>
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- Page Header / Hero -->
<section class="about-hero-section" style="background: linear-gradient(135deg, var(--dark), var(--light)); color: white; text-align: center;">
    <div class="container" data-aos="fade-up">
        <h1 class="fw-800 display-4 mb-3"><span style="color:var(--primary)">Tentang</span> Kami</h1>
        <p class="lead" style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 0 auto;">
            Ketahui lebih dekat perjalanan dan dedikasi kami dalam menyajikan kopi terbaik untuk Anda.
        </p>
    </div>
</section>

<!-- About Details -->
<section class="about-section" style="background: var(--light);">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <div style="position: relative;">
                    <?php if (!empty($settings['about_image'])): ?>
                        <img src="<?= e(assetUrl($settings['about_image'])) ?>" alt="Tentang Kami" class="img-fluid rounded-4 shadow-lg" style="width: 100%; max-height: 500px; object-fit: cover;">
                    <?php else: ?>
                        <img src="<?= APP_URL ?>/assets/uploads/ashbab_logo.png" alt="Ashbab Cafe" class="img-fluid rounded-4 shadow-lg" style="width: 100%; max-height: 500px; object-fit: contain; background: var(--dark); padding: 40px;">
                    <?php endif; ?>
                    <div style="position: absolute; bottom: -30px; right: -30px; background: var(--primary); color: var(--dark); padding: 20px 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(255,215,0,0.3);" class="d-none d-md-block">
                        <h3 class="fw-800 mb-0">100%</h3>
                        <p class="mb-0 fw-600">Kopi Asli Pilihan</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="hero-badge mb-3" style="background:rgba(255,215,0,0.1);border-color:rgba(255,215,0,0.2);color:var(--primary)">
                    <i class="fa-solid fa-mug-hot me-2"></i>Cerita Kami
                </div>
                <h2 class="fw-800 mb-4" style="color: var(--text-dark);">Sejarah & Filosofi <span style="color: var(--primary);">ASHBAB CAFE</span></h2>
                <p class="text-muted lh-lg mb-4">
                    Berawal dari kecintaan kami terhadap secangkir kopi, <strong><?= e($settings['cafe_name'] ?? 'ASHBAB CAFE') ?></strong> didirikan dengan tujuan menjadi tempat bertemunya berbagai cerita, ide, dan kebersamaan. Kami percaya bahwa setiap seduhan kopi bukan hanya sekadar minuman, melainkan jembatan yang menghubungkan persahabatan.
                </p>
                <p class="text-muted lh-lg mb-4">
                    Dengan biji kopi pilihan yang dipanggang dengan standar kualitas tinggi, serta suasana kafe yang dirancang secara modern dan <em>cozy</em>, kami berusaha memberikan pengalaman bersantai yang tak terlupakan.
                </p>
                
                <div class="row g-4 mt-2">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-start gap-3">
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(255,215,0,0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.25rem;">
                                <i class="fa-solid fa-bullseye"></i>
                            </div>
                            <div>
                                <h6 class="fw-700 mb-1">Visi Kami</h6>
                                <p class="text-muted small mb-0">Menjadi kafe pilihan utama yang menginspirasi melalui rasa dan kenyamanan.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-start gap-3">
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(255,215,0,0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.25rem;">
                                <i class="fa-solid fa-rocket"></i>
                            </div>
                            <div>
                                <h6 class="fw-700 mb-1">Misi Kami</h6>
                                <p class="text-muted small mb-0">Menyajikan produk berkualitas dan pelayanan ramah dalam suasana yang berkesan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="about-section" style="background: var(--dark); color: white;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-800 text-white">Nilai Inti Kami</h2>
            <div class="section-divider mx-auto" style="background:var(--primary)"></div>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div style="font-size: 3rem; color: var(--primary); margin-bottom: 20px;"><i class="fa-solid fa-heart"></i></div>
                <h5 class="fw-700 mb-3">Kualitas Tinggi</h5>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">Kami tidak pernah berkompromi soal bahan baku. Kualitas adalah janji kami kepada Anda.</p>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div style="font-size: 3rem; color: var(--primary); margin-bottom: 20px;"><i class="fa-solid fa-face-smile"></i></div>
                <h5 class="fw-700 mb-3">Pelayanan Prima</h5>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">Senyum dan keramahan adalah standar utama kami dalam melayani setiap pelanggan.</p>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div style="font-size: 3rem; color: var(--primary); margin-bottom: 20px;"><i class="fa-solid fa-couch"></i></div>
                <h5 class="fw-700 mb-3">Kenyamanan Tempat</h5>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">Desain interior yang elegan dan menenangkan dirancang khusus untuk Anda bersantai.</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<?= renderSiteFooter() ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
