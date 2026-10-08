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

$pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
try {
    // Normal case (shared hosting): the database already exists and your user can only use it
    new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, $pdoOptions);
    echo "✓ Database '$dbname' reachable.\n";
} catch (PDOException $e) {
    // Local development: the database may simply not exist yet — try to create it
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, $pdoOptions);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database '$dbname' created.\n";
    } catch (PDOException $e2) {
        echo "✗ Connection failed: " . $e2->getMessage() . "\n";
        exit(1);
    }
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
        // Drop "-- comment" lines first, then split on semicolons and run each statement
        $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($file));
        $statements = array_filter(array_map('trim', explode(';', $sql)), fn($s) => $s !== '');
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
