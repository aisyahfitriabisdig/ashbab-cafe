<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `ashbab_cafe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created or exists.\n";
    
    $pdo->exec("USE `ashbab_cafe`");
    
    // Load SQL
    $sqlFile = __DIR__ . '/database/ashbab_cafe.sql';
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("Could not read SQL file.");
    }
    
    // Split by semicolons or just execute the whole string
    $pdo->exec($sql);
    
    echo "Database imported successfully.\n";

    // Set passwords to 'password' using password_hash with bcrypt 
    // (native to PHP instead of $2b$ which might not work)
    $password = password_hash('password', PASSWORD_BCRYPT);
    $pdo->exec("UPDATE users SET password = '$password'");
    echo "Passwords reset to 'password' for all users.\n";

} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
