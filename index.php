<?php
require_once 'fonctions.php';

// Fetch all employees
try {
    if (!$pdo) {
        throw new PDOException('Connexion DB indisponible');
    }
    $stmt = $pdo->query("SELECT e.matricule, e.nom, e.codeGr, g.salaireBase FROM employe e LEFT JOIN grade g ON e.codeGr = g.codeGr ORDER BY e.matricule ASC");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch grades for filter and modal
    $stmtGrades = $pdo->query("SELECT codeGr, intitule FROM grade");
    $grades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Graceful fallback when DB is unreachable
    $employes = [];
    $grades = [];
    $dbError = $e->getMessage();
}

$maxSalaireInfo = salaireMax();
$totalMasseSalariale = totalSalaire();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard RH | Gestion Paie</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="layout-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-shapes"></i> RhPro
        </div>
        <ul class="nav-links">
            <li><a href="index.php" class="nav-link active"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h2>Tableau de bord</h2>
                <p>Bienvenue dans votre espace de gestion RH.</p>
            </div>
            
            <div style="display: flex; gap: 1rem; align-items: center;">
                <!-- Add Employee Button -->
                <button onclick="openAddModal()" style="padding: 0.75rem 1.5rem; background-color: var(--accent-primary); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;">
                    <i class="fa-solid fa-plus"></i> Nouveau
                </button>

                <!-- Grade Filter -->
                <select id="gradeFilter" style="padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; outline: none; bg-color: var(--bg-card);">
                    <option value="">Tous les grades</option>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?php echo htmlspecialchars($g['codeGr']); ?>"><?php echo htmlspecialchars($g['codeGr']); ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Search Box -->
                <div style="position: relative;">
                    <input type="text" id="searchInput" placeholder="Rechercher..." 
                           style="padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; width: 250px; outline: none;">
                    <i class="fa-solid fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon-wrapper"><i class="fa-solid fa-wallet"></i></div>
                <div class="stat-info">
                    <h4>Masse Salariale</h4>
                    <div class="value"><?php echo number_format($totalMasseSalariale, 0, ',', ' '); ?> <small style="font-size: 0.8rem; color: var(--text-secondary);">FCFA</small></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-wrapper"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <h4>Effectif Total</h4>
                    <div class="value"><?php echo count($employes); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-wrapper"><i class="fa-solid fa-crown"></i></div>
                <div class="stat-info">
                    <h4>Salaire Max</h4>
                    <div class="value" style="font-size: 1rem;"><?php echo htmlspecialchars($maxSalaireInfo); ?></div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Table Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Liste des Employés</h3>
                    <button class="btn-icon"><i class="fa-solid fa-filter"></i></button>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom & Prénoms</th>
                                <th>Grade</th>
                                <th>Salaire Net</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employes as $emp): ?>
                                <?php $net = salaireNet($emp['matricule']); ?>
                                <tr>
                                    <td><span style="font-family: monospace; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($emp['matricule']); ?></span></td>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($emp['nom']); ?></td>
                                    <td><span style="font-size: 0.75rem; background: var(--accent-light); color: var(--accent-primary); padding: 2px 8px; border-radius: 99px; font-weight: 700;"><?php echo htmlspecialchars($emp['codeGr']); ?></span></td>
                                    <td><strong><?php echo number_format($net, 0, ',', ' '); ?></strong> FCFA</td>
                                    <td>
                                        <button class="btn-icon" onclick="openDetails('<?php echo $emp['matricule']; ?>')" title="Voir détails">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <a href="bulletin.php?matricule=<?php echo $emp['matricule']; ?>" class="btn-icon" title="Imprimer Bulletin" style="display:inline-block; text-decoration:none;">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <button class="btn-icon delete-btn" onclick="deleteEmployee('<?php echo $emp['matricule']; ?>')" title="Supprimer">
                                            <i class="fa-solid fa-trash" style="color: #ef4444;"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Répartition par Grade</h3>
                </div>
                <div class="chart-container">
                    <canvas id="salaryChart"></canvas>
                </div>
                <div style="margin-top: 1rem; text-align: center; color: var(--text-secondary); font-size: 0.875rem;">
                    Visualisation de la distribution des effectifs par grade.
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Modal -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle" style="font-size: 1.25rem; font-weight: 600;">Détails Employé</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- AJAX Content -->
            <div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement...</div>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size: 1.25rem; font-weight: 600;">Ajouter un Employé</h3>
            <span class="close-add">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addEmployeeForm">
                <input type="hidden" name="action" value="add">
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Matricule</label>
                    <input type="text" name="matricule" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Nom Prenoms</label>
                    <input type="text" name="nom" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Grade</label>
                    <select name="codeGr" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: white;">
                        <?php foreach ($grades as $g): ?>
                            <option value="<?php echo $g['codeGr']; ?>"><?php echo $g['codeGr']; ?> - <?php echo $g['intitule']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Téléphone</label>
                    <input type="text" name="tel" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                </div>

                <div style="text-align: right;">
                    <button type="submit" style="background-color: var(--accent-primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
