<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$new_password = password_hash('password', PASSWORD_BCRYPT);
try {
    db()->execute("UPDATE users SET password = ?", [$new_password]);
    echo "Password updated successfully to: " . $new_password;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
