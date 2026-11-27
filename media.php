<?php
/**
 * ASHBAB CAFE - Database Media Endpoint
 * File: media.php
 * Serves images stored in the `uploaded_files` table (fallback when Supabase Storage / local disk is unavailable).
 */
require_once __DIR__ . '/includes/config.php';

$id = $_GET['id'] ?? '';
if (!is_string($id) || $id === '' || !preg_match('/^[a-zA-Z0-9_.-]+$/', $id)) {
    http_response_code(400);
    exit('Bad Request');
}

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => DB_DRIVER === 'pgsql',
        PDO::ATTR_TIMEOUT            => 5,
    ];

    if (DB_DRIVER === 'pgsql') {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require';
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    }

    $pdo  = new PDO($dsn, DB_USER, DB_PASS, $options);
    $stmt = $pdo->prepare('SELECT mime_type, data FROM uploaded_files WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row || empty($row['data'])) {
        http_response_code(404);
        exit('Not Found');
    }

    $mime = strtolower(trim((string)($row['mime_type'] ?? '')));
    if (!str_starts_with($mime, 'image/')) {
        http_response_code(404);
        exit('Not Found');
    }

    $bytes = base64_decode($row['data'], true);
    if ($bytes === false) {
        http_response_code(404);
        exit('Not Found');
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: public, max-age=86400, stale-while-revalidate=604800');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
} catch (Throwable $e) {
    http_response_code(404);
    exit('Not Found');
}
