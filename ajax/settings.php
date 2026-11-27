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
$fields = ['logo','favicon','qris_image','about_image'];
$paths  = [];
foreach ($fields as $field) {
    if (!empty($_FILES[$field]['name']) && !is_array($_FILES[$field]['name'])) {
        $folder = $field === 'qris_image' ? 'payments' : $field;
        $path   = uploadFile($_FILES[$field], $folder);
        if ($path) {
            $paths[$field] = $path;
            // Delete old file
            if (!empty($current[$field]) && file_exists(ROOT_PATH . $current[$field])) {
                @unlink(ROOT_PATH . $current[$field]);
            }
        }
    }
}

// Handle banners (multiple)
$bannerPaths = [];
if (!empty($current['banner'])) {
    $decoded = json_decode($current['banner'], true);
    if (is_array($decoded)) {
        $bannerPaths = $decoded;
    } else if (is_string($current['banner'])) {
        $bannerPaths = [$current['banner']];
    }
}

if (!empty($_FILES['banner']['name']) && is_array($_FILES['banner']['name'])) {
    $count = count($_FILES['banner']['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES['banner']['error'][$i] === UPLOAD_ERR_OK) {
            $fileArray = [
                'name'     => $_FILES['banner']['name'][$i],
                'type'     => $_FILES['banner']['type'][$i],
                'tmp_name' => $_FILES['banner']['tmp_name'][$i],
                'error'    => $_FILES['banner']['error'][$i],
                'size'     => $_FILES['banner']['size'][$i]
            ];
            $path = uploadFile($fileArray, 'banner');
            if ($path) {
                $bannerPaths[] = $path;
            }
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

jsonResponse(['success'=>true,'message'=>'Pengaturan berhasil disimpan!']);
