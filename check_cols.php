<?php
require_once 'connexion.php';
try {
    $stmt = $pdo->query("DESCRIBE employe");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
