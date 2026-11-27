<?php
/**
 * ASHBAB CAFE - Vercel Serverless PHP Front Controller
 * Mengarahkan semua request .php melalui 1 Serverless Function agar kompatibel dengan Vercel Hobby.
 */

$root     = dirname(__DIR__);
$rootReal = realpath($root);

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uriPath = '/' . ltrim($uriPath, '/');

if ($uriPath === '/') {
    $uriPath = '/index.php';
} elseif ($uriPath === '/admin' || $uriPath === '/admin/') {
    $uriPath = '/admin/dashboard.php';
} elseif ($uriPath === '/cashier' || $uriPath === '/cashier/') {
    $uriPath = '/cashier/dashboard.php';
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
