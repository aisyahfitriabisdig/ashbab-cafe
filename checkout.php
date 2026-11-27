<?php
/**
 * ASHBAB CAFE - Checkout Page
 * File: checkout.php
 */
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Checkout';
$settings  = getSettings();
$taxPct    = (float)($settings['tax_percent'] ?? 10);

// Direct QRIS image download handler
if (isset($_GET['download_qris']) && !empty($settings['qris_image'])) {
    $qrisPath = trim((string)$settings['qris_image']);
    $bytes    = false;
    $mime     = 'image/png';

    if (str_starts_with($qrisPath, 'media.php')) {
        parse_str((string)(parse_url($qrisPath, PHP_URL_QUERY) ?? ''), $q);
        $mediaId = $q['id'] ?? '';
        if (is_string($mediaId) && preg_match('/^[a-zA-Z0-9_.-]+$/', $mediaId)) {
            try {
                $row = db()->fetch("SELECT mime_type, data FROM uploaded_files WHERE id = ? LIMIT 1", [$mediaId]);
                if ($row && !empty($row['data'])) {
                    $bytes = base64_decode($row['data'], true);
                    $mime  = normalizeImageMime((string)($row['mime_type'] ?? 'image/png'));
                }
            } catch (Throwable $e) {
                $bytes = false;
            }
        }
    } elseif (str_starts_with($qrisPath, 'http://') || str_starts_with($qrisPath, 'https://')) {
        if (function_exists('curl_init')) {
            $ch = curl_init($qrisPath);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 15,
            ]);
            $res = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            if ($res !== false && $code >= 200 && $code < 300) {
                $bytes = $res;
                if ($ct !== '') {
                    $mime = normalizeImageMime($ct);
                }
            }
        } else {
            $bytes = @file_get_contents($qrisPath);
        }
    } else {
        $localFile = ROOT_PATH . ltrim($qrisPath, '/');
        if (is_file($localFile)) {
            $bytes = @file_get_contents($localFile);
            if (function_exists('finfo_open')) {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = (string)@finfo_file($finfo, $localFile);
                    @finfo_close($finfo);
                    if ($detected !== '') {
                        $mime = normalizeImageMime($detected);
                    }
                }
            }
        }
    }

    if ($bytes !== false && $bytes !== '') {
        if (!str_starts_with($mime, 'image/')) {
            $mime = 'image/png';
        }
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $ext = $extMap[$mime] ?? 'png';
        $filename = 'QRIS-ASHBAB-CAFE.' . $ext;

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        echo $bytes;
        exit;
    }

    redirect(assetUrl($qrisPath));
}

// Redirect if cart empty
if (empty($_SESSION['cart'])) {
    redirect(APP_URL . '/menu.php');
}

// Build cart summary
$cartItems = [];
$subtotal  = 0;
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$menus = db()->fetchAll("SELECT * FROM menus WHERE id IN ($placeholders)", $ids);
$menuMap = array_column($menus, null, 'id');

foreach ($_SESSION['cart'] as $menuId => $item) {
    $menu = $menuMap[$menuId] ?? null;
    if (!$menu) continue;
    $itemSub = $menu['price'] * $item['quantity'];
    $subtotal += $itemSub;
    $cartItems[] = ['menu' => $menu, 'quantity' => $item['quantity'], 'subtotal' => $itemSub];
}

$tax   = $subtotal * ($taxPct / 100);
$total = $subtotal + $tax;

require_once __DIR__ . '/includes/header.php';
?>
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="checkout-section">
<div class="container">
    <div class="d-flex align-items-center mb-4" data-aos="fade-up">
        <a href="<?= APP_URL ?>/cart.php" class="btn btn-sm me-3" style="background:var(--accent);color:var(--primary);border-radius:50px;padding:8px 18px;font-weight:600">
            <i class="fa-solid fa-arrow-left me-1"></i>Keranjang
        </a>
        <h3 class="fw-800 mb-0">Checkout</h3>
    </div>

    <div class="row g-4">
        <!-- Checkout Form -->
        <div class="col-lg-7" data-aos="fade-right">
            <form id="checkoutForm" enctype="multipart/form-data">
                <?= csrfField() ?>

                <!-- Customer Info -->
                <div class="admin-card mb-4">
                    <h5 class="fw-700 mb-4"><i class="fa-solid fa-user me-2" style="color:var(--primary)"></i>Data Pelanggan</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="customer_name" id="customerName" required placeholder="Masukkan nama Anda">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor HP <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" id="customerPhone" required placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor Meja <small class="text-muted">(opsional)</small></label>
                            <input type="text" class="form-control" name="table_number" id="tableNumber" placeholder="Contoh: 5">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Pesanan <small class="text-muted">(opsional)</small></label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Contoh: tanpa es, less sugar, dll..."><?= e($_SESSION['order_notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="admin-card mb-4">
                    <h5 class="fw-700 mb-4"><i class="fa-solid fa-credit-card me-2" style="color:var(--primary)"></i>Metode Pembayaran</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="payment-option selected" id="payQRIS">
                                <i class="fa-solid fa-qrcode"></i>
                                <p>QRIS</p>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="paymentMethodInput" value="qris" required>

                    <!-- QRIS Info -->
                    <div id="qrisInfo" class="mt-4 text-center" style="display:block">
                        <h6 class="fw-700 mb-3"><i class="fa-solid fa-qrcode me-2" style="color:var(--primary)"></i>Scan QRIS</h6>
                        <?php if (!empty($settings['qris_image'])): ?>
                            <div>
                                <img id="qrisCheckoutImg" src="<?= e(assetUrl($settings['qris_image'])) ?>" alt="QRIS" style="max-width:220px;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.1)">
                            </div>
                            <div class="mt-2">
                                <a href="<?= APP_URL ?>/checkout.php?download_qris=1" id="btnDownloadQRIS" download="QRIS-ASHBAB-CAFE.png" class="btn btn-sm rounded-pill px-3 py-1" style="background:var(--accent);color:var(--primary);font-weight:600;border:1px solid rgba(139,69,19,.2)">
                                    <i class="fa-solid fa-download me-1"></i>Unduh QR Code
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="p-4 rounded-3" style="background:#f8fafc;border:2px dashed #e2e8f0">
                                <i class="fa-solid fa-qrcode fa-3x text-muted"></i>
                                <p class="text-muted mt-2 mb-0 small">QRIS belum diatur</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <p class="text-muted mb-1">Total Pembayaran:</p>
                            <h4 class="fw-800" style="color:var(--primary)"><?= formatRupiah($total) ?></h4>
                            <div class="small text-muted mt-1" id="qrisOrderRef" style="display:none">
                                No. Tagihan: <strong id="qrisOrderNumber" style="color:var(--primary)">-</strong>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2 px-3 py-2 rounded-pill mw-100" id="paymentWaitingBadge" style="background:#fff8e6;border:1px solid #fbd38d;color:#b45309;font-size:.83rem;font-weight:600;white-space:normal;line-height:1.4">
                                <i class="fa-solid fa-spinner fa-spin flex-shrink-0"></i>
                                <span id="paymentWaitText">Mendeteksi status pembayaran secara otomatis...</span>
                            </div>
                            <p class="text-muted small mt-2 mb-0" style="font-size:.78rem">
                                Silakan scan & selesaikan pembayaran QRIS. Halaman ini akan otomatis berubah begitu pembayaran terkonfirmasi.
                            </p>
                        </div>
                    </div>

                    <div id="paymentSuccessInfo" class="mt-4 text-center" style="display:none">
                        <div class="p-4 rounded-3" style="background:rgba(25, 135, 84, 0.1); border: 2px dashed #198754;">
                            <i class="fa-solid fa-circle-check fa-3x text-success mb-2"></i>
                            <h5 class="text-success fw-700 mb-0">Pembayaran Berhasil Dikonfirmasi</h5>
                            <p class="text-muted small mt-1 mb-0">Terima kasih, pembayaran QRIS Anda telah terverifikasi. Silakan klik tombol <b>Buat Pesanan</b> di bawah.</p>
                        </div>
                    </div>
                    
                    <input type="hidden" name="payment_verified" id="paymentVerified" value="0">

                </div>

                <button type="submit" class="btn-primary-cafe w-100 justify-content-center d-flex py-3 fs-5" id="btnSubmitOrder" disabled>
                    <i class="fa-solid fa-paper-plane me-2"></i>Buat Pesanan
                </button>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-5">
            <div class="order-summary" data-aos="fade-left">
                <h5 class="fw-800 mb-4"><i class="fa-solid fa-receipt me-2" style="color:var(--primary)"></i>Pesanan Anda</h5>
                <?php foreach ($cartItems as $item): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-600 small"><?= e($item['menu']['name']) ?></div>
                        <div class="text-muted" style="font-size:.78rem">x<?= $item['quantity'] ?> × <?= formatRupiah($item['menu']['price']) ?></div>
                    </div>
                    <span class="fw-700 small"><?= formatRupiah($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="summary-row mt-2"><span>Subtotal</span><span><?= formatRupiah($subtotal) ?></span></div>
                <div class="summary-row"><span>Pajak (<?= $taxPct ?>%)</span><span><?= formatRupiah($tax) ?></span></div>
                <div class="summary-row summary-total"><span>Total Bayar</span><span><?= formatRupiah($total) ?></span></div>
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



// Automatic Real-Time QRIS Payment Detection
let paymentPollTimer = null;
let syncDebounceTimer = null;
let isPaymentConfirmed = false;
let isCheckingPayment = false;

function markPaymentAsConfirmed(orderNumber) {
    if (isPaymentConfirmed) return;
    isPaymentConfirmed = true;
    if (paymentPollTimer) {
        clearInterval(paymentPollTimer);
        paymentPollTimer = null;
    }

    document.getElementById('qrisInfo').style.display = 'none';
    document.getElementById('paymentSuccessInfo').style.display = 'block';
    document.getElementById('paymentVerified').value = '1';

    const btnSubmit = document.getElementById('btnSubmitOrder');
    if (btnSubmit) btnSubmit.disabled = false;

    showToast('Pembayaran QRIS berhasil dikonfirmasi!', 'success');
}

function checkPaymentStatus() {
    if (isPaymentConfirmed || isCheckingPayment) return;
    isCheckingPayment = true;

    const formEl = document.getElementById('checkoutForm');
    const formData = new FormData(formEl);

    fetch(APP_URL + '/ajax/check_payment.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(res => {
            isCheckingPayment = false;
            if (!res.success) return;

            if (res.order_number) {
                const refWrap = document.getElementById('qrisOrderRef');
                const refNum  = document.getElementById('qrisOrderNumber');
                if (refWrap && refNum) {
                    refNum.textContent = '#' + res.order_number;
                    refWrap.style.display = 'block';
                }
            }

            if (res.payment_status === 'confirmed') {
                markPaymentAsConfirmed(res.order_number);
            } else if (res.payment_status === 'rejected') {
                const waitText = document.getElementById('paymentWaitText');
                if (waitText) {
                    waitText.textContent = 'Pembayaran ditolak. Silakan hubungi kasir.';
                }
                showToast('Pembayaran ditolak oleh kasir.', 'error');
            }
        })
        .catch(() => {
            isCheckingPayment = false;
        });
}

// Start automatic polling immediately & every 2.5 seconds
checkPaymentStatus();
paymentPollTimer = setInterval(checkPaymentStatus, 2500);

// Sync customer details automatically as they type
['customerName', 'customerPhone', 'tableNumber'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        if (syncDebounceTimer) clearTimeout(syncDebounceTimer);
        syncDebounceTimer = setTimeout(checkPaymentStatus, 600);
    });
});
document.querySelector('textarea[name="notes"]')?.addEventListener('input', () => {
    if (syncDebounceTimer) clearTimeout(syncDebounceTimer);
    syncDebounceTimer = setTimeout(checkPaymentStatus, 600);
});

// Submit
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (document.getElementById('paymentVerified').value !== '1') { 
        showToast('Selesaikan pembayaran terlebih dahulu.', 'warning'); 
        return; 
    }

    const btn = document.getElementById('btnSubmitOrder');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Memproses...';

    const form = new FormData(this);
    fetch(APP_URL + '/ajax/checkout.php', { method:'POST', body: form })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Pesanan Dibuat! 🎉',
                    html: `Nomor pesanan Anda: <br><b class="fs-5" style="color:var(--primary)">${res.order_number}</b><br><small>Simpan nomor ini untuk tracking pesanan.</small>`,
                    confirmButtonText: 'Lacak Pesanan',
                    confirmButtonColor: '#8B4513',
                    allowOutsideClick: false,
                }).then(() => {
                    window.location = APP_URL + '/tracking.php?order=' + res.order_number;
                });
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i>Buat Pesanan';
                alertError(res.message || 'Gagal membuat pesanan.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i>Buat Pesanan';
            alertError('Terjadi kesalahan. Silakan coba lagi.');
        });
});
</script>
