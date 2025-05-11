<?php

/**
 * Check and repair SQLite database if possible
 * @param string $dbFile Path to the database file
 * @param int $maxRetries Number of retries for busy database
 * @return PDO|false Returns PDO object if successful, false otherwise
 */
function getPDO($dbFile) {
    try {
        // Ensure parent directory exists
        $dbDir = dirname($dbFile);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0775, true);
        }

        // Create PDO connection
        $dsn = 'sqlite:' . $dbFile;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ];
        $pdo = new PDO($dsn, null, null, $options);

        // Enable WAL mode
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA foreign_keys = ON;');

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}
