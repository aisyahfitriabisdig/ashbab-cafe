<?php
/**
 * ASHBAB CAFE - AJAX: Track Order
 * File: ajax/track_order.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$myOrders = $_SESSION['my_orders'] ?? [];
session_write_close();

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

$activeOrders = [];
if (!empty($myOrders)) {
    $placeholders = implode(',', array_fill(0, count($myOrders), '?'));
    $activeOrders = db()->fetchAll(
        "SELECT order_number, order_status, payment_status FROM orders
         WHERE order_number IN ($placeholders) AND order_status IN ('waiting','accepted','processing')
         ORDER BY created_at DESC",
        array_values($myOrders)
    );
}

jsonResponse([
    'success'       => true,
    'order'         => $order,
    'active_orders' => $activeOrders,
]);
