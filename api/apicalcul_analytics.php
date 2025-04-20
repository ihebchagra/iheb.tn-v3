<?php

include_once $_SERVER["DOCUMENT_ROOT"] . '/../config.php';

try {
  $dbDir = dirname(ANALYTICS_DB_FILE);
  if (!is_dir($dbDir)) {
    if (!mkdir($dbDir, 0775, true)) {
      error_log("APIcalcul Analytics Error: Could not create directory: " . $dbDir);
      return false;
    }
  }

  $pdo = new PDO('sqlite:' . ANALYTICS_DB_FILE);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec('PRAGMA journal_mode = WAL;');

  // Create the table if it doesn't exist
  $pdo->exec("CREATE TABLE IF NOT EXISTS apicalcul_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    db_type TEXT NOT NULL,
    profile TEXT NOT NULL,
    results TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    user_agent TEXT)");

  // Get input data
  $json_data = file_get_contents('php://input');
  $data = json_decode($json_data, true);
  
  if (!$data) {
    throw new Exception("Invalid JSON data");
  }

  $db_type = $data['db_type'] ?? 'unknown';
  $profile = json_encode($data['profile'] ?? []);
  $results = json_encode($data['results'] ?? []);
  $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
  $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

  $sql = "INSERT INTO apicalcul_analytics (db_type, profile, results, ip_address, user_agent)
  VALUES (:db_type, :profile, :results, :ip_address, :user_agent)";

  $stmt = $pdo->prepare($sql);

  $stmt->bindParam(':db_type', $db_type, PDO::PARAM_STR);
  $stmt->bindParam(':profile', $profile, PDO::PARAM_STR);
  $stmt->bindParam(':results', $results, PDO::PARAM_STR);
  $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
  $stmt->bindParam(':user_agent', $user_agent, PDO::PARAM_STR);

  $stmt->execute();
  return true;

} catch (PDOException $e) {
  error_log("APIcalcul Analytics Database Error: " . $e->getMessage());
  return false;
} catch (Exception $e) {
  error_log("APIcalcul Analytics General Error: " . $e->getMessage());
  return false;
}
