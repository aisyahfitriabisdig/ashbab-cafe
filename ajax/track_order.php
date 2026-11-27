<?php
/**
 * ASHBAB CAFE - AJAX: Track Order
 * File: ajax/track_order.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$orderNumber = clean($_GET['order_number'] ?? '');
if (!$orderNumber) { jsonResponse(['success'=>false,'message'=>'Nomor pesanan kosong']); }

$order = db()->fetch("SELECT * FROM orders WHERE order_number=?", [$orderNumber]);
if (!$order) { jsonResponse(['success'=>false,'message'=>'Pesanan tidak ditemukan. Periksa kembali nomor pesanan Anda.']); }

$items = db()->fetchAll(
    "SELECT oi.*, m.name FROM order_items oi JOIN menus m ON oi.menu_id=m.id WHERE oi.order_id=?",
    [$order['id']]
);

$order['created_at'] = formatDate($order['created_at'],'d M Y H:i');
$order['items']      = $items;

jsonResponse(['success'=>true,'order'=>$order]);
