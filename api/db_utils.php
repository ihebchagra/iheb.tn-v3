<?php

/**
 * Get a PDO connection to the SQLite database.
 * Uses a static variable to reuse the connection within the same PHP process.
 *
 * @param string $dbFile Path to the database file.
 * @return PDO|false Returns PDO object if successful, false otherwise.
 */
function getPDO(string $dbFile): PDO|false {
    static $pdo = null; // Static variable to hold the connection

    // If a connection for this $dbFile already exists in this script execution, return it.
    // Note: This helps if getPDO is called multiple times *within the same script run*.
    // It does NOT persist the connection across different HTTP requests.
    if ($pdo instanceof PDO) {
        // A basic check to see if the DSN matches, in case getPDO could be called with different files.
        // For this specific setup where ANALYTICS_DB_FILE is always the same, this might be overkill,
        // but good practice if the function were more generic.
        // However, for simplicity with a single DB file, we can assume if $pdo is set, it's the right one.
        // For true multi-db support with this static pattern, $pdo would need to be an array keyed by $dbFile.
        // For now, assuming one DB target for this function in this context.
        return $pdo;
    }

    try {
        $dbDir = dirname($dbFile);
        if (!is_dir($dbDir)) {
            if (!mkdir($dbDir, 0775, true) && !is_dir($dbDir)) {
                // Check again if directory exists, another process might have created it.
                error_log("Failed to create directory: " . $dbDir);
                return false;
            }
            // It's good practice to ensure the directory is writable by the web server
            if (!is_writable($dbDir)) {
                error_log("Database directory is not writable: " . $dbDir);
                // Potentially return false or throw, depending on how critical this is.
                // For now, we'll let PDO connection attempt handle it if it fails.
            }
        }

        // Ensure the database file itself is writable if it exists, or its directory is writable if it doesn't.
        if (file_exists($dbFile) && !is_writable($dbFile)) {
            error_log("Database file is not writable: " . $dbFile);
            // return false; // Or handle error
        } elseif (!file_exists($dbFile) && !is_writable($dbDir)) {
            error_log("Database directory is not writable for new DB creation: " . $dbDir);
            // return false; // Or handle error
        }


        $dsn = 'sqlite:' . $dbFile;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5, // Connection timeout in seconds (reduced from 10, 5 is often enough)
            PDO::ATTR_PERSISTENT => false, // Usually NOT recommended for SQLite with web servers due to PHP's process model.
                                          // Each PHP-FPM child might hold a persistent connection, leading to "database is locked"
                                          // if not managed carefully. Stick to non-persistent for SQLite unless you have a very
                                          // specific setup (e.g., Swoole, RoadRunner).
        ];

        $pdoInstance = new PDO($dsn, null, null, $options);

        // Crucial PRAGMAs for stability and performance
        $pdoInstance->exec('PRAGMA journal_mode = WAL;'); // Enables Write-Ahead Logging for better concurrency
        $pdoInstance->exec('PRAGMA synchronous = NORMAL;'); // WAL + NORMAL is a good balance of safety and speed. FULL is safer but slower.
        $pdoInstance->exec('PRAGMA busy_timeout = 5000;'); // Wait 5000ms (5 seconds) if DB is locked, before failing. ADJUST AS NEEDED.
        $pdoInstance->exec('PRAGMA foreign_keys = ON;');
        $pdoInstance->exec('PRAGMA temp_store = MEMORY;'); // Use memory for temporary tables/indices

        $pdo = $pdoInstance; // Store the successful connection in the static variable
        return $pdo;

    } catch (PDOException $e) {
        error_log("Database connection or PRAGMA setup failed for '$dbFile': " . $e->getMessage());
        // You might want to log $e->getCode() as well for more specific SQLite errors
        return false;
    }
}

/**
 * Optional: Function to explicitly close the PDO connection.
 * PHP usually handles this at script end, but can be useful in long-running scripts or specific scenarios.
 */
function closePDO() {
    // Access the static variable from getPDO. This is a bit of a hacky way.
    // A better approach would be for getPDO to return an object that manages the PDO instance,
    // or make $pdo a global static variable (generally discouraged), or pass $pdo around.
    // For simplicity here, we'll assume getPDO has been called and might have set a static $pdo.
    // This part is tricky without refactoring getPDO to handle its static $pdo more explicitly for closing.
    // A better singleton pattern or a dedicated DB manager class would handle this cleaner.

    // For now, since $pdo is static *within* getPDO, we can't easily nullify it from outside
    // to force a fresh connection on next getPDO call within the same script.
    // The main benefit of static $pdo is for multiple calls to getPDO *within one script execution*.
    // It doesn't persist across HTTP requests.

    // If you truly need to manage a single shared PDO instance across a request lifecycle
    // and close it, you'd typically pass the PDO object around or use a registry/service container.
}

?>
