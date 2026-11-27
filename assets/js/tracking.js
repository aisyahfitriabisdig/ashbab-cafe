/**
 * ASHBAB CAFE - Order Tracking JavaScript
 * File: assets/js/tracking.js
 */

'use strict';

let trackingInterval = null;
let lastStatus = null;
let notifSound = null;

// Create notification sound
function createSound() {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.frequency.value = 880;
    osc.type = 'sine';
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.5);
}

// Track order by order number
function trackOrder(orderNumber) {
    const resultDiv = document.getElementById('trackingResult');
    const loadingDiv = document.getElementById('trackingLoading');

    if (!orderNumber) {
        showToast('Masukkan nomor pesanan.', 'warning');
        return;
    }

    if (loadingDiv) loadingDiv.style.display = 'block';
    if (resultDiv) resultDiv.style.display = 'none';

    fetch(APP_URL + '/ajax/track_order.php?order_number=' + encodeURIComponent(orderNumber))
        .then(r => r.json())
        .then(res => {
            if (loadingDiv) loadingDiv.style.display = 'none';
            if (res.success) {
                renderTracking(res.order);
                if (resultDiv) resultDiv.style.display = 'block';
                startPolling(orderNumber);
            } else {
                showToast(res.message || 'Pesanan tidak ditemukan.', 'error');
            }
        })
        .catch(() => {
            if (loadingDiv) loadingDiv.style.display = 'none';
            showToast('Gagal mengambil data pesanan.', 'error');
        });
}

function renderTracking(order) {
    document.getElementById('t_order_number').textContent = order.order_number;
    document.getElementById('t_customer').textContent    = order.customer_name;
    document.getElementById('t_phone').textContent       = order.phone;
    document.getElementById('t_table').textContent       = order.table_number || '-';
    document.getElementById('t_payment').textContent     = order.payment_method === 'qris' ? 'QRIS' : 'Transfer Bank';
    document.getElementById('t_total').textContent       = formatRupiah(order.total_amount);
    document.getElementById('t_date').textContent        = order.created_at;

    // Payment status
    const payBadge = document.getElementById('t_pay_status');
    const payColors = { pending:'warning', confirmed:'success', rejected:'danger' };
    const payLabels = { pending:'Menunggu Konfirmasi', confirmed:'Dikonfirmasi', rejected:'Ditolak' };
    if (payBadge) {
        payBadge.className = `badge bg-${payColors[order.payment_status] || 'secondary'}`;
        payBadge.textContent = payLabels[order.payment_status] || order.payment_status;
    }

    // Order status timeline
    const steps = ['waiting','accepted','processing','completed'];
    const cancelledStep = order.order_status === 'cancelled';
    const curIdx = steps.indexOf(order.order_status);

    steps.forEach((step, i) => {
        const el = document.getElementById('step_' + step);
        if (!el) return;
        el.classList.remove('done','active');
        if (cancelledStep && step === 'waiting') {
            el.classList.add('done');
        } else if (!cancelledStep) {
            if (i < curIdx) el.classList.add('done');
            else if (i === curIdx) el.classList.add('active');
        }
    });

    // Cancelled notice
    const cancelEl = document.getElementById('cancelledNotice');
    if (cancelEl) cancelEl.style.display = cancelledStep ? 'block' : 'none';

    // Render items
    const itemsList = document.getElementById('t_items');
    if (itemsList && order.items) {
        itemsList.innerHTML = order.items.map(item => `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <span class="fw-600">${item.name}</span>
                    <span class="text-muted ms-2">x${item.quantity}</span>
                </div>
                <span class="fw-600 text-primary-cafe">${formatRupiah(item.subtotal)}</span>
            </div>`).join('');
    }

    // Notify if status changed
    if (lastStatus && lastStatus !== order.order_status) {
        const labels = { accepted:'Pesanan Diterima ✅', processing:'Sedang Diproses 🍳', completed:'Pesanan Selesai 🎉', cancelled:'Pesanan Dibatalkan ❌' };
        const msg = labels[order.order_status] || 'Status pesanan berubah';
        showToast(msg, order.order_status === 'cancelled' ? 'error' : 'success', 5000);
        try { createSound(); } catch(e) {}
    }
    lastStatus = order.order_status;

    // Handle completed / cancelled
    if (['completed','cancelled'].includes(order.order_status)) {
        stopPolling();
        
        // Add a button to go to history
        const timeline = document.querySelector('.progress-timeline');
        let historyBtn = document.getElementById('historyBtnContainer');
        if (!historyBtn && timeline) {
            historyBtn = document.createElement('div');
            historyBtn.id = 'historyBtnContainer';
            historyBtn.className = 'mt-4 pt-4 border-top text-center';
            historyBtn.innerHTML = `<p class="mb-3">Pesanan Anda telah selesai diproses.</p><a href="${APP_URL}/history.php" class="btn btn-dark fw-bold rounded-pill px-4"><i class="fa-solid fa-clock-rotate-left me-2"></i>Lihat Riwayat Pesanan</a>`;
            timeline.parentNode.appendChild(historyBtn);
        }
    } else {
        const historyBtn = document.getElementById('historyBtnContainer');
        if (historyBtn) historyBtn.remove();
    }
}

function startPolling(orderNumber) {
    stopPolling();
    trackingInterval = setInterval(() => trackOrder(orderNumber), 8000);
}

function stopPolling() {
    if (trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
}

// Init tracking form
const trackForm = document.getElementById('trackingForm');
if (trackForm) {
    trackForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const val = document.getElementById('orderNumberInput')?.value?.trim();
        trackOrder(val);
    });

    // Auto-track if order_number in URL or if ACTIVE_ORDERS has items
    const urlParams = new URLSearchParams(window.location.search);
    const autoNum = urlParams.get('order');
    if (autoNum) {
        const input = document.getElementById('orderNumberInput');
        if (input) input.value = autoNum;
        trackOrder(autoNum);
    } else if (typeof ACTIVE_ORDERS !== 'undefined' && ACTIVE_ORDERS.length > 0) {
        trackOrder(ACTIVE_ORDERS[0]);
    }
}
