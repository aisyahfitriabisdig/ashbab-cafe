<?php
/**
 * ASHBAB CAFE - AJAX: Get Single Menu
 * File: ajax/get_menu.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$id   = (int)($_GET['id'] ?? 0);
$menu = db()->fetch(
    "SELECT m.*, c.name as category_name FROM menus m JOIN categories c ON m.category_id=c.id WHERE m.id=?",
    [$id]
);

if (!$menu) { jsonResponse(['success'=>false,'message'=>'Menu tidak ditemukan']); }
jsonResponse(['success'=>true,'menu'=>$menu]);
