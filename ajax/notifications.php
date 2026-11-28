<?php
/**
 * ASHBAB CAFE - AJAX: Notifications
 * File: ajax/notifications.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
session_write_close(); // Lepaskan lock sesi agar tidak memblokir tab lain (mencegah bug loading terus)

header('Content-Type: application/json');

// GET - fetch notifications & live real-time orders state for Cashier and Admin
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $notifications = db()->fetchAll(
        "SELECT n.*, o.order_number FROM notifications n
         LEFT JOIN orders o ON n.order_id = o.id
         WHERE n.target = 'admin'
         ORDER BY n.created_at DESC LIMIT 20"
    );

    foreach ($notifications as &$n) {
        $n['created_at'] = date('d M Y H:i', strtotime($n['created_at']));
    }
    unset($n);

    $unreadCount = (int)(db()->fetch("SELECT COUNT(*) c FROM notifications WHERE status='unread' AND target='admin'")['c'] ?? 0);

    $todayIncome = (float)(db()->fetch(
        "SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE order_status='completed' AND DATE(created_at)=CURRENT_DATE"
    )['c'] ?? 0);

    $orderCounts = [
        'waiting'                => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status='waiting'")['c'] ?? 0),
        'processing'             => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status IN ('accepted','processing')")['c'] ?? 0),
        'completed'              => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE order_status='completed' AND DATE(created_at)=CURRENT_DATE")['c'] ?? 0),
        'today_income_formatted' => formatRupiah($todayIncome),
    ];

    // Build fingerprint of latest orders state so client only re-renders DOM when data actually changes
    $filterStatus = clean($_GET['filter_status'] ?? '');
    $filterDate   = clean($_GET['filter_date']   ?? '');

    $allOrdersForHash = db()->fetchAll(
        "SELECT id, order_number, customer_name, phone, table_number, notes, total_amount, payment_method, payment_status, order_status, updated_at
         FROM orders ORDER BY id DESC LIMIT 100"
    );
    $ordersHash = md5(json_encode($allOrdersForHash) . '|' . $filterStatus . '|' . $filterDate);

    // 1. Cashier Dashboard Active Orders HTML
    $recentActive = db()->fetchAll(
        "SELECT * FROM orders WHERE order_status NOT IN ('completed','cancelled') ORDER BY created_at DESC LIMIT 10"
    );
    ob_start();
    if (empty($recentActive)): ?>
      <div class="text-center py-5 text-muted">
        <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
        <p>Tidak ada pesanan aktif saat ini</p>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($recentActive as $o): ?>
        <div class="col-md-6 col-lg-4">
          <div class="order-card">
            <div class="order-card-header">
              <div>
                <div class="order-number"><?= e($o['order_number']) ?></div>
                <div class="order-time"><?= formatDate($o['created_at'],'H:i - d M') ?></div>
              </div>
              <?= orderStatusBadge($o['order_status']) ?>
            </div>
            <div class="order-card-body">
              <div class="order-customer"><?= e($o['customer_name']) ?></div>
              <?php if ($o['table_number']): ?>
              <div class="order-table"><i class="fa-solid fa-chair me-1"></i>Meja <?= e($o['table_number']) ?></div>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <div><?= paymentStatusBadge($o['payment_status']) ?></div>
                <div class="order-total mb-0"><?= formatRupiah((float)$o['total_amount']) ?></div>
              </div>
            </div>
            <div class="order-card-footer flex-wrap gap-2">
              <?php if ($o['payment_status']==='pending'): ?>
              <button class="btn btn-success btn-sm rounded-pill flex-fill" onclick="confirmPayment(<?= $o['id'] ?>)">
                <i class="fa-solid fa-check me-1"></i>Konfirmasi Bayar
              </button>
              <?php endif; ?>
              <?php if ($o['order_status']==='waiting'): ?>
              <button class="btn btn-outline-success btn-sm rounded-pill flex-fill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'accepted')">
                <i class="fa-solid fa-thumbs-up me-1"></i>Terima
              </button>
              <?php elseif ($o['order_status']==='accepted'): ?>
              <button class="btn btn-outline-primary btn-sm rounded-pill flex-fill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'processing')">
                <i class="fa-solid fa-mug-hot me-1"></i>Proses
              </button>
              <?php elseif ($o['order_status']==='processing'): ?>
              <button class="btn btn-outline-success btn-sm rounded-pill flex-fill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'completed')">
                <i class="fa-solid fa-star me-1"></i>Selesai
              </button>
              <?php endif; ?>
              <?php if ($o['payment_status'] === 'confirmed'): ?>
              <a href="<?= APP_URL ?>/cashier/receipt.php?order_id=<?= $o['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill ms-auto">
                <i class="fa-solid fa-print"></i>
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif;
    $cashierDashboardHtml = ob_get_clean();

    // 2. Cashier Orders Page Grid HTML
    $cSql    = "SELECT * FROM orders WHERE 1=1";
    $cParams = [];
    if ($filterStatus !== '') {
        $cSql .= " AND order_status=?";
        $cParams[] = $filterStatus;
    }
    if ($filterDate !== '') {
        $cSql .= " AND DATE(created_at)=?";
        $cParams[] = $filterDate;
    }
    $cSql .= " ORDER BY created_at DESC";
    $filteredOrders = db()->fetchAll($cSql, $cParams);

    ob_start();
    if (empty($filteredOrders)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
      <p>Tidak ada pesanan<?= $filterStatus ? ' dengan status "' . e($filterStatus) . '"' : '' ?></p>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($filteredOrders as $o): ?>
      <div class="col-md-6 col-lg-4">
        <div class="order-card">
          <div class="order-card-header">
            <div>
              <div class="order-number"><?= e($o['order_number']) ?></div>
              <div class="order-time"><i class="fa-regular fa-clock me-1"></i><?= formatDate($o['created_at'],'H:i d M') ?></div>
            </div>
            <?= orderStatusBadge($o['order_status']) ?>
          </div>
          <div class="order-card-body">
            <div class="order-customer"><i class="fa-solid fa-user me-2" style="color:var(--primary)"></i><?= e($o['customer_name']) ?></div>
            <div class="order-table text-muted small mt-1">
              <i class="fa-solid fa-phone me-1"></i><?= e($o['phone']) ?>
              <?php if ($o['table_number']): ?>
               &nbsp;·&nbsp;<i class="fa-solid fa-chair me-1"></i>Meja <?= e($o['table_number']) ?>
              <?php endif; ?>
            </div>
            <?php if ($o['notes']): ?>
            <div class="mt-2 p-2 rounded-2 small" style="background:#fff8f0;border-left:3px solid var(--secondary);color:#222">
              <i class="fa-solid fa-note-sticky me-1" style="color:var(--secondary)"></i><?= e($o['notes']) ?>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
              <div>
                <div class="text-muted small">Pembayaran</div>
                <div><?= paymentStatusBadge($o['payment_status']) ?></div>
              </div>
              <div class="text-end">
                <div class="text-muted small"><?= strtoupper(e($o['payment_method'])) ?></div>
                <div class="order-total"><?= formatRupiah((float)$o['total_amount']) ?></div>
              </div>
            </div>
          </div>
          <div class="order-card-footer flex-wrap">
            <?php if ($o['payment_status']==='pending'): ?>
            <button class="btn btn-success btn-sm rounded-pill" onclick="confirmPayment(<?= $o['id'] ?>)">
              <i class="fa-solid fa-check me-1"></i>Konfirmasi Bayar
            </button>
            <?php endif; ?>
            <?php if ($o['order_status']==='waiting'): ?>
            <button class="btn btn-outline-success btn-sm rounded-pill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'accepted')">
              <i class="fa-solid fa-thumbs-up me-1"></i>Terima
            </button>
            <?php elseif ($o['order_status']==='accepted'): ?>
            <button class="btn btn-outline-primary btn-sm rounded-pill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'processing')">
              <i class="fa-solid fa-mug-hot me-1"></i>Proses
            </button>
            <?php elseif ($o['order_status']==='processing'): ?>
            <button class="btn btn-outline-success btn-sm rounded-pill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'completed')">
              <i class="fa-solid fa-star me-1"></i>Selesai
            </button>
            <?php endif; ?>
            <?php if (!in_array($o['order_status'],['completed','cancelled'], true)): ?>
            <button class="btn btn-outline-danger btn-sm rounded-pill" onclick="cashierUpdateStatus(<?= $o['id'] ?>,'cancelled')">
              <i class="fa-solid fa-ban me-1"></i>Batalkan
            </button>
            <?php endif; ?>
            <?php if (!empty($o['payment_proof'])): ?>
            <button class="btn btn-outline-secondary btn-sm rounded-pill"
              onclick="window.open('<?= e(assetUrl($o['payment_proof'])) ?>','_blank')">
              <i class="fa-solid fa-image me-1"></i>Bukti
            </button>
            <?php endif; ?>
            <?php if ($o['payment_status'] === 'confirmed'): ?>
            <a href="<?= APP_URL ?>/cashier/receipt.php?order_id=<?= $o['id'] ?>"
               class="btn btn-outline-dark btn-sm rounded-pill ms-auto">
              <i class="fa-solid fa-print me-1"></i>Struk
            </a>
            <?php else: ?>
            <div class="ms-auto text-muted small"><i class="fa-solid fa-circle-exclamation me-1"></i>Bayar dulu</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif;
    $cashierOrdersHtml = ob_get_clean();

    // 3. Admin Dashboard Recent Orders tbody HTML
    $adminRecent = array_slice($allOrdersForHash, 0, 8);
    ob_start();
    foreach ($adminRecent as $i => $o): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><span class="fw-700" style="color:var(--primary)"><?= e($o['order_number']) ?></span></td>
      <td><?= e($o['customer_name']) ?></td>
      <td class="fw-700"><?= formatRupiah((float)$o['total_amount']) ?></td>
      <td><?= strtoupper(e($o['payment_method'])) ?> <?= paymentStatusBadge($o['payment_status']) ?></td>
      <td><?= orderStatusBadge($o['order_status']) ?></td>
      <td class="text-muted small"><?= formatDate($o['updated_at'] ?? 'now','d M H:i') ?></td>
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
    <?php endforeach;
    if (empty($adminRecent)): ?>
    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada pesanan</td></tr>
    <?php endif;
    $adminRecentHtml = ob_get_clean();

    // 4. Admin Orders Page tbody HTML
    ob_start();
    foreach ($filteredOrders as $i => $o): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><span class="fw-700" style="color:var(--primary)"><?= e($o['order_number']) ?></span></td>
      <td>
        <div class="fw-600"><?= e($o['customer_name']) ?></div>
        <small class="text-muted"><?= e($o['phone']) ?></small>
      </td>
      <td class="fw-700"><?= formatRupiah((float)$o['total_amount']) ?></td>
      <td><?= strtoupper(e($o['payment_method'])) ?></td>
      <td><?= paymentStatusBadge($o['payment_status']) ?></td>
      <td><?= orderStatusBadge($o['order_status']) ?></td>
      <td class="text-muted small"><?= formatDate($o['created_at'],'d M Y H:i') ?></td>
      <td>
        <?php if ($o['payment_status'] === 'pending'): ?>
        <button class="btn btn-success btn-sm rounded-pill px-2 py-1 me-1" title="Konfirmasi Pembayaran QRIS"
          onclick="confirmPayment(<?= $o['id'] ?>)">
          <i class="fa-solid fa-check me-1"></i>Konfirmasi
        </button>
        <?php endif; ?>
        <button class="action-btn view me-1" title="Detail"
          onclick="viewOrder(<?= $o['id'] ?>)"><i class="fa-solid fa-eye"></i>
        </button>
      </td>
    </tr>
    <?php endforeach;
    $adminOrdersHtml = ob_get_clean();

    jsonResponse([
        'success'                => true,
        'notifications'          => $notifications,
        'unread_count'           => $unreadCount,
        'order_counts'           => $orderCounts,
        'orders_hash'            => $ordersHash,
        'cashier_dashboard_html' => $cashierDashboardHtml,
        'cashier_orders_html'    => $cashierOrdersHtml,
        'admin_recent_html'      => $adminRecentHtml,
        'admin_orders_html'      => $adminOrdersHtml,
        'admin_orders_count'     => count($filteredOrders),
    ]);
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
