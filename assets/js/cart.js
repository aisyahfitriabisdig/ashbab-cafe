/**
 * ASHBAB CAFE - Cart JavaScript
 * File: assets/js/cart.js
 */

'use strict';

const Cart = (() => {
    // Add to cart
    function add(menuId, btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        ajaxPost(APP_URL + '/ajax/add_cart.php', {
            menu_id: menuId,
            csrf_token: CSRF_TOKEN
        }, res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cart-plus me-1"></i> Tambah';
            if (res.success) {
                updateCartBadge(res.cart_count);
                showToast(res.message || 'Ditambahkan ke keranjang!', 'success');
            } else {
                showToast(res.message || 'Gagal menambahkan.', 'error');
            }
        }, () => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cart-plus me-1"></i> Tambah';
            showToast('Terjadi kesalahan.', 'error');
        });
    }

    // Update quantity
    function update(menuId, qty) {
        ajaxPost(APP_URL + '/ajax/update_cart.php', {
            menu_id: menuId,
            quantity: qty,
            csrf_token: CSRF_TOKEN
        }, res => {
            if (res.success) {
                refreshCartPage(res);
            } else {
                showToast(res.message || 'Gagal update.', 'error');
            }
        });
    }

    // Remove from cart
    function remove(menuId) {
        ajaxPost(APP_URL + '/ajax/remove_cart.php', {
            menu_id: menuId,
            csrf_token: CSRF_TOKEN
        }, res => {
            if (res.success) {
                const item = document.querySelector(`.cart-item[data-id="${menuId}"]`);
                if (item) {
                    item.style.animation = 'fadeOut .3s ease forwards';
                    setTimeout(() => {
                        item.remove();
                        refreshCartPage(res);
                    }, 300);
                }
                updateCartBadge(res.cart_count);
                showToast('Item dihapus.', 'info');
            }
        });
    }

    function updateCartBadge(count) {
        document.querySelectorAll('#navCartCount').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    }

    function refreshCartPage(res) {
        // Update subtotals
        if (res.items) {
            res.items.forEach(item => {
                const sub = document.querySelector(`.item-subtotal[data-id="${item.menu_id}"]`);
                const qty = document.querySelector(`.qty-num[data-id="${item.menu_id}"]`);
                if (sub) sub.textContent = formatRupiah(item.subtotal);
                if (qty) qty.textContent = item.quantity;
            });
        }
        if (res.subtotal !== undefined) {
            const el = document.getElementById('cartSubtotal');
            if (el) el.textContent = formatRupiah(res.subtotal);
        }
        if (res.tax !== undefined) {
            const el = document.getElementById('cartTax');
            if (el) el.textContent = formatRupiah(res.tax);
        }
        if (res.total !== undefined) {
            const el = document.getElementById('cartTotal');
            if (el) el.textContent = formatRupiah(res.total);
        }
        updateCartBadge(res.cart_count);
    }

    return { add, update, remove };
})();

// ============================================================
// Event Delegation for Cart Page
// ============================================================
document.addEventListener('click', function (e) {
    // Add to cart button
    const addBtn = e.target.closest('.btn-add-cart');
    if (addBtn && addBtn.dataset.id) {
        Cart.add(addBtn.dataset.id, addBtn);
        return;
    }

    // Qty increase
    const qtyPlus = e.target.closest('.qty-plus');
    if (qtyPlus) {
        const id  = qtyPlus.dataset.id;
        const num = document.querySelector(`.qty-num[data-id="${id}"]`);
        if (num) {
            const newQty = parseInt(num.textContent) + 1;
            Cart.update(id, newQty);
            num.textContent = newQty;
        }
        return;
    }

    // Qty decrease
    const qtyMinus = e.target.closest('.qty-minus');
    if (qtyMinus) {
        const id  = qtyMinus.dataset.id;
        const num = document.querySelector(`.qty-num[data-id="${id}"]`);
        if (num) {
            const cur = parseInt(num.textContent);
            if (cur <= 1) {
                confirmDelete('Hapus item dari keranjang?', () => Cart.remove(id));
            } else {
                const newQty = cur - 1;
                Cart.update(id, newQty);
                num.textContent = newQty;
            }
        }
        return;
    }

    // Remove button
    const removeBtn = e.target.closest('.btn-remove-cart');
    if (removeBtn) {
        const id = removeBtn.dataset.id;
        confirmDelete('Hapus item dari keranjang?', () => Cart.remove(id));
    }
});
