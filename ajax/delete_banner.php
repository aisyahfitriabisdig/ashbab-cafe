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
if (!$current) {
    jsonResponse(['success'=>false,'message'=>'Pengaturan tidak ditemukan']);
}

if ($action === 'menu_bg_delete' || $action === 'menu_bg_reorder') {
    ensureMenuBgColumn();
    $cfg    = getMenuBgConfig();
    $bucket = in_array($_POST['bucket'] ?? '', ['shared', 'home', 'menu'], true) ? $_POST['bucket'] : 'shared';
    $key    = 'images_' . $bucket;
    $list   = $cfg[$key] ?? [];

    if ($action === 'menu_bg_reorder') {
        $requested = json_decode((string)($_POST['order'] ?? ''), true);
        if (!is_array($requested)) {
            jsonResponse(['success'=>false,'message'=>'Format urutan tidak valid']);
        }
        $newOrder = [];
        foreach ($requested as $item) {
            $item = trim((string)$item);
            if ($item !== '' && in_array($item, $list, true) && !in_array($item, $newOrder, true)) {
                $newOrder[] = $item;
            }
        }
        foreach ($list as $existing) {
            if (!in_array($existing, $newOrder, true)) {
                $newOrder[] = $existing;
            }
        }
        $cfg[$key] = array_values($newOrder);
        db()->execute("UPDATE settings SET menu_bg_config=? WHERE id=?", [json_encode($cfg, JSON_UNESCAPED_SLASHES), $current['id']]);
        jsonResponse(['success'=>true,'message'=>'Urutan background menu berhasil disimpan']);
    }

    $pathToDelete = $_POST['path'] ?? '';
    if ($pathToDelete === '') {
        jsonResponse(['success'=>false,'message'=>'Path tidak valid']);
    }
    $newList = [];
    $found   = false;
    foreach ($list as $p) {
        if ($p === $pathToDelete && !$found) {
            $found = true;
            deleteUploadedFile($p);
        } else {
            $newList[] = $p;
        }
    }
    if (!$found) {
        jsonResponse(['success'=>false,'message'=>'Gambar tidak ditemukan']);
    }
    $cfg[$key] = array_values($newList);
    db()->execute("UPDATE settings SET menu_bg_config=? WHERE id=?", [json_encode($cfg, JSON_UNESCAPED_SLASHES), $current['id']]);
    jsonResponse(['success'=>true,'message'=>'Gambar background menu berhasil dihapus']);
}

if (empty($current['banner'])) {
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
