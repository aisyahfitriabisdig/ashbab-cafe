<?php
/**
 * ASHBAB CAFE - AJAX: Notifications
 * File: ajax/notifications.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
session_write_close(); // Lepaskan lock sesi agar tidak memblokir tab lain (mencegah bug loading terus)

header('Content-Type: application/json');

// GET - fetch notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $notifications = db()->fetchAll(
        "SELECT n.*, o.order_number FROM notifications n
         LEFT JOIN orders o ON n.order_id = o.id
         WHERE n.target = 'admin'
         ORDER BY n.created_at DESC LIMIT 20"
    );

    // Format timestamps
    foreach ($notifications as &$n) {
        $n['created_at'] = date('d M Y H:i', strtotime($n['created_at']));
    }

    $unreadCount = db()->fetch("SELECT COUNT(*) c FROM notifications WHERE status='unread' AND target='admin'")['c'] ?? 0;

    // Order counts for cashier dashboard
    $orderCounts = [
        'waiting'    => db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status='waiting'")['c'] ?? 0,
        'processing' => db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status IN ('accepted','processing')")['c'] ?? 0,
        'completed'  => db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status='completed' AND DATE(created_at)=CURRENT_DATE")['c'] ?? 0,
    ];

    jsonResponse(['success'=>true,'notifications'=>$notifications,'unread_count'=>$unreadCount,'order_counts'=>$orderCounts]);
}

// POST - mark read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

    $action = clean($_POST['action'] ?? '');

    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        db()->execute("UPDATE notifications SET status='read' WHERE id=?", [$id]);
        jsonResponse(['success'=>true]);
    }

    if ($action === 'mark_all_read') {
        db()->execute("UPDATE notifications SET status='read' WHERE target='admin'");
        jsonResponse(['success'=>true]);
    }

    jsonResponse(['success'=>false,'message'=>'Aksi tidak dikenali']);
}
