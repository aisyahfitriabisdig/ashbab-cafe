<?php
/**
 * ASHBAB CAFE - AJAX: Menus CRUD
 * File: ajax/menus.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$action     = clean($_POST['action'] ?? '');
$id         = (int)($_POST['id']    ?? 0);
$name       = clean($_POST['name']        ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);

$rawPrice   = trim((string)($_POST['price'] ?? '0'));
$rawPrice   = preg_replace('/[^\d.,]/', '', $rawPrice);
if (preg_match('/^\d{1,3}(\.\d{3})+$/', $rawPrice)) {
    $rawPrice = str_replace('.', '', $rawPrice);
} elseif (preg_match('/^\d{1,3}(,\d{3})+$/', $rawPrice)) {
    $rawPrice = str_replace(',', '', $rawPrice);
} else {
    $rawPrice = str_replace(',', '.', $rawPrice);
}
$price      = (float)$rawPrice;
$stock      = max(0, (int)($_POST['stock'] ?? 0));
$description= clean($_POST['description'] ?? '');
$status     = in_array($_POST['status']??'', ['available','unavailable'], true) ? $_POST['status'] : 'available';

$hasImageUpload = isset($_FILES['image'])
    && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    && !empty($_FILES['image']['name']);

switch ($action) {
    case 'add':
        if (!$name || !$categoryId || $price <= 0) {
            jsonResponse(['success'=>false,'message'=>'Data tidak lengkap. Nama, kategori, dan harga wajib diisi.']);
        }
        $cat = db()->fetch("SELECT id FROM categories WHERE id=?", [$categoryId]);
        if (!$cat) {
            jsonResponse(['success'=>false,'message'=>'Kategori yang dipilih tidak ditemukan.']);
        }
        $imagePath = null;
        if ($hasImageUpload) {
            $imagePath = uploadFile($_FILES['image'], 'menu');
            if (!$imagePath) {
                $err = getLastUploadError() ?: 'Format/ukuran file tidak valid.';
                jsonResponse(['success'=>false,'message'=>"Gagal mengunggah foto menu: {$err}"]);
            }
        }
        $newId = db()->insert(
            "INSERT INTO menus (category_id,name,description,price,stock,image,status) VALUES (?,?,?,?,?,?,?)",
            [$categoryId,$name,$description,$price,$stock,$imagePath,$status]
        );
        jsonResponse([
            'success' => true,
            'message' => "Menu '{$name}' berhasil ditambahkan",
            'menu_id' => $newId,
        ]);

    case 'edit':
        if (!$id || !$name || !$categoryId || $price <= 0) {
            jsonResponse(['success'=>false,'message'=>'Data tidak lengkap. Nama, kategori, dan harga wajib diisi.']);
        }
        $existingMenu = db()->fetch("SELECT * FROM menus WHERE id=?", [$id]);
        if (!$existingMenu) {
            jsonResponse(['success'=>false,'message'=>'Menu tidak ditemukan di database.']);
        }
        $cat = db()->fetch("SELECT id FROM categories WHERE id=?", [$categoryId]);
        if (!$cat) {
            jsonResponse(['success'=>false,'message'=>'Kategori yang dipilih tidak ditemukan.']);
        }

        $finalImage = $existingMenu['image'] ?? null;
        $uploadedNewImage = false;

        if ($hasImageUpload) {
            $imagePath = uploadFile($_FILES['image'], 'menu');
            if (!$imagePath) {
                $err = getLastUploadError() ?: 'Format/ukuran file tidak valid.';
                jsonResponse(['success'=>false,'message'=>"Gagal mengunggah foto menu: {$err}"]);
            }
            $finalImage = $imagePath;
            $uploadedNewImage = true;
        }

        db()->execute(
            "UPDATE menus SET category_id=?,name=?,description=?,price=?,stock=?,image=?,status=? WHERE id=?",
            [$categoryId,$name,$description,$price,$stock,$finalImage,$status,$id]
        );

        if ($uploadedNewImage && !empty($existingMenu['image']) && $existingMenu['image'] !== $finalImage) {
            $sharedCount = (int)(db()->fetch(
                "SELECT COUNT(*) as c FROM menus WHERE image=? AND id<>?",
                [$existingMenu['image'], $id]
            )['c'] ?? 0);
            if ($sharedCount === 0) {
                deleteUploadedFile($existingMenu['image']);
            }
        }

        jsonResponse([
            'success'   => true,
            'message'   => "Menu '{$name}' berhasil diperbarui",
            'image_url' => assetUrl($finalImage),
        ]);

    case 'delete':
        if (!$id) { jsonResponse(['success'=>false,'message'=>'ID tidak valid']); }
        $menu = db()->fetch("SELECT * FROM menus WHERE id=?", [$id]);
        if (!$menu) {
            jsonResponse(['success'=>false,'message'=>'Menu tidak ditemukan']);
        }
        try {
            db()->execute("DELETE FROM menus WHERE id=?", [$id]);
        } catch (Throwable $e) {
            jsonResponse([
                'success' => false,
                'message' => 'Menu tidak dapat dihapus karena sudah tercatat dalam riwayat pesanan. Silakan ubah statusnya menjadi Tidak Tersedia.',
            ]);
        }
        if (!empty($menu['image'])) {
            $sharedCount = (int)(db()->fetch(
                "SELECT COUNT(*) as c FROM menus WHERE image=?",
                [$menu['image']]
            )['c'] ?? 0);
            if ($sharedCount === 0) {
                deleteUploadedFile($menu['image']);
            }
        }
        jsonResponse(['success'=>true,'message'=>'Menu berhasil dihapus']);

    case 'toggle_status':
        if (!$id) { jsonResponse(['success'=>false,'message'=>'ID tidak valid']); }
        $newStatus = in_array($_POST['status']??'', ['available','unavailable']) ? $_POST['status'] : 'available';
        db()->execute("UPDATE menus SET status=? WHERE id=?", [$newStatus,$id]);
        jsonResponse(['success'=>true,'message'=>'Status menu diperbarui']);

    default:
        jsonResponse(['success'=>false,'message'=>'Aksi tidak dikenali']);
}
