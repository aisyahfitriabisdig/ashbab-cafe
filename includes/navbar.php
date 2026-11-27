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
<nav class="navbar navbar-expand navbar-dark fixed-top" id="mainNavbar">
    <div class="container px-2 px-md-3">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-1 gap-md-2 me-1 me-md-3" href="<?= APP_URL ?>/">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?= APP_URL . '/' . $settings['logo'] ?>" alt="Logo" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle">
            <?php else: ?>
                <img src="<?= APP_URL ?>/assets/uploads/ashbab_logo.png" alt="Ashbab Logo" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle">
            <?php endif; ?>
            <span class="brand-name fw-bold d-none d-sm-block"><?= e($settings['cafe_name'] ?? 'ASHBAB CAFE') ?></span>
        </a>

        <!-- Menu -->
        <div class="d-flex flex-grow-1 justify-content-end justify-content-lg-center" id="navMenu">
            <ul class="navbar-nav flex-row align-items-center mx-auto gap-1 gap-md-2 mobile-nav-list">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/">
                        <i class="fa-solid fa-house me-1"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'menu.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/menu.php">
                        <i class="fa-solid fa-utensils me-1"></i>Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'tracking.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/tracking.php">
                        <i class="fa-solid fa-location-dot me-1"></i>Tracking
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/about.php">
                        <i class="fa-solid fa-circle-info me-1"></i>Tentang
                    </a>
                </li>
            </ul>

            <!-- Cart Button -->
            <?php if ($currentPage === 'menu.php'): ?>
            <a href="<?= APP_URL ?>/cart.php" class="btn position-relative d-flex align-items-center justify-content-center ms-2 ms-md-3 mt-0 cart-btn-nav" style="width: 35px; height: 35px; border-radius: 50%; background-color: var(--primary); color: var(--dark); border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <i class="fa-solid fa-cart-shopping" style="font-size: 0.9rem;"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="navCartCount" style="font-size: 0.65rem;">
                    <?= $cartCount ?>
                </span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
