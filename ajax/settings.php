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

if ($current) {
    db()->execute(
        "UPDATE settings SET cafe_name=?,address=?,phone=?,email=?,bank_account=?,footer_text=?,tax_percent=?,logo=?,favicon=?,banner=?,qris_image=?,about_image=? WHERE id=?",
        [$cafeName,$address,$phone,$email,$bankAccount,$footerText,$taxPercent,$logoPath,$faviconPath,$bannerPath,$qrisPath,$aboutImg,$current['id']]
    );
} else {
    db()->insert(
        "INSERT INTO settings (cafe_name,address,phone,email,bank_account,footer_text,tax_percent,logo,favicon,banner,qris_image,about_image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
        [$cafeName,$address,$phone,$email,$bankAccount,$footerText,$taxPercent,$logoPath,$faviconPath,$bannerPath,$qrisPath,$aboutImg]
    );
}

jsonResponse([
    'success'  => true,
    'message'  => 'Pengaturan berhasil disimpan!',
    'qris_url' => assetUrl($qrisPath),
]);
