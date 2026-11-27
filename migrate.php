<?php
require_once __DIR__ . '/includes/database.php';

try {
    // Modify column type
    db()->execute("ALTER TABLE settings MODIFY banner TEXT");

    // Get current banner and convert to JSON array if it's not already
    $settings = db()->fetch("SELECT id, banner FROM settings LIMIT 1");
    if ($settings && !empty($settings['banner'])) {
        $banner = trim($settings['banner']);
        // Check if it's already JSON
        if (strpos($banner, '[') !== 0) {
            $jsonBanner = json_encode([$banner]);
            db()->execute("UPDATE settings SET banner = ? WHERE id = ?", [$jsonBanner, $settings['id']]);
            echo "Successfully migrated banner to JSON format.\n";
        } else {
            echo "Banner is already in JSON format.\n";
        }
    } else {
        echo "No banner to migrate.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
