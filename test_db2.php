<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
try {
    echo "Connecting to MySQL...\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3
    ]);
    echo "Connected to MySQL successfully!\n";
    
    echo "Checking databases...\n";
    $stmt = $pdo->query("SHOW DATABASES");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Database'] . "\n";
    }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
