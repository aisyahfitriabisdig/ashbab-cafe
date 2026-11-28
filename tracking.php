<?php
/**
 * ASHBAB CAFE - Order Tracking Page
 * File: tracking.php
 */
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Tracking Pesanan';
$settings  = getSettings();

// Get active session orders
$activeOrders = [];
if (!empty($_SESSION['my_orders'])) {
    $placeholders = str_repeat('?,', count($_SESSION['my_orders']) - 1) . '?';
    $sql = "SELECT order_number, order_status FROM orders WHERE order_number IN ($placeholders) AND order_status IN ('waiting','accepted','processing') ORDER BY created_at DESC";
    $activeOrders = db()->fetchAll($sql, $_SESSION['my_orders']);
}

require_once __DIR__ . '/includes/header.php';
?>
<script>
    const ACTIVE_ORDERS = <?= json_encode(array_column($activeOrders, 'order_number')) ?>;
</script>
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="tracking-section">
<div class="container">

    <div class="text-center mb-5" data-aos="fade-up">
        <div class="hero-badge" style="background:rgba(139,69,19,.08);border-color:rgba(139,69,19,.2);color:var(--primary)">
            <i class="fa-solid fa-location-dot me-2"></i>Tracking
        </div>
        <h2 class="section-title mt-2">Lacak Pesanan Anda</h2>
        <div class="section-divider mx-auto"></div>
        <p class="section-subtitle">Masukkan nomor pesanan untuk melihat status terkini</p>
    </div>

    <!-- Search Form -->
    <div class="tracking-card mb-4" data-aos="fade-up">
        <?php if (!empty($activeOrders)): ?>
            <div class="mb-4 p-3 rounded" style="background: rgba(var(--primary-rgb), 0.1); border: 1px solid rgba(var(--primary-rgb), 0.3);">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-bell me-2"></i>Pesanan Aktif Anda</h6>
                <div class="d-flex flex-wrap gap-2" id="activeOrderButtons">
                    <?php foreach ($activeOrders as $o): ?>
                        <button class="btn btn-outline-dark fw-600 rounded-pill" onclick="trackOrder('<?= e($o['order_number']) ?>')">
                            #<?= e($o['order_number']) ?> 
                            <?php if ($o['order_status'] === 'waiting'): ?>
                                <span class="badge bg-secondary ms-1">Menunggu</span>
                            <?php elseif ($o['order_status'] === 'accepted'): ?>
                                <span class="badge bg-info ms-1">Diterima</span>
                            <?php elseif ($o['order_status'] === 'processing'): ?>
                                <span class="badge bg-warning ms-1">Diproses</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 text-muted small"><i class="fa-solid fa-circle-info me-1"></i>Pilih pesanan di atas untuk melacak secara otomatis, atau gunakan kolom pencarian di bawah.</div>
            </div>
        <?php endif; ?>

        <form id="trackingForm">
            <div class="input-group" style="gap:12px">
                <input type="text" class="form-control form-control-lg" id="orderNumberInput"
                       placeholder="Masukkan nomor pesanan (contoh: ASH-20260701001)"
                       style="border-radius:50px;border:2px solid var(--border);padding:14px 24px;font-weight:600">
                <button type="submit" class="btn-primary-cafe" style="border-radius:50px;white-space:nowrap">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Cari
                </button>
            </div>
        </form>
    </div>

    <!-- Loading -->
    <div id="trackingLoading" class="text-center py-5" style="display:none">
        <div style="font-size:3rem">☕</div>
        <p class="mt-2 text-muted">Mencari pesanan...</p>
    </div>

    <!-- Result -->
    <div id="trackingResult" style="display:none" data-aos="fade-up">
        <div class="row g-4">
            <!-- Order Info -->
            <div class="col-lg-5">
                <div class="tracking-card h-100">
                    <h5 class="fw-800 mb-4"><i class="fa-solid fa-receipt me-2" style="color:var(--primary)"></i>Detail Pesanan</h5>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">Nomor Pesanan</div>
                        <div class="fw-800 fs-5" style="color:var(--primary)" id="t_order_number">-</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="text-muted small mb-1">Pelanggan</div>
                            <div class="fw-700" id="t_customer">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small mb-1">No. HP</div>
                            <div class="fw-600" id="t_phone">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small mb-1">No. Meja</div>
                            <div class="fw-600" id="t_table">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small mb-1">Pembayaran</div>
                            <div class="fw-600" id="t_payment">-</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small mb-1">Status Pembayaran</div>
                        <span id="t_pay_status" class="badge bg-warning">-</span>
                    </div>

                    <h6 class="fw-700 mb-2 mt-4">Item Pesanan</h6>
                    <div id="t_items"></div>

                    <div class="mt-3 pt-3 border-top d-flex justify-content-between">
                        <span class="fw-700">Total</span>
                        <span class="fw-800 fs-5" style="color:var(--primary)" id="t_total">-</span>
                    </div>
                    <div class="text-muted small mt-2" id="t_date">-</div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="col-lg-7">
                <div class="tracking-card h-100">
                    <h5 class="fw-800 mb-4"><i class="fa-solid fa-route me-2" style="color:var(--primary)"></i>Status Pesanan</h5>

                    <!-- Cancelled Notice -->
                    <div id="cancelledNotice" class="alert alert-danger mb-4" style="display:none;border-radius:12px">
                        <i class="fa-solid fa-circle-xmark me-2"></i>
                        <strong>Pesanan Dibatalkan</strong><br>
                        <small>Maaf, pesanan Anda telah dibatalkan. Silakan hubungi kasir untuk informasi lebih lanjut.</small>
                    </div>

                    <div class="progress-timeline">
                        <!-- Step: Waiting -->
                        <div class="timeline-item" id="step_waiting">
                            <div class="timeline-dot">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">Pesanan Diterima</div>
                                <div class="timeline-time">Pesanan telah masuk ke sistem</div>
                            </div>
                        </div>

                        <!-- Step: Accepted -->
                        <div class="timeline-item" id="step_accepted">
                            <div class="timeline-dot">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">Dikonfirmasi</div>
                                <div class="timeline-time">Pesanan sudah dikonfirmasi kasir</div>
                            </div>
                        </div>

                        <!-- Step: Processing -->
                        <div class="timeline-item" id="step_processing">
                            <div class="timeline-dot">
                                <i class="fa-solid fa-mug-hot"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">Sedang Diproses</div>
                                <div class="timeline-time">Dapur sedang menyiapkan pesanan Anda</div>
                            </div>
                        </div>

                        <!-- Step: Completed -->
                        <div class="timeline-item" id="step_completed">
                            <div class="timeline-dot">
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">Pesanan Selesai 🎉</div>
                                <div class="timeline-time">Selamat menikmati hidangan Anda!</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top text-center text-muted small">
                        Status diperbarui otomatis secara real-time
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<?= renderSiteFooter() ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
const APP_URL    = '<?= APP_URL ?>';
const CSRF_TOKEN = '<?= generateCSRF() ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/tracking.js?v=<?= APP_VERSION ?>"></script>
