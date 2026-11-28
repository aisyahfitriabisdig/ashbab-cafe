<?php
/**
 * ASHBAB CAFE - Vercel Serverless PHP Front Controller
 * Mengarahkan semua request .php melalui 1 Serverless Function agar kompatibel dengan Vercel Hobby.
 */

$root     = dirname(__DIR__);
$rootReal = realpath($root);

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uriPath = '/' . ltrim($uriPath, '/');

// Support Google Search Console HTML file verification (e.g. /google1234567890abcdef.html)
if (preg_match('#^/(google[a-zA-Z0-9]+\.html)$#', $uriPath, $m)) {
    header('Content-Type: text/html; charset=utf-8');
    echo 'google-site-verification: ' . $m[1];
    exit;
}

$cleanRoutes = [
    '/'            => '/index.php',
    '/beranda'     => '/index.php',
    '/home'        => '/index.php',
    '/menu'        => '/menu.php',
    '/tracking'    => '/tracking.php',
    '/about'       => '/about.php',
    '/tentang'     => '/about.php',
    '/cart'        => '/cart.php',
    '/keranjang'   => '/cart.php',
    '/checkout'    => '/checkout.php',
    '/robots.txt'  => '/robots.php',
    '/sitemap.xml' => '/sitemap.php',
    '/admin'       => '/admin/dashboard.php',
    '/admin/'      => '/admin/dashboard.php',
    '/cashier'     => '/cashier/dashboard.php',
    '/cashier/'    => '/cashier/dashboard.php',
];

if (isset($cleanRoutes[$uriPath])) {
    $uriPath = $cleanRoutes[$uriPath];
}

$target = realpath($root . $uriPath);

if (
    $target === false ||
    $rootReal === false ||
    !str_starts_with($target, $rootReal . DIRECTORY_SEPARATOR) ||
    !is_file($target) ||
    pathinfo($target, PATHINFO_EXTENSION) !== 'php'
) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

// Blokir akses langsung ke folder internal
$relPath = str_replace('\\', '/', substr($target, strlen($rootReal) + 1));
if (
    str_starts_with($relPath, 'includes/') ||
    str_starts_with($relPath, 'api/') ||
    str_starts_with($relPath, 'database/')
) {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

chdir(dirname($target));
$_SERVER['SCRIPT_FILENAME'] = $target;
$_SERVER['SCRIPT_NAME']     = '/' . $relPath;
$_SERVER['PHP_SELF']        = '/' . $relPath;

require $target;
