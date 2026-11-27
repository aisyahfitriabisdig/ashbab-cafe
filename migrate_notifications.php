<?php
require_once __DIR__ . '/includes/functions.php';

try {
    $db = db()->getConnection();
    
    // Check if target column exists
    $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'target'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE notifications ADD COLUMN target ENUM('admin','customer') NOT NULL DEFAULT 'admin' AFTER order_id");
        echo "Successfully added 'target' column to notifications table.\n";
    } else {
        echo "Column 'target' already exists in notifications table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
