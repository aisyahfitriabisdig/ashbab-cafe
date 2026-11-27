<?php
/**
 * ASHBAB CAFE - AJAX: Real-Time QRIS Payment Status Check & Pending Order Sync
 * File: ajax/check_payment.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Token tidak valid'], 403);
}
if (empty($_SESSION['cart'])) {
    jsonResponse(['success' => false, 'message' => 'Keranjang kosong']);
}

$rawName     = clean($_POST['customer_name'] ?? '');
$rawPhone    = clean($_POST['phone']         ?? '');
$tableNumber = clean($_POST['table_number']  ?? '');
$notes       = clean($_POST['notes']         ?? '');

$customerName = $rawName !== '' ? $rawName : 'Pelanggan QRIS';
$phone        = $rawPhone !== '' ? $rawPhone : '-';

// Calculate cart items and total
$settings  = getSettings();
$taxPct    = (float)($settings['tax_percent'] ?? 10);
$subtotal  = 0;
$cartItems = [];

foreach ($_SESSION['cart'] as $menuId => $item) {
    $menuId = (int)$menuId;
    $qty    = max(1, (int)($item['quantity'] ?? 1));
    $menu   = db()->fetch("SELECT * FROM menus WHERE id=? AND status='available'", [$menuId]);
    if (!$menu) continue;
    $sub       = (float)$menu['price'] * $qty;
    $subtotal += $sub;
    $cartItems[$menuId] = [
        'menu_id'  => $menuId,
        'menu'     => $menu,
        'quantity' => $qty,
        'price'    => (float)$menu['price'],
        'subtotal' => $sub,
    ];
}

if (empty($cartItems)) {
    jsonResponse(['success' => false, 'message' => 'Tidak ada menu yang tersedia di keranjang']);
}

$tax   = $subtotal * ($taxPct / 100);
$total = $subtotal + $tax;

$pendingId = isset($_SESSION['pending_order_id']) ? (int)$_SESSION['pending_order_id'] : 0;
$order     = null;

if ($pendingId > 0) {
    $order = db()->fetch("SELECT * FROM orders WHERE id=?", [$pendingId]);
    if (!$order || $order['order_status'] === 'cancelled' || $order['payment_status'] === 'rejected') {
        if ($order && ($order['payment_status'] === 'rejected' || $order['order_status'] === 'cancelled')) {
            unset($_SESSION['pending_order_id']);
            jsonResponse([
                'success'        => true,
                'payment_status' => 'rejected',
                'order_status'   => $order['order_status'],
                'order_number'   => $order['order_number'],
                'order_id'       => $order['id'],
            ]);
        }
        unset($_SESSION['pending_order_id']);
        $order = null;
    }
}

try {
    if (!$order) {
        // Verify stock before creating pending order
        foreach ($cartItems as $ci) {
            if ($ci['quantity'] > (int)$ci['menu']['stock']) {
                jsonResponse(['success' => false, 'message' => "Stok {$ci['menu']['name']} tidak mencukupi"]);
            }
        }

        $pdo = db()->getConnection();
        $pdo->beginTransaction();

        $orderNumber = generateOrderNumber();
        $orderId = db()->insert(
            "INSERT INTO orders (order_number,customer_name,phone,table_number,notes,total_amount,payment_method,payment_proof,payment_status,order_status)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$orderNumber, $customerName, $phone, $tableNumber ?: null, $notes ?: null, $total, 'qris', null, 'pending', 'waiting']
        );

        foreach ($cartItems as $ci) {
            db()->insert(
                "INSERT INTO order_items (order_id,menu_id,quantity,price,subtotal) VALUES (?,?,?,?,?)",
                [$orderId, $ci['menu_id'], $ci['quantity'], $ci['price'], $ci['subtotal']]
            );
            db()->execute(
                "UPDATE menus SET stock = stock - ? WHERE id=?",
                [$ci['quantity'], $ci['menu_id']]
            );
        }

        db()->insert(
            "INSERT INTO notifications (order_id,target,title,message) VALUES (?,?,?,?)",
            [
                $orderId,
                'admin',
                "Menunggu Konfirmasi QRIS 💳",
                "Tagihan #{$orderNumber} ({$customerName}) sebesar " . formatRupiah($total) . " menunggu verifikasi pembayaran QRIS."
            ]
        );

        $pdo->commit();

        $_SESSION['pending_order_id'] = $orderId;
        if (!isset($_SESSION['my_orders'])) {
            $_SESSION['my_orders'] = [];
        }
        if (!in_array($orderNumber, $_SESSION['my_orders'], true)) {
            $_SESSION['my_orders'][] = $orderNumber;
        }

        $order = db()->fetch("SELECT * FROM orders WHERE id=?", [$orderId]);
    } else {
        $orderId = (int)$order['id'];

        // Sync customer info if user typed/updated form fields
        $newName  = $rawName !== '' ? $rawName : $order['customer_name'];
        $newPhone = $rawPhone !== '' ? $rawPhone : $order['phone'];
        $newTable = $tableNumber !== '' ? $tableNumber : $order['table_number'];
        $newNotes = $notes !== '' ? $notes : $order['notes'];

        if (
            $newName !== $order['customer_name'] ||
            $newPhone !== $order['phone'] ||
            $newTable !== $order['table_number'] ||
            $newNotes !== $order['notes']
        ) {
            db()->execute(
                "UPDATE orders SET customer_name=?, phone=?, table_number=?, notes=? WHERE id=?",
                [$newName, $newPhone, $newTable ?: null, $newNotes ?: null, $orderId]
            );
            $order['customer_name'] = $newName;
            $order['phone']         = $newPhone;
            $order['table_number']  = $newTable;
            $order['notes']         = $newNotes;
        }

        // If still pending and cart items/total changed, sync items & total
        if ($order['payment_status'] === 'pending' && abs((float)$order['total_amount'] - $total) > 0.01) {
            $pdo = db()->getConnection();
            $pdo->beginTransaction();

            $oldItems = db()->fetchAll("SELECT menu_id, quantity FROM order_items WHERE order_id=?", [$orderId]);
            foreach ($oldItems as $oi) {
                db()->execute("UPDATE menus SET stock = stock + ? WHERE id=?", [$oi['quantity'], $oi['menu_id']]);
            }
            db()->execute("DELETE FROM order_items WHERE order_id=?", [$orderId]);

            foreach ($cartItems as $ci) {
                db()->insert(
                    "INSERT INTO order_items (order_id,menu_id,quantity,price,subtotal) VALUES (?,?,?,?,?)",
                    [$orderId, $ci['menu_id'], $ci['quantity'], $ci['price'], $ci['subtotal']]
                );
                db()->execute(
                    "UPDATE menus SET stock = stock - ? WHERE id=?",
                    [$ci['quantity'], $ci['menu_id']]
                );
            }

            db()->execute("UPDATE orders SET total_amount=? WHERE id=?", [$total, $orderId]);
            $pdo->commit();
            $order['total_amount'] = $total;
        }
    }

    jsonResponse([
        'success'        => true,
        'order_id'       => (int)$order['id'],
        'order_number'   => $order['order_number'],
        'payment_status' => $order['payment_status'],
        'order_status'   => $order['order_status'],
        'total_amount'   => (float)$order['total_amount'],
    ]);
} catch (Throwable $e) {
    if (db()->getConnection()->inTransaction()) {
        db()->getConnection()->rollBack();
    }
    jsonResponse(['success' => false, 'message' => 'Gagal memeriksa status pembayaran: ' . $e->getMessage()]);
}
