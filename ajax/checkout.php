<?php
/**
 * ASHBAB CAFE - AJAX: Checkout
 * File: ajax/checkout.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }
if (empty($_SESSION['cart'])) { jsonResponse(['success'=>false,'message'=>'Keranjang kosong']); }

$customerName   = clean($_POST['customer_name'] ?? '');
$phone          = clean($_POST['phone']         ?? '');
$tableNumber    = clean($_POST['table_number']  ?? '');
$notes          = clean($_POST['notes']         ?? '');
$paymentMethod  = clean($_POST['payment_method']?? '');

$paymentVerified= clean($_POST['payment_verified']?? '');

if (!$customerName) { jsonResponse(['success'=>false,'message'=>'Nama pelanggan wajib diisi']); }
if (!$phone)        { jsonResponse(['success'=>false,'message'=>'Nomor HP wajib diisi']); }
if ($paymentMethod !== 'qris') { jsonResponse(['success'=>false,'message'=>'Metode pembayaran tidak valid']); }
if ($paymentVerified !== '1')  { jsonResponse(['success'=>false,'message'=>'Selesaikan pembayaran terlebih dahulu']); }

$proofPath = null;

// Calculate total
$settings = getSettings();
$taxPct   = (float)($settings['tax_percent'] ?? 10);
$subtotal = 0;
$cartItems = [];

foreach ($_SESSION['cart'] as $menuId => $item) {
    $menu = db()->fetch("SELECT * FROM menus WHERE id=? AND status='available'", [$menuId]);
    if (!$menu) continue;
    if ($item['quantity'] > $menu['stock']) {
        jsonResponse(['success'=>false,'message'=>"Stok {$menu['name']} tidak mencukupi"]);
    }
    $sub = $menu['price'] * $item['quantity'];
    $subtotal += $sub;
    $cartItems[] = ['menu_id'=>$menuId,'menu'=>$menu,'quantity'=>$item['quantity'],'price'=>$menu['price'],'subtotal'=>$sub];
}

if (empty($cartItems)) { jsonResponse(['success'=>false,'message'=>'Tidak ada menu yang tersedia']); }

$tax   = $subtotal * ($taxPct / 100);
$total = $subtotal + $tax;

try {
    $db  = db()->getConnection();
    $db->beginTransaction();

    $orderNumber = generateOrderNumber();

    // Insert order
    $orderId = db()->insert(
        "INSERT INTO orders (order_number,customer_name,phone,table_number,notes,total_amount,payment_method,payment_proof,payment_status,order_status)
         VALUES (?,?,?,?,?,?,?,?,?,?)",
        [$orderNumber,$customerName,$phone,$tableNumber?:null,$notes?:null,$total,$paymentMethod,$proofPath,'confirmed','waiting']
    );

    // Insert items & update stock
    foreach ($cartItems as $ci) {
        db()->insert(
            "INSERT INTO order_items (order_id,menu_id,quantity,price,subtotal) VALUES (?,?,?,?,?)",
            [$orderId,$ci['menu_id'],$ci['quantity'],$ci['price'],$ci['subtotal']]
        );
        db()->execute(
            "UPDATE menus SET stock = stock - ? WHERE id=?",
            [$ci['quantity'],$ci['menu_id']]
        );
    }

    // Notification for cashier/admin
    db()->insert(
        "INSERT INTO notifications (order_id,target,title,message) VALUES (?,?,?,?)",
        [$orderId,'admin',"Pesanan Baru! 🔔","Pesanan #{$orderNumber} dari {$customerName} sebesar ".formatRupiah($total)." telah masuk."]
    );

    $db->commit();

    // Clear cart
    unset($_SESSION['cart'], $_SESSION['order_notes']);
    
    // Save to session tracking
    if (!isset($_SESSION['my_orders'])) {
        $_SESSION['my_orders'] = [];
    }
    if (!in_array($orderNumber, $_SESSION['my_orders'])) {
        $_SESSION['my_orders'][] = $orderNumber;
    }

    jsonResponse(['success'=>true,'message'=>'Pesanan berhasil dibuat!','order_number'=>$orderNumber,'order_id'=>$orderId]);

} catch (Exception $e) {
    db()->getConnection()->rollBack();
    jsonResponse(['success'=>false,'message'=>'Terjadi kesalahan: '.$e->getMessage()]);
}
