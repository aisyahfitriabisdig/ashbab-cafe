<?php
/**
 * ASHBAB CAFE - HTML Head / Header Include
 * File: includes/header.php
 * NOTE: Pages must echo </head><body> after including this file
 *       (allows per-page CSS injection before </head>)
 */
$settings   = getSettings();
$seoCfg     = getMenuBgConfig();
$cafeName   = e($settings['cafe_name'] ?? 'ASHBAB CAFE');
$baseUrl    = rtrim(APP_URL ?: 'https://ashbabcafe.my.id', '/');
$scriptName = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$scriptDir  = basename(dirname($_SERVER['PHP_SELF'] ?? '/'));

$isPrivatePage = in_array($scriptDir, ['admin', 'cashier', 'ajax'], true)
                 || in_array($scriptName, ['login.php', 'logout.php', 'cart.php', 'checkout.php'], true);
$isHomePage    = !$isPrivatePage && ($scriptName === 'index.php' || $scriptName === '');

if ($isHomePage) {
    $fullTitle = $cafeName . ' - Official Website | Nikmati Kopi Terbaik Setiap Hari';
} else {
    $fullTitle = isset($pageTitle) ? e($pageTitle) . ' | ' . $cafeName : $cafeName;
}

$metaDesc     = e($seoCfg['seo_description'] ?? ($cafeName . ' - Sistem Pemesanan Makanan & Minuman Online'));
$metaKeywords = e($seoCfg['seo_keywords'] ?? 'ashbab cafe, ASHBAB CAFE, ashbab coffee, kafe ashbab');
$gscToken     = trim((string)($seoCfg['google_verification'] ?? ''));
$singleResult = !empty($seoCfg['seo_single_result']);

// Canonical URL: Consolidate public pages to the main homepage URL so Google indexes all public pages
// while displaying 1 main result (https://ashbabcafe.my.id/) when users search "ashbab cafe" or "ASHBAB CAFE"
if ($singleResult || $isHomePage) {
    $canonicalUrl = $baseUrl . '/';
} else {
    $canonicalUrl = $baseUrl . '/' . ($scriptName === 'index.php' ? '' : $scriptName);
}

$ogImage = !empty($settings['logo'])
    ? assetUrl($settings['logo'])
    : ($baseUrl . '/assets/uploads/logo/favicon_round.png');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $metaDesc ?>">
    <meta name="keywords" content="<?= $metaKeywords ?>">
    <meta name="author" content="<?= $cafeName ?>">
    <meta name="application-name" content="<?= $cafeName ?>">
    <meta name="theme-color" content="#FFD700">
    <?php if ($isPrivatePage): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <?php endif; ?>
    <?php if ($gscToken !== ''): ?>
    <meta name="google-site-verification" content="<?= e($gscToken) ?>">
    <?php endif; ?>
    <title><?= $fullTitle ?></title>

    <!-- Open Graph / Social & Google Rich Preview -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= $cafeName ?>">
    <meta property="og:title" content="<?= $fullTitle ?>">
    <meta property="og:description" content="<?= $metaDesc ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:locale" content="id_ID">

    <?php if (!$isPrivatePage):
    $schemaData = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'         => 'WebSite',
                '@id'           => $baseUrl . '/#website',
                'url'           => $baseUrl . '/',
                'name'          => html_entity_decode($cafeName, ENT_QUOTES, 'UTF-8'),
                'alternateName' => ['ashbab cafe', 'ASHBAB CAFE', 'Ashbab Cafe', 'Ashbab Coffee'],
                'description'   => html_entity_decode($metaDesc, ENT_QUOTES, 'UTF-8'),
                'inLanguage'    => 'id-ID',
            ],
            [
                '@type'         => 'CafeOrCoffeeShop',
                '@id'           => $baseUrl . '/#cafe',
                'name'          => html_entity_decode($cafeName, ENT_QUOTES, 'UTF-8'),
                'alternateName' => ['ashbab cafe', 'ASHBAB CAFE', 'Ashbab Coffee'],
                'url'           => $baseUrl . '/',
                'image'         => $ogImage,
                'logo'          => $baseUrl . '/assets/uploads/logo/favicon_round.png',
                'description'   => html_entity_decode($metaDesc, ENT_QUOTES, 'UTF-8'),
                'telephone'     => $settings['phone'] ?? '',
                'email'         => $settings['email'] ?? '',
                'address'       => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => $settings['address'] ?? '',
                    'addressCountry'  => 'ID',
                ],
                'servesCuisine' => ['Coffee', 'Indonesian Food', 'Beverages'],
                'hasMenu'       => $baseUrl . '/menu.php',
            ],
        ],
    ];
    ?>
    <script type="application/ld+json"><?= json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>

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
