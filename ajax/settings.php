<?php
/**
 * ASHBAB CAFE - AJAX: Settings Save
 * File: ajax/settings.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$cafeName    = clean($_POST['cafe_name']    ?? '');
$address     = clean($_POST['address']      ?? '');
$phone       = clean($_POST['phone']        ?? '');
$email       = clean($_POST['email']        ?? '');
$bankAccount = clean($_POST['bank_account'] ?? '');
$footerText  = clean($_POST['footer_text']  ?? '');
$taxPercent  = (float)($_POST['tax_percent']?? 10);

if (!$cafeName) { jsonResponse(['success'=>false,'message'=>'Nama cafe wajib diisi']); }

$current = db()->fetch("SELECT * FROM settings LIMIT 1");

// Handle file uploads
$fields = [
    'logo'        => 'Logo',
    'favicon'     => 'Favicon',
    'qris_image'  => 'QRIS',
    'about_image' => 'Gambar Tentang Kami',
];
$faviconTypes = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'];
$paths = [];
foreach ($fields as $field => $label) {
    if (!empty($_FILES[$field]['name']) && !is_array($_FILES[$field]['name'])) {
        $folder = $field === 'qris_image' ? 'payments' : $field;
        $path   = $field === 'favicon'
            ? uploadFile($_FILES[$field], $folder, $faviconTypes)
            : uploadFile($_FILES[$field], $folder);
        if (!$path) {
            $err = getLastUploadError() ?: 'Periksa format dan ukuran file.';
            jsonResponse(['success' => false, 'message' => "Gagal mengunggah {$label}: {$err}"]);
        }
        $paths[$field] = $path;
        deleteUploadedFile($current[$field] ?? null);
    }
}

// Handle banners (ordering, replacing individual slides, and adding multiple new slides)
$existingBanners = [];
if (!empty($current['banner'])) {
    $decoded = json_decode($current['banner'], true);
    if (is_array($decoded)) {
        $existingBanners = array_values(array_filter($decoded));
    } elseif (is_string($current['banner'])) {
        $existingBanners = [$current['banner']];
    }
}

// Start from user-submitted order if provided (only allowing known existing banner paths)
if (isset($_POST['banner_order']) && is_array($_POST['banner_order'])) {
    $submittedOrder = [];
    foreach ($_POST['banner_order'] as $bPath) {
        $bPath = trim((string)$bPath);
        if ($bPath !== '' && in_array($bPath, $existingBanners, true)) {
            $submittedOrder[] = $bPath;
        }
    }
    $bannerPaths = $submittedOrder;
} else {
    $bannerPaths = $existingBanners;
}

// Handle replacing existing individual banner slides (banner_replace[])
if (!empty($_FILES['banner_replace']['name']) && is_array($_FILES['banner_replace']['name'])) {
    foreach ($_FILES['banner_replace']['name'] as $idx => $repName) {
        if (($repName ?? '') !== '' && isset($bannerPaths[$idx])) {
            $fileArray = [
                'name'     => $_FILES['banner_replace']['name'][$idx],
                'type'     => $_FILES['banner_replace']['type'][$idx],
                'tmp_name' => $_FILES['banner_replace']['tmp_name'][$idx],
                'error'    => $_FILES['banner_replace']['error'][$idx],
                'size'     => $_FILES['banner_replace']['size'][$idx],
            ];
            $newSlidePath = uploadFile($fileArray, 'banner');
            if (!$newSlidePath) {
                $err = getLastUploadError() ?: 'Periksa format dan ukuran file.';
                jsonResponse(['success' => false, 'message' => "Gagal mengganti gambar Hero #" . ($idx + 1) . ": {$err}"]);
            }
            $oldSlidePath = $bannerPaths[$idx];
            $bannerPaths[$idx] = $newSlidePath;
            if ($oldSlidePath !== $newSlidePath && !in_array($oldSlidePath, $bannerPaths, true)) {
                deleteUploadedFile($oldSlidePath);
            }
        }
    }
}

// Handle newly added banners (banner[])
if (!empty($_FILES['banner']['name']) && is_array($_FILES['banner']['name'])) {
    $count = count($_FILES['banner']['name']);
    for ($i = 0; $i < $count; $i++) {
        if (($_FILES['banner']['name'][$i] ?? '') !== '') {
            $fileArray = [
                'name'     => $_FILES['banner']['name'][$i],
                'type'     => $_FILES['banner']['type'][$i],
                'tmp_name' => $_FILES['banner']['tmp_name'][$i],
                'error'    => $_FILES['banner']['error'][$i],
                'size'     => $_FILES['banner']['size'][$i]
            ];
            $path = uploadFile($fileArray, 'banner');
            if (!$path) {
                $err = getLastUploadError() ?: 'Periksa format dan ukuran file.';
                jsonResponse(['success' => false, 'message' => "Gagal mengunggah Banner: {$err}"]);
            }
            $bannerPaths[] = $path;
        }
    }
}
$paths['banner'] = !empty($bannerPaths) ? json_encode(array_values($bannerPaths), JSON_UNESCAPED_SLASHES) : null;

$logoPath    = $paths['logo']       ?? ($current['logo']       ?? null);
$faviconPath = $paths['favicon']    ?? ($current['favicon']    ?? null);
$bannerPath  = $paths['banner'];
$qrisPath    = $paths['qris_image'] ?? ($current['qris_image'] ?? null);
$aboutImg    = $paths['about_image']?? ($current['about_image']?? null);

// Handle Menu Section Background Configuration & Images
ensureMenuBgColumn();
$currentMenuBg = getMenuBgConfig();

$allowedPositions   = ['center center', 'top center', 'bottom center', 'left center', 'right center'];
$allowedSizes       = ['cover', 'contain'];
$allowedAttachments = ['scroll', 'fixed'];

$menuBgEnabled    = !empty($_POST['menu_bg_enabled']);
$menuBgMode       = ($_POST['menu_bg_mode'] ?? 'same') === 'separate' ? 'separate' : 'same';
$menuBgPosition   = in_array($_POST['menu_bg_position'] ?? '', $allowedPositions, true) ? $_POST['menu_bg_position'] : 'center center';
$menuBgSize       = in_array($_POST['menu_bg_size'] ?? '', $allowedSizes, true) ? $_POST['menu_bg_size'] : 'cover';
$menuBgAttachment = in_array($_POST['menu_bg_attachment'] ?? '', $allowedAttachments, true) ? $_POST['menu_bg_attachment'] : 'scroll';
$menuBgOpacity    = isset($_POST['menu_bg_opacity']) ? max(20, min(95, (int)$_POST['menu_bg_opacity'])) : 75;

$bucketKeys = ['shared', 'home', 'menu'];
$finalBuckets = [];

foreach ($bucketKeys as $bKey) {
    $existingList = $currentMenuBg['images_' . $bKey] ?? [];
    $orderField   = 'menu_bg_' . $bKey . '_order';
    $replaceField = 'menu_bg_' . $bKey . '_replace';
    $addField     = 'menu_bg_' . $bKey;

    if (isset($_POST[$orderField]) && is_array($_POST[$orderField])) {
        $list = [];
        foreach ($_POST[$orderField] as $p) {
            $p = trim((string)$p);
            if ($p !== '' && in_array($p, $existingList, true)) {
                $list[] = $p;
            }
        }
    } else {
        $list = $existingList;
    }

    // Handle individual replacements in this bucket
    if (!empty($_FILES[$replaceField]['name']) && is_array($_FILES[$replaceField]['name'])) {
        foreach ($_FILES[$replaceField]['name'] as $idx => $repName) {
            if (($repName ?? '') !== '' && isset($list[$idx])) {
                $fileArray = [
                    'name'     => $_FILES[$replaceField]['name'][$idx],
                    'type'     => $_FILES[$replaceField]['type'][$idx],
                    'tmp_name' => $_FILES[$replaceField]['tmp_name'][$idx],
                    'error'    => $_FILES[$replaceField]['error'][$idx],
                    'size'     => $_FILES[$replaceField]['size'][$idx],
                ];
                $newBgPath = uploadFile($fileArray, 'menu_bg');
                if (!$newBgPath) {
                    $err = getLastUploadError() ?: 'Periksa format dan ukuran file.';
                    jsonResponse(['success' => false, 'message' => "Gagal mengganti gambar background menu: {$err}"]);
                }
                $oldBgPath = $list[$idx];
                $list[$idx] = $newBgPath;
                if ($oldBgPath !== $newBgPath && !in_array($oldBgPath, $list, true)) {
                    deleteUploadedFile($oldBgPath);
                }
            }
        }
    }

    // Handle newly added images in this bucket
    if (!empty($_FILES[$addField]['name']) && is_array($_FILES[$addField]['name'])) {
        $cnt = count($_FILES[$addField]['name']);
        for ($i = 0; $i < $cnt; $i++) {
            if (($_FILES[$addField]['name'][$i] ?? '') !== '') {
                $fileArray = [
                    'name'     => $_FILES[$addField]['name'][$i],
                    'type'     => $_FILES[$addField]['type'][$i],
                    'tmp_name' => $_FILES[$addField]['tmp_name'][$i],
                    'error'    => $_FILES[$addField]['error'][$i],
                    'size'     => $_FILES[$addField]['size'][$i],
                ];
                $newBgPath = uploadFile($fileArray, 'menu_bg');
                if (!$newBgPath) {
                    $err = getLastUploadError() ?: 'Periksa format dan ukuran file.';
                    jsonResponse(['success' => false, 'message' => "Gagal mengunggah gambar background menu: {$err}"]);
                }
                $list[] = $newBgPath;
            }
        }
    }

    $finalBuckets['images_' . $bKey] = array_values($list);
}

$hoursWeekdayLabel = clean($_POST['hours_weekday_label'] ?? '');
$hoursWeekdayTime  = clean($_POST['hours_weekday_time']  ?? '');
$hoursWeekendLabel = clean($_POST['hours_weekend_label'] ?? '');
$hoursWeekendTime  = clean($_POST['hours_weekend_time']  ?? '');
$hoursBadgeText    = clean($_POST['hours_badge_text']    ?? '');

$gscRaw = trim((string)($_POST['google_verification'] ?? ''));
if (preg_match('/content=["\']([^"\']+)["\']/i', $gscRaw, $m)) {
    $gscRaw = trim($m[1]);
}
$gscVerification = clean($gscRaw);
$seoDescription  = clean($_POST['seo_description'] ?? '');
$seoKeywords     = clean($_POST['seo_keywords']    ?? '');
$seoSingleResult = !empty($_POST['seo_single_result']);

$menuBgConfigJson = json_encode([
    'enabled'             => $menuBgEnabled,
    'mode'                => $menuBgMode,
    'position'            => $menuBgPosition,
    'size'                => $menuBgSize,
    'attachment'          => $menuBgAttachment,
    'overlay_opacity'     => $menuBgOpacity,
    'images_shared'       => $finalBuckets['images_shared'],
    'images_home'         => $finalBuckets['images_home'],
    'images_menu'         => $finalBuckets['images_menu'],
    'hours_weekday_label' => $hoursWeekdayLabel !== '' ? $hoursWeekdayLabel : 'Senin – Jumat',
    'hours_weekday_time'  => $hoursWeekdayTime  !== '' ? $hoursWeekdayTime  : '08.00 – 22.00',
    'hours_weekend_label' => $hoursWeekendLabel !== '' ? $hoursWeekendLabel : 'Sabtu – Minggu',
    'hours_weekend_time'  => $hoursWeekendTime  !== '' ? $hoursWeekendTime  : '08.00 – 23.00',
    'hours_badge_text'    => $hoursBadgeText,
    'google_verification' => $gscVerification,
    'seo_description'     => $seoDescription !== '' ? $seoDescription : ($currentMenuBg['seo_description'] ?? ''),
    'seo_keywords'        => $seoKeywords    !== '' ? $seoKeywords    : ($currentMenuBg['seo_keywords'] ?? ''),
    'seo_single_result'   => $seoSingleResult,
], JSON_UNESCAPED_SLASHES);

if ($current) {
    db()->execute(
        "UPDATE settings SET cafe_name=?,address=?,phone=?,email=?,bank_account=?,footer_text=?,tax_percent=?,logo=?,favicon=?,banner=?,qris_image=?,about_image=?,menu_bg_config=? WHERE id=?",
        [$cafeName,$address,$phone,$email,$bankAccount,$footerText,$taxPercent,$logoPath,$faviconPath,$bannerPath,$qrisPath,$aboutImg,$menuBgConfigJson,$current['id']]
    );
} else {
    db()->insert(
        "INSERT INTO settings (cafe_name,address,phone,email,bank_account,footer_text,tax_percent,logo,favicon,banner,qris_image,about_image,menu_bg_config) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [$cafeName,$address,$phone,$email,$bankAccount,$footerText,$taxPercent,$logoPath,$faviconPath,$bannerPath,$qrisPath,$aboutImg,$menuBgConfigJson]
    );
}

jsonResponse([
    'success'  => true,
    'message'  => 'Pengaturan berhasil disimpan!',
    'qris_url' => assetUrl($qrisPath),
]);
