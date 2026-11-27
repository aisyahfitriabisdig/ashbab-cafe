<?php
/**
 * ASHBAB CAFE - AJAX: Delete Banner
 * File: ajax/delete_banner.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$pathToDelete = $_POST['path'] ?? '';
if (empty($pathToDelete)) {
    jsonResponse(['success'=>false,'message'=>'Path tidak valid']);
}

$current = db()->fetch("SELECT * FROM settings LIMIT 1");
if (!$current || empty($current['banner'])) {
    jsonResponse(['success'=>false,'message'=>'Banner tidak ditemukan']);
}

$decoded = json_decode($current['banner'], true);
$bannerPaths = is_array($decoded) ? $decoded : [$current['banner']];

$newPaths = [];
$found = false;
foreach ($bannerPaths as $path) {
    if ($path === $pathToDelete) {
        $found = true;
        if (file_exists(ROOT_PATH . $path)) {
            @unlink(ROOT_PATH . $path);
        }
    } else {
        $newPaths[] = $path;
    }
}

if ($found) {
    $newBannerStr = !empty($newPaths) ? json_encode(array_values($newPaths), JSON_UNESCAPED_SLASHES) : null;
    db()->execute("UPDATE settings SET banner=? WHERE id=?", [$newBannerStr, $current['id']]);
    jsonResponse(['success'=>true,'message'=>'Banner berhasil dihapus']);
} else {
    jsonResponse(['success'=>false,'message'=>'Banner tidak ditemukan di database']);
}
