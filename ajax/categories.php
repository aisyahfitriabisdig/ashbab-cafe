<?php
/**
 * ASHBAB CAFE - AJAX: Categories CRUD
 * File: ajax/categories.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$action = clean($_POST['action'] ?? '');
$name   = clean($_POST['name']   ?? '');
$icon   = clean($_POST['icon']   ?? 'fa-utensils');
$id     = (int)($_POST['id']     ?? 0);

switch ($action) {
    case 'add':
        if (!$name) { jsonResponse(['success'=>false,'message'=>'Nama kategori wajib diisi']); }
        $exists = db()->fetch("SELECT id FROM categories WHERE name=?", [$name]);
        if ($exists) { jsonResponse(['success'=>false,'message'=>'Kategori sudah ada']); }
        db()->insert("INSERT INTO categories (name,icon) VALUES (?,?)", [$name,$icon]);
        jsonResponse(['success'=>true,'message'=>"Kategori '{$name}' berhasil ditambahkan"]);

    case 'edit':
        if (!$id || !$name) { jsonResponse(['success'=>false,'message'=>'Data tidak lengkap']); }
        db()->execute("UPDATE categories SET name=?,icon=? WHERE id=?", [$name,$icon,$id]);
        jsonResponse(['success'=>true,'message'=>"Kategori berhasil diperbarui"]);

    case 'delete':
        if (!$id) { jsonResponse(['success'=>false,'message'=>'ID tidak valid']); }
        $menuCount = db()->fetch("SELECT COUNT(*) c FROM menus WHERE category_id=?",[$id])['c'] ?? 0;
        if ($menuCount > 0) {
            jsonResponse(['success'=>false,'message'=>"Kategori ini memiliki {$menuCount} menu. Hapus menu terlebih dahulu."]);
        }
        db()->execute("DELETE FROM categories WHERE id=?", [$id]);
        jsonResponse(['success'=>true,'message'=>'Kategori berhasil dihapus']);

    default:
        jsonResponse(['success'=>false,'message'=>'Aksi tidak dikenali']);
}
