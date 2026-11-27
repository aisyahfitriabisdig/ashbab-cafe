<?php
/**
 * ASHBAB CAFE - AJAX: Users CRUD
 * File: ajax/users.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$action   = clean($_POST['action']   ?? '');
$id       = (int)($_POST['id']       ?? 0);
$name     = clean($_POST['name']     ?? '');
$username = clean($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role     = in_array($_POST['role']??'',['admin','cashier']) ? $_POST['role'] : 'cashier';

switch ($action) {
    case 'add':
        if (!$name || !$username || !$password) {
            jsonResponse(['success'=>false,'message'=>'Nama, username, dan password wajib diisi']);
        }
        if (strlen($password) < 6) {
            jsonResponse(['success'=>false,'message'=>'Password minimal 6 karakter']);
        }
        $exists = db()->fetch("SELECT id FROM users WHERE username=?", [$username]);
        if ($exists) { jsonResponse(['success'=>false,'message'=>'Username sudah digunakan']); }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        db()->insert("INSERT INTO users (name,username,password,role) VALUES (?,?,?,?)", [$name,$username,$hash,$role]);
        jsonResponse(['success'=>true,'message'=>"Pengguna '{$name}' berhasil ditambahkan"]);

    case 'edit':
        if (!$id || !$name || !$username) {
            jsonResponse(['success'=>false,'message'=>'Data tidak lengkap']);
        }
        $exists = db()->fetch("SELECT id FROM users WHERE username=? AND id!=?", [$username,$id]);
        if ($exists) { jsonResponse(['success'=>false,'message'=>'Username sudah digunakan pengguna lain']); }

        if ($password) {
            if (strlen($password) < 6) { jsonResponse(['success'=>false,'message'=>'Password minimal 6 karakter']); }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            db()->execute("UPDATE users SET name=?,username=?,password=?,role=? WHERE id=?", [$name,$username,$hash,$role,$id]);
        } else {
            db()->execute("UPDATE users SET name=?,username=?,role=? WHERE id=?", [$name,$username,$role,$id]);
        }
        jsonResponse(['success'=>true,'message'=>"Pengguna '{$name}' berhasil diperbarui"]);

    case 'delete':
        if (!$id) { jsonResponse(['success'=>false,'message'=>'ID tidak valid']); }
        if ($id === (int)(currentUser()['id'])) {
            jsonResponse(['success'=>false,'message'=>'Tidak bisa menghapus akun sendiri']);
        }
        $adminCount = db()->fetch("SELECT COUNT(*) c FROM users WHERE role='admin'")['c'] ?? 0;
        $targetUser = db()->fetch("SELECT role FROM users WHERE id=?", [$id]);
        if ($targetUser && $targetUser['role']==='admin' && $adminCount <= 1) {
            jsonResponse(['success'=>false,'message'=>'Tidak bisa menghapus satu-satunya admin']);
        }
        db()->execute("DELETE FROM users WHERE id=?", [$id]);
        jsonResponse(['success'=>true,'message'=>'Pengguna berhasil dihapus']);

    default:
        jsonResponse(['success'=>false,'message'=>'Aksi tidak dikenali']);
}
