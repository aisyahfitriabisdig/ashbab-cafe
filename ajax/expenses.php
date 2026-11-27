<?php
/**
 * ASHBAB CAFE - AJAX: Expenses CRUD
 * File: ajax/expenses.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$action      = clean($_POST['action']       ?? '');
$id          = (int)($_POST['id']           ?? 0);
$name        = clean($_POST['name']         ?? '');
$category    = clean($_POST['category']     ?? '');
$amount      = (float)($_POST['amount']     ?? 0);
$description = clean($_POST['description']  ?? '');
$expenseDate = clean($_POST['expense_date'] ?? date('Y-m-d'));

switch ($action) {
    case 'add':
        if (!$name || !$category || $amount <= 0 || !$expenseDate) {
            jsonResponse(['success'=>false,'message'=>'Data tidak lengkap']);
        }
        db()->insert(
            "INSERT INTO expenses (name,category,amount,description,expense_date) VALUES (?,?,?,?,?)",
            [$name,$category,$amount,$description?:null,$expenseDate]
        );
        jsonResponse(['success'=>true,'message'=>"Pengeluaran '{$name}' berhasil ditambahkan"]);

    case 'edit':
        if (!$id || !$name || $amount <= 0) {
            jsonResponse(['success'=>false,'message'=>'Data tidak lengkap']);
        }
        db()->execute(
            "UPDATE expenses SET name=?,category=?,amount=?,description=?,expense_date=? WHERE id=?",
            [$name,$category,$amount,$description?:null,$expenseDate,$id]
        );
        jsonResponse(['success'=>true,'message'=>'Pengeluaran berhasil diperbarui']);

    case 'delete':
        if (!$id) { jsonResponse(['success'=>false,'message'=>'ID tidak valid']); }
        db()->execute("DELETE FROM expenses WHERE id=?", [$id]);
        jsonResponse(['success'=>true,'message'=>'Pengeluaran berhasil dihapus']);

    default:
        jsonResponse(['success'=>false,'message'=>'Aksi tidak dikenali']);
}
