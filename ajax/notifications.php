<?php
/**
 * ASHBAB CAFE - AJAX: Notifications
 * File: ajax/notifications.php
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
session_write_close(); // Lepaskan lock sesi agar tidak memblokir tab lain (mencegah bug loading terus)

header('Content-Type: application/json');

// GET - fetch notifications & live real-time orders/payments state for Cashier and Admin
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ensureOrderSubmittedColumn();

    $notifications = db()->fetchAll(
        "SELECT n.*, o.order_number, o.customer_name, o.payment_status, o.order_status, COALESCE(o.is_submitted, 1) as is_submitted
         FROM notifications n
         LEFT JOIN orders o ON n.order_id = o.id
         WHERE n.target = 'admin'
         ORDER BY n.created_at DESC LIMIT 30"
    );

    foreach ($notifications as &$n) {
        $n['created_at_formatted'] = date('d M H:i', strtotime($n['created_at']));
        $n['created_at']           = date('d M Y H:i', strtotime($n['created_at']));
        $orderId                   = (int)($n['order_id'] ?? 0);
        $payStatus                 = (string)($n['payment_status'] ?? '');
        $isSubmitted               = (int)($n['is_submitted'] ?? 1) === 1;
        $isPaymentNotif            = ($payStatus === 'pending')
                                     || !$isSubmitted
                                     || stripos((string)($n['title'] ?? ''), 'QRIS') !== false
                                     || stripos((string)($n['title'] ?? ''), 'Pembayaran') !== false;

        $n['is_payment_notif'] = $isPaymentNotif;
        if ($payStatus === 'pending') {
            $n['target_url']   = APP_URL . '/cashier/payments.php?payment_filter=pending' . ($orderId > 0 ? '&order_id=' . $orderId : '');
            $n['action_label'] = !empty($n['order_number']) ? 'Konfirmasi Pembayaran #' . $n['order_number'] : 'Buka Kelola Pembayaran';
        } elseif ($isPaymentNotif) {
            $n['target_url']   = APP_URL . '/cashier/payments.php?payment_filter=all' . ($orderId > 0 ? '&order_id=' . $orderId : '');
            $n['action_label'] = !empty($n['order_number']) ? 'Lihat Pembayaran #' . $n['order_number'] : 'Buka Kelola Pembayaran';
        } else {
            $n['target_url']   = APP_URL . '/cashier/orders.php' . ($orderId > 0 ? '?order_id=' . $orderId : '');
            $n['action_label'] = !empty($n['order_number']) ? 'Lihat Pesanan #' . $n['order_number'] : 'Buka Kelola Pesanan';
        }
    }
    unset($n);

    $unreadCount = (int)(db()->fetch("SELECT COUNT(*) c FROM notifications WHERE status='unread' AND target='admin'")['c'] ?? 0);

    $todayIncome = (float)(db()->fetch(
        "SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE COALESCE(is_submitted,1)=1 AND order_status='completed' AND DATE(created_at)=CURRENT_DATE"
    )['c'] ?? 0);

    $orderCounts = [
        'waiting'                => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE COALESCE(is_submitted,1)=1 AND payment_status='confirmed' AND order_status='waiting'")['c'] ?? 0),
        'processing'             => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE COALESCE(is_submitted,1)=1 AND payment_status='confirmed' AND order_status IN ('accepted','processing')")['c'] ?? 0),
        'completed'              => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE COALESCE(is_submitted,1)=1 AND order_status='completed' AND DATE(created_at)=CURRENT_DATE")['c'] ?? 0),
        'today_income_formatted' => formatRupiah($todayIncome),
    ];

    $paymentCounts = [
        'pending'        => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE payment_status='pending'")['c'] ?? 0),
        'waiting_submit' => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE payment_status='confirmed' AND COALESCE(is_submitted,1)=0")['c'] ?? 0),
        'submitted'      => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE payment_status='confirmed' AND COALESCE(is_submitted,1)=1")['c'] ?? 0),
        'rejected'       => (int)(db()->fetch("SELECT COUNT(*) c FROM orders WHERE payment_status='rejected'")['c'] ?? 0),
    ];

    // Build fingerprint of latest orders & payments state so client only re-renders DOM when data actually changes
    $filterStatus  = clean($_GET['filter_status']  ?? '');
    $filterDate    = clean($_GET['filter_date']    ?? '');
    $paymentFilter = clean($_GET['payment_filter'] ?? 'all');
    $focusOrderId  = (int)($_GET['order_id']       ?? 0);
    if (!in_array($paymentFilter, ['all', 'pending', 'confirmed', 'rejected'], true)) {
        $paymentFilter = 'all';
    }

    $allOrdersForHash = db()->fetchAll(
        "SELECT id, order_number, customer_name, phone, table_number, notes, total_amount, payment_method, payment_status, order_status, COALESCE(is_submitted,1) as is_submitted, updated_at, created_at
         FROM orders ORDER BY id DESC LIMIT 100"
    );
    $ordersHash = md5(json_encode($allOrdersForHash) . '|' . $filterStatus . '|' . $filterDate . '|' . $paymentFilter . '|' . $focusOrderId);

    // 0. Cashier Payments Page Grid HTML (all payments before/after confirmation)
    $pSql    = "SELECT * FROM orders WHERE 1=1";
    $pParams = [];
    if ($paymentFilter !== 'all') {
        if ($focusOrderId > 0) {
            $pSql .= " AND (payment_status=? OR id=?)";
            $pParams[] = $paymentFilter;
            $pParams[] = $focusOrderId;
        } else {
            $pSql .= " AND payment_status=?";
            $pParams[] = $paymentFilter;
        }
    }
    if ($focusOrderId > 0) {
        $pSql .= " ORDER BY CASE WHEN id=? THEN -1 WHEN payment_status='pending' THEN 0 WHEN COALESCE(is_submitted,1)=0 THEN 1 ELSE 2 END, updated_at DESC, id DESC LIMIT 60";
        $pParams[] = $focusOrderId;
    } else {
        $pSql .= " ORDER BY CASE WHEN payment_status='pending' THEN 0 WHEN COALESCE(is_submitted,1)=0 THEN 1 ELSE 2 END, updated_at DESC, id DESC LIMIT 60";
    }
    $paymentsList = db()->fetchAll($pSql, $pParams);

    ob_start();
    if (empty($paymentsList)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-credit-card fa-3x mb-3 d-block opacity-25"></i>
      <p>Belum ada data pembayaran pada kategori ini</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($paymentsList as $p):
        $isSubmitted = (int)($p['is_submitted'] ?? 1) === 1;
        $isFocused   = ($focusOrderId > 0 && (int)$p['id'] === $focusOrderId);
        $pItems = db()->fetchAll(
            "SELECT oi.quantity, m.name FROM order_items oi JOIN menus m ON oi.menu_id=m.id WHERE oi.order_id=?",
            [$p['id']]
        );
      ?>
      <div class="col-md-6 col-lg-4" id="payment-card-<?= (int)$p['id'] ?>">
        <div class="order-card" style="<?= $isFocused ? 'border:2px solid var(--primary);box-shadow:0 0 0 4px rgba(245,197,24,.22);' : '' ?>">
          <div class="order-card-header">
            <div>
              <div class="order-number">
                <?= e($p['order_number']) ?>
                <?php if ($isFocused): ?>
                  <span class="badge bg-warning text-dark ms-1" style="font-size:.68rem">Dari Notifikasi</span>
                <?php endif; ?>
              </div>
              <div class="order-time"><i class="fa-regular fa-clock me-1"></i><?= formatDate($p['updated_at'] ?? $p['created_at'],'H:i - d M') ?></div>
            </div>
            <?= paymentStatusBadge($p['payment_status']) ?>
          </div>
          <div class="order-card-body">
            <div class="order-customer"><i class="fa-solid fa-user me-2" style="color:var(--primary)"></i><?= e($p['customer_name']) ?></div>
            <div class="order-table text-muted small mt-1">
              <i class="fa-solid fa-phone me-1"></i><?= e($p['phone']) ?>
              <?php if ($p['table_number']): ?>
               &nbsp;·&nbsp;<i class="fa-solid fa-chair me-1"></i>Meja <?= e($p['table_number']) ?>
              <?php endif; ?>
            </div>
            <?php if (!empty($pItems)): ?>
            <div class="mt-2 pt-2 border-top small text-muted">
              <?php foreach ($pItems as $pi): ?>
                <div>• <?= e($pi['name']) ?> <strong class="text-white">x<?= (int)$pi['quantity'] ?></strong></div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
              <div>
                <div class="text-muted small">Tahap Pelanggan</div>
                <?php if ($p['payment_status'] === 'pending'): ?>
                  <span class="badge bg-warning text-dark">Menunggu Konfirmasi Kasir</span>
                <?php elseif ($p['payment_status'] === 'confirmed' && !$isSubmitted): ?>
                  <span class="badge bg-info text-dark">Menunggu Klik Buat Pesanan</span>
                <?php elseif ($p['payment_status'] === 'confirmed' && $isSubmitted): ?>
                  <span class="badge bg-success">Pesanan Sudah Dibuat</span>
                <?php else: ?>
                  <span class="badge bg-danger">Ditolak</span>
                <?php endif; ?>
              </div>
              <div class="text-end">
                <div class="text-muted small"><?= strtoupper(e($p['payment_method'])) ?></div>
                <div class="order-total mb-0"><?= formatRupiah((float)$p['total_amount']) ?></div>
              </div>
            </div>
          </div>
          <div class="order-card-footer flex-wrap gap-2">
            <?php if ($p['payment_status'] === 'pending'): ?>
              <button class="btn btn-success btn-sm rounded-pill flex-fill" onclick="confirmPayment(<?= $p['id'] ?>)">
                <i class="fa-solid fa-check me-1"></i>Konfirmasi Pembayaran
              </button>
              <button class="btn btn-outline-danger btn-sm rounded-pill" onclick="rejectPaymentCashier(<?= $p['id'] ?>)">
                <i class="fa-solid fa-xmark me-1"></i>Tolak
              </button>
            <?php elseif ($p['payment_status'] === 'confirmed' && !$isSubmitted): ?>
              <div class="w-100 text-center small text-warning py-1">
                <i class="fa-solid fa-spinner fa-spin me-1"></i>Menunggu pelanggan menekan tombol <b>Buat Pesanan</b>...
              </div>
            <?php elseif ($p['payment_status'] === 'confirmed' && $isSubmitted): ?>
              <a href="<?= APP_URL ?>/cashier/orders.php" class="btn btn-outline-success btn-sm rounded-pill w-100">
                <i class="fa-solid fa-receipt me-1"></i>Sudah Masuk ke Kelola Pesanan
              </a>
            <?php else: ?>
              <div class="w-100 text-center small text-danger py-1">Pembayaran telah ditolak</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif;
    $cashierPaymentsHtml = ob_get_clean();

    // 1. Cashier Dashboard Active Orders HTML (only confirmed & submitted orders)
    $recentActive = db()->fetchAll(
        "SELECT * FROM orders WHERE COALESCE(is_submitted,1)=1 AND payment_status='confirmed' AND order_status NOT IN ('completed','cancelled') ORDER BY created_at DESC LIMIT 10"
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

    // 2. Cashier Orders Page Grid HTML (only confirmed & submitted orders)
    $cSql    = "SELECT * FROM orders WHERE COALESCE(is_submitted,1)=1 AND payment_status='confirmed'";
    $cParams = [];
    if ($filterStatus !== '') {
        $cSql .= " AND order_status=?";
        $cParams[] = $filterStatus;
    }
    if ($filterDate !== '') {
        $cSql .= " AND DATE(created_at)=?";
        $cParams[] = $filterDate;
    }
    if ($focusOrderId > 0) {
        $cSql .= " ORDER BY CASE WHEN id=? THEN 0 ELSE 1 END, created_at DESC";
        $cParams[] = $focusOrderId;
    } else {
        $cSql .= " ORDER BY created_at DESC";
    }
    $filteredOrders = db()->fetchAll($cSql, $cParams);

    ob_start();
    if (empty($filteredOrders)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
      <p>Tidak ada pesanan<?= $filterStatus ? ' dengan status "' . e($filterStatus) . '"' : '' ?></p>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($filteredOrders as $o):
        $isFocused = ($focusOrderId > 0 && (int)$o['id'] === $focusOrderId);
      ?>
      <div class="col-md-6 col-lg-4" id="order-card-<?= (int)$o['id'] ?>">
        <div class="order-card" style="<?= $isFocused ? 'border:2px solid var(--primary);box-shadow:0 0 0 4px rgba(245,197,24,.22);' : '' ?>">
          <div class="order-card-header">
            <div>
              <div class="order-number">
                <?= e($o['order_number']) ?>
                <?php if ($isFocused): ?>
                  <span class="badge bg-warning text-dark ms-1" style="font-size:.68rem">Dari Notifikasi</span>
                <?php endif; ?>
              </div>
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
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif;
    $cashierOrdersHtml = ob_get_clean();

    // 3. Admin Dashboard Recent Orders tbody HTML (only submitted orders)
    $submittedOnly = array_values(array_filter($allOrdersForHash, static fn($r) => (int)($r['is_submitted'] ?? 1) === 1));
    $adminRecent   = array_slice($submittedOnly, 0, 8);
    ob_start();
    foreach ($adminRecent as $i => $o): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><span class="fw-700" style="color:var(--primary)"><?= e($o['order_number']) ?></span></td>
      <td><?= e($o['customer_name']) ?></td>
      <td class="fw-700"><?= formatRupiah((float)$o['total_amount']) ?></td>
      <td><?= strtoupper(e($o['payment_method'])) ?> <?= paymentStatusBadge($o['payment_status']) ?></td>
      <td><?= orderStatusBadge($o['order_status']) ?></td>
      <td class="text-muted small"><?= formatDate($o['created_at'] ?? 'now','d M H:i') ?></td>
    </tr>
    <?php endforeach;
    if (empty($adminRecent)): ?>
    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pesanan</td></tr>
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
        <button class="action-btn view me-1" title="Detail"
          onclick="viewOrder(<?= $o['id'] ?>)"><i class="fa-solid fa-eye"></i>
        </button>
      </td>
    </tr>
    <?php endforeach;
    $adminOrdersHtml = ob_get_clean();

    // 5. Cashier Notifications Page HTML
    ob_start();
    if (empty($notifications)): ?>
    <div class="text-center py-5 text-muted">
      <i class="fa-solid fa-bell-slash fa-3x mb-3 d-block opacity-25"></i>
      <p>Belum ada notifikasi</p>
    </div>
    <?php else: ?>
    <div>
      <?php foreach ($notifications as $n):
        $isPendingPay = ($n['payment_status'] ?? '') === 'pending';
        $isPayNotif   = !empty($n['is_payment_notif']);
        $targetUrl    = $n['target_url'] ?? (APP_URL . '/cashier/payments.php');
        $actionLabel  = $n['action_label'] ?? 'Buka Kelola Pembayaran';
      ?>
      <div class="notif-item" onclick="window.location='<?= e($targetUrl) ?>'"
           style="cursor:pointer;border-radius:12px;border:1px solid <?= $isPendingPay ? 'var(--primary)' : 'rgba(255,255,255,.08)' ?>;margin-bottom:10px;padding:16px 20px;display:flex;gap:14px;align-items:flex-start;transition:all .2s ease">
        <div class="notif-item-icon" style="width:42px;height:42px;border-radius:10px;background:rgba(245,197,24,.15);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fa-solid <?= $isPayNotif ? 'fa-credit-card' : 'fa-receipt' ?>"></i>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="fw-700" style="font-size:.92rem"><?= e($n['title']) ?></div>
            <?php if ($isPendingPay): ?>
              <span class="badge bg-warning text-dark" style="font-size:.7rem">Menunggu Konfirmasi Pembayaran</span>
            <?php endif; ?>
          </div>
          <div class="text-muted" style="font-size:.85rem;margin-top:3px"><?= e($n['message']) ?></div>
          <?php if (!empty($n['order_number'])): ?>
          <a href="<?= e($targetUrl) ?>" onclick="event.stopPropagation()" class="d-inline-flex align-items-center gap-1 mt-2 text-decoration-none"
             style="font-size:.82rem;color:var(--primary);font-weight:700">
            <i class="fa-solid <?= $isPayNotif ? 'fa-credit-card' : 'fa-receipt' ?>"></i>
            <span><?= e($actionLabel) ?></span>
          </a>
          <?php endif; ?>
        </div>
        <div class="text-muted" style="font-size:.75rem;white-space:nowrap">
          <?= e($n['created_at_formatted'] ?? $n['created_at']) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif;
    $cashierNotificationsHtml = ob_get_clean();

    jsonResponse([
        'success'                    => true,
        'notifications'              => $notifications,
        'unread_count'               => $unreadCount,
        'order_counts'               => $orderCounts,
        'payment_counts'             => $paymentCounts,
        'orders_hash'                => $ordersHash,
        'cashier_payments_html'      => $cashierPaymentsHtml,
        'cashier_dashboard_html'     => $cashierDashboardHtml,
        'cashier_orders_html'        => $cashierOrdersHtml,
        'cashier_notifications_html' => $cashierNotificationsHtml,
        'admin_recent_html'          => $adminRecentHtml,
        'admin_orders_html'          => $adminOrdersHtml,
        'admin_orders_count'         => count($filteredOrders),
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
