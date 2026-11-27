/**
 * ASHBAB CAFE - Admin JavaScript
 * File: assets/js/admin.js
 */

'use strict';

// ============================================================
// Notification Polling (Admin)
// ============================================================
let notifCount = 0;
let notifInit  = false;

function pollNotifications() {
    fetch(APP_URL + '/ajax/notifications.php?role=admin')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const badge = document.getElementById('notifBadge');
            const list  = document.getElementById('notifList');
            const count = res.unread_count || 0;

            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }

            if (list && res.notifications) {
                if (res.notifications.length === 0) {
                    list.innerHTML = `<div class="text-center p-4 text-muted"><i class="fa-solid fa-bell-slash fa-2x mb-2 d-block"></i>Tidak ada notifikasi</div>`;
                } else {
                    list.innerHTML = res.notifications.map(n => `
                        <div class="notif-item ${n.status==='unread'?'unread':''}" onclick="markNotifRead(${n.id})">
                            <div class="notif-item-icon"><i class="fa-solid fa-receipt"></i></div>
                            <div>
                                <div class="notif-item-title">${n.title}</div>
                                <div class="notif-item-msg">${n.message}</div>
                                <div class="notif-item-time">${n.created_at}</div>
                            </div>
                        </div>`).join('');
                }
            }

            // New notification arrived
            if (notifInit && count > notifCount) {
                showToast('🔔 Ada pesanan / pembayaran QRIS baru!', 'info');
                const bell = document.getElementById('notifBell');
                if (bell) { bell.classList.add('bell-ring'); setTimeout(() => bell.classList.remove('bell-ring'), 1000); }
            }
            notifCount = count;
            notifInit  = true;
        })
        .catch(() => {});
}

function markNotifRead(id) {
    fetch(APP_URL + '/ajax/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=mark_read&id=${id}&csrf_token=${CSRF_TOKEN}`
    }).then(() => pollNotifications());
}

function markAllRead() {
    fetch(APP_URL + '/ajax/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=mark_all_read&csrf_token=${CSRF_TOKEN}`
    }).then(() => pollNotifications());
}

// Start polling
if (typeof APP_URL !== 'undefined') {
    pollNotifications();
    setInterval(pollNotifications, 10000);
}

// ============================================================
// CRUD: Categories
// ============================================================
function saveCategory(form) {
    const data = new FormData(form);
    ajaxPost(APP_URL + '/ajax/categories.php', data, res => {
        if (res.success) {
            alertSuccess(res.message);
            setTimeout(() => location.reload(), 1200);
        } else {
            alertError(res.message || 'Gagal menyimpan kategori.');
        }
    });
}

function deleteCategory(id) {
    confirmDelete('Kategori akan dihapus beserta semua menu-nya!', () => {
        ajaxPost(APP_URL + '/ajax/categories.php', { action:'delete', id, csrf_token: CSRF_TOKEN }, res => {
            if (res.success) {
                alertSuccess('Kategori dihapus.');
                document.querySelector(`tr[data-cat-id="${id}"]`)?.remove();
            } else {
                alertError(res.message);
            }
        });
    });
}

// ============================================================
// CRUD: Menus
// ============================================================
function deleteMenu(id) {
    confirmDelete('Menu ini akan dihapus permanen!', () => {
        ajaxPost(APP_URL + '/ajax/menus.php', { action:'delete', id, csrf_token: CSRF_TOKEN }, res => {
            if (res.success) {
                alertSuccess('Menu dihapus.');
                document.querySelector(`tr[data-menu-id="${id}"]`)?.remove();
            } else {
                alertError(res.message);
            }
        });
    });
}

function toggleMenuStatus(id, currentStatus) {
    const newStatus = currentStatus === 'available' ? 'unavailable' : 'available';
    ajaxPost(APP_URL + '/ajax/menus.php', { action:'toggle_status', id, status: newStatus, csrf_token: CSRF_TOKEN }, res => {
        if (res.success) {
            showToast(`Status menu diubah ke ${newStatus === 'available' ? 'Tersedia' : 'Tidak Tersedia'}`, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            alertError(res.message);
        }
    });
}

// ============================================================
// Order Management
// ============================================================
function updateOrderStatus(orderId, status) {
    ajaxPost(APP_URL + '/ajax/update_order_status.php', {
        order_id: orderId, status, csrf_token: CSRF_TOKEN
    }, res => {
        if (res.success) {
            showToast('Status pesanan diperbarui.', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            alertError(res.message || 'Gagal update status.');
        }
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

function rejectPayment(orderId) {
    Swal.fire({
        title: 'Tolak Pembayaran',
        input: 'textarea',
        inputLabel: 'Alasan penolakan (opsional)',
        inputPlaceholder: 'Masukkan alasan...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Tolak',
        cancelButtonText: 'Batal',
    }).then(result => {
        if (result.isConfirmed) {
            ajaxPost(APP_URL + '/ajax/update_order_status.php', {
                order_id: orderId, payment_action:'reject', reason: result.value, csrf_token: CSRF_TOKEN
            }, res => {
                if (res.success) { alertSuccess('Pembayaran ditolak.'); setTimeout(() => location.reload(), 1000); }
                else alertError(res.message);
            });
        }
    });
}

// ============================================================
// Expenses CRUD
// ============================================================
function deleteExpense(id) {
    confirmDelete('Data pengeluaran akan dihapus!', () => {
        ajaxPost(APP_URL + '/ajax/expenses.php', { action:'delete', id, csrf_token: CSRF_TOKEN }, res => {
            if (res.success) {
                alertSuccess('Pengeluaran dihapus.');
                document.querySelector(`tr[data-exp-id="${id}"]`)?.remove();
            } else {
                alertError(res.message);
            }
        });
    });
}

// ============================================================
// User Management
// ============================================================
function deleteUser(id) {
    confirmDelete('Akun pengguna akan dihapus!', () => {
        ajaxPost(APP_URL + '/ajax/users.php', { action:'delete', id, csrf_token: CSRF_TOKEN }, res => {
            if (res.success) {
                alertSuccess('Pengguna dihapus.');
                document.querySelector(`tr[data-user-id="${id}"]`)?.remove();
            } else {
                alertError(res.message);
            }
        });
    });
}
