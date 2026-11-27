<?php
/**
 * ASHBAB CAFE - Export Excel (CSV) Report
 * File: exports/export_excel.php
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
$totalProfit   = $totalIncome - $totalExpenses;

// Output as CSV (opens in Excel)
$filename = 'Laporan_' . $cafeName . '_' . $startDate . '_sd_' . $endDate . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM for Excel UTF-8
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// === Header Info ===
fputcsv($out, [$cafeName . ' - LAPORAN KEUANGAN']);
fputcsv($out, ['Periode: ' . indonesianDate($startDate) . ' s/d ' . indonesianDate($endDate)]);
fputcsv($out, ['Dicetak: ' . date('d M Y H:i') . ' oleh ' . currentUser()['name']]);
fputcsv($out, []);

// === Summary ===
fputcsv($out, ['RINGKASAN']);
fputcsv($out, ['Total Pesanan', count($orders)]);
fputcsv($out, ['Total Pemasukan', $totalIncome]);
fputcsv($out, ['Total Pengeluaran', $totalExpenses]);
fputcsv($out, ['Keuntungan Bersih', $totalProfit]);
fputcsv($out, []);

// === Orders ===
fputcsv($out, ['DATA PESANAN']);
fputcsv($out, ['No','Nomor Pesanan','Nama Pelanggan','No HP','No Meja','Total','Metode Bayar','Status Bayar','Status Pesanan','Tanggal']);
$statusOrderMap  = ['waiting'=>'Menunggu','accepted'=>'Diterima','processing'=>'Diproses','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
$statusPayMap    = ['pending'=>'Menunggu','confirmed'=>'Dikonfirmasi','rejected'=>'Ditolak'];

foreach ($orders as $i => $o) {
    fputcsv($out, [
        $i + 1,
        $o['order_number'],
        $o['customer_name'],
        $o['phone'],
        $o['table_number'] ?? '-',
        $o['total_amount'],
        strtoupper($o['payment_method']),
        $statusPayMap[$o['payment_status']] ?? $o['payment_status'],
        $statusOrderMap[$o['order_status']] ?? $o['order_status'],
        $o['created_at'],
    ]);
}
fputcsv($out, ['','','','','Total Pemasukan (Selesai)', $totalIncome]);
fputcsv($out, []);

// === Expenses ===
fputcsv($out, ['DATA PENGELUARAN']);
fputcsv($out, ['No','Nama Pengeluaran','Kategori','Nominal','Tanggal','Keterangan']);
foreach ($expenses as $i => $exp) {
    fputcsv($out, [
        $i + 1,
        $exp['name'],
        $exp['category'],
        $exp['amount'],
        $exp['expense_date'],
        $exp['description'] ?? '-',
    ]);
}
fputcsv($out, ['','','Total Pengeluaran', $totalExpenses]);
fputcsv($out, []);
fputcsv($out, ['KEUNTUNGAN BERSIH', '', '', $totalProfit]);

fclose($out);
