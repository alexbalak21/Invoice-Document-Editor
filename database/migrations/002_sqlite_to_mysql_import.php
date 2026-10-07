#!/usr/bin/env php
<?php
/**
 * One-time migration: copy data from the old SQLite db to MySQL.
 * Usage: php database/migrations/002_sqlite_to_mysql_import.php /path/to/documents.sqlite
 *
 * Run AFTER php database/migrate.php
 */

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

use App\Core\Database;

$sqlitePath = $argv[1] ?? dirname(dirname(__DIR__)) . '/db/documents.sqlite';

if (!file_exists($sqlitePath)) {
    echo "SQLite file not found: $sqlitePath\n";
    echo "Usage: php database/migrations/002_sqlite_to_mysql_import.php [/path/to/documents.sqlite]\n";
    exit(1);
}

echo "Importing from: $sqlitePath\n\n";

$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$mysql  = Database::getInstance();

$tables = ['documents', 'customers', 'items'];
foreach ($tables as $table) {
    // Check if table exists in SQLite
    $exists = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
    if (!$exists) { echo "Skipping $table (not in SQLite)\n"; continue; }

    $rows = $sqlite->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { echo "$table: empty, skipping.\n"; continue; }

    $cols    = array_keys($rows[0]);
    // Remove auto-increment id and timestamp columns — let MySQL generate them
    $insert  = array_filter($cols, fn($c) => !in_array($c, ['id']));
    $colList = implode(', ', $insert);
    $params  = implode(', ', array_map(fn($c) => ":$c", $insert));

    $stmt = $mysql->prepare("INSERT INTO $table ($colList) VALUES ($params)");
    $count = 0;
    foreach ($rows as $row) {
        $bound = [];
        foreach ($insert as $col) $bound[":$col"] = $row[$col];
        $stmt->execute($bound);
        $count++;
    }
    echo "✓ $table: $count row(s) imported.\n";
}

echo "\nDone.\n";
