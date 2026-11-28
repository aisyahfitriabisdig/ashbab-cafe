<?php
/**
 * ASHBAB CAFE - HTML Head / Header Include
 * File: includes/header.php
 * NOTE: Pages must echo </head><body> after including this file
 *       (allows per-page CSS injection before </head>)
 */
$settings = getSettings();
$cafeName  = e($settings['cafe_name'] ?? 'ASHBAB CAFE');
$pageTitle = isset($pageTitle) ? e($pageTitle) . ' | ' . $cafeName : $cafeName;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $cafeName ?> - Sistem Pemesanan Makanan & Minuman Online">
    <meta name="theme-color" content="#FFD700">
    <title><?= $pageTitle ?></title>

    <!-- Favicon (Hardcoded circular PNG from assets/uploads/logo) -->
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/uploads/logo/favicon_round.png?v=2">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/uploads/logo/favicon_round.png?v=2">

    <!-- Preconnect CDN -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Font Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom Public CSS -->
    <link href="<?= APP_URL ?>/assets/css/style.css?v=<?= APP_VERSION ?>" rel="stylesheet">
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
