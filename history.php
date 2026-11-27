<?php
/**
 * ASHBAB CAFE - Order History Page
 * File: history.php
 */
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Riwayat Pesanan';
$settings  = getSettings();

require_once __DIR__ . '/includes/header.php';

// Get completed/cancelled orders from session
$historyOrders = [];
if (!empty($_SESSION['my_orders'])) {
    $placeholders = str_repeat('?,', count($_SESSION['my_orders']) - 1) . '?';
    // Limit to 5 initially
    $sql = "SELECT * FROM orders WHERE order_number IN ($placeholders) AND order_status IN ('completed','cancelled') ORDER BY created_at DESC LIMIT 5";
    $historyOrders = db()->fetchAll($sql, $_SESSION['my_orders']);
    
    // Calculate total history to see if we need 'Load More'
    $sqlTotal = "SELECT COUNT(*) as total FROM orders WHERE order_number IN ($placeholders) AND order_status IN ('completed','cancelled')";
    $totalHistory = db()->fetch($sqlTotal, $_SESSION['my_orders'])['total'];
} else {
    $totalHistory = 0;
}
?>
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="tracking-section" style="min-height: calc(100vh - 200px);">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="hero-badge" style="background:rgba(139,69,19,.08);border-color:rgba(139,69,19,.2);color:var(--primary)">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat
            </div>
            <h2 class="section-title mt-2">Data Pesanan Anda</h2>
            <div class="section-divider mx-auto"></div>
            <p class="section-subtitle">Daftar pesanan yang telah selesai diproses</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div id="historyContainer">
                    <?php if (empty($historyOrders)): ?>
                        <div class="text-center py-5 admin-card">
                            <div style="font-size: 4rem; margin-bottom: 1rem; color: #ccc;">📦</div>
                            <h5 class="fw-bold">Belum ada riwayat pesanan</h5>
                            <p class="text-muted">Anda belum memiliki pesanan yang telah selesai.</p>
                            <a href="<?= APP_URL ?>/menu.php" class="btn btn-primary-cafe rounded-pill px-4 mt-2">Pesan Sekarang</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($historyOrders as $order): ?>
                            <div class="admin-card mb-4" data-aos="fade-up">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <div class="fw-bold fs-5 text-primary-cafe">#<?= e($order['order_number']) ?></div>
                                        <div class="text-muted small"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></div>
                                    </div>
                                    <div>
                                        <?php if ($order['order_status'] === 'completed'): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Selesai</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i>Dibatalkan</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-sm-6">
                                        <div class="text-muted small">Total Pembayaran</div>
                                        <div class="fw-bold"><?= formatRupiah($order['total_amount']) ?></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="text-muted small">Metode Pembayaran</div>
                                        <div class="fw-bold"><?= $order['payment_method'] === 'qris' ? 'QRIS' : 'Transfer Bank' ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($historyOrders) && $totalHistory > 5): ?>
                    <div class="text-center mt-4">
                        <button id="btnLoadMore" class="btn btn-outline-dark fw-bold rounded-pill px-4 py-2" onclick="loadMoreHistory()">
                            <i class="fa-solid fa-angles-down me-2"></i>Lihat Data Pesanan Lainnya
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="site-footer">
    <p class="mb-0"><?= e($settings['footer_text'] ?? '© '.date('Y').' ASHBAB CAFE') ?></p>
</footer>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
let currentOffset = 5;
function loadMoreHistory() {
    const btn = document.getElementById('btnLoadMore');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Memuat...';
    btn.disabled = true;

    fetch('<?= APP_URL ?>/ajax/get_history.php?offset=' + currentOffset)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data.length > 0) {
                const container = document.getElementById('historyContainer');
                res.data.forEach(order => {
                    const statusBadge = order.order_status === 'completed' 
                        ? '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Selesai</span>'
                        : '<span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i>Dibatalkan</span>';
                    
                    const payment = order.payment_method === 'qris' ? 'QRIS' : 'Transfer Bank';
                    
                    const html = `
                    <div class="admin-card mb-4" style="animation: fadeIn 0.5s;">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <div class="fw-bold fs-5 text-primary-cafe">#${order.order_number}</div>
                                <div class="text-muted small">${order.date_formatted}</div>
                            </div>
                            <div>${statusBadge}</div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Total Pembayaran</div>
                                <div class="fw-bold">${order.total_formatted}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Metode Pembayaran</div>
                                <div class="fw-bold">${payment}</div>
                            </div>
                        </div>
                    </div>`;
                    container.insertAdjacentHTML('beforeend', html);
                });

                currentOffset += res.data.length;
                if (!res.has_more) {
                    btn.remove();
                } else {
                    btn.innerHTML = '<i class="fa-solid fa-angles-down me-2"></i>Lihat Data Pesanan Lainnya';
                    btn.disabled = false;
                }
            } else {
                btn.remove();
            }
        })
        .catch(() => {
            btn.innerHTML = '<i class="fa-solid fa-angles-down me-2"></i>Lihat Data Pesanan Lainnya';
            btn.disabled = false;
            alert('Gagal memuat data pesanan.');
        });
}
</script>

</body>
</html>
