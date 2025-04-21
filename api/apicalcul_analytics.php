<?php

include_once $_SERVER["DOCUMENT_ROOT"] . '/../config.php';

/**
 * Check and repair SQLite database if possible
 * @param string $dbFile Path to the database file
 * @param int $maxRetries Number of retries for busy database
 * @return PDO|false Returns PDO object if successful, false otherwise
 */
function getCheckedPDO($dbFile, $maxRetries = 3) {
  $retries = 0;
  
  while ($retries <= $maxRetries) {
    try {
      // Check if database file exists
      if (file_exists($dbFile)) {
        // Check if database is writable
        if (!is_writable($dbFile) || (file_exists($dbFile.'-wal') && !is_writable($dbFile.'-wal'))) {
          error_log("Database or WAL file is not writable: " . $dbFile);
          return false;
        }
        
        // Try to verify database integrity
        $output = null;
        $returnVar = null;
        exec('sqlite3 ' . escapeshellarg($dbFile) . ' "PRAGMA integrity_check;"', $output, $returnVar);
        
        // If database is corrupted, try to recover or recreate
        if ($returnVar !== 0 || (isset($output[0]) && $output[0] !== 'ok')) {
          error_log("Database corrupt. Attempting recovery: " . $dbFile);
          
          // Create backup of corrupted file
          $backupFile = $dbFile . '.corrupted.' . time();
          copy($dbFile, $backupFile);
          
          // Try to recover with sqlite3 recovery tools, or recreate if needed
          if (!file_exists($dbFile . '-wal')) {
            // No WAL file, safe to recreate
            unlink($dbFile);
          } else {
            // Try to recover from WAL first
            exec('sqlite3 ' . escapeshellarg($dbFile) . ' "PRAGMA wal_checkpoint(FULL);"', $output, $returnVar);
            if ($returnVar !== 0) {
              // If recovery fails, recreate
              unlink($dbFile);
              if (file_exists($dbFile . '-wal')) unlink($dbFile . '-wal');
              if (file_exists($dbFile . '-shm')) unlink($dbFile . '-shm');
            }
          }
        }
      } else {
        // Check if directory is writable
        $dbDir = dirname($dbFile);
        if (!is_writable($dbDir)) {
          error_log("Database directory is not writable: " . $dbDir);
          return false;
        }
      }
      
      // Create new PDO connection with timeout
      $dsn = 'sqlite:' . $dbFile;
      $options = [
        PDO::ATTR_TIMEOUT => 5, // 5 seconds timeout
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      ];
      
      $pdo = new PDO($dsn, null, null, $options);
      $pdo->exec('PRAGMA journal_mode = WAL;');
      $pdo->exec('PRAGMA synchronous = NORMAL;');
      $pdo->exec('PRAGMA temp_store = MEMORY;');
      $pdo->exec('PRAGMA busy_timeout = 3000;'); // 3 seconds busy timeout
      
      // Test write access with a simple operation
      $testWrite = false;
      $pdo->beginTransaction();
      try {
        $pdo->exec('CREATE TABLE IF NOT EXISTS _test_write (id INTEGER PRIMARY KEY)');
        $pdo->exec('INSERT OR IGNORE INTO _test_write (id) VALUES (1)');
        $pdo->commit();
        $testWrite = true;
      } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Database is not writable: " . $e->getMessage());
      }
      
      return $testWrite ? $pdo : false;
      
    } catch (PDOException $e) {
      // Check if the error is due to database being locked
      if (strpos($e->getMessage(), 'database is locked') !== false && $retries < $maxRetries) {
        $retries++;
        error_log("Database locked, retrying ({$retries}/{$maxRetries})...");
        usleep(mt_rand(500000, 1500000)); // Random delay between 0.5-1.5 seconds
        continue;
      }
      error_log("Database connection failed: " . $e->getMessage());
      return false;
    } catch (Exception $e) {
      error_log("Database recovery failed: " . $e->getMessage());
      return false;
    }
  }
  
  error_log("Maximum retries reached for database connection");
  return false;
}

try {
  $dbDir = dirname(ANALYTICS_DB_FILE);
  if (!is_dir($dbDir)) {
    if (!mkdir($dbDir, 0775, true)) {
      error_log("APIcalcul Analytics Error: Could not create directory: " . $dbDir);
      return false;
    }
  }

  $pdo = getCheckedPDO(ANALYTICS_DB_FILE);
  if ($pdo === false) {
    throw new Exception("Could not create or recover database connection");
  }

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
