<?php
/**
 * ASHBAB CAFE - AJAX: Get Order Detail
 * File: ajax/get_order.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$id    = (int)($_GET['id'] ?? 0);
$order = db()->fetch("SELECT * FROM orders WHERE id=?", [$id]);
if (!$order) { jsonResponse(['success'=>false,'message'=>'Pesanan tidak ditemukan']); }

$order['created_at'] = formatDate($order['created_at'],'d M Y H:i');

$items = db()->fetchAll(
    "SELECT oi.*, m.name FROM order_items oi JOIN menus m ON oi.menu_id=m.id WHERE oi.order_id=?",
    [$id]
);

jsonResponse(['success'=>true,'order'=>$order,'items'=>$items]);
