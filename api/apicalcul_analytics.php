<?php

include_once $_SERVER["DOCUMENT_ROOT"] . '/../config.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/../api/db_utils.php';

try {
    $pdo = getPDO(ANALYTICS_DB_FILE);
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS apicalcul_analytics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        db_type TEXT NOT NULL,
        profile TEXT NOT NULL,
        results TEXT NOT NULL,
        ip_address TEXT NOT NULL,
        user_agent TEXT)");

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception("Invalid JSON data");
    }

    $stmt = $pdo->prepare("INSERT INTO apicalcul_analytics (db_type, profile, results, ip_address, user_agent)
        VALUES (:db_type, :profile, :results, :ip_address, :user_agent)");
    $stmt->execute([
        ':db_type' => $data['db_type'] ?? 'unknown',
        ':profile' => json_encode($data['profile'] ?? []),
        ':results' => json_encode($data['results'] ?? []),
        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
