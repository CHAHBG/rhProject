# 📖 Manuel des Procédures - ProjetRH

## Guide Complet du Code Source pour Débutants

Ce document explique **chaque fichier** du projet avec des explications détaillées. Il suit les **bonnes pratiques** de développement web : séparer le HTML, CSS et JavaScript dans des fichiers distincts.

---

## 🎯 Règle d'Or : Séparation du Code

> **Ne jamais mélanger HTML, CSS et JavaScript dans le même fichier !**

### ❌ Mauvaise pratique (à éviter)
```html
<button style="color: red; font-size: 20px;" onclick="alert('Bonjour')">
    Cliquer
</button>
```

### ✅ Bonne pratique (à suivre)
```html
<!-- HTML : Structure uniquement -->
<button id="monBouton" class="btn-primary">Cliquer</button>
```
```css
/* CSS : Style dans un fichier séparé */
.btn-primary {
    color: red;
    font-size: 20px;
}
```
```javascript
// JavaScript : Comportement dans un fichier séparé
document.getElementById('monBouton').addEventListener('click', function() {
    alert('Bonjour');
});
```

### Pourquoi cette séparation ?
| Avantage | Explication |
|----------|-------------|
| **Lisibilité** | Chaque fichier a un rôle clair |
| **Maintenance** | Facile de trouver et modifier le code |
| **Réutilisation** | Le CSS/JS peut être utilisé sur plusieurs pages |
| **Performance** | Le navigateur met en cache les fichiers externes |

---

# 📁 Structure des Fichiers

```
ProjetRH/
├── index.php           → Page principale (Dashboard)
├── bulletin.php        → Page du bulletin de salaire
├── connexion.php       → Connexion à la base de données
├── fonctions.php       → Fonctions de calcul PHP
├── get_details.php     → API JSON (pour AJAX)
├── css/
│   └── style.css       → Tous les styles visuels
├── js/
│   └── script.js       → Toute la logique JavaScript
└── README.md           → Documentation
```

---

# 🐘 FICHIERS PHP

---

## 1. `connexion.php` - Connexion à la Base de Données

### 📍 Rôle
Établit une connexion sécurisée avec MySQL en utilisant PDO.

### 📝 Code Complet Commenté

```php
<?php
/**
 * CONNEXION À LA BASE DE DONNÉES
 * Ce fichier crée la variable $pdo utilisable dans tout le projet
 */

try {
    // ═══════════════════════════════════════════════════════════
    // ÉTAPE 1 : Définir les paramètres de connexion
    // ═══════════════════════════════════════════════════════════
    
    $host = 'localhost';      // Adresse du serveur (localhost = votre PC)
    $dbname = 'rh_projet';    // Nom de votre base de données
    $username = 'root';       // Nom d'utilisateur MySQL
    $password = '';           // Mot de passe (vide par défaut sur WAMP)
    
    // ═══════════════════════════════════════════════════════════
    // ÉTAPE 2 : Créer la connexion PDO
    // ═══════════════════════════════════════════════════════════
    
    // La syntaxe : new PDO("type:host=xxx;dbname=xxx", user, password)
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    
    // ═══════════════════════════════════════════════════════════
    // ÉTAPE 3 : Configurer PDO pour afficher les erreurs
    // ═══════════════════════════════════════════════════════════
    
    // ERRMODE_EXCEPTION = Si erreur SQL, PHP lance une exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // ═══════════════════════════════════════════════════════════
    // GESTION DES ERREURS
    // ═══════════════════════════════════════════════════════════
    
    // die() = Arrête le script et affiche un message
    die("Erreur de connexion : " . $e->getMessage());
}
?>
```

### 🔑 Concepts Clés

| Concept | Explication |
|---------|-------------|
| `try/catch` | Bloc qui "essaie" du code et "attrape" les erreurs |
| `PDO` | PHP Data Objects - classe pour accéder aux bases de données |
| `$pdo` | Variable qui contient la connexion (réutilisable partout) |
| `die()` | Arrête le script et affiche un message |

### 💡 Comment l'utiliser ?
```php
// Dans n'importe quel autre fichier PHP :
require_once 'connexion.php';  // Charge le fichier

// Maintenant $pdo est disponible !
$stmt = $pdo->query("SELECT * FROM Employe");
```

---

## 2. `fonctions.php` - Fonctions de Calcul

### 📍 Rôle
Contient toutes les fonctions métier pour calculer les salaires.

### 📝 Code Complet Commenté

```php
<?php
/**
 * FONCTIONS DE CALCUL DES SALAIRES
 * Toute la logique métier est centralisée ici
 */

// Inclut la connexion à la BDD (on a besoin de $pdo)
require_once 'connexion.php';

// ═══════════════════════════════════════════════════════════════════
// FONCTION A : COMPTER LES INDEMNITÉS D'UN EMPLOYÉ
// ═══════════════════════════════════════════════════════════════════

/**
 * Compte le nombre d'indemnités auxquelles un employé a droit
 * 
 * @param string $matricule - Le matricule de l'employé (ex: "M01")
 * @return int - Le nombre d'indemnités
 */
function nbIndemnite($matricule) {
    global $pdo;  // Récupère la variable $pdo de connexion.php
    
    // --- ÉTAPE 1 : Trouver le grade de l'employé ---
    // prepare() crée une requête avec un ? pour le paramètre
    $stmt = $pdo->prepare("SELECT codeGr FROM Employe WHERE matricule = ?");
    
    // execute() remplace le ? par la valeur et exécute
    $stmt->execute([$matricule]);
    
    // fetch() récupère UNE ligne de résultat
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si employé non trouvé, retourner 0
    if (!$emp) return 0;
    
    // --- ÉTAPE 2 : Compter les indemnités de ce grade ---
    $stmt2 = $pdo->prepare("SELECT * FROM ADroit WHERE codeGr = ?");
    $stmt2->execute([$emp['codeGr']]);
    
    // fetchAll() récupère TOUTES les lignes
    $indemnites = $stmt2->fetchAll();
    
    // count() compte le nombre d'éléments (en PHP, pas en SQL !)
    return count($indemnites);
}

// ═══════════════════════════════════════════════════════════════════
// FONCTION B : CALCULER LE TOTAL DES INDEMNITÉS D'UN GRADE
// ═══════════════════════════════════════════════════════════════════

/**
 * Calcule la somme des montants d'indemnités pour un grade
 * 
 * @param string $codeGr - Le code du grade (ex: "A5")
 * @return float - Le total des indemnités
 */
function totalIndeminite($codeGr) {
    global $pdo;
    $somme = 0;  // Initialise la somme à zéro
    
    // Récupérer tous les montants pour ce grade
    $stmt = $pdo->prepare("SELECT montant FROM ADroit WHERE codeGr = ?");
    $stmt->execute([$codeGr]);
    $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Additionner en PHP (pas de SUM() SQL, c'est interdit par l'énoncé)
    foreach ($lignes as $ligne) {
        $somme += $ligne['montant'];  // += signifie "ajouter à"
    }
    
    return $somme;
}

// ═══════════════════════════════════════════════════════════════════
// FONCTION C : CALCULER LE SALAIRE NET
// ═══════════════════════════════════════════════════════════════════

/**
 * Calcule le salaire net d'un employé
 * Formule : Salaire Base + Indemnités - 5% du Salaire Base
 * 
 * @param string $matricule - Le matricule de l'employé
 * @return float - Le salaire net
 */
function salaireNet($matricule) {
    global $pdo;
    
    // --- Récupérer le grade et le salaire de base ---
    // JOIN permet de lier deux tables (Employe et Grade)
    $stmt = $pdo->prepare("
        SELECT g.codeGr, g.salaireBase 
        FROM Employe e 
        JOIN Grade g ON e.codeGr = g.codeGr 
        WHERE e.matricule = ?
    ");
    $stmt->execute([$matricule]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) return 0;  // Si pas trouvé
    
    // --- Calculs ---
    $salaireBase = $data['salaireBase'];
    $totalInd = totalIndeminite($data['codeGr']);  // Réutilise fonction B
    
    $impot = $salaireBase * 0.05;  // 5% d'impôt sur le salaire de base
    $net = $salaireBase + $totalInd - $impot;
    
    return $net;
}

// ═══════════════════════════════════════════════════════════════════
// FONCTION D : TROUVER L'EMPLOYÉ LE MIEUX PAYÉ
// ═══════════════════════════════════════════════════════════════════

/**
 * Trouve le nom de l'employé avec le salaire le plus élevé
 * 
 * @return string - Nom et salaire formaté
 */
function salaireMax() {
    global $pdo;
    
    // Récupérer tous les employés
    $stmt = $pdo->query("SELECT matricule, nom FROM Employe");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $maxSalaire = -1;  // Commence à -1 pour être sûr de trouver un max
    $nomRiche = "";
    
    // Parcourir chaque employé et calculer son salaire
    foreach ($employes as $emp) {
        $salaire = salaireNet($emp['matricule']);  // Réutilise fonction C
        
        // Si ce salaire est plus grand que le max actuel
        if ($salaire > $maxSalaire) {
            $maxSalaire = $salaire;
            $nomRiche = $emp['nom'];
        }
    }
    
    // number_format() formate le nombre avec des espaces
    return $nomRiche . " (" . number_format($maxSalaire, 0, ',', ' ') . " FCFA)";
}

// ═══════════════════════════════════════════════════════════════════
// FONCTION E : CALCULER LA MASSE SALARIALE TOTALE
// ═══════════════════════════════════════════════════════════════════

/**
 * Calcule la somme de tous les salaires nets
 * 
 * @return float - Le total de tous les salaires
 */
function totalSalaire() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT matricule FROM Employe");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalGlobal = 0;
    
    // Additionner le salaire net de chaque employé
    foreach ($employes as $emp) {
        $totalGlobal += salaireNet($emp['matricule']);
    }
    
    return $totalGlobal;
}
?>
```

### 🔑 Concepts Clés

| Fonction PHP | Explication |
|--------------|-------------|
| `global $pdo` | Importe une variable globale dans la fonction |
| `prepare()` | Prépare une requête SQL avec des `?` pour les paramètres |
| `execute([...])` | Exécute la requête en remplaçant les `?` |
| `fetch()` | Récupère 1 résultat |
| `fetchAll()` | Récupère tous les résultats |
| `foreach` | Boucle qui parcourt un tableau |

---

## 3. `get_details.php` - API JSON

### 📍 Rôle
Retourne les détails d'un employé au format JSON pour les requêtes AJAX.

### 📝 Code Complet Commenté

```php
<?php
/**
 * API JSON - DÉTAILS D'UN EMPLOYÉ
 * Ce fichier est appelé par JavaScript (AJAX)
 * Il retourne des données au format JSON, pas du HTML
 */

require_once 'fonctions.php';

// Indique au navigateur qu'on renvoie du JSON (pas du HTML)
header('Content-Type: application/json');

// ═══════════════════════════════════════════════════════════════════
// VÉRIFICATION DU PARAMÈTRE
// ═══════════════════════════════════════════════════════════════════

// $_GET contient les paramètres de l'URL (ex: ?matricule=M01)
if (!isset($_GET['matricule'])) {
    // json_encode() convertit un tableau PHP en texte JSON
    echo json_encode(['error' => 'Matricule manquant']);
    exit;  // Arrête le script
}

$matricule = $_GET['matricule'];

// ═══════════════════════════════════════════════════════════════════
// RÉCUPÉRATION DES DONNÉES
// ═══════════════════════════════════════════════════════════════════

try {
    // --- Info employé + grade ---
    $stmt = $pdo->prepare("
        SELECT e.matricule, e.nom, e.codeGr, g.intitule, g.salaireBase 
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

    // --- Liste des indemnités ---
    $stmt2 = $pdo->prepare("
        SELECT i.libelle, a.montant 
        FROM ADroit a
        JOIN Indemnite i ON a.codeInd = i.codeInd
        WHERE a.codeGr = ?
    ");
    $stmt2->execute([$emp['codeGr']]);
    $indemnites = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // --- Calculs ---
    $totalIndemnites = 0;
    foreach ($indemnites as $ind) {
        $totalIndemnites += $ind['montant'];
    }

    $salaireBase = $emp['salaireBase'];
    $impot = $salaireBase * 0.05;
    $salaireNet = $salaireBase + $totalIndemnites - $impot;

    // ═══════════════════════════════════════════════════════════════════
    // RETOURNER LES DONNÉES EN JSON
    // ═══════════════════════════════════════════════════════════════════
    
    echo json_encode([
        'matricule' => $emp['matricule'],
        'nom' => $emp['nom'],
        'grade' => $emp['codeGr'] . ' - ' . $emp['intitule'],
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
```

### 🔑 Ce que retourne cette API

Quand on appelle `get_details.php?matricule=M01`, on reçoit :

```json
{
    "matricule": "M01",
    "nom": "Toto Ali",
    "grade": "A5 - Directeur",
    "salaireBase": 300000,
    "indemnites": [
        {"libelle": "Transport", "montant": 10000},
        {"libelle": "Logement", "montant": 70000}
    ],
    "totalIndemnites": 80000,
    "impot": 15000,
    "salaireNet": 365000
}
```

---

## 4. `bulletin.php` - Bulletin de Salaire

### 📍 Rôle
Génère un bulletin de paie imprimable au format A4.

### 📝 Code Complet Commenté

```php
<?php
/**
 * BULLETIN DE SALAIRE - Page imprimable
 * Génère une fiche de paie pour un employé
 */

require_once 'fonctions.php';

// ═══════════════════════════════════════════════════════════════════
// RÉCUPÉRATION DES DONNÉES
// ═══════════════════════════════════════════════════════════════════

$matricule = $_GET['matricule'] ?? null;

if (!$matricule) {
    die("Matricule manquant.");
}

try {
    // Récupérer toutes les infos de l'employé
    $stmt = $pdo->prepare("
        SELECT e.*, g.salaireBase, g.intitule 
        FROM Employe e 
        JOIN Grade g ON e.codeGr = g.codeGr 
        WHERE e.matricule = ?
    ");
    $stmt->execute([$matricule]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("Employé introuvable.");
    }

    // Récupérer la liste des indemnités
    $stmt2 = $pdo->prepare("
        SELECT i.libelle, a.montant 
        FROM ADroit a
        JOIN Indemnite i ON a.codeInd = i.codeInd
        WHERE a.codeGr = ?
    ");
    $stmt2->execute([$data['codeGr']]);
    $indemnites = $stmt2->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════
// CALCULS
// ═══════════════════════════════════════════════════════════════════

$salaireBase = $data['salaireBase'];

$totalIndemnites = 0;
foreach ($indemnites as $ind) {
    $totalIndemnites += $ind['montant'];
}

$salaireBrut = $salaireBase + $totalIndemnites;
$impot = $salaireBase * 0.05;
$salaireNet = $salaireBrut - $impot;

// Téléphone (avec valeur par défaut si non renseigné)
$tel = $data['tel'] ?? 'Non renseigné';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin - <?php echo htmlspecialchars($data['nom']); ?></title>
    <!-- Les styles sont en bas car spécifiques à cette page -->
    <link rel="stylesheet" href="css/bulletin.css">
</head>
<body>
    <!-- Boutons d'action (cachés à l'impression) -->
    <a href="index.php" class="back-btn">← Retour</a>
    <button class="print-btn" onclick="window.print()">🖨️ Imprimer</button>

    <!-- Le bulletin au format A4 -->
    <div class="bulletin-page">
        <!-- En-tête -->
        <header class="bulletin-header">
            <div class="company">RH PRO</div>
            <div class="title">BULLETIN DE SALAIRE</div>
            <div class="date"><?php echo date('d/m/Y'); ?></div>
        </header>

        <!-- Informations employé -->
        <section class="employee-info">
            <div class="info-row">
                <span class="label">Matricule :</span>
                <span><?php echo htmlspecialchars($data['matricule']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Nom :</span>
                <span><?php echo htmlspecialchars($data['nom']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Grade :</span>
                <span><?php echo htmlspecialchars($data['codeGr']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Téléphone :</span>
                <span><?php echo htmlspecialchars($tel); ?></span>
            </div>
        </section>

        <!-- Tableau des rubriques -->
        <table class="salary-table">
            <thead>
                <tr>
                    <th>Rubrique</th>
                    <th>Montant (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Salaire de Base</td>
                    <td class="amount"><?php echo number_format($salaireBase, 0, ',', ' '); ?></td>
                </tr>
                
                <?php foreach ($indemnites as $ind): ?>
                <tr>
                    <td>Indemnité : <?php echo htmlspecialchars($ind['libelle']); ?></td>
                    <td class="amount"><?php echo number_format($ind['montant'], 0, ',', ' '); ?></td>
                </tr>
                <?php endforeach; ?>

                <tr class="subtotal">
                    <td>Salaire Brut</td>
                    <td class="amount"><?php echo number_format($salaireBrut, 0, ',', ' '); ?></td>
                </tr>

                <tr class="deduction">
                    <td>Impôt (5% sur Base)</td>
                    <td class="amount">- <?php echo number_format($impot, 0, ',', ' '); ?></td>
                </tr>

                <tr class="net-pay">
                    <td>NET À PAYER</td>
                    <td class="amount"><?php echo number_format($salaireNet, 0, ',', ' '); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-block">
                <p>L'Employeur</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-block">
                <p>L'Employé</p>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>
</body>
</html>
```

---

# 🎨 FICHIERS CSS

---

## `css/style.css` - Styles Principaux

### 📍 Rôle
Contient TOUS les styles visuels du projet. Aucun style inline dans le HTML !

### 📝 Code Complet Commenté

```css
/**
 * ═══════════════════════════════════════════════════════════════════
 * VARIABLES CSS (Couleurs et polices réutilisables)
 * ═══════════════════════════════════════════════════════════════════
 */
:root {
    /* Couleurs principales */
    --bg-body: #f8fafc;           /* Fond de page (gris clair) */
    --bg-card: #ffffff;           /* Fond des cartes (blanc) */
    --text-primary: #1e293b;      /* Texte principal (noir) */
    --text-secondary: #64748b;    /* Texte secondaire (gris) */
    
    /* Couleur d'accent (vert émeraude) */
    --accent-primary: #059669;
    --accent-hover: #047857;      /* Plus foncé au survol */
    --accent-light: #d1fae5;      /* Version claire pour les fonds */
    
    /* Bordures et ombres */
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    
    /* Police de caractères */
    --font-family: 'Inter', system-ui, sans-serif;
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * RESET ET BASE
 * ═══════════════════════════════════════════════════════════════════
 */

/* Reset : Supprime les marges par défaut du navigateur */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;  /* Les bordures sont incluses dans la taille */
}

/* Style de base du body */
body {
    background-color: var(--bg-body);
    color: var(--text-primary);
    font-family: var(--font-family);
    line-height: 1.5;  /* Hauteur de ligne pour la lisibilité */
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * LAYOUT (Disposition de la page)
 * ═══════════════════════════════════════════════════════════════════
 */

/* Conteneur principal avec sidebar + contenu */
.layout-wrapper {
    display: flex;        /* Flexbox pour aligner côte à côte */
    min-height: 100vh;    /* 100% de la hauteur de l'écran */
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * SIDEBAR (Barre latérale)
 * ═══════════════════════════════════════════════════════════════════
 */

.sidebar {
    width: 260px;
    background-color: var(--bg-card);
    border-right: 1px solid var(--border-color);
    padding: 2rem;
    position: fixed;      /* Reste en place quand on scroll */
    height: 100vh;
}

/* Logo/Marque */
.brand {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: 3rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.brand i {
    color: var(--accent-primary);
}

/* Menu de navigation */
.nav-links {
    list-style: none;  /* Supprime les puces */
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    color: var(--text-secondary);
    text-decoration: none;  /* Supprime le soulignement */
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s;   /* Animation douce */
}

/* Effet au survol */
.nav-link:hover, 
.nav-link.active {
    background-color: var(--accent-light);
    color: var(--accent-primary);
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * CONTENU PRINCIPAL
 * ═══════════════════════════════════════════════════════════════════
 */

.main-content {
    flex: 1;              /* Prend tout l'espace restant */
    margin-left: 260px;   /* Laisse la place pour la sidebar */
    padding: 2rem;
}

/* Barre du haut avec titre et recherche */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.5rem;
}

.page-title h2 {
    font-size: 1.875rem;
    font-weight: 700;
}

.page-title p {
    color: var(--text-secondary);
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * CARTES DE STATISTIQUES
 * ═══════════════════════════════════════════════════════════════════
 */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);  /* 3 colonnes égales */
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--bg-card);
    padding: 1.5rem;
    border-radius: 1rem;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

/* Animation au survol */
.stat-card:hover {
    transform: translateY(-2px);  /* Monte légèrement */
    box-shadow: var(--shadow-md);
}

/* Icône dans un cercle */
.stat-icon-wrapper {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 0.75rem;
    background-color: var(--accent-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent-primary);
    font-size: 1.5rem;
}

/* Texte des stats */
.stat-info h4 {
    color: var(--text-secondary);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-info .value {
    font-size: 1.75rem;
    font-weight: 700;
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * CARTES GÉNÉRIQUES
 * ═══════════════════════════════════════════════════════════════════
 */

.card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 1rem;
    box-shadow: var(--shadow-sm);
    padding: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.card-title {
    font-size: 1.125rem;
    font-weight: 600;
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * TABLEAUX
 * ═══════════════════════════════════════════════════════════════════
 */

table {
    width: 100%;
    border-collapse: collapse;  /* Fusionne les bordures */
}

th {
    text-align: left;
    padding: 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
    background-color: #f8fafc;
    border-bottom: 1px solid var(--border-color);
}

td {
    padding: 1rem;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--border-color);
}

/* Dernière ligne sans bordure */
tr:last-child td {
    border-bottom: none;
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * BOUTONS
 * ═══════════════════════════════════════════════════════════════════
 */

.btn-icon {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: all 0.2s;
}

.btn-icon:hover {
    color: var(--accent-primary);
    background-color: var(--accent-light);
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * MODAL (Fenêtre popup)
 * ═══════════════════════════════════════════════════════════════════
 */

.modal {
    display: none;            /* Caché par défaut */
    position: fixed;
    z-index: 1000;            /* Au-dessus de tout */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);  /* Fond semi-transparent */
    backdrop-filter: blur(4px);            /* Flou en arrière-plan */
}

.modal-content {
    background-color: var(--bg-card);
    margin: 10vh auto;        /* 10% du haut, centré horizontalement */
    width: 90%;
    max-width: 500px;
    border-radius: 1rem;
    box-shadow: var(--shadow-md);
    animation: slideIn 0.3s ease-out;  /* Animation d'apparition */
}

/* Animation d'entrée */
@keyframes slideIn {
    from { 
        transform: translateY(-20px); 
        opacity: 0; 
    }
    to { 
        transform: translateY(0); 
        opacity: 1; 
    }
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1.5rem;
}

/* Bouton de fermeture */
.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * GRILLE DE CONTENU
 * ═══════════════════════════════════════════════════════════════════
 */

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;  /* 2/3 + 1/3 */
    gap: 1.5rem;
}

/* Conteneur du graphique */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
```

---

# 🟨 FICHIERS JAVASCRIPT

---

## `js/script.js` - Logique Interactive

### 📍 Rôle
Gère toutes les interactions utilisateur : recherche, graphiques, modals.

### 📝 Code Complet Commenté

```javascript
/**
 * ═══════════════════════════════════════════════════════════════════
 * SCRIPT PRINCIPAL
 * Ce fichier gère toute l'interactivité de l'application
 * ═══════════════════════════════════════════════════════════════════
 */

// Attendre que la page soit complètement chargée
document.addEventListener('DOMContentLoaded', function() {
    
    // ═══════════════════════════════════════════════════════════════
    // 1. GRAPHIQUE (Chart.js)
    // ═══════════════════════════════════════════════════════════════
    
    // Récupérer l'élément canvas pour le graphique
    const ctx = document.getElementById('salaryChart');
    
    if (ctx) {
        // Collecter les grades depuis le tableau HTML
        const grades = {};
        
        // Parcourir chaque ligne du tableau
        document.querySelectorAll('tbody tr').forEach(row => {
            // row.cells[2] = la 3ème colonne (index 2) = Grade
            const grade = row.cells[2].innerText;
            
            if (grade) {
                // Compter combien de fois chaque grade apparaît
                grades[grade] = (grades[grade] || 0) + 1;
            }
        });

        // Préparer les données pour Chart.js
        const chartData = {
            labels: Object.keys(grades),    // ["A1", "A5", ...]
            datasets: [{
                label: 'Effectif par Grade',
                data: Object.values(grades), // [2, 3, ...]
                backgroundColor: [
                    '#059669', '#10b981', '#34d399', '#6ee7b7', '#a7f3d0'
                ],
                borderWidth: 0,
                borderRadius: 4
            }]
        };

        // Créer le graphique
        new Chart(ctx, {
            type: 'doughnut',  // Type = donut (cercle avec trou)
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'  // Taille du trou au centre
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. RECHERCHE EN TEMPS RÉEL
    // ═══════════════════════════════════════════════════════════════
    
    const searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        // Écouter chaque frappe de touche
        searchInput.addEventListener('keyup', function(e) {
            // Récupérer le texte tapé (en minuscules)
            const term = e.target.value.toLowerCase();
            
            // Récupérer toutes les lignes du tableau
            const rows = document.querySelectorAll('tbody tr');
            
            // Parcourir chaque ligne
            rows.forEach(row => {
                // Récupérer le texte de la ligne
                const text = row.innerText.toLowerCase();
                
                // Afficher/masquer selon si le texte correspond
                // includes() vérifie si une chaîne contient une autre
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. MODAL (Fenêtre popup)
    // ═══════════════════════════════════════════════════════════════
    
    const modal = document.getElementById('detailModal');
    const closeBtn = document.querySelector('.close');
    
    // Fermer le modal en cliquant sur X
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = "none";
        };
    }
    
    // Fermer le modal en cliquant en dehors
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    // ═══════════════════════════════════════════════════════════════
    // 4. FONCTION POUR OUVRIR LES DÉTAILS (appelée depuis le HTML)
    // ═══════════════════════════════════════════════════════════════
    
    // window.openDetails rend la fonction accessible globalement
    window.openDetails = function(matricule) {
        
        // fetch() = Requête AJAX pour récupérer des données
        fetch('get_details.php?matricule=' + matricule)
            .then(response => response.json())  // Convertir en JSON
            .then(data => {
                // Vérifier s'il y a une erreur
                if(data.error) {
                    alert(data.error);
                    return;
                }
                
                // Mettre à jour le titre du modal
                document.getElementById('modalTitle').innerText = 
                    'Détails: ' + data.nom;
                
                // Construire le contenu HTML
                let content = `
                    <div class="detail-row">
                        <span>Matricule</span> 
                        <span>${data.matricule}</span>
                    </div>
                    <div class="detail-row">
                        <span>Grade</span> 
                        <span>${data.grade}</span>
                    </div>
                    <div class="detail-row">
                        <span>Salaire de Base</span> 
                        <span>${formatMoney(data.salaireBase)}</span>
                    </div>
                    <br>
                    <strong>Indemnités:</strong>
                `;
                
                // Ajouter chaque indemnité
                if (data.indemnites.length > 0) {
                    data.indemnites.forEach(ind => {
                        content += `
                            <div class="detail-row" style="padding-left:1rem; color:#64748b;">
                                <span>${ind.libelle}</span> 
                                <span>+ ${formatMoney(ind.montant)}</span>
                            </div>
                        `;
                    });
                } else {
                    content += `
                        <div class="detail-row" style="padding-left:1rem; color:#64748b;">
                            Aucune indemnité
                        </div>
                    `;
                }

                // Ajouter les totaux
                content += `
                    <div class="detail-row">
                        <span>Total Indemnités</span> 
                        <span>${formatMoney(data.totalIndemnites)}</span>
                    </div>
                    <div class="detail-row">
                        <span>Impôt (5%)</span> 
                        <span style="color:#ef4444;">- ${formatMoney(data.impot)}</span>
                    </div>
                    <div class="detail-row total">
                        <span>Salaire Net</span> 
                        <span>${formatMoney(data.salaireNet)}</span>
                    </div>
                `;

                // Injecter le contenu dans le modal
                document.getElementById('modalBody').innerHTML = content;
                
                // Afficher le modal
                modal.style.display = "block";
            })
            .catch(err => console.error('Erreur:', err));
    };

    // ═══════════════════════════════════════════════════════════════
    // 5. FONCTION UTILITAIRE : Formater les montants
    // ═══════════════════════════════════════════════════════════════
    
    function formatMoney(amount) {
        // Intl.NumberFormat formate les nombres selon la locale
        return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
    }
});
```

### 🔑 Concepts JavaScript Clés

| Concept | Explication |
|---------|-------------|
| `DOMContentLoaded` | Événement qui se déclenche quand la page est prête |
| `querySelector()` | Sélectionne UN élément HTML |
| `querySelectorAll()` | Sélectionne TOUS les éléments correspondants |
| `addEventListener()` | Écoute un événement (click, keyup, etc.) |
| `fetch()` | Fait une requête HTTP (AJAX) |
| `.then()` | Gère la réponse d'une promesse |
| Template literals | `` `${variable}` `` - Chaînes avec variables intégrées |

---

# 📋 RÉCAPITULATIF

## Où mettre chaque type de code ?

| Type de Code | Fichier | Exemple |
|--------------|---------|---------|
| **Structure HTML** | `*.php` ou `*.html` | `<div>`, `<table>`, `<button>` |
| **Styles visuels** | `css/style.css` | couleurs, tailles, animations |
| **Logique backend** | `*.php` | requêtes SQL, calculs serveur |
| **Logique frontend** | `js/script.js` | interactions utilisateur, AJAX |

## Comment lier les fichiers ?

```html
<!-- Dans le <head> : -->
<link rel="stylesheet" href="css/style.css">

<!-- Avant </body> : -->
<script src="js/script.js"></script>
```

## Règles d'or à retenir

1. ✅ **Un fichier = un rôle** (séparation des responsabilités)
2. ✅ **Pas de style inline** (`style="..."` dans le HTML)
3. ✅ **Pas d'événements inline** (`onclick="..."` dans le HTML)
4. ✅ **Utilisez des classes CSS** pour cibler les éléments
5. ✅ **Utilisez des IDs** pour les éléments uniques

---

**📚 Ce manuel vous servira de référence tout au long de vos projets !**
