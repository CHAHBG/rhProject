<?php
require_once 'connexion.php';
try {
    $stmt = $pdo->query("DESCRIBE Employe");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
