<?php
/**
 * ASHBAB CAFE - AJAX: Add to Cart
 * File: ajax/add_cart.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false,'message'=>'Method not allowed'],405); }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { jsonResponse(['success'=>false,'message'=>'Token tidak valid'],403); }

$menuId = (int)($_POST['menu_id'] ?? 0);
if (!$menuId) { jsonResponse(['success'=>false,'message'=>'Menu tidak valid']); }

$menu = db()->fetch("SELECT * FROM menus WHERE id=? AND status='available'", [$menuId]);
if (!$menu) { jsonResponse(['success'=>false,'message'=>'Menu tidak tersedia']); }
if ($menu['stock'] <= 0) { jsonResponse(['success'=>false,'message'=>'Stok habis']); }

// Add or increment
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$cur = $_SESSION['cart'][$menuId]['quantity'] ?? 0;
if ($cur + 1 > $menu['stock']) {
    jsonResponse(['success'=>false,'message'=>'Stok tidak mencukupi (stok: '.$menu['stock'].')']);
}
$_SESSION['cart'][$menuId] = ['quantity' => $cur + 1, 'price' => $menu['price']];

$cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
jsonResponse(['success'=>true,'message'=>$menu['name'].' ditambahkan ke keranjang!','cart_count'=>$cartCount]);
