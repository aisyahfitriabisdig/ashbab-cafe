<?php
/**
 * ASHBAB CAFE - PostgreSQL Session Handler (dioptimalkan untuk Vercel Serverless)
 * File: includes/session_handler.php
 */

class PgSessionHandler implements SessionHandlerInterface {
    private PDO $pdo;
    private string $lastReadData = '';
    private int $lastActivity = 0;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        // Jika belum ada cookie session, tidak perlu query ke DB
        if (empty($_COOKIE[session_name()])) {
            $this->lastReadData = '';
            $this->lastActivity = 0;
            return '';
        }

        $stmt = $this->pdo->prepare("SELECT data, last_activity FROM sessions WHERE id = ? AND last_activity >= ?");
        $stmt->execute([$id, time() - SESSION_TIMEOUT]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->lastReadData = $row['data'];
            $this->lastActivity = (int)$row['last_activity'];
            return $row['data'];
        }

        $this->lastReadData = '';
        $this->lastActivity = 0;
        return '';
    }

    public function write(string $id, string $data): bool {
        // Jangan tulis ke DB jika session kosong dan sebelumnya juga kosong
        if ($data === '' && $this->lastReadData === '') {
            return true;
        }
        // Jangan tulis ulang jika data tidak berubah & baru diperbarui < 120 detik lalu
        if ($data === $this->lastReadData && (time() - $this->lastActivity) < 120) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
             ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity"
        );
        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_activity < ?");
        $stmt->execute([time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}
