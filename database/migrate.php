#!/usr/bin/env php
<?php
/**
 * Database migration runner
 * Usage: php database/migrate.php
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Core\Env;

echo "DocEditor — Database Migration\n";
echo "================================\n\n";

$host   = Env::get('DB_HOST',     '127.0.0.1');
$port   = Env::get('DB_PORT',     '3306');
$dbname = Env::get('DB_DATABASE', 'doceditor');
$user   = Env::get('DB_USERNAME', 'root');
$pass   = Env::get('DB_PASSWORD', '');

echo "Host:     $host:$port\n";
echo "Database: $dbname\n";
echo "User:     $user\n\n";

// Connect without specifying database first, to create it if needed
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$dbname' ready.\n";
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Run all .sql files in order
$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    echo "Running: $name ... ";
    try {
        // Connect to the database now
        $pdo2 = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $sql = file_get_contents($file);
        // Split on semicolons and run each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => $s !== '' && !preg_match('/^--/', $s) && !preg_match('/^\/\*/', $s)
        );
        foreach ($statements as $stmt) {
            if (trim($stmt) !== '') $pdo2->exec($stmt);
        }
        echo "✓\n";
    } catch (PDOException $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\n✓ All migrations complete.\n";
