<?php
require_once 'connexion.php';

// a) nbIndeminite(matricule)
function nbIndemnite($matricule) {
    global $pdo;
    // 1. Trouver le grade de l'employé
    $stmt = $pdo->prepare("SELECT codeGr FROM Employe WHERE matricule = ?");
    $stmt->execute([$matricule]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$emp) return 0; // Employé inexistant
    
    // 2. Compter les indemnités liées à ce grade
    // Note: On n'utilise pas COUNT(*) en SQL pour respecter la consigne, on compte en PHP.
    $stmt2 = $pdo->prepare("SELECT * FROM ADroit WHERE codeGr = ?");
    $stmt2->execute([$emp['codeGr']]);
    $indemnites = $stmt2->fetchAll();
    
    return count($indemnites);
}

// b) totalIndeminite(codeGr)
function totalIndeminite($codeGr) {
    global $pdo;
    $somme = 0;
    
    // Récupérer toutes les lignes ADroit pour ce grade
    $stmt = $pdo->prepare("SELECT montant FROM ADroit WHERE codeGr = ?");
    $stmt->execute([$codeGr]);
    $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul de la somme en PHP (INTERDIT DE FAIRE SUM() EN SQL)
    foreach ($lignes as $ligne) {
        $somme += $ligne['montant'];
    }
    
    return $somme;
}

// c) salaireNet(matricule)
function salaireNet($matricule) {
    global $pdo;
    
    // Récupérer le grade et le salaire de base
    $stmt = $pdo->prepare("
        SELECT g.codeGr, g.salaireBase 
        FROM Employe e 
        JOIN Grade g ON e.codeGr = g.codeGr 
        WHERE e.matricule = ?
    ");
    $stmt->execute([$matricule]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) return 0;
    
    $salaireBase = $data['salaireBase'];
    $codeGr = $data['codeGr'];
    
    // Utilisation de la fonction précédente pour le total des indemnités
    $totalInd = totalIndeminite($codeGr);
    
    // Calcul : Base + Indemnités - 5% Base
    $impot = $salaireBase * 0.05;
    $net = $salaireBase + $totalInd - $impot;
    
    return $net;
}

// d) salaireMax()
function salaireMax() {
    global $pdo;
    
    // Récupérer tous les employés
    $stmt = $pdo->query("SELECT matricule, nom FROM Employe");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $maxSalaire = -1;
    $nomRiche = "";
    
    // On boucle sur tous les employés pour calculer leur salaire et trouver le max
    foreach ($employes as $emp) {
        $salaire = salaireNet($emp['matricule']);
        if ($salaire > $maxSalaire) {
            $maxSalaire = $salaire;
            $nomRiche = $emp['nom'];
        }
    }
    
    return $nomRiche . " (" . number_format($maxSalaire, 0, ',', ' ') . " FCFA)";
}

// e) totalSalaire()
function totalSalaire() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT matricule FROM Employe");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalGlobal = 0;
    
    foreach ($employes as $emp) {
        $totalGlobal += salaireNet($emp['matricule']);
    }
    
    return $totalGlobal;
}
?>