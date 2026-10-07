<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database — PDO MySQL singleton.
 * Call Database::getInstance() anywhere to get the shared connection.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host    = Env::get('DB_HOST',     '127.0.0.1');
        $port    = Env::get('DB_PORT',     '3306');
        $dbname  = Env::get('DB_DATABASE', 'doceditor');
        $user    = Env::get('DB_USERNAME', 'root');
        $pass    = Env::get('DB_PASSWORD', '');
        $charset = Env::get('DB_CHARSET',  'utf8mb4');

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Don't expose credentials in the error message
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        return self::$instance;
    }

    // Prevent instantiation
    private function __construct() {}
    private function __clone() {}
}
