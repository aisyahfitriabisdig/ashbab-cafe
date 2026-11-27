<?php
/**
 * ASHBAB CAFE - AJAX: Update Order Status
 * File: ajax/update_order_status.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$orderId = (int)($_POST['order_id'] ?? 0);
$order   = db()->fetch("SELECT * FROM orders WHERE id=?", [$orderId]);
if (!$order) { jsonResponse(['success'=>false,'message'=>'Pesanan tidak ditemukan']); }

// Payment action
if (!empty($_POST['payment_action'])) {
    $action = $_POST['payment_action'];
    if ($action === 'confirm') {
        db()->execute("UPDATE orders SET payment_status='confirmed' WHERE id=?", [$orderId]);
        // Auto-accept order
        if ($order['order_status'] === 'waiting') {
            db()->execute("UPDATE orders SET order_status='accepted' WHERE id=?", [$orderId]);
        }
        db()->insert(
            "INSERT INTO notifications (order_id, target, title, message) VALUES (?,?,?,?)",
            [$orderId, 'customer', "Pembayaran Dikonfirmasi ✅", "Pembayaran QRIS untuk pesanan #{$order['order_number']} telah dikonfirmasi."]
        );
        jsonResponse(['success'=>true,'message'=>'Pembayaran dikonfirmasi']);
    } elseif ($action === 'reject') {
        if ($order['order_status'] !== 'cancelled') {
            $items = db()->fetchAll("SELECT menu_id, quantity FROM order_items WHERE order_id=?", [$orderId]);
            foreach ($items as $item) {
                db()->execute("UPDATE menus SET stock = stock + ? WHERE id=?", [$item['quantity'], $item['menu_id']]);
            }
        }
        db()->execute("UPDATE orders SET payment_status='rejected', order_status='cancelled' WHERE id=?", [$orderId]);
        jsonResponse(['success'=>true,'message'=>'Pembayaran ditolak']);
    }
    jsonResponse(['success'=>false,'message'=>'Aksi tidak valid']);
}

// Order status update
$status = clean($_POST['status'] ?? '');
$validStatuses = ['waiting','accepted','processing','completed','cancelled'];
if (!in_array($status, $validStatuses)) { jsonResponse(['success'=>false,'message'=>'Status tidak valid']); }

if ($status === 'cancelled' && $order['order_status'] !== 'cancelled') {
    $items = db()->fetchAll("SELECT menu_id, quantity FROM order_items WHERE order_id=?", [$orderId]);
    foreach ($items as $item) {
        db()->execute("UPDATE menus SET stock = stock + ? WHERE id=?", [$item['quantity'], $item['menu_id']]);
    }
}
db()->execute("UPDATE orders SET order_status=? WHERE id=?", [$status, $orderId]);

// Add notification for status change
$labels = ['accepted'=>'Diterima','processing'=>'Sedang Diproses','completed'=>'Selesai ✅','cancelled'=>'Dibatalkan ❌'];
if (isset($labels[$status])) {
    db()->insert(
        "INSERT INTO notifications (order_id, target, title, message) VALUES (?,?,?,?)",
        [$orderId,'customer',"Update Pesanan #{$order['order_number']}","Status pesanan Anda: {$labels[$status]}"]
    );
}

jsonResponse(['success'=>true,'message'=>'Status pesanan diperbarui ke: '.$status]);
