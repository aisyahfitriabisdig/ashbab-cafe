<?php
/**
 * ASHBAB CAFE - Admin/Cashier Sidebar
 * File: includes/sidebar.php
 */
$user = currentUser();
$settings = getSettings();
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function sideLink(string $href, string $icon, string $label, string $current, string $file): string {
    $active = ($current === $file) ? 'active' : '';
    return "<li class='nav-item'>
        <a href='{$href}' class='nav-link sidebar-link {$active}'>
            <i class='fa-solid {$icon} sidebar-icon'></i>
            <span class='sidebar-label'>{$label}</span>
        </a>
    </li>";
}
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?= APP_URL ?>/<?= e($settings['logo']) ?>" alt="Logo" style="width: 40px; height: 40px; object-fit: cover;" class="rounded-circle">
            <?php else: ?>
                <img src="<?= APP_URL ?>/assets/uploads/ashbab_logo.png" alt="Ashbab Logo" style="width: 40px; height: 40px; object-fit: cover;" class="rounded-circle">
            <?php endif; ?>
            <div>
                <div class="fw-bold text-white"><?= e($settings['cafe_name'] ?? 'ASHBAB CAFE') ?></div>
                <small class="text-warning text-capitalize"><?= e($user['role']) ?> Panel</small>
            </div>
        </div>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="fa-solid fa-user-circle fa-2x"></i>
        </div>
        <div class="ms-2">
            <div class="user-name"><?= e($user['name']) ?></div>
            <small class="text-capitalize text-warning"><?= e($user['role']) ?></small>
        </div>
    </div>

    <hr class="sidebar-divider">

    <!-- Nav Links -->
    <ul class="nav flex-column sidebar-nav">

        <?php if ($user['role'] === 'admin'): ?>
            <!-- ADMIN MENU -->
            <li class="nav-label">UTAMA</li>
            <?= sideLink(APP_URL.'/admin/dashboard.php', 'fa-gauge', 'Dashboard', $currentFile, 'dashboard.php') ?>
            <?= sideLink(APP_URL.'/admin/orders.php', 'fa-receipt', 'Pesanan', $currentFile, 'orders.php') ?>

            <li class="nav-label">MANAJEMEN</li>
            <?= sideLink(APP_URL.'/admin/categories.php', 'fa-tags', 'Kategori', $currentFile, 'categories.php') ?>
            <?= sideLink(APP_URL.'/admin/menus.php', 'fa-utensils', 'Menu', $currentFile, 'menus.php') ?>

            <li class="nav-label">KEUANGAN</li>
            <?= sideLink(APP_URL.'/admin/income.php',   'fa-arrow-trend-up',  'Pemasukan',   $currentFile, 'income.php') ?>
            <?= sideLink(APP_URL.'/admin/expenses.php', 'fa-money-bill-wave', 'Pengeluaran', $currentFile, 'expenses.php') ?>
            <?= sideLink(APP_URL.'/admin/reports.php',  'fa-chart-line',      'Laporan',     $currentFile, 'reports.php') ?>

            <li class="nav-label">PENGATURAN</li>
            <?= sideLink(APP_URL.'/admin/users.php', 'fa-users', 'Pengguna', $currentFile, 'users.php') ?>
            <?= sideLink(APP_URL.'/admin/settings.php', 'fa-gear', 'Pengaturan', $currentFile, 'settings.php') ?>

        <?php elseif ($user['role'] === 'cashier'): ?>
            <!-- CASHIER MENU -->
            <li class="nav-label">KASIR</li>
            <?= sideLink(APP_URL.'/cashier/dashboard.php', 'fa-gauge', 'Dashboard', $currentFile, 'dashboard.php') ?>
            <?= sideLink(APP_URL.'/cashier/orders.php', 'fa-receipt', 'Kelola Pesanan', $currentFile, 'orders.php') ?>
            <div style="display:none;">
                <?= sideLink(APP_URL.'/cashier/receipt.php',       'fa-print',        'Cetak Struk',   $currentFile, 'receipt.php') ?>
                <?= sideLink(APP_URL.'/cashier/notifications.php', 'fa-bell',         'Notifikasi',    $currentFile, 'notifications.php') ?>
            </div>
        <?php endif; ?>

        <hr class="sidebar-divider">
        <!-- Back to Site -->
        <li class="nav-item" <?php if($user['role'] === 'cashier') echo 'style="display:none;"'; ?>>
            <a href="<?= APP_URL ?>/" class="nav-link sidebar-link" target="_blank">
                <i class="fa-solid fa-globe sidebar-icon"></i>
                <span class="sidebar-label">Lihat Website</span>
            </a>
        </li>

        <!-- Logout -->
        <li class="nav-item">
            <a href="<?= APP_URL ?>/logout.php" class="nav-link sidebar-link text-danger">
                <i class="fa-solid fa-right-from-bracket sidebar-icon"></i>
                <span class="sidebar-label">Keluar</span>
            </a>
        </li>
    </ul>

</nav>
