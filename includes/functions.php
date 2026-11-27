<?php
/**
 * ASHBAB CAFE - Helper Functions
 * File: includes/functions.php
 */

require_once __DIR__ . '/database.php';

// ============================================================
// CSRF Token
// ============================================================
function generateCSRF(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRF(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

// ============================================================
// XSS Protection
// ============================================================
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function clean(string $str): string {
    return trim(strip_tags($str));
}

// ============================================================
// Currency Format
// ============================================================
function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// ============================================================
// Order Number Generator
// ============================================================
function generateOrderNumber(): string {
    $prefix = 'ASH';
    $date = date('Ymd');
    // Get count of today's orders
    $count = db()->fetch(
        "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURRENT_DATE"
    )['total'] ?? 0;
    $seq = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    return "{$prefix}-{$date}{$seq}";
}

// ============================================================
// File Upload (Supabase Storage -> Lokal Disk -> Database Fallback)
// ============================================================
$GLOBALS['last_upload_error'] = '';

function getLastUploadError(): string {
    return (string)($GLOBALS['last_upload_error'] ?? '');
}

function normalizeImageMime(string $mime): string {
    $mime = strtolower(trim(explode(';', $mime)[0]));
    $map = [
        'image/jpg'   => 'image/jpeg',
        'image/pjpeg' => 'image/jpeg',
        'image/x-png' => 'image/png',
        'image/x-icon' => 'image/vnd.microsoft.icon',
    ];
    return $map[$mime] ?? $mime;
}

function ensureSupabaseBucketPublic(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    if (DB_DRIVER === 'pgsql' && SUPABASE_BUCKET !== '') {
        try {
            db()->execute(
                "INSERT INTO storage.buckets (id, name, public) VALUES (?, ?, true)
                 ON CONFLICT (id) DO UPDATE SET public = true",
                [SUPABASE_BUCKET, SUPABASE_BUCKET]
            );
        } catch (Throwable $e) {
            // Ignore if storage schema is not accessible by current DB role
        }
    }
}

function ensureUploadedFilesTable(): void {
    static $ensured = false;
    if ($ensured) return;

    if (DB_DRIVER === 'pgsql') {
        db()->execute(
            "CREATE TABLE IF NOT EXISTS uploaded_files (
                id VARCHAR(191) PRIMARY KEY,
                mime_type VARCHAR(100) NOT NULL,
                data TEXT NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
    } else {
        db()->execute(
            "CREATE TABLE IF NOT EXISTS uploaded_files (
                id VARCHAR(191) PRIMARY KEY,
                mime_type VARCHAR(100) NOT NULL,
                data LONGTEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
    $ensured = true;
}

function saveUploadedFileToDb(string $id, string $mimeType, string $binaryContent): bool {
    try {
        ensureUploadedFilesTable();
        $encoded = base64_encode($binaryContent);
        if (DB_DRIVER === 'pgsql') {
            db()->execute(
                "INSERT INTO uploaded_files (id, mime_type, data) VALUES (?, ?, ?)
                 ON CONFLICT (id) DO UPDATE SET mime_type = EXCLUDED.mime_type, data = EXCLUDED.data",
                [$id, $mimeType, $encoded]
            );
        } else {
            db()->execute(
                "INSERT INTO uploaded_files (id, mime_type, data) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE mime_type = VALUES(mime_type), data = VALUES(data)",
                [$id, $mimeType, $encoded]
            );
        }
        return true;
    } catch (Throwable $e) {
        $GLOBALS['last_upload_error'] = 'Gagal menyimpan file ke database.';
        return false;
    }
}

function deleteUploadedFile(?string $path): void {
    if (!$path) return;
    $path = trim($path);
    if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
        return;
    }
    if (str_starts_with($path, 'media.php')) {
        parse_str((string)(parse_url($path, PHP_URL_QUERY) ?? ''), $q);
        $id = $q['id'] ?? '';
        if (is_string($id) && preg_match('/^[a-zA-Z0-9_.-]+$/', $id)) {
            try {
                db()->execute("DELETE FROM uploaded_files WHERE id = ?", [$id]);
            } catch (Throwable $e) {
                // Ignore if table doesn't exist
            }
        }
        return;
    }
    $localFile = ROOT_PATH . ltrim($path, '/');
    if (file_exists($localFile)) {
        @unlink($localFile);
    }
}

function uploadFile(
    array $file,
    string $folder = 'uploads',
    array $allowedTypes = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/webp', 'image/gif']
): ?string {
    $GLOBALS['last_upload_error'] = '';

    $errCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errCode !== UPLOAD_ERR_OK) {
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $GLOBALS['last_upload_error'] = 'Ukuran file melebihi batas server.';
        } elseif ($errCode === UPLOAD_ERR_NO_FILE) {
            $GLOBALS['last_upload_error'] = 'Tidak ada file yang dipilih.';
        } else {
            $GLOBALS['last_upload_error'] = 'Terjadi kesalahan saat mengunggah file (kode ' . (int)$errCode . ').';
        }
        return null;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $GLOBALS['last_upload_error'] = 'Ukuran file maksimal 5MB.';
        return null;
    }

    $tmpName = $file['tmp_name'] ?? '';
    $detectedMime = '';
    if ($tmpName !== '' && is_file($tmpName) && function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detectedMime = (string)@finfo_file($finfo, $tmpName);
            @finfo_close($finfo);
        }
    }
    $mime = normalizeImageMime($detectedMime !== '' ? $detectedMime : (string)($file['type'] ?? ''));
    $normalizedAllowed = array_unique(array_map('normalizeImageMime', $allowedTypes));

    if ($mime === '' || !in_array($mime, $normalizedAllowed, true)) {
        $GLOBALS['last_upload_error'] = 'Format gambar tidak didukung (gunakan JPG, PNG, WEBP, atau GIF).';
        return null;
    }

    $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($folder, '/')) ?: 'uploads';

    $extFromName = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $extMap = [
        'image/jpeg'               => 'jpg',
        'image/png'                => 'png',
        'image/webp'               => 'webp',
        'image/gif'                => 'gif',
        'image/vnd.microsoft.icon' => 'ico',
        'image/svg+xml'            => 'svg',
    ];
    $validExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'ico', 'svg'];
    $ext = in_array($extFromName, $validExts, true) ? $extFromName : ($extMap[$mime] ?? 'png');
    $filename = uniqid('', true) . '_' . time() . '.' . $ext;
    $filename = str_replace('.', '', pathinfo($filename, PATHINFO_FILENAME)) . '.' . $ext;

    $content = null;

    // Tier 1: Upload ke Supabase Storage jika konfigurasi tersedia
    if (SUPABASE_URL !== '' && SUPABASE_KEY !== '') {
        ensureSupabaseBucketPublic();
        $objectPath = $folder . '/' . $filename;
        $endpoint   = SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $objectPath;
        $content    = @file_get_contents($tmpName);

        if ($content !== false) {
            $httpCode = 0;
            if (function_exists('curl_init')) {
                $ch = curl_init($endpoint);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $content,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: Bearer ' . SUPABASE_KEY,
                        'apikey: ' . SUPABASE_KEY,
                        'Content-Type: ' . $mime,
                        'x-upsert: true',
                    ],
                ]);
                curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            } else {
                $ctx = stream_context_create([
                    'http' => [
                        'method'        => 'POST',
                        'header'        => "Authorization: Bearer " . SUPABASE_KEY . "\r\n"
                                         . "apikey: " . SUPABASE_KEY . "\r\n"
                                         . "Content-Type: " . $mime . "\r\n"
                                         . "x-upsert: true\r\n",
                        'content'       => $content,
                        'timeout'       => 15,
                        'ignore_errors' => true,
                    ],
                ]);
                @file_get_contents($endpoint, false, $ctx);
                if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                    $httpCode = (int)$m[1];
                }
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $objectPath;
            }
        }
    }

    // Tier 2: Local disk (non-Vercel / XAMPP)
    if (!IS_VERCEL) {
        $uploadDir = ROOT_PATH . 'assets/uploads/' . $folder . '/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        if (is_dir($uploadDir) && is_writable($uploadDir)) {
            $destination = $uploadDir . $filename;
            if (@move_uploaded_file($tmpName, $destination)) {
                return 'assets/uploads/' . $folder . '/' . $filename;
            }
        }
    }

    // Tier 3: Database media storage fallback (Vercel tanpa Supabase Storage atau fallback)
    if ($content === null) {
        $content = @file_get_contents($tmpName);
    }
    if ($content !== false) {
        $key = $folder . '_' . $filename;
        if (saveUploadedFileToDb($key, $mime, $content)) {
            return 'media.php?id=' . $key;
        }
    }

    if ($GLOBALS['last_upload_error'] === '') {
        $GLOBALS['last_upload_error'] = 'Gagal menyimpan file gambar.';
    }
    return null;
}

function assetUrl(?string $path): string {
    if (!$path) return '';
    $path = trim($path);
    if ($path === '') return '';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
        return $path;
    }
    return APP_URL . '/' . ltrim($path, '/');
}

function assetExists(?string $path): bool {
    if (!$path) return false;
    $path = trim($path);
    if ($path === '') return false;
    if (
        str_starts_with($path, 'http://') ||
        str_starts_with($path, 'https://') ||
        str_starts_with($path, 'data:') ||
        str_starts_with($path, 'media.php')
    ) {
        return true;
    }
    return file_exists(ROOT_PATH . ltrim($path, '/'));
}

// ============================================================
// Settings
// ============================================================
function ensureMenuBgColumn(): void {
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;

    try {
        if (DB_DRIVER === 'pgsql') {
            db()->execute("ALTER TABLE settings ADD COLUMN IF NOT EXISTS menu_bg_config TEXT DEFAULT NULL");
        } else {
            $col = db()->fetch("SHOW COLUMNS FROM settings LIKE 'menu_bg_config'");
            if (!$col) {
                db()->execute("ALTER TABLE settings ADD COLUMN menu_bg_config TEXT DEFAULT NULL");
            }
        }
    } catch (Throwable $e) {
        // Ignore if already exists
    }
}

function getSettings(bool $refresh = false): array {
    static $settings = null;
    if ($settings === null || $refresh) {
        $settings = db()->fetch("SELECT * FROM settings LIMIT 1") ?? [];
        if (!empty($settings) && !array_key_exists('menu_bg_config', $settings)) {
            ensureMenuBgColumn();
            $settings = db()->fetch("SELECT * FROM settings LIMIT 1") ?? $settings;
        }
    }
    return $settings;
}

function getSetting(string $key, string $default = ''): string {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

function getMenuBgConfig(): array {
    $settings = getSettings();
    $defaults = [
        'enabled'         => true,
        'mode'            => 'same', // 'same' | 'separate'
        'position'        => 'center center',
        'size'            => 'cover',
        'attachment'      => 'scroll',
        'overlay_opacity' => 75,
        'images_shared'   => [],
        'images_home'     => [],
        'images_menu'     => [],
    ];

    if (empty($settings['menu_bg_config'])) {
        return $defaults;
    }

    $decoded = json_decode((string)$settings['menu_bg_config'], true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    $allowedPositions   = ['center center', 'top center', 'bottom center', 'left center', 'right center'];
    $allowedSizes       = ['cover', 'contain'];
    $allowedAttachments = ['scroll', 'fixed'];

    $pos = in_array($decoded['position'] ?? '', $allowedPositions, true) ? $decoded['position'] : 'center center';
    $sz  = in_array($decoded['size'] ?? '', $allowedSizes, true) ? $decoded['size'] : 'cover';
    $att = in_array($decoded['attachment'] ?? '', $allowedAttachments, true) ? $decoded['attachment'] : 'scroll';
    $op  = isset($decoded['overlay_opacity']) ? max(0, min(95, (int)$decoded['overlay_opacity'])) : 75;

    $cleanList = static function ($list): array {
        if (!is_array($list)) return [];
        $out = [];
        foreach ($list as $item) {
            $item = trim((string)$item);
            if ($item !== '') $out[] = $item;
        }
        return array_values($out);
    };

    return [
        'enabled'         => !empty($decoded['enabled']),
        'mode'            => ($decoded['mode'] ?? 'same') === 'separate' ? 'separate' : 'same',
        'position'        => $pos,
        'size'            => $sz,
        'attachment'      => $att,
        'overlay_opacity' => $op,
        'images_shared'   => $cleanList($decoded['images_shared'] ?? []),
        'images_home'     => $cleanList($decoded['images_home'] ?? []),
        'images_menu'     => $cleanList($decoded['images_menu'] ?? []),
    ];
}

function getMenuBgImagesForTarget(string $target = 'home'): array {
    $cfg = getMenuBgConfig();
    if (!$cfg['enabled']) {
        return [];
    }
    if ($cfg['mode'] === 'separate') {
        if ($target === 'menu') {
            return !empty($cfg['images_menu']) ? $cfg['images_menu'] : $cfg['images_shared'];
        }
        return !empty($cfg['images_home']) ? $cfg['images_home'] : $cfg['images_shared'];
    }
    return $cfg['images_shared'];
}

function renderMenuSectionBg(string $target = 'home'): string {
    $cfg    = getMenuBgConfig();
    $images = getMenuBgImagesForTarget($target);
    if (!$cfg['enabled'] || empty($images)) {
        return '';
    }

    $pos = e($cfg['position']);
    $sz  = e($cfg['size']);
    $att = e($cfg['attachment']);

    $midAlpha  = round(max(0, min(95, (int)$cfg['overlay_opacity'])) / 100, 2);
    $edgeAlpha = round(min(0.96, $midAlpha + 0.14), 2);
    $uid       = 'menuBg_' . preg_replace('/[^a-zA-Z0-9]/', '', $target);

    $html  = '<div class="menu-section-bg-wrap" id="' . $uid . '" aria-hidden="true">';
    foreach ($images as $idx => $imgPath) {
        $activeClass = $idx === 0 ? ' active' : '';
        $url         = e(assetUrl($imgPath));
        $html .= '<div class="menu-section-bg-slide' . $activeClass . '" style="background-image:url(\'' . $url . '\');background-position:' . $pos . ';background-size:' . $sz . ';background-attachment:' . $att . ';"></div>';
    }
    $html .= '<div class="menu-section-bg-overlay" style="background:linear-gradient(180deg, rgba(10,10,10,' . $edgeAlpha . ') 0%, rgba(15,15,15,' . $midAlpha . ') 50%, rgba(10,10,10,' . $edgeAlpha . ') 100%);"></div>';
    $html .= '</div>';

    if (count($images) > 1) {
        $html .= '<script>
document.addEventListener("DOMContentLoaded", function() {
    const wrap = document.getElementById("' . $uid . '");
    if (!wrap) return;
    const slides = wrap.querySelectorAll(".menu-section-bg-slide");
    if (slides.length <= 1) return;
    let idx = 0;
    setInterval(function() {
        slides[idx].classList.remove("active");
        idx = (idx + 1) % slides.length;
        slides[idx].classList.add("active");
    }, 5500);
});
</script>';
    }

    return $html;
}

function renderSiteFooter(): string {
    $settings   = getSettings();
    $cafeName   = e($settings['cafe_name'] ?? 'ASHBAB CAFE');
    $address    = e($settings['address'] ?? 'Jl. Contoh No. 123, Indonesia');
    $phone      = e($settings['phone'] ?? '08123456789');
    $email      = e($settings['email'] ?? 'ashbabcafe@email.com');
    $footerText = e($settings['footer_text'] ?? ('© ' . date('Y') . ' ' . ($settings['cafe_name'] ?? 'ASHBAB CAFE') . '. All Rights Reserved.'));
    $logoUrl    = !empty($settings['logo']) ? e(assetUrl($settings['logo'])) : (APP_URL . '/assets/uploads/ashbab_logo.png');
    $appUrl     = APP_URL;

    return <<<HTML
<footer class="site-footer">
    <div class="site-footer-glow" aria-hidden="true"></div>
    <div class="container position-relative" style="z-index:2">
        <div class="row g-4 pb-4">
            <!-- Brand Info -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="{$logoUrl}" alt="{$cafeName}" class="footer-brand-logo">
                    <div>
                        <h5 class="footer-brand-title mb-0">{$cafeName}</h5>
                        <span class="footer-brand-tag">Coffee &amp; Eatery</span>
                    </div>
                </div>
                <p class="footer-desc mb-3">
                    Tempat terbaik menikmati kopi pilihan dan hidangan berkualitas dengan suasana nyaman serta layanan pemesanan digital langsung dari meja Anda.
                </p>
                <div class="footer-socials d-flex align-items-center gap-2">
                    <a href="https://www.instagram.com/ashbab.coffee" target="_blank" rel="noopener" class="footer-social-btn" title="Instagram @ashbab.coffee">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="tel:{$phone}" class="footer-social-btn" title="Telepon">
                        <i class="fa-solid fa-phone"></i>
                    </a>
                    <a href="mailto:{$email}" class="footer-social-btn" title="Email">
                        <i class="fa-solid fa-envelope"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-heading">Navigasi</h6>
                <ul class="footer-links list-unstyled mb-0">
                    <li><a href="{$appUrl}/"><i class="fa-solid fa-chevron-right"></i>Beranda</a></li>
                    <li><a href="{$appUrl}/menu.php"><i class="fa-solid fa-chevron-right"></i>Menu Kami</a></li>
                    <li><a href="{$appUrl}/tracking.php"><i class="fa-solid fa-chevron-right"></i>Lacak Pesanan</a></li>
                    <li><a href="{$appUrl}/about.php"><i class="fa-solid fa-chevron-right"></i>Tentang Kami</a></li>
                    <li><a href="{$appUrl}/cart.php"><i class="fa-solid fa-chevron-right"></i>Keranjang</a></li>
                </ul>
            </div>

            <!-- Opening Hours -->
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="footer-heading">Jam Operasional</h6>
                <ul class="footer-hours list-unstyled mb-3">
                    <li class="d-flex justify-content-between">
                        <span>Senin – Jumat</span>
                        <strong class="text-white">08.00 – 22.00</strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Sabtu – Minggu</span>
                        <strong class="text-white">08.00 – 23.00</strong>
                    </li>
                </ul>
                <div class="footer-open-badge">
                    <span class="footer-open-dot"></span> Buka Setiap Hari
                </div>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">Kontak &amp; Lokasi</h6>
                <ul class="footer-contact list-unstyled mb-0">
                    <li>
                        <span class="footer-contact-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <span>{$address}</span>
                    </li>
                    <li>
                        <span class="footer-contact-icon"><i class="fa-solid fa-phone"></i></span>
                        <a href="tel:{$phone}">{$phone}</a>
                    </li>
                    <li>
                        <span class="footer-contact-icon"><i class="fa-solid fa-envelope"></i></span>
                        <a href="mailto:{$email}">{$email}</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="site-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 pt-3">
            <p class="mb-0 footer-copy">{$footerText}</p>
            <div class="d-flex align-items-center gap-3">
                <span class="footer-payment-pill"><i class="fa-solid fa-qrcode me-1" style="color:var(--primary)"></i>QRIS Digital Ready</span>
                <a href="#" class="footer-top-btn" title="Kembali ke atas" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">
                    <i class="fa-solid fa-arrow-up"></i>
                </a>
            </div>
        </div>
    </div>
</footer>
HTML;
}

// ============================================================
// Status Badges
// ============================================================
function orderStatusBadge(string $status): string {
    $badges = [
        'waiting'    => ['warning', 'Menunggu Konfirmasi'],
        'accepted'   => ['info', 'Diterima'],
        'processing' => ['primary', 'Diproses'],
        'completed'  => ['success', 'Selesai'],
        'cancelled'  => ['danger', 'Dibatalkan'],
    ];
    $b = $badges[$status] ?? ['secondary', $status];
    return '<span class="badge bg-' . $b[0] . '">' . $b[1] . '</span>';
}

function paymentStatusBadge(string $status): string {
    $badges = [
        'pending'   => ['warning', 'Menunggu'],
        'confirmed' => ['success', 'Dikonfirmasi'],
        'rejected'  => ['danger', 'Ditolak'],
    ];
    $b = $badges[$status] ?? ['secondary', $status];
    return '<span class="badge bg-' . $b[0] . '">' . $b[1] . '</span>';
}

// ============================================================
// Pagination
// ============================================================
function paginate(string $table, string $where = '', array $params = [], int $perPage = 10): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;

    $countSql = "SELECT COUNT(*) as total FROM $table " . ($where ? "WHERE $where" : '');
    $total = (int)(db()->fetch($countSql, $params)['total'] ?? 0);
    $totalPages = (int)ceil($total / $perPage);

    return [
        'page'       => $page,
        'per_page'   => $perPage,
        'total'      => $total,
        'total_pages'=> $totalPages,
        'offset'     => $offset,
        'limit'      => $perPage,
    ];
}

// ============================================================
// Date Helpers
// ============================================================
function formatDate(string $date, string $format = 'd M Y H:i'): string {
    return date($format, strtotime($date));
}

function indonesianDate(string $date): string {
    $months = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];
    $d = date('d', strtotime($date));
    $m = $months[(int)date('m', strtotime($date))];
    $y = date('Y', strtotime($date));
    return "$d $m $y";
}

// ============================================================
// JSON Response
// ============================================================
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ============================================================
// Redirect
// ============================================================
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// ============================================================
// Flash Messages
// ============================================================
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================================
// Dashboard Stats
// ============================================================
function getDashboardStats(): array {
    $today = date('Y-m-d');
    $month = date('Y-m');
    $year  = date('Y');

    $totalOrders    = db()->fetch("SELECT COUNT(*) as c FROM orders")['c'] ?? 0;
    $totalMenus     = db()->fetch("SELECT COUNT(*) as c FROM menus")['c'] ?? 0;
    $totalIncome    = db()->fetch("SELECT COALESCE(SUM(total_amount),0) as c FROM orders WHERE order_status='completed'")['c'] ?? 0;
    $totalExpenses  = db()->fetch("SELECT COALESCE(SUM(amount),0) as c FROM expenses")['c'] ?? 0;
    $todayIncome    = db()->fetch("SELECT COALESCE(SUM(total_amount),0) as c FROM orders WHERE order_status='completed' AND DATE(created_at)=?", [$today])['c'] ?? 0;
    $monthStart     = $month . '-01';
    $nextMonthStart = date('Y-m-d', strtotime($monthStart . ' +1 month'));
    $monthIncome    = db()->fetch("SELECT COALESCE(SUM(total_amount),0) as c FROM orders WHERE order_status='completed' AND created_at >= ? AND created_at < ?", [$monthStart, $nextMonthStart])['c'] ?? 0;

    return [
        'total_orders'   => $totalOrders,
        'total_menus'    => $totalMenus,
        'total_income'   => $totalIncome,
        'total_expenses' => $totalExpenses,
        'total_profit'   => $totalIncome - $totalExpenses,
        'today_income'   => $todayIncome,
        'month_income'   => $monthIncome,
    ];
}
