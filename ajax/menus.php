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
$price      = (float)($_POST['price']     ?? 0);
$stock      = (int)($_POST['stock']       ?? 0);
$description= clean($_POST['description'] ?? '');
$status     = in_array($_POST['status']??'', ['available','unavailable']) ? $_POST['status'] : 'available';

switch ($action) {
    case 'add':
        if (!$name || !$categoryId || $price <= 0) {
            jsonResponse(['success'=>false,'message'=>'Data tidak lengkap. Nama, kategori, dan harga wajib diisi.']);
        }
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $imagePath = uploadFile($_FILES['image'], 'menu');
        }
        db()->insert(
            "INSERT INTO menus (category_id,name,description,price,stock,image,status) VALUES (?,?,?,?,?,?,?)",
            [$categoryId,$name,$description,$price,$stock,$imagePath,$status]
        );
        jsonResponse(['success'=>true,'message'=>"Menu '{$name}' berhasil ditambahkan"]);

    case 'edit':
        if (!$id || !$name || !$categoryId || $price <= 0) {
            jsonResponse(['success'=>false,'message'=>'Data tidak lengkap']);
        }
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $imagePath = uploadFile($_FILES['image'], 'menu');
        }
        if ($imagePath) {
            db()->execute(
                "UPDATE menus SET category_id=?,name=?,description=?,price=?,stock=?,image=?,status=? WHERE id=?",
                [$categoryId,$name,$description,$price,$stock,$imagePath,$status,$id]
            );
        } else {
            db()->execute(
                "UPDATE menus SET category_id=?,name=?,description=?,price=?,stock=?,status=? WHERE id=?",
                [$categoryId,$name,$description,$price,$stock,$status,$id]
            );
        }
        jsonResponse(['success'=>true,'message'=>"Menu '{$name}' berhasil diperbarui"]);

    case 'delete':
        if (!$id) { jsonResponse(['success'=>false,'message'=>'ID tidak valid']); }
        $menu = db()->fetch("SELECT * FROM menus WHERE id=?",[$id]);
        if ($menu && $menu['image'] && file_exists(ROOT_PATH . $menu['image'])) {
            unlink(ROOT_PATH . $menu['image']);
        }
        db()->execute("DELETE FROM menus WHERE id=?", [$id]);
        jsonResponse(['success'=>true,'message'=>'Menu berhasil dihapus']);

    case 'toggle_status':
        if (!$id) { jsonResponse(['success'=>false,'message'=>'ID tidak valid']); }
        $newStatus = in_array($_POST['status']??'', ['available','unavailable']) ? $_POST['status'] : 'available';
        db()->execute("UPDATE menus SET status=? WHERE id=?", [$newStatus,$id]);
        jsonResponse(['success'=>true,'message'=>'Status menu diperbarui']);

    default:
        jsonResponse(['success'=>false,'message'=>'Aksi tidak dikenali']);
}
