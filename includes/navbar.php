<?php
/**
 * ASHBAB CAFE - Public Navbar
 * File: includes/navbar.php
 */
$settings = getSettings();
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNavbar">
    <div class="container px-3">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2 me-2" href="<?= APP_URL ?>/">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?= e(assetUrl($settings['logo'])) ?>" alt="Logo" class="navbar-brand-logo rounded-circle">
            <?php else: ?>
                <img src="<?= APP_URL ?>/assets/uploads/ashbab_logo.png" alt="Ashbab Logo" class="navbar-brand-logo rounded-circle">
            <?php endif; ?>
            <span class="brand-name fw-bold text-truncate"><?= e($settings['cafe_name'] ?? 'ASHBAB CAFE') ?></span>
        </a>

        <!-- Mobile Right Actions: Cart + Hamburger Toggler -->
        <div class="d-flex align-items-center gap-2 order-lg-3">
            <a href="<?= APP_URL ?>/cart.php"
               class="btn position-relative d-flex align-items-center justify-content-center cart-btn-nav"
               title="Keranjang Belanja"
               aria-label="Keranjang Belanja">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="navCartCount">
                    <?= $cartCount ?>
                </span>
            </a>

            <button class="navbar-toggler nav-hamburger-btn d-lg-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navMenu"
                    aria-controls="navMenu"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <!-- Collapsible Navigation Menu -->
        <div class="collapse navbar-collapse justify-content-lg-center order-lg-2" id="navMenu">
            <ul class="navbar-nav align-items-lg-center mx-lg-auto gap-1 gap-lg-2 mt-3 mt-lg-0 mobile-nav-list">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/">
                        <i class="fa-solid fa-house me-2 me-lg-1"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'menu.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/menu.php">
                        <i class="fa-solid fa-utensils me-2 me-lg-1"></i>Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'tracking.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/tracking.php">
                        <i class="fa-solid fa-location-dot me-2 me-lg-1"></i>Tracking
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/about.php">
                        <i class="fa-solid fa-circle-info me-2 me-lg-1"></i>Tentang
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
