<?php
/**
 * ASHBAB CAFE - Cart Page
 * File: cart.php
 */
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Keranjang';
$settings  = getSettings();
$taxPct    = (float)($settings['tax_percent'] ?? 10);

// Build cart items with menu data
$cartItems = [];
$subtotal  = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $menus = db()->fetchAll("SELECT * FROM menus WHERE id IN ($placeholders)", $ids);
    $menuMap = array_column($menus, null, 'id');

    foreach ($_SESSION['cart'] as $menuId => $item) {
        $menu = $menuMap[$menuId] ?? null;
        if (!$menu) continue;
        $itemSubtotal = $menu['price'] * $item['quantity'];
        $subtotal += $itemSubtotal;
        $cartItems[] = [
            'menu'     => $menu,
            'quantity' => $item['quantity'],
            'subtotal' => $itemSubtotal,
        ];
    }
}

$tax   = $subtotal * ($taxPct / 100);
$total = $subtotal + $tax;

require_once __DIR__ . '/includes/header.php';
?>
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="cart-section">
<div class="container">
    <div class="d-flex align-items-center mb-4" data-aos="fade-up">
        <a href="<?= APP_URL ?>/menu.php" class="btn btn-sm me-3" style="background:var(--accent);color:var(--primary);border-radius:50px;padding:8px 18px;font-weight:600">
            <i class="fa-solid fa-arrow-left me-1"></i>Menu
        </a>
        <h3 class="fw-800 mb-0">Keranjang Belanja</h3>
    </div>

    <?php if (empty($cartItems)): ?>
    <!-- Empty Cart -->
    <div class="text-center py-5" data-aos="zoom-in">
        <div style="font-size:5rem">🛒</div>
        <h4 class="fw-700 mt-3">Keranjang Kosong</h4>
        <p class="text-muted mb-4">Anda belum menambahkan menu apapun.</p>
        <a href="<?= APP_URL ?>/menu.php" class="btn-primary-cafe">
            <i class="fa-solid fa-utensils me-2"></i>Lihat Menu
        </a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <!-- Cart Items -->
        <div class="col-lg-8">
            <?php foreach ($cartItems as $item): ?>
            <div class="cart-item" data-id="<?= $item['menu']['id'] ?>">
                <!-- Image -->
                <?php if ($item['menu']['image'] && file_exists(ROOT_PATH . $item['menu']['image'])): ?>
                    <img src="<?= APP_URL . '/' . e($item['menu']['image']) ?>" alt="<?= e($item['menu']['name']) ?>" class="cart-item-img" style="width:80px;height:80px;object-fit:cover;border-radius:10px;flex-shrink:0">
                <?php else: ?>
                    <div class="cart-item-img">🍽️</div>
                <?php endif; ?>

                <!-- Info -->
                <div class="flex-grow-1">
                    <h6 class="fw-700 mb-1"><?= e($item['menu']['name']) ?></h6>
                    <div class="text-muted small"><?= formatRupiah($item['menu']['price']) ?> / item</div>
                </div>

                <!-- Qty Controls -->
                <div class="qty-control">
                    <button class="qty-btn qty-minus" data-id="<?= $item['menu']['id'] ?>">−</button>
                    <span class="qty-num" data-id="<?= $item['menu']['id'] ?>"><?= $item['quantity'] ?></span>
                    <button class="qty-btn qty-plus" data-id="<?= $item['menu']['id'] ?>">+</button>
                </div>

                <!-- Subtotal -->
                <div class="text-end ms-3">
                    <div class="fw-700" style="color:var(--primary)" data-id="<?= $item['menu']['id'] ?>" class="item-subtotal">
                        <?= formatRupiah($item['subtotal']) ?>
                    </div>
                    <button class="btn btn-sm btn-danger mt-1 btn-remove-cart" data-id="<?= $item['menu']['id'] ?>" title="Hapus">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Notes -->
            <div class="admin-card mt-3">
                <label class="form-label fw-700"><i class="fa-solid fa-note-sticky me-2" style="color:var(--primary)"></i>Catatan Pesanan</label>
                <textarea class="form-control" id="orderNotes" rows="3" placeholder="Contoh: tanpa es, less sugar, dll..." style="resize:none"><?= e($_SESSION['order_notes'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="order-summary" data-aos="fade-left">
                <h5 class="fw-800 mb-4"><i class="fa-solid fa-receipt me-2" style="color:var(--primary)"></i>Ringkasan</h5>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="cartSubtotal"><?= formatRupiah($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Pajak (<?= $taxPct ?>%)</span>
                    <span id="cartTax"><?= formatRupiah($tax) ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span id="cartTotal"><?= formatRupiah($total) ?></span>
                </div>
                <a href="<?= APP_URL ?>/checkout.php" class="btn-primary-cafe w-100 justify-content-center mt-4 d-flex"
                   onclick="saveNotes()">
                    <i class="fa-solid fa-arrow-right me-2"></i>Lanjut Checkout
                </a>
                <a href="<?= APP_URL ?>/menu.php" class="btn w-100 mt-2 text-center" style="color:var(--primary);font-weight:600">
                    <i class="fa-solid fa-plus me-1"></i>Tambah Menu
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>

<footer class="site-footer">
    <p class="mb-0"><?= e($settings['footer_text'] ?? '© '.date('Y').' ASHBAB CAFE') ?></p>
</footer>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
    const APP_URL    = '<?= APP_URL ?>';
    const CSRF_TOKEN = '<?= generateCSRF() ?>';

    function saveNotes() {
        const notes = document.getElementById('orderNotes')?.value;
        if (notes !== undefined) {
            fetch(APP_URL + '/ajax/update_cart.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'action=save_notes&notes='+encodeURIComponent(notes)+'&csrf_token='+CSRF_TOKEN
            });
        }
    }
</script>
<script src="<?= APP_URL ?>/assets/js/cart.js"></script>
