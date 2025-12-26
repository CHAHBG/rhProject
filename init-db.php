<?php
/**
 * Database Initialization Script
 * This script is called during Docker startup to ensure the database schema is created
 */

// Suppress output during normal operation
if (php_sapi_name() !== 'cli') {
    exit('This script can only be run from CLI');
}

try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: 3306;
    $dbname = getenv('DB_NAME') ?: 'rh_projet';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    if (getenv('DB_PORT')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    // First connect to MySQL without database to create it if needed
    $dsn = "mysql:host=$host;port=$port;charset=utf8";
    $pdo = new PDO($dsn, $username, $password, $options);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "[DB Init] Database '$dbname' ready.\n";

    // Now connect to the specific database
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password, $options);

    // Check if tables already exist
    $result = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$dbname' AND table_name IN ('employe', 'grade', 'indemnite', 'adroit')");
    $tableCount = $result->fetchColumn();

    if ($tableCount > 0) {
        echo "[DB Init] Tables already exist. Skipping schema import.\n";
        exit(0);
    }

    // Read SQL file
    $sqlFile = '/var/www/html/rh_projet.sql';
    if (!file_exists($sqlFile)) {
        echo "[DB Init] Warning: SQL file not found at $sqlFile\n";
        exit(0);
    }

    $sql = file_get_contents($sqlFile);
    
    // Remove comments and extra whitespace
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
    $sql = preg_replace('/\/\*(.|\n)*?\*\//', '', $sql);
    
    // Split by semicolon
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($s) { return !empty($s); }
    );

    $executed = 0;
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (Exception $e) {
            // Log but continue
            echo "[DB Init] Warning executing statement: " . $e->getMessage() . "\n";
        }
    }

    echo "[DB Init] Successfully imported $executed SQL statements.\n";

} catch (Exception $e) {
    echo "[DB Init] Error: " . $e->getMessage() . "\n";
    // Don't exit with error - allow Apache to continue even if DB init fails
    // The application will show the error when accessed
    exit(0);
}
