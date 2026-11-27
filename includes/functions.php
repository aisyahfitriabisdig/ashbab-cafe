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
// File Upload (Supabase Storage di Vercel, lokal di XAMPP)
// ============================================================
function uploadFile(array $file, string $folder = 'uploads', array $allowedTypes = ['image/jpeg','image/png','image/webp']): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if (!in_array($file['type'], $allowedTypes)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // 5MB max

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;

    // Upload ke Supabase Storage jika konfigurasi tersedia
    if (SUPABASE_URL !== '' && SUPABASE_KEY !== '') {
        $objectPath = trim($folder, '/') . '/' . $filename;
        $endpoint   = SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $objectPath;
        $content    = file_get_contents($file['tmp_name']);
        if ($content === false) return null;

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $content,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . SUPABASE_KEY,
                'apikey: ' . SUPABASE_KEY,
                'Content-Type: ' . $file['type'],
                'x-upsert: true',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $objectPath;
        }
        return null;
    }

    $uploadDir = ROOT_PATH . 'assets/uploads/' . $folder . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $destination = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'assets/uploads/' . $folder . '/' . $filename;
    }
    return null;
}

function assetUrl(?string $path): string {
    if (!$path) return '';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return APP_URL . '/' . ltrim($path, '/');
}

// ============================================================
// Settings
// ============================================================
function getSettings(): array {
    static $settings = null;
    if ($settings === null) {
        $settings = db()->fetch("SELECT * FROM settings LIMIT 1") ?? [];
    }
    return $settings;
}

function getSetting(string $key, string $default = ''): string {
    $settings = getSettings();
    return $settings[$key] ?? $default;
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
