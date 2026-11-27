<?php
/**
 * ASHBAB CAFE - AJAX: Update Cart
 * File: ajax/update_cart.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }

$action = clean($_POST['action'] ?? '');

// Save notes
if ($action === 'save_notes') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false]); }
    $_SESSION['order_notes'] = clean($_POST['notes'] ?? '');
    jsonResponse(['success'=>true]);
}

// Update quantity
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$menuId = (int)($_POST['menu_id'] ?? 0);
$qty    = (int)($_POST['quantity'] ?? 0);

if (!isset($_SESSION['cart'][$menuId])) { jsonResponse(['success'=>false,'message'=>'Item tidak ada di keranjang']); }

if ($qty <= 0) {
    unset($_SESSION['cart'][$menuId]);
} else {
    $menu = db()->fetch("SELECT * FROM menus WHERE id=?", [$menuId]);
    if ($menu && $qty > $menu['stock']) { jsonResponse(['success'=>false,'message'=>'Stok tidak mencukupi']); }
    $_SESSION['cart'][$menuId]['quantity'] = $qty;
}

// Recalculate
$settings = getSettings();
$taxPct   = (float)($settings['tax_percent'] ?? 10);
$items    = [];
$subtotal = 0;

foreach ($_SESSION['cart'] as $id => $item) {
    $m = db()->fetch("SELECT * FROM menus WHERE id=?", [$id]);
    if (!$m) continue;
    $sub      = $m['price'] * $item['quantity'];
    $subtotal += $sub;
    $items[]  = ['menu_id'=>$id,'quantity'=>$item['quantity'],'subtotal'=>$sub];
}

$tax   = $subtotal * ($taxPct / 100);
$total = $subtotal + $tax;
$cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));

jsonResponse(['success'=>true,'items'=>$items,'subtotal'=>$subtotal,'tax'=>$tax,'total'=>$total,'cart_count'=>$cartCount]);
