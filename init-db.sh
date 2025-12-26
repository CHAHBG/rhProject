#!/bin/bash

# Wait for MySQL to be ready (if using cloud MySQL)
if [ -z "$DB_HOST" ] || [ "$DB_HOST" = "localhost" ]; then
    echo "Local database assumed (localhost)"
else
    echo "Waiting for remote database to be ready..."
    # For cloud databases, they should already be up
fi

# Import the SQL schema using mysql client
# We'll try using PHP PDO instead since mysql client may not be available

# Create a PHP script that imports the SQL
php -r "
try {
    \$host = getenv('DB_HOST') ?: 'localhost';
    \$port = getenv('DB_PORT') ?: 3306;
    \$dbname = getenv('DB_NAME') ?: 'rh_projet';
    \$username = getenv('DB_USER') ?: 'root';
    \$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    \$options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    if (getenv('DB_PORT')) {
        \$options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        \$options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    // First connect to MySQL without database to create it if needed
    \$dsn = \"mysql:host=\$host;port=\$port;charset=utf8\";
    \$pdo = new PDO(\$dsn, \$username, \$password, \$options);

    // Create database if it doesn't exist
    \$pdo->exec(\"CREATE DATABASE IF NOT EXISTS \\\`\$dbname\\\`\");

    // Now connect to the specific database
    \$dsn = \"mysql:host=\$host;port=\$port;dbname=\$dbname;charset=utf8\";
    \$pdo = new PDO(\$dsn, \$username, \$password, \$options);

    // Read and execute SQL file
    \$sql = file_get_contents('/var/www/html/rh_projet.sql');
    
    // Execute SQL statements (split by ;)
    \$statements = array_filter(array_map('trim', explode(';', \$sql)), function(\$s) {
        return !empty(\$s) && strpos(\$s, '--') !== 0;
    });

    foreach (\$statements as \$statement) {
        if (!empty(\$statement)) {
            try {
                \$pdo->exec(\$statement);
            } catch (Exception \$e) {
                // Some statements might fail (e.g., DROP TABLE IF EXISTS when table doesn't exist)
                // Continue with next statement
                echo \"Warning: \" . \$e->getMessage() . \"\\n\";
            }
        }
    }

    echo \"Database schema imported successfully!\\n\";
} catch (Exception \$e) {
    echo \"Error: \" . \$e->getMessage() . \"\\n\";
    exit(1);
}
"
