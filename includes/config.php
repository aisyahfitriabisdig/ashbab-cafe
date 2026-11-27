<?php
/**
 * ASHBAB CAFE - Application Configuration
 * File: includes/config.php
 */

// ============================================================
// Environment Detection
// ============================================================
define('IS_VERCEL', getenv('VERCEL') === '1' || isset($_SERVER['VERCEL']));
define('IS_PRODUCTION', IS_VERCEL || getenv('APP_ENV') === 'production');

// ============================================================
// Database Configuration (Mendukung DATABASE_URL atau env terpisah)
// ============================================================
$databaseUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: '';

if ($databaseUrl !== '') {
    $parsed = parse_url($databaseUrl);
    define('DB_DRIVER', 'pgsql');
    define('DB_HOST', $parsed['host'] ?? 'localhost');
    define('DB_PORT', (string)($parsed['port'] ?? '5432'));
    define('DB_USER', urldecode($parsed['user'] ?? 'postgres'));
    define('DB_PASS', urldecode($parsed['pass'] ?? ''));
    define('DB_NAME', ltrim($parsed['path'] ?? '/postgres', '/'));
} else {
    $envHost   = getenv('DB_HOST') ?: 'localhost';
    $envDriver = getenv('DB_DRIVER') ?: ($envHost !== 'localhost' && $envHost !== '127.0.0.1' ? 'pgsql' : 'mysql');
    define('DB_DRIVER', $envDriver);
    define('DB_HOST', $envHost);
    define('DB_PORT', getenv('DB_PORT') ?: ($envDriver === 'pgsql' ? '6543' : '3306'));
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    define('DB_NAME', getenv('DB_NAME') ?: 'ashbab_cafe');
}
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// Supabase Storage Configuration (untuk upload gambar di Vercel)
// ============================================================
define('SUPABASE_URL', rtrim(getenv('SUPABASE_URL') ?: '', '/'));
define('SUPABASE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: getenv('SUPABASE_KEY') ?: '');
define('SUPABASE_BUCKET', getenv('SUPABASE_BUCKET') ?: 'uploads');

// ============================================================
// Application Configuration
// ============================================================
define('APP_NAME', 'ASHBAB CAFE');

$envAppUrl = getenv('APP_URL');
if ($envAppUrl) {
    define('APP_URL', rtrim($envAppUrl, '/'));
} elseif (IS_VERCEL && !empty($_SERVER['HTTP_HOST'])) {
    define('APP_URL', 'https://' . $_SERVER['HTTP_HOST']);
} else {
    define('APP_URL', 'http://localhost/ashbab-cafe');
}

define('APP_VERSION', '1.0.0');

// ============================================================
// Path Configuration
// ============================================================
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('EXPORT_PATH', ROOT_PATH . 'exports/');

// ============================================================
// Tax Configuration (from settings table)
// ============================================================
define('DEFAULT_TAX', 10); // 10%

// ============================================================
// Session Configuration
// ============================================================
define('SESSION_TIMEOUT', 3600); // 1 hour

// ============================================================
// Security
// ============================================================
define('CSRF_TOKEN_NAME', 'ashbab_csrf_token');

// ============================================================
// Error Reporting
// ============================================================
if (IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ============================================================
// Timezone
// ============================================================
date_default_timezone_set('Asia/Jakarta');
