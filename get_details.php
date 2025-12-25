<?php
// get_details.php - Returns JSON details for a specific employee
require_once 'fonctions.php';

header('Content-Type: application/json');

if (!isset($_GET['matricule'])) {
    echo json_encode(['error' => 'Matricule manquant']);
    exit;
}

$matricule = $_GET['matricule'];

try {
    // 1. Get Employee Basic Info
    $stmt = $pdo->prepare("
        SELECT e.matricule, e.nom, e.codeGr, g.intitule as gradeIntitule, g.salaireBase 
        FROM Employe e 
        JOIN Grade g ON e.codeGr = g.codeGr 
        WHERE e.matricule = ?
    ");
    $stmt->execute([$matricule]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        echo json_encode(['error' => 'Employé non trouvé']);
        exit;
    }

    // 2. Get Indemnities Details
    // We recreate logic from totalIndeminite to get individual items for the modal
    $stmt2 = $pdo->prepare("
        SELECT i.libelle, a.montant 
        FROM ADroit a
        JOIN Indemnite i ON a.codeInd = i.codeInd
        WHERE a.codeGr = ?
    ");
    $stmt2->execute([$emp['codeGr']]);
    $indemnites = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calculate Totals
    $totalIndemnites = 0;
    foreach ($indemnites as $ind) {
        $totalIndemnites += $ind['montant'];
    }

    $salaireBase = $emp['salaireBase'];
    $impot = $salaireBase * 0.05;
    $salaireNet = $salaireBase + $totalIndemnites - $impot;

    // 4. Return Data
    echo json_encode([
        'matricule' => $emp['matricule'],
        'nom' => $emp['nom'],
        'grade' => $emp['codeGr'] . ' - ' . $emp['gradeIntitule'],
        'salaireBase' => $salaireBase,
        'indemnites' => $indemnites,
        'totalIndemnites' => $totalIndemnites,
        'impot' => $impot,
        'salaireNet' => $salaireNet
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
