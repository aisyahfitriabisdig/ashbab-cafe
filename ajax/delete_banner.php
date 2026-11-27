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

$action = clean($_POST['action'] ?? 'delete');

$current = db()->fetch("SELECT * FROM settings LIMIT 1");
if (!$current || empty($current['banner'])) {
    jsonResponse(['success'=>false,'message'=>'Banner tidak ditemukan']);
}

$decoded = json_decode($current['banner'], true);
$bannerPaths = is_array($decoded) ? array_values(array_filter($decoded)) : [$current['banner']];

if ($action === 'reorder') {
    $rawOrder = $_POST['order'] ?? '';
    $requested = json_decode($rawOrder, true);
    if (!is_array($requested)) {
        jsonResponse(['success'=>false,'message'=>'Format urutan tidak valid']);
    }
    $newOrder = [];
    foreach ($requested as $item) {
        $item = trim((string)$item);
        if ($item !== '' && in_array($item, $bannerPaths, true) && !in_array($item, $newOrder, true)) {
            $newOrder[] = $item;
        }
    }
    foreach ($bannerPaths as $existing) {
        if (!in_array($existing, $newOrder, true)) {
            $newOrder[] = $existing;
        }
    }
    $newBannerStr = !empty($newOrder) ? json_encode(array_values($newOrder), JSON_UNESCAPED_SLASHES) : null;
    db()->execute("UPDATE settings SET banner=? WHERE id=?", [$newBannerStr, $current['id']]);
    jsonResponse(['success'=>true,'message'=>'Urutan gambar Hero berhasil disimpan']);
}

$pathToDelete = $_POST['path'] ?? '';
if (empty($pathToDelete)) {
    jsonResponse(['success'=>false,'message'=>'Path tidak valid']);
}

$newPaths = [];
$found = false;
foreach ($bannerPaths as $path) {
    if ($path === $pathToDelete) {
        $found = true;
        deleteUploadedFile($path);
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
