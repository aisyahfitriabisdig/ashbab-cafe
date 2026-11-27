<?php
/**
 * ASHBAB CAFE - AJAX: Remove from Cart
 * File: ajax/remove_cart.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$menuId = (int)($_POST['menu_id'] ?? 0);
unset($_SESSION['cart'][$menuId]);

$settings = getSettings();
$taxPct   = (float)($settings['tax_percent'] ?? 10);
$subtotal = 0;
foreach ($_SESSION['cart'] as $id => $item) {
    $m = db()->fetch("SELECT price FROM menus WHERE id=?", [$id]);
    if ($m) $subtotal += $m['price'] * $item['quantity'];
}
$tax   = $subtotal * ($taxPct / 100);
$total = $subtotal + $tax;
$cartCount = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));

jsonResponse(['success'=>true,'subtotal'=>$subtotal,'tax'=>$tax,'total'=>$total,'cart_count'=>$cartCount]);
