<?php
/**
 * ASHBAB CAFE - AJAX: Customer Notifications
 * File: ajax/customer_notifications.php
 */
require_once __DIR__ . '/../includes/functions.php';
session_write_close();


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $myOrders = $_SESSION['my_orders'] ?? [];
    if (empty($myOrders)) {
        jsonResponse(['success'=>true, 'notifications'=>[]]);
    }
    
    $placeholders = implode(',', array_fill(0, count($myOrders), '?'));
    
    // Ambil notifikasi pelanggan yang masih unread untuk pesanan-pesanannya
    $sql = "SELECT n.*, o.order_number FROM notifications n 
            JOIN orders o ON n.order_id = o.id 
            WHERE n.target = 'customer' AND n.status = 'unread' 
            AND o.order_number IN ($placeholders)
            ORDER BY n.created_at ASC"; 
            
    $notifications = db()->fetchAll($sql, $myOrders);
    
    jsonResponse(['success'=>true, 'notifications'=>$notifications]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean($_POST['action'] ?? '');
    
    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        db()->execute("UPDATE notifications SET status='read' WHERE id=? AND target='customer'", [$id]);
        jsonResponse(['success'=>true]);
    }
    jsonResponse(['success'=>false]);
}
