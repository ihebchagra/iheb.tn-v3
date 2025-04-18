<?php

include_once $_SERVER["DOCUMENT_ROOT"] . '/../config.php';

try {
  $dbDir = dirname(ANALYTICS_DB_FILE);
  if (!is_dir($dbDir)) {
    if (!mkdir($dbDir, 0775, true)) {
      error_log("Analytics Error: Could not create directory: " . $dbDir);
      return false;
    }
  }

  $pdo = new PDO('sqlite:' . ANALYTICS_DB_FILE);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec('PRAGMA journal_mode = WAL;');

  $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    url TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    user_agent TEXT)");

  $url = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 500) : null;
  $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
  $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

  $sql = "INSERT INTO page_views (url, ip_address, user_agent)
  VALUES (:url, :ip_address, :user_agent)";

  $stmt = $pdo->prepare($sql);

  $stmt->bindParam(':url', $url, PDO::PARAM_STR);
  $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
  $stmt->bindParam(':user_agent', $user_agent, PDO::PARAM_STR);

  $stmt->execute();
  return true;

} catch (PDOException $e) {
  error_log("Analytics Database Error: " . $e->getMessage());
  return false;
} catch (Exception $e) {
  error_log("Analytics General Error: " . $e->getMessage());
  return false;
}

?>
