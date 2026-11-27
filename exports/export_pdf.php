<?php
/**
 * ASHBAB CAFE - Export PDF Report (HTML print version)
 * File: exports/export_pdf.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$startDate = clean($_GET['start'] ?? date('Y-m-01'));
$endDate   = clean($_GET['end']   ?? date('Y-m-d'));
$settings  = getSettings();
$cafeName  = $settings['cafe_name'] ?? 'ASHBAB CAFE';

$orders = db()->fetchAll(
    "SELECT * FROM orders WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC",
    [$startDate, $endDate]
);
$expenses = db()->fetchAll(
    "SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC",
    [$startDate, $endDate]
);

$totalIncome   = array_sum(array_column(array_filter($orders, fn($o) => $o['order_status']==='completed'), 'total_amount'));
$totalExpenses = array_sum(array_column($expenses, 'amount'));
$totalOrders   = count($orders);
$totalProfit   = $totalIncome - $totalExpenses;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan <?= e($cafeName) ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #333; background: white; }
  .container { max-width: 900px; margin: 0 auto; padding: 20px; }
  .header { text-align: center; border-bottom: 3px solid #8B4513; padding-bottom: 16px; margin-bottom: 20px; }
  .header h1 { font-size: 22px; color: #8B4513; margin-bottom: 4px; }
  .header p  { font-size: 12px; color: #666; }
  .summary   { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
  .sum-card  { flex: 1; min-width: 160px; border: 1px solid #ddd; border-radius: 8px; padding: 12px; text-align: center; }
  .sum-card .label { font-size: 11px; color: #666; margin-bottom: 4px; }
  .sum-card .value { font-size: 14px; font-weight: bold; }
  .sum-card.income  .value { color: #10b981; }
  .sum-card.expense .value { color: #ef4444; }
  .sum-card.profit  .value { color: #8B4513; }
  h2 { font-size: 14px; color: #8B4513; margin: 20px 0 10px; border-left: 4px solid #8B4513; padding-left: 8px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 11px; }
  th { background: #8B4513; color: white; padding: 8px 10px; text-align: left; }
  td { padding: 7px 10px; border-bottom: 1px solid #eee; }
  tr:nth-child(even) td { background: #fef9f5; }
  .badge { padding: 2px 8px; border-radius: 50px; font-size: 10px; font-weight: bold; }
  .badge-success  { background: #d1fae5; color: #065f46; }
  .badge-warning  { background: #fef3c7; color: #92400e; }
  .badge-danger   { background: #fee2e2; color: #991b1b; }
  .badge-primary  { background: #dbeafe; color: #1e40af; }
  .badge-info     { background: #e0f2fe; color: #075985; }
  .badge-secondary{ background: #f3f4f6; color: #374151; }
  .total-row td { font-weight: bold; background: #fef3c7 !important; }
  .footer { text-align: center; margin-top: 32px; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 12px; }
  .print-btn { position: fixed; top: 20px; right: 20px; background: #8B4513; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: bold; }
  @media print { .print-btn, .back-btn { display: none; } }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨️ Cetak PDF</button>

<div class="container">
  <div class="header">
    <h1>☕ <?= e($cafeName) ?></h1>
    <?php if (!empty($settings['address'])): ?><p><?= e($settings['address']) ?></p><?php endif; ?>
    <p style="margin-top:8px;font-size:13px;font-weight:bold;color:#555">
      LAPORAN KEUANGAN — <?= indonesianDate($startDate) ?> s/d <?= indonesianDate($endDate) ?>
    </p>
    <p style="font-size:11px;color:#999">Dicetak: <?= date('d M Y H:i') ?> oleh <?= e(currentUser()['name']) ?></p>
  </div>

  <!-- Summary -->
  <div class="summary">
    <div class="sum-card"><div class="label">Total Pesanan</div><div class="value"><?= $totalOrders ?></div></div>
    <div class="sum-card income"><div class="label">Total Pemasukan</div><div class="value"><?= formatRupiah($totalIncome) ?></div></div>
    <div class="sum-card expense"><div class="label">Total Pengeluaran</div><div class="value"><?= formatRupiah($totalExpenses) ?></div></div>
    <div class="sum-card profit"><div class="label">Keuntungan Bersih</div><div class="value"><?= formatRupiah($totalProfit) ?></div></div>
  </div>

  <!-- Orders -->
  <h2>Data Pesanan (<?= count($orders) ?>)</h2>
  <table>
    <thead><tr><th>#</th><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Metode</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $i => $o): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= e($o['order_number']) ?></td>
        <td><?= e($o['customer_name']) ?></td>
        <td><?= formatRupiah($o['total_amount']) ?></td>
        <td><?= strtoupper(e($o['payment_method'])) ?></td>
        <td>
          <?php $pMap=['confirmed'=>['success','Dikonfirmasi'],'rejected'=>['danger','Ditolak'],'pending'=>['warning','Menunggu']];
                $p=$pMap[$o['payment_status']]??['secondary',$o['payment_status']]; ?>
          <span class="badge badge-<?= $p[0] ?>"><?= $p[1] ?></span>
        </td>
        <td>
          <?php $sMap=['completed'=>['success','Selesai'],'cancelled'=>['danger','Dibatalkan'],'processing'=>['primary','Diproses'],'accepted'=>['info','Diterima'],'waiting'=>['warning','Menunggu']];
                $s=$sMap[$o['order_status']]??['secondary',$o['order_status']]; ?>
          <span class="badge badge-<?= $s[0] ?>"><?= $s[1] ?></span>
        </td>
        <td><?= formatDate($o['created_at'],'d/m/Y H:i') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?><tr><td colspan="8" style="text-align:center;color:#999">Tidak ada data</td></tr><?php endif; ?>
      <?php if (!empty($orders)): ?>
      <tr class="total-row">
        <td colspan="3">Total Pemasukan (Selesai)</td>
        <td><?= formatRupiah($totalIncome) ?></td>
        <td colspan="4"></td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Expenses -->
  <h2>Data Pengeluaran (<?= count($expenses) ?>)</h2>
  <table>
    <thead><tr><th>#</th><th>Nama</th><th>Kategori</th><th>Nominal</th><th>Tanggal</th><th>Keterangan</th></tr></thead>
    <tbody>
      <?php foreach ($expenses as $i => $exp): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= e($exp['name']) ?></td>
        <td><?= e($exp['category']) ?></td>
        <td><?= formatRupiah($exp['amount']) ?></td>
        <td><?= formatDate($exp['expense_date'],'d/m/Y') ?></td>
        <td><?= e($exp['description'] ?? '-') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($expenses)): ?><tr><td colspan="6" style="text-align:center;color:#999">Tidak ada data</td></tr><?php endif; ?>
      <?php if (!empty($expenses)): ?>
      <tr class="total-row">
        <td colspan="3">Total Pengeluaran</td>
        <td><?= formatRupiah($totalExpenses) ?></td>
        <td colspan="2"></td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Profit Summary -->
  <table>
    <thead><tr><th colspan="2">RINGKASAN KEUNTUNGAN</th></tr></thead>
    <tbody>
      <tr><td>Total Pemasukan</td><td style="color:#10b981;font-weight:bold"><?= formatRupiah($totalIncome) ?></td></tr>
      <tr><td>Total Pengeluaran</td><td style="color:#ef4444;font-weight:bold"><?= formatRupiah($totalExpenses) ?></td></tr>
      <tr class="total-row"><td>KEUNTUNGAN BERSIH</td><td style="color:<?= $totalProfit>=0?'#8B4513':'#ef4444' ?>"><?= formatRupiah($totalProfit) ?></td></tr>
    </tbody>
  </table>

  <div class="footer">
    Laporan ini dibuat otomatis oleh sistem <?= e($cafeName) ?> • <?= date('d M Y H:i') ?>
  </div>
</div>
<script>
  // Auto print on load if pdf param present
  if (new URLSearchParams(location.search).get('autoprint') === '1') window.print();
</script>
</body>
</html>
