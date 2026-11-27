/**
 * ASHBAB CAFE - Cashier JavaScript
 * File: assets/js/cashier.js
 */

'use strict';

let cashierNotifCount = 0;
let cashierNotifInit  = false;

// ============================================================
// Real-time Notifications (Cashier)
// ============================================================
function pollCashierNotifications() {
    fetch(APP_URL + '/ajax/notifications.php?role=cashier')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const badge = document.getElementById('notifBadge');
            const count = res.unread_count || 0;

            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }

            if (cashierNotifInit && count > cashierNotifCount) {
                showToast('🔔 Ada pesanan / pembayaran QRIS baru!', 'info', 5000);
                playNotifSound();
                const bell = document.getElementById('notifBell');
                if (bell) { bell.classList.add('bell-ring'); setTimeout(() => bell.classList.remove('bell-ring'), 1000); }
                if (typeof Swal === 'undefined' || !Swal.isVisible()) {
                    setTimeout(() => location.reload(), 1500);
                }
            }
            cashierNotifCount = count;
            cashierNotifInit  = true;

            // Update order counts in dashboard
            if (res.order_counts) {
                const el_new  = document.getElementById('count_waiting');
                const el_proc = document.getElementById('count_processing');
                const el_done = document.getElementById('count_completed');
                if (el_new)  el_new.textContent  = res.order_counts.waiting    || 0;
                if (el_proc) el_proc.textContent  = res.order_counts.processing || 0;
                if (el_done) el_done.textContent  = res.order_counts.completed  || 0;
            }
        }).catch(() => {});
}

function playNotifSound() {
    try {
        const ctx  = new (window.AudioContext || window.webkitAudioContext)();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        [440, 554, 659].forEach((freq, i) => {
            const t = ctx.currentTime + i * 0.15;
            osc.frequency.setValueAtTime(freq, t);
        });
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.6);
    } catch(e) {}
}

// Start polling
if (typeof APP_URL !== 'undefined') {
    pollCashierNotifications();
    setInterval(pollCashierNotifications, 7000);
}

// ============================================================
// Print Receipt
// ============================================================
function printReceipt(orderId, format = 'browser') {
    if (format === 'pdf') {
        window.open(APP_URL + '/cashier/receipt.php?order_id=' + orderId + '&format=pdf', '_blank');
        return;
    }
    const win = window.open(APP_URL + '/cashier/receipt.php?order_id=' + orderId, '_blank', 'width=400,height=600');
    win?.focus();
    win?.addEventListener('load', () => win.print());
}

// ============================================================
// Order Status Update (Cashier)
// ============================================================
function cashierUpdateStatus(orderId, status) {
    const labels = {
        accepted: 'Terima pesanan ini?',
        processing: 'Tandai pesanan sedang diproses?',
        completed: 'Tandai pesanan selesai?',
        cancelled: 'Batalkan pesanan ini?'
    };

    Swal.fire({
        title: 'Update Status',
        text: labels[status] || 'Ubah status pesanan?',
        icon: status === 'cancelled' ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: status === 'cancelled' ? '#ef4444' : '#10b981',
        confirmButtonText: 'Ya',
        cancelButtonText: 'Tidak',
    }).then(result => {
        if (!result.isConfirmed) return;
        ajaxPost(APP_URL + '/ajax/update_order_status.php', {
            order_id: orderId, status, csrf_token: CSRF_TOKEN
        }, res => {
            if (res.success) {
                showToast('Status diperbarui!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                alertError(res.message || 'Gagal update.');
            }
        });
    });
}

function confirmPayment(orderId) {
    Swal.fire({
        title: 'Konfirmasi Pembayaran',
        text: 'Tandai pembayaran ini sebagai dikonfirmasi?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Konfirmasi',
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) {
            ajaxPost(APP_URL + '/ajax/update_order_status.php', {
                order_id: orderId, payment_action:'confirm', csrf_token: CSRF_TOKEN
            }, res => {
                if (res.success) { alertSuccess('Pembayaran dikonfirmasi.'); setTimeout(() => location.reload(), 1000); }
                else alertError(res.message);
            });
        }
    });
}
