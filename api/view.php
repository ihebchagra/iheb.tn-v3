<?php

include_once $_SERVER["DOCUMENT_ROOT"] . '/../config.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/../api/db_utils.php';

try {
    $pdo = getPDO(ANALYTICS_DB_FILE);
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        url TEXT NOT NULL,
        ip_address TEXT NOT NULL,
        user_agent TEXT)");

    $url = $_SERVER['HTTP_REFERER'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO page_views (url, ip_address, user_agent)
        VALUES (:url, :ip_address, :user_agent)");
    $stmt->execute([
        ':url' => $url,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent,
    ]);

    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}
