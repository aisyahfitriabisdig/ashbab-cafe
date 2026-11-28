<?php
/**
 * ASHBAB CAFE - Admin Dashboard
 * File: admin/dashboard.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Dashboard Admin';
$stats     = getDashboardStats();

// Sales chart last 7 days
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
$dailySales = db()->fetchAll(
    "SELECT DATE(created_at) as date, SUM(total_amount) as total
     FROM orders WHERE order_status='completed' AND created_at >= ?
     GROUP BY DATE(created_at) ORDER BY date",
    [$sevenDaysAgo]
);

// Monthly sales current year
$yearStart     = date('Y-01-01');
$nextYearStart = date('Y-01-01', strtotime('+1 year'));
$monthlySales = db()->fetchAll(
    "SELECT EXTRACT(MONTH FROM created_at) as month, SUM(total_amount) as total
     FROM orders WHERE order_status='completed' AND created_at >= ? AND created_at < ?
     GROUP BY EXTRACT(MONTH FROM created_at)",
    [$yearStart, $nextYearStart]
);

// Top menus
$topMenus = db()->fetchAll(
    "SELECT m.name, SUM(oi.quantity) as total_qty
     FROM order_items oi JOIN menus m ON oi.menu_id=m.id
     GROUP BY oi.menu_id, m.name ORDER BY total_qty DESC LIMIT 6"
);

// Recent orders
$recentOrders = db()->fetchAll(
    "SELECT * FROM orders ORDER BY created_at DESC LIMIT 8"
);

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css?v=7">
</head>
<body class="admin-body">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="admin-content" id="adminContent">
  <!-- Topbar -->
  <div class="topbar">
    <button class="topbar-toggle" id="topbarToggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="topbar-title">Dashboard</h1>
    <div class="topbar-right">
      <button class="topbar-notif" id="notifBell" data-bs-toggle="dropdown">
        <i class="fa-solid fa-bell"></i>
        <span class="notif-badge" id="notifBadge" style="display:none">0</span>
      </button>
      <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0">
        <div class="notif-header">
          <span><i class="fa-solid fa-bell me-2"></i>Notifikasi</span>
          <button onclick="markAllRead()" class="btn btn-sm btn-outline-light py-0">Baca Semua</button>
        </div>
        <div id="notifList" style="max-height:320px;overflow-y:auto">
          <div class="text-center p-4 text-muted">Memuat...</div>
        </div>
      </div>
      <div class="topbar-user">
        <i class="fa-solid fa-user-circle fa-lg" style="color:var(--primary)"></i>
        <div>
          <div class="topbar-user-name"><?= e(currentUser()['name']) ?></div>
          <div class="topbar-user-role"><?= e(currentUser()['role']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="page-content">
    <!-- Stat Cards -->
    <div class="row g-4 mb-4">
      <?php
      $statCards = [
        ['label'=>'Total Pesanan',   'value'=>$stats['total_orders'],   'icon'=>'fa-receipt',         'type'=>'primary', 'prefix'=>'',     'suffix'=>''],
        ['label'=>'Total Menu',      'value'=>$stats['total_menus'],    'icon'=>'fa-utensils',        'type'=>'info',    'prefix'=>'',     'suffix'=>''],
        ['label'=>'Total Pemasukan', 'value'=>$stats['total_income'],   'icon'=>'fa-sack-dollar',     'type'=>'success', 'prefix'=>'Rp ', 'suffix'=>''],
        ['label'=>'Total Pengeluaran','value'=>$stats['total_expenses'],'icon'=>'fa-wallet',          'type'=>'danger',  'prefix'=>'Rp ', 'suffix'=>''],
        ['label'=>'Total Keuntungan','value'=>$stats['total_profit'],   'icon'=>'fa-coins',           'type'=>'warning', 'prefix'=>'Rp ', 'suffix'=>''],
      ];
      foreach ($statCards as $c): ?>
      <div class="col-sm-6 col-lg-4 col-xl">
        <div class="stat-widget <?= $c['type'] ?>">
          <div class="stat-icon <?= $c['type'] ?>"><i class="fa-solid <?= $c['icon'] ?>"></i></div>
          <div class="stat-value" data-counter="<?= $c['value'] ?>" data-prefix="<?= $c['prefix'] ?>"><?= $c['prefix'] . number_format($c['value'],0,',','.') ?></div>
          <div class="stat-label"><?= $c['label'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
      <!-- Daily Sales Chart -->
      <div class="col-lg-8">
        <div class="admin-card">
          <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-chart-line me-2" style="color:var(--primary)"></i>Penjualan 7 Hari Terakhir</h2>
          </div>
          <div class="chart-container" style="height:280px">
            <canvas id="dailyChart"></canvas>
          </div>
        </div>
      </div>
      <!-- Top Menus Doughnut -->
      <div class="col-lg-4">
        <div class="admin-card">
          <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-star me-2" style="color:var(--primary)"></i>Menu Terlaris</h2>
          </div>
          <div class="chart-container" style="height:280px">
            <canvas id="topMenuChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly Chart -->
    <div class="row g-4 mb-4">
      <div class="col-lg-12">
        <div class="admin-card">
          <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="fa-solid fa-chart-bar me-2" style="color:var(--primary)"></i>Penjualan Bulanan <?= date('Y') ?></h2>
          </div>
          <div class="chart-container" style="height:260px">
            <canvas id="monthlyChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fa-solid fa-receipt me-2" style="color:var(--primary)"></i>Pesanan Terbaru</h2>
        <a href="<?= APP_URL ?>/admin/orders.php" class="btn btn-sm btn-outline-secondary rounded-pill">Lihat Semua</a>
      </div>
      <div class="table-wrapper">
        <table class="table admin-table mb-0">
          <thead><tr><th>#</th><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Waktu</th><th>Aksi</th></tr></thead>
          <tbody id="adminRecentOrdersBody">
            <?php foreach ($recentOrders as $i => $o): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><span class="fw-700" style="color:var(--primary)"><?= e($o['order_number']) ?></span></td>
              <td><?= e($o['customer_name']) ?></td>
              <td class="fw-700"><?= formatRupiah($o['total_amount']) ?></td>
              <td><?= strtoupper(e($o['payment_method'])) ?> <?= paymentStatusBadge($o['payment_status']) ?></td>
              <td><?= orderStatusBadge($o['order_status']) ?></td>
              <td class="text-muted small"><?= formatDate($o['created_at'],'d M H:i') ?></td>
              <td>
                <?php if ($o['payment_status'] === 'pending'): ?>
                <button class="btn btn-success btn-sm rounded-pill px-2 py-1" onclick="confirmPayment(<?= $o['id'] ?>)">
                  <i class="fa-solid fa-check me-1"></i>Konfirmasi
                </button>
                <?php else: ?>
                <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada pesanan</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div><!-- page-content -->
</div><!-- admin-content -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';

// Daily Sales Chart
const dailyLabels = <?= json_encode(array_map(fn($r)=>date('d M',strtotime($r['date'])), $dailySales)) ?>;
const dailyData   = <?= json_encode(array_map(fn($r)=>(float)$r['total'], $dailySales)) ?>;
new Chart(document.getElementById('dailyChart'), {
  type:'line',
  data:{
    labels: dailyLabels,
    datasets:[{
      label:'Pemasukan (Rp)',
      data: dailyData,
      borderColor:'#8B4513', backgroundColor:'rgba(139,69,19,.08)',
      borderWidth:3, fill:true, tension:.4,
      pointBackgroundColor:'#8B4513', pointRadius:5
    }]
  },
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
    scales:{y:{ticks:{callback:v=>'Rp '+v.toLocaleString('id-ID')},grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}
});

// Monthly Sales Chart
const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
const monthlyRaw  = <?= json_encode($monthlySales) ?>;
const monthlyData = Array(12).fill(0);
monthlyRaw.forEach(r => { monthlyData[r.month-1] = parseFloat(r.total); });
new Chart(document.getElementById('monthlyChart'), {
  type:'bar',
  data:{
    labels: monthNames,
    datasets:[{
      label:'Pemasukan (Rp)',
      data: monthlyData,
      backgroundColor:'rgba(139,69,19,.75)', borderRadius:8
    }]
  },
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
    scales:{y:{ticks:{callback:v=>'Rp '+v.toLocaleString('id-ID')},grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}
});

// Top Menu Doughnut
const topMenuNames  = <?= json_encode(array_column($topMenus,'name')) ?>;
const topMenuQtys   = <?= json_encode(array_column($topMenus,'total_qty')) ?>;
new Chart(document.getElementById('topMenuChart'), {
  type:'doughnut',
  data:{
    labels: topMenuNames,
    datasets:[{
      data: topMenuQtys,
      backgroundColor:['#8B4513','#D2691E','#F5DEB3','#cd853f','#a0522d','#bc8a5f'],
      borderWidth:2, borderColor:'white'
    }]
  },
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}
});
</script>
<script src="<?= APP_URL ?>/assets/js/admin.js?v=<?= APP_VERSION ?>"></script>
