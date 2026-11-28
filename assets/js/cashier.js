/**
 * ASHBAB CAFE - Cashier JavaScript
 * File: assets/js/cashier.js
 */

'use strict';

let cashierNotifCount = 0;
let cashierNotifInit  = false;
let cashierOrdersHash = '';
let isPollingCashier  = false;

// ============================================================
// Real-time Notifications & Live Order DOM Sync (Cashier)
// ============================================================
function pollCashierNotifications() {
    if (isPollingCashier) return;
    isPollingCashier = true;

    const urlParams = new URLSearchParams(window.location.search);
    const filterStatus = urlParams.get('status') || '';

    fetch(APP_URL + '/ajax/notifications.php?role=cashier&filter_status=' + encodeURIComponent(filterStatus) + '&_t=' + Date.now())
        .then(r => r.json())
        .then(res => {
            isPollingCashier = false;
            if (!res.success) return;
            const badge = document.getElementById('notifBadge');
            const count = res.unread_count || 0;

            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }

            if (cashierNotifInit && count > cashierNotifCount) {
                showToast('🔔 Ada pesanan / pembayaran QRIS baru!', 'info', 4000);
                playNotifSound();
                const bell = document.getElementById('notifBell');
                if (bell) { bell.classList.add('bell-ring'); setTimeout(() => bell.classList.remove('bell-ring'), 1000); }
            }
            cashierNotifCount = count;
            cashierNotifInit  = true;

            // Update order counts in dashboard without reload
            if (res.order_counts) {
                const el_new  = document.getElementById('count_waiting');
                const el_proc = document.getElementById('count_processing');
                const el_done = document.getElementById('count_completed');
                const el_inc  = document.getElementById('count_income');
                if (el_new)  el_new.textContent  = res.order_counts.waiting    || 0;
                if (el_proc) el_proc.textContent = res.order_counts.processing || 0;
                if (el_done) el_done.textContent = res.order_counts.completed  || 0;
                if (el_inc && res.order_counts.today_income_formatted) {
                    el_inc.textContent = res.order_counts.today_income_formatted;
                }
            }

            // Live DOM update for order cards whenever any order is created/updated
            if (res.orders_hash && res.orders_hash !== cashierOrdersHash) {
                cashierOrdersHash = res.orders_hash;
                const dashWrap   = document.getElementById('cashierDashboardOrders');
                const ordersWrap = document.getElementById('cashierOrdersGrid');
                if (dashWrap && typeof res.cashier_dashboard_html === 'string') {
                    dashWrap.innerHTML = res.cashier_dashboard_html;
                }
                if (ordersWrap && typeof res.cashier_orders_html === 'string') {
                    ordersWrap.innerHTML = res.cashier_orders_html;
                }
            }
        }).catch(() => {
            isPollingCashier = false;
        });
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

// Start real-time polling (every 1.5s + on window focus)
if (typeof APP_URL !== 'undefined') {
    pollCashierNotifications();
    setInterval(pollCashierNotifications, 1500);
    window.addEventListener('focus', pollCashierNotifications);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) pollCashierNotifications();
    });
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
// Order Status Update (Cashier) - Instant Real-Time DOM Update
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
                showToast('Status pesanan diperbarui!', 'success');
                cashierOrdersHash = '';
                pollCashierNotifications();
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
                if (res.success) {
                    showToast('Pembayaran dikonfirmasi!', 'success');
                    cashierOrdersHash = '';
                    pollCashierNotifications();
                } else {
                    alertError(res.message);
                }
            });
        }
    });
}
