<?php
header('Content-Type: application/json');
$resp = [
  'uptime' => time(),
  'php_version' => PHP_VERSION,
  'status' => 'starting'
];
try {
  require_once 'connexion.php';
  $resp['status'] = 'ok';
  $resp['db'] = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_NAME') ?: 'rh_projet'
  ];
  // Check tables exist
  $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('employe','grade','indemnite','adroit')");
  $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
  $resp['tables_present'] = $tables;
  // Try simple query
  try {
    $ok = $pdo->query("SELECT COUNT(*) FROM employe")->fetchColumn();
    $resp['employe_count'] = (int)$ok;
  } catch (Throwable $e) {
    $resp['employe_query_error'] = $e->getMessage();
  }
} catch (Throwable $e) {
  $resp['status'] = 'error';
  $resp['error'] = $e->getMessage();
}
echo json_encode($resp);
