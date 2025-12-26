<?php
try {
    // Configuration : Cloud (Env vars) ou Local (Defaut)
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: 3306;
    $dbname = getenv('DB_NAME') ?: 'rh_projet';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    // Options PDO (SSL strict pour Aiven/Cloud si nécessaire)
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Ajouter SSL si on est sur le cloud (port différent de 3306 souvent signe de cloud)
    if (getenv('DB_PORT')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // Disable strict verify for compatibility
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Avoid fatal exit; allow pages to render gracefully
    $pdo = null;
}
?>