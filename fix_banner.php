<?php
require_once __DIR__ . '/includes/database.php';

$settings = db()->fetch('SELECT id, banner FROM settings LIMIT 1');
if ($settings && !empty($settings['banner'])) {
    $bannerStr = $settings['banner'];
    
    // Attempt to decode
    $decoded = json_decode($bannerStr, true);
    if (is_array($decoded)) {
        // Fix any slashes
        $fixed = array_map(function($path) {
            return str_replace('\\/', '/', $path);
        }, $decoded);
        
        $newStr = json_encode($fixed, JSON_UNESCAPED_SLASHES);
        db()->execute('UPDATE settings SET banner=? WHERE id=?', [$newStr, $settings['id']]);
        echo "Fixed successfully. New str: " . $newStr;
    } else {
        echo "Could not decode JSON.";
    }
}
