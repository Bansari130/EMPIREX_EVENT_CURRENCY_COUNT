<?php
/**
 * Database configuration
 * Update these 4 values to match your MySQL setup, then import database.sql
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'event_currency');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL of your app (used to build QR code links). No trailing slash.
// Example: 'http://192.168.1.10/event-currency-system'
define('BASE_URL', 'http://localhost/EventCoin-Event-Currency-System');

// Default starting balance for every new team
define('DEFAULT_BALANCE', 2000);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed. Check config/database.php — ' . $e->getMessage());
        }
    }
    return $pdo;
}
