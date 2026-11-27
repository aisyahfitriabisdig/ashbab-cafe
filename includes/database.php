<?php
/**
 * ASHBAB CAFE - Database Connection (PDO MySQL & PostgreSQL/Supabase)
 * File: includes/database.php
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => DB_DRIVER === 'pgsql', // Penting untuk PgBouncer / Supabase Transaction Pooler
            PDO::ATTR_TIMEOUT            => 5,
        ];

        if (DB_DRIVER === 'pgsql') {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
            try {
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (IS_PRODUCTION) {
                    die("Database connection error. Please check environment configuration.");
                }
                die("PostgreSQL Connection Error: " . $e->getMessage());
            }
        } else {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            try {
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Auto-heal lokal untuk XAMPP MySQL
                try {
                    $tempPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, $options);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $tempPdo->exec("USE `" . DB_NAME . "`");
                    $sqlFile = ROOT_PATH . 'database/ashbab_cafe.sql';
                    if (file_exists($sqlFile)) {
                        $tempPdo->exec(file_get_contents($sqlFile));
                    }
                    $this->pdo = $tempPdo;
                } catch (Exception $ex) {
                    die("Database Error. Please run XAMPP MySQL. Details: " . $ex->getMessage());
                }
            }
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): int {
        if (DB_DRIVER === 'pgsql' && stripos($sql, 'RETURNING') === false) {
            $sql = rtrim($sql, "; \t\n\r\0\x0B") . ' RETURNING id';
            $stmt = $this->query($sql, $params);
            return (int)$stmt->fetchColumn();
        }
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }
}

// Global shorthand
function db(): Database {
    return Database::getInstance();
}

// ============================================================
// Start Session (gunakan PostgreSQL session handler di pgsql)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    if (DB_DRIVER === 'pgsql') {
        require_once __DIR__ . '/session_handler.php';
        $handler = new PgSessionHandler(Database::getInstance()->getConnection());
        session_set_save_handler($handler, true);
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || IS_VERCEL;
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
