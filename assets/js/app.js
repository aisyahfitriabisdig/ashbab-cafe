/**
 * ASHBAB CAFE - Main App JavaScript
 * File: assets/js/app.js
 */

'use strict';

// ============================================================
// Page Loader
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        setTimeout(() => loader.classList.add('hidden'), 300);
    }
});

// ============================================================
// Navbar Scroll Effect
// ============================================================
const navbar = document.getElementById('mainNavbar');
if (navbar) {
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    });
}

// ============================================================
// Dark Mode (Admin)
// ============================================================
const darkToggle = document.getElementById('darkModeToggle');
const darkIcon   = document.getElementById('darkModeIcon');
const darkText   = document.getElementById('darkModeText');

function applyDarkMode(enabled) {
    document.body.classList.toggle('dark-mode', enabled);
    if (darkIcon) darkIcon.className = enabled ? 'fa-solid fa-sun me-2' : 'fa-solid fa-moon me-2';
    if (darkText) darkText.textContent = enabled ? 'Light Mode' : 'Dark Mode';
    localStorage.setItem('darkMode', enabled ? '1' : '0');
}

if (darkToggle) {
    const saved = localStorage.getItem('darkMode') === '1';
    applyDarkMode(saved);
    darkToggle.addEventListener('click', () => {
        applyDarkMode(!document.body.classList.contains('dark-mode'));
    });
}

// ============================================================
// Sidebar Toggle
// ============================================================
const sidebar         = document.getElementById('sidebar');
const sidebarOverlay  = document.getElementById('sidebarOverlay');
const topbarToggle    = document.getElementById('topbarToggle');
const adminContent    = document.querySelector('.admin-content');

function isMobile() { return window.innerWidth < 993; }

if (topbarToggle && sidebar) {
    topbarToggle.addEventListener('click', () => {
        if (isMobile()) {
            sidebar.classList.toggle('mobile-open');
            sidebarOverlay?.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            adminContent?.classList.toggle('shifted');
        }
    });
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', () => {
        sidebar?.classList.remove('mobile-open');
        sidebarOverlay.classList.remove('show');
    });
}

// ============================================================
// Toast Notification
// ============================================================
function showToast(message, type = 'success', duration = 3500) {
    const icons = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-circle-info', warning:'fa-triangle-exclamation' };
    const colors = { success:'#10b981', error:'#ef4444', info:'#3b82f6', warning:'#f59e0b' };

    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }

    const id = 'toast_' + Date.now();
    const toast = document.createElement('div');
    toast.id = id;
    toast.innerHTML = `
        <div class="d-flex align-items-center gap-3 custom-toast" style="min-width:280px">
            <i class="fa-solid ${icons[type] || icons.info} fa-lg" style="color:${colors[type] || colors.info}"></i>
            <span style="font-size:.9rem;font-weight:500;flex:1">${message}</span>
            <button type="button" class="btn-close btn-close-sm" onclick="document.getElementById('${id}').remove()"></button>
        </div>`;
    container.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast, { delay: duration, autohide: true });
    // Use as plain div with auto-remove
    setTimeout(() => toast.remove(), duration + 500);
    // Simple show
    toast.style.cssText = 'animation:slideInRight .35s ease';
}

// ============================================================
// Sweet Alert Helpers
// ============================================================
function confirmDelete(message, callback) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: message || 'Data akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        borderRadius: '16px',
    }).then(result => { if (result.isConfirmed && callback) callback(); });
}

function alertSuccess(msg) {
    Swal.fire({ icon:'success', title:'Berhasil!', text:msg, timer:2000, showConfirmButton:false });
}

function alertError(msg) {
    Swal.fire({ icon:'error', title:'Gagal!', text:msg });
}

// ============================================================
// AJAX Helper
// ============================================================
function ajaxPost(url, data, onSuccess, onError) {
    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: data instanceof FormData ? data : new URLSearchParams(data),
    })
    .then(r => r.json())
    .then(res => {
        if (onSuccess) onSuccess(res);
    })
    .catch(err => {
        console.error('AJAX error:', err);
        if (onError) onError(err);
    });
}

// ============================================================
// Format Rupiah
// ============================================================
function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

// ============================================================
// Animate Counter (Dashboard Stats)
// ============================================================
function animateCounter(el, target, prefix = '', suffix = '') {
    const duration = 1200;
    const start = performance.now();
    const update = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        el.textContent = prefix + Math.floor(ease * target).toLocaleString('id-ID') + suffix;
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}

document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseFloat(el.dataset.counter) || 0;
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    animateCounter(el, target, prefix, suffix);
});

// ============================================================
// Image Preview on Upload
// ============================================================
function initImagePreview(inputId, previewId) {
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;
    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(file);
    });
}

// ============================================================
// DataTable Default Init
// ============================================================
function initDataTable(tableId, options = {}) {
    const defaults = {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
        },
        responsive: true,
        pageLength: 10,
        ...options
    };
    if ($.fn.DataTable.isDataTable('#' + tableId)) return;
    return $('#' + tableId).DataTable(defaults);
}

// ============================================================
// Smooth Scroll
// ============================================================
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
