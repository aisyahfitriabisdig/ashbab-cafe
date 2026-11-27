<?php
/**
 * ASHBAB CAFE - Cashier Receipt
 * File: cashier/receipt.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('cashier', 'admin');

$orderId  = (int)($_GET['order_id'] ?? 0);
$format   = $_GET['format'] ?? 'browser';

if (!$orderId) redirect(APP_URL . '/cashier/orders.php');

$order = db()->fetch("SELECT * FROM orders WHERE id=?", [$orderId]);
if (!$order) redirect(APP_URL . '/cashier/orders.php');

$items    = db()->fetchAll(
    "SELECT oi.*, m.name FROM order_items oi JOIN menus m ON oi.menu_id=m.id WHERE oi.order_id=?",
    [$orderId]
);
$settings = getSettings();
$cafeName = $settings['cafe_name'] ?? 'ASHBAB CAFE';

// If PDF format, serve using simple HTML print
if ($format === 'pdf') {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= e($order['order_number']) ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Courier New', monospace; font-size:12px; background:#f5f5f5; }
  .receipt-wrap { max-width:320px; margin:20px auto; background:white; padding:20px; }
  .r-center { text-align:center; }
  .r-logo { font-size:2.5rem; margin-bottom:4px; }
  .r-name { font-size:16px; font-weight:bold; letter-spacing:2px; }
  .r-addr { font-size:11px; color:#555; margin-top:2px; }
  .r-dash { border-top:1px dashed #999; margin:10px 0; }
  .r-row  { display:flex; justify-content:space-between; padding:2px 0; }
  .r-item-name { font-weight:bold; margin-bottom:2px; }
  .r-item-detail { font-size:11px; color:#555; }
  .r-total-row { font-weight:bold; font-size:13px; }
  .r-footer { text-align:center; margin-top:8px; font-size:11px; color:#666; }
  .r-qr { text-align:center; margin:8px 0; font-size:2.5rem; }
  .btn-actions { display:flex; gap:8px; margin:20px auto; max-width:320px; }
  .btn-print { flex:1; padding:12px; background:#8B4513; color:white; border:none; border-radius:8px; font-size:14px; font-weight:bold; cursor:pointer; }
  .btn-back  { flex:1; padding:12px; background:#64748b; color:white; border:none; border-radius:8px; font-size:14px; font-weight:bold; cursor:pointer; text-align:center; text-decoration:none; display:flex; align-items:center; justify-content:center; }
  @media print {
    body { background:white; }
    .btn-actions { display:none; }
    .receipt-wrap { margin:0; padding:10px; box-shadow:none; }
  }
</style>
</head>
<body>

<!-- Print Buttons (hidden when printing) -->
<div class="btn-actions no-print">
  <a href="<?= APP_URL ?>/cashier/orders.php" class="btn-back">← Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
</div>

<!-- Receipt -->
<div class="receipt-wrap" id="receipt">
  <div class="r-center">
    <?php if (!empty($settings['logo'])): ?>
      <img src="<?= e(assetUrl($settings['logo'])) ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin-bottom:8px">
    <?php else: ?>
      <div class="r-logo">☕</div>
    <?php endif; ?>
    <div class="r-name"><?= e($cafeName) ?></div>
    <?php if (!empty($settings['address'])): ?>
    <div class="r-addr"><?= e($settings['address']) ?></div>
    <?php endif; ?>
    <?php if (!empty($settings['phone'])): ?>
    <div class="r-addr">Telp: <?= e($settings['phone']) ?></div>
    <?php endif; ?>
  </div>

  <div class="r-dash"></div>

  <div class="r-row"><span>No. Pesanan</span><span><?= e($order['order_number']) ?></span></div>
  <div class="r-row"><span>Tanggal</span><span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span></div>
  <div class="r-row"><span>Pelanggan</span><span><?= e($order['customer_name']) ?></span></div>
  <div class="r-row"><span>No. HP</span><span><?= e($order['phone']) ?></span></div>
  <?php if ($order['table_number']): ?>
  <div class="r-row"><span>No. Meja</span><span><?= e($order['table_number']) ?></span></div>
  <?php endif; ?>
  <div class="r-row"><span>Kasir</span><span><?= e(currentUser()['name']) ?></span></div>

  <div class="r-dash"></div>

  <div style="font-weight:bold;margin-bottom:6px">DETAIL PESANAN</div>
  <?php foreach ($items as $item): ?>
  <div style="margin-bottom:6px">
    <div class="r-item-name"><?= e($item['name']) ?></div>
    <div class="r-row r-item-detail">
      <span><?= $item['quantity'] ?> x Rp <?= number_format($item['price'],0,',','.') ?></span>
      <span>Rp <?= number_format($item['subtotal'],0,',','.') ?></span>
    </div>
  </div>
  <?php endforeach; ?>

  <?php
  $tax    = $order['total_amount'] - ($order['total_amount'] / (1 + (($settings['tax_percent']??10)/100)));
  $subtot = $order['total_amount'] - $tax;
  ?>
  <div class="r-dash"></div>
  <div class="r-row"><span>Subtotal</span><span>Rp <?= number_format($subtot,0,',','.') ?></span></div>
  <div class="r-row"><span>Pajak (<?= $settings['tax_percent']??10 ?>%)</span><span>Rp <?= number_format($tax,0,',','.') ?></span></div>
  <div class="r-dash"></div>
  <div class="r-row r-total-row">
    <span>TOTAL</span>
    <span>Rp <?= number_format($order['total_amount'],0,',','.') ?></span>
  </div>
  <div class="r-row" style="margin-top:4px">
    <span>Pembayaran</span>
    <span><?= strtoupper(e($order['payment_method'])) ?></span>
  </div>
  <div class="r-row">
    <span>Status</span>
    <span><?= $order['payment_status']==='confirmed'?'✓ LUNAS':'MENUNGGU' ?></span>
  </div>

  <?php if ($order['notes']): ?>
  <div class="r-dash"></div>
  <div>Catatan: <?= e($order['notes']) ?></div>
  <?php endif; ?>

  <div class="r-dash"></div>
  <div class="r-qr">📱</div>
  <div class="r-footer">
    <div>Scan QR untuk tracking pesanan:</div>
    <div><b><?= e($order['order_number']) ?></b></div>
  </div>
  <div class="r-dash"></div>
  <div class="r-footer">
    Terima kasih telah berkunjung ke<br>
    <b><?= e($cafeName) ?></b><br>
    Selamat menikmati hidangan Anda!
  </div>
</div>

</body>
</html>
