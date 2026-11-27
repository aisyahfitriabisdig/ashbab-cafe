<?php
/**
 * ASHBAB CAFE - AJAX: Get History
 * File: ajax/get_history.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['my_orders'])) {
    jsonResponse(['success' => false, 'message' => 'Tidak ada pesanan']);
}

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 5;
$limit = 5;

$placeholders = str_repeat('?,', count($_SESSION['my_orders']) - 1) . '?';
// Calculate total to check if has more
$sqlTotal = "SELECT COUNT(*) as total FROM orders WHERE order_number IN ($placeholders) AND order_status IN ('completed','cancelled')";
$total = db()->fetch($sqlTotal, $_SESSION['my_orders'])['total'];

// Fetch paginated
$sql = "SELECT * FROM orders WHERE order_number IN ($placeholders) AND order_status IN ('completed','cancelled') ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$orders = db()->fetchAll($sql, $_SESSION['my_orders']);

$data = [];
foreach ($orders as $order) {
    $order['total_formatted'] = formatRupiah($order['total_amount']);
    $order['date_formatted'] = date('d M Y H:i', strtotime($order['created_at']));
    $data[] = $order;
}

jsonResponse([
    'success' => true,
    'data' => $data,
    'has_more' => ($offset + $limit) < $total
]);
