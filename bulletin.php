<?php
require_once 'fonctions.php';

if (!isset($_GET['matricule'])) {
    die("Matricule manquant.");
}

$matricule = $_GET['matricule'];

try {
    // Info Employé + Grade
    $stmt = $pdo->prepare("
        SELECT * 
        FROM employe e 
        JOIN grade g ON e.codeGr = g.codeGr 
        WHERE e.matricule = ?
    ");
    $stmt->execute([$matricule]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("Employé introuvable.");
    }

    // Info Indemnités
    $stmt2 = $pdo->prepare("
        SELECT i.libelle, a.montant 
        FROM adroit a
        JOIN indemnite i ON a.codeInd = i.codeInd
        WHERE a.codeGr = ?
    ");
    $stmt2->execute([$data['codeGr']]);
    $indemnites = $stmt2->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}

// Calculations
$salaireBase = $data['salaireBase'];
$totalIndemnites = 0;
foreach ($indemnites as $ind) {
    $totalIndemnites += $ind['montant'];
}
$salaireBrut = $salaireBase + $totalIndemnites;
$impot = $salaireBase * 0.05; // 5% du salaire de base (selon regle c)
$salaireNet = $salaireBrut - $impot;

// Attempt to find telephone field
$tel = isset($data['tel']) ? $data['tel'] : (isset($data['telephone']) ? $data['telephone'] : 'Non renseigné');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Salaire - <?php echo htmlspecialchars($data['nom']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #525659;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
        }
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm; /* A4 */
            padding: 20mm;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .company-logo {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .doc-title {
            font-size: 28px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 2px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            width: 120px;
            display: inline-block;
        }
        .table-section {
            width: 100%;
            margin-bottom: 40px;
            border-collapse: collapse;
        }
        .table-section th, .table-section td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .table-section th {
            background-color: #f8f9fa;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 14px;
        }
        .amount-col {
            text-align: right;
            font-family: monospace;
            font-size: 16px;
        }
        .total-row td {
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .net-pay {
            background-color: #e6fcf5;
            color: #047857;
            font-size: 1.2em;
        }
        .footer {
            margin-top: 80px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .print-btn:hover {
            background: #1d4ed8;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #4b5563;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .page { box-shadow: none; width: 100%; height: auto; padding: 0; margin: 0; }
            .print-btn, .back-btn { display: none; }
        }
    </style>
</head>
<body>

<a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Retour</a>
<button class="print-btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer</button>

<div class="page">
    <div class="header">
        <div class="company-logo"><i class="fa-solid fa-building"></i> RH PRO</div>
        <div class="doc-title">Bulletin de Salaire</div>
        <div><?php echo date('d/m/Y'); ?></div>
    </div>

    <div class="info-grid">
        <div class="info-col">
            <div class="info-item"><span class="info-label">Matricule :</span> <?php echo htmlspecialchars($data['matricule']); ?></div>
            <div class="info-item"><span class="info-label">Nom :</span> <?php echo htmlspecialchars($data['nom']); ?></div>
            <div class="info-item"><span class="info-label">Téléphone :</span> <?php echo htmlspecialchars($tel); ?></div>
        </div>
        <div class="info-col">
            <div class="info-item"><span class="info-label">Grade :</span> <?php echo htmlspecialchars($data['codeGr']); ?></div>
            <div class="info-item"><span class="info-label">Intitulé :</span> <?php echo htmlspecialchars($data['intitule'] ?? 'N/A'); ?></div>
            <div class="info-item"><span class="info-label">Période :</span> <?php echo date('M Y'); ?></div>
        </div>
    </div>

    <table class="table-section">
        <thead>
            <tr>
                <th>Rubrique</th>
                <th style="width: 150px;">Base</th>
                <th style="width: 150px;">Montant (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Salaire de Base</strong></td>
                <td>-</td>
                <td class="amount-col"><?php echo number_format($salaireBase, 0, ',', ' '); ?></td>
            </tr>
            
            <?php foreach ($indemnites as $ind): ?>
            <tr>
                <td>Indemnité : <?php echo htmlspecialchars($ind['libelle']); ?></td>
                <td>-</td>
                <td class="amount-col"><?php echo number_format($ind['montant'], 0, ',', ' '); ?></td>
            </tr>
            <?php endforeach; ?>

            <tr class="total-row" style="background-color: #f1f5f9;">
                <td><strong>Salaire Brut</strong></td>
                <td></td>
                <td class="amount-col"><?php echo number_format($salaireBrut, 0, ',', ' '); ?></td>
            </tr>

            <tr>
                <td>Retenue Impôt (5% sur Base)</td>
                <td>5 %</td>
                <td class="amount-col" style="color: #ef4444;">- <?php echo number_format($impot, 0, ',', ' '); ?></td>
            </tr>

            <tr class="total-row net-pay">
                <td colspan="2">NET A PAYER</td>
                <td class="amount-col"><?php echo number_format($salaireNet, 0, ',', ' '); ?></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 60px; display: flex; justify-content: space-between;">
        <div style="text-align: center;">
            <p><strong>L'Employeur</strong></p>
            <br><br><br>
            <p>Signature</p>
        </div>
        <div style="text-align: center;">
            <p><strong>L'L'employé</strong></p>
            <br><br><br>
            <p>Signature</p>
        </div>
    </div>

    <div class="footer">
        RH PRO S.A. - Capital de 10 000 000 FCFA - RC Abidjan 1234567<br>
        Ce bulletin de paie est généré automatiquement par le système.
    </div>
</div>

</body>
</html>
