# 📘 Guide Complet : Créer une Application de Gestion RH en PHP/PDO

Ce guide vous explique **étape par étape** comment construire cette application de gestion des ressources humaines. Idéal pour les débutants qui veulent apprendre PHP, PDO et le développement web.

---

## 📋 Table des Matières

1. [Prérequis](#1-prérequis)
2. [Comprendre le Schéma Relationnel](#2-comprendre-le-schéma-relationnel)
3. [Créer la Base de Données](#3-créer-la-base-de-données)
4. [Connexion PHP avec PDO](#4-connexion-php-avec-pdo)
5. [Créer les Fonctions de Calcul](#5-créer-les-fonctions-de-calcul)
6. [Créer l'Interface Web](#6-créer-linterface-web)
7. [Ajouter de l'Interactivité (JavaScript)](#7-ajouter-de-linteractivité-javascript)
8. [Créer le Bulletin de Salaire](#8-créer-le-bulletin-de-salaire)

---

## 1. Prérequis

### Logiciels nécessaires
- **WAMP/XAMPP** : Serveur local avec Apache, MySQL et PHP
- **Navigateur web** : Chrome, Firefox, etc.
- **Éditeur de code** : VS Code, Sublime Text, etc.

### Connaissances requises
- Bases de HTML/CSS
- Notions de PHP (variables, fonctions, boucles)
- Comprendre ce qu'est une base de données

---

## 2. Comprendre le Schéma Relationnel

Avant de coder, il faut **concevoir** la base de données. Voici les entités de notre système :

```
┌─────────────┐       ┌─────────────┐
│   Grade     │       │  Indemnite  │
├─────────────┤       ├─────────────┤
│ codeGr (PK) │       │ codeInd(PK) │
│ salaireBase │       │ libelle     │
│ intitule    │       └─────────────┘
└─────────────┘              │
      ▲                      │
      │           ┌──────────┴──────────┐
      │           │       ADroit        │
      │           ├─────────────────────┤
      │           │ codeGr (FK, PK)     │
      │           │ codeInd (FK, PK)    │
      │           │ montant             │
      │           └─────────────────────┘
      │
┌─────┴───────┐
│  Employe    │
├─────────────┤
│matricule(PK)│
│ nom         │
│ codeGr (FK) │
│ tel         │
└─────────────┘
```

### Explication des relations :
- **Grade** : Contient les catégories de salaires (A1, A2, A5...)
- **Indemnite** : Types d'indemnités possibles (Transport, Logement...)
- **ADroit** : Table d'association qui lie un Grade à ses Indemnités avec un montant
- **Employe** : Les employés, chacun rattaché à un Grade

---

## 3. Créer la Base de Données

### Étape 3.1 : Ouvrir phpMyAdmin
1. Lancez WAMP/XAMPP
2. Allez sur `http://localhost/phpmyadmin`
3. Créez une nouvelle base : `rh_projet`

### Étape 3.2 : Créer les tables SQL

Copiez ce code dans l'onglet "SQL" de phpMyAdmin :

```sql
-- Table des Grades
CREATE TABLE Grade (
    codeGr VARCHAR(5) PRIMARY KEY,
    salaireBase DECIMAL(10,2) NOT NULL,
    intitule VARCHAR(100)
);

-- Table des Indemnités
CREATE TABLE Indemnite (
    codeInd VARCHAR(5) PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL
);

-- Table des Employés
CREATE TABLE Employe (
    matricule VARCHAR(10) PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    codeGr VARCHAR(5),
    tel VARCHAR(20),
    FOREIGN KEY (codeGr) REFERENCES Grade(codeGr)
);

-- Table d'association ADroit (Grade <-> Indemnité)
CREATE TABLE ADroit (
    codeGr VARCHAR(5),
    codeInd VARCHAR(5),
    montant DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (codeGr, codeInd),
    FOREIGN KEY (codeGr) REFERENCES Grade(codeGr),
    FOREIGN KEY (codeInd) REFERENCES Indemnite(codeInd)
);
```

### Étape 3.3 : Insérer des données de test

```sql
-- Grades
INSERT INTO Grade VALUES ('A1', 100000, 'Agent');
INSERT INTO Grade VALUES ('A5', 300000, 'Directeur');

-- Indemnités
INSERT INTO Indemnite VALUES ('I1', 'Transport');
INSERT INTO Indemnite VALUES ('I2', 'Logement');

-- Liaison Grade-Indemnité
INSERT INTO ADroit VALUES ('A5', 'I1', 10000);
INSERT INTO ADroit VALUES ('A5', 'I2', 70000);

-- Employés
INSERT INTO Employe VALUES ('M01', 'Toto Ali', 'A5', '07878987');
INSERT INTO Employe VALUES ('M02', 'Marie Koné', 'A1', '05551234');
```

---

## 4. Connexion PHP avec PDO

### Qu'est-ce que PDO ?
**PDO** (PHP Data Objects) est une extension PHP qui permet de se connecter à une base de données de manière **sécurisée**.

### Créer le fichier `connexion.php`

```php
<?php
try {
    // Paramètres de connexion
    $host = 'localhost';      // Adresse du serveur MySQL
    $dbname = 'rh_projet';    // Nom de la base de données
    $username = 'root';       // Utilisateur MySQL (défaut WAMP)
    $password = '';           // Mot de passe (vide par défaut sur WAMP)
    
    // Création de la connexion PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    
    // Active le mode erreur pour débugger facilement
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Si erreur, afficher le message et arrêter le script
    die("Erreur de connexion : " . $e->getMessage());
}
?>
```

### Explication :
- `try/catch` : Gère les erreurs proprement
- `new PDO(...)` : Crée une connexion à MySQL
- `setAttribute(...)` : Active l'affichage des erreurs SQL

---

## 5. Créer les Fonctions de Calcul

### Créer le fichier `fonctions.php`

Ce fichier contient toute la **logique métier** (calculs de salaire).

```php
<?php
require_once 'connexion.php'; // Inclut la connexion à la base

// ============================================
// a) Compter les indemnités d'un employé
// ============================================
function nbIndemnite($matricule) {
    global $pdo; // Accède à la variable $pdo créée dans connexion.php
    
    // 1. Trouver le grade de l'employé
    $stmt = $pdo->prepare("SELECT codeGr FROM Employe WHERE matricule = ?");
    $stmt->execute([$matricule]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$emp) return 0; // Employé non trouvé
    
    // 2. Compter les indemnités de ce grade (sans COUNT SQL)
    $stmt2 = $pdo->prepare("SELECT * FROM ADroit WHERE codeGr = ?");
    $stmt2->execute([$emp['codeGr']]);
    $indemnites = $stmt2->fetchAll();
    
    return count($indemnites); // Compte en PHP, pas en SQL
}

// ============================================
// b) Calculer le total des indemnités d'un grade
// ============================================
function totalIndeminite($codeGr) {
    global $pdo;
    $somme = 0;
    
    $stmt = $pdo->prepare("SELECT montant FROM ADroit WHERE codeGr = ?");
    $stmt->execute([$codeGr]);
    $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul de la somme en PHP (pas de SUM() SQL)
    foreach ($lignes as $ligne) {
        $somme += $ligne['montant'];
    }
    
    return $somme;
}

// ============================================
// c) Calculer le salaire net d'un employé
// Formule : Base + Indemnités - 5% de Base
// ============================================
function salaireNet($matricule) {
    global $pdo;
    
    // Récupérer le grade et salaire de base
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
    $totalInd = totalIndeminite($data['codeGr']); // Réutilise la fonction b)
    
    // Calcul final
    $impot = $salaireBase * 0.05;
    $net = $salaireBase + $totalInd - $impot;
    
    return $net;
}

// ============================================
// d) Trouver l'employé le mieux payé
// ============================================
function salaireMax() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT matricule, nom FROM Employe");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $maxSalaire = -1;
    $nomRiche = "";
    
    // Parcourir tous les employés pour trouver le max
    foreach ($employes as $emp) {
        $salaire = salaireNet($emp['matricule']);
        if ($salaire > $maxSalaire) {
            $maxSalaire = $salaire;
            $nomRiche = $emp['nom'];
        }
    }
    
    return $nomRiche . " (" . number_format($maxSalaire, 0, ',', ' ') . " FCFA)";
}

// ============================================
// e) Calculer la masse salariale totale
// ============================================
function totalSalaire() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT matricule FROM Employe");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = 0;
    foreach ($employes as $emp) {
        $total += salaireNet($emp['matricule']);
    }
    
    return $total;
}
?>
```

### Points clés :
- **`global $pdo`** : Permet d'utiliser la connexion dans les fonctions
- **`prepare()` + `execute()`** : Requêtes préparées (sécurisées contre les injections SQL)
- **`fetch()` vs `fetchAll()`** : `fetch()` = 1 résultat, `fetchAll()` = tous les résultats

---

## 6. Créer l'Interface Web

### Créer le fichier `index.php`

C'est la page principale qui affiche le tableau de bord.

```php
<?php
require_once 'fonctions.php'; // Charge les fonctions ET la connexion

// Récupérer tous les employés
$stmt = $pdo->query("
    SELECT e.matricule, e.nom, e.codeGr, g.salaireBase 
    FROM Employe e 
    LEFT JOIN Grade g ON e.codeGr = g.codeGr
");
$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les stats globales
$maxSalaireInfo = salaireMax();
$totalMasseSalariale = totalSalaire();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard RH</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Gestion des Ressources Humaines</h1>
    
    <!-- Statistiques -->
    <div class="stats">
        <div class="stat-card">
            <h3>Masse Salariale</h3>
            <p><?php echo number_format($totalMasseSalariale, 0, ',', ' '); ?> FCFA</p>
        </div>
        <div class="stat-card">
            <h3>Effectif</h3>
            <p><?php echo count($employes); ?></p>
        </div>
    </div>
    
    <!-- Tableau des employés -->
    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Grade</th>
                <th>Salaire Net</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employes as $emp): ?>
            <tr>
                <td><?php echo $emp['matricule']; ?></td>
                <td><?php echo $emp['nom']; ?></td>
                <td><?php echo $emp['codeGr']; ?></td>
                <td><?php echo number_format(salaireNet($emp['matricule']), 0, ',', ' '); ?> FCFA</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
```

### Explication :
- **Partie PHP (en haut)** : Prépare les données avant l'affichage
- **Partie HTML (en bas)** : Affiche les données avec `<?php echo ... ?>`
- **`foreach`** : Boucle pour afficher chaque employé

---

## 7. Ajouter de l'Interactivité (JavaScript)

### Créer `js/script.js`

Pour ajouter des fonctionnalités dynamiques sans recharger la page :

```javascript
// Recherche en temps réel dans le tableau
document.getElementById('searchInput').addEventListener('keyup', function(e) {
    const term = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});
```

### Pour les requêtes AJAX (charger des données sans recharger) :

```javascript
function chargerDetails(matricule) {
    fetch('get_details.php?matricule=' + matricule)
        .then(response => response.json())
        .then(data => {
            // Afficher les données dans un modal
            console.log(data);
        });
}
```

---

## 8. Créer le Bulletin de Salaire

### Créer `bulletin.php`

Page imprimable qui génère une fiche de paie :

```php
<?php
require_once 'fonctions.php';

$matricule = $_GET['matricule']; // Récupère le matricule depuis l'URL

// Récupérer les infos de l'employé
$stmt = $pdo->prepare("
    SELECT e.*, g.salaireBase, g.intitule 
    FROM Employe e 
    JOIN Grade g ON e.codeGr = g.codeGr 
    WHERE e.matricule = ?
");
$stmt->execute([$matricule]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculs
$salaireBase = $emp['salaireBase'];
$totalInd = totalIndeminite($emp['codeGr']);
$impot = $salaireBase * 0.05;
$salaireNet = $salaireBase + $totalInd - $impot;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bulletin de Salaire</title>
    <style>
        body { font-family: Arial; }
        .bulletin { width: 600px; margin: auto; border: 1px solid #000; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #ccc; padding: 8px; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <div class="bulletin">
        <h1>Bulletin de Salaire</h1>
        <p><strong>Matricule :</strong> <?php echo $emp['matricule']; ?></p>
        <p><strong>Nom :</strong> <?php echo $emp['nom']; ?></p>
        <p><strong>Grade :</strong> <?php echo $emp['codeGr']; ?></p>
        
        <table>
            <tr><th>Rubrique</th><th>Montant</th></tr>
            <tr><td>Salaire de Base</td><td><?php echo number_format($salaireBase, 0, ',', ' '); ?></td></tr>
            <tr><td>Total Indemnités</td><td><?php echo number_format($totalInd, 0, ',', ' '); ?></td></tr>
            <tr><td>Impôt (5%)</td><td>- <?php echo number_format($impot, 0, ',', ' '); ?></td></tr>
            <tr><td><strong>NET A PAYER</strong></td><td><strong><?php echo number_format($salaireNet, 0, ',', ' '); ?></strong></td></tr>
        </table>
        
        <button onclick="window.print()">Imprimer</button>
    </div>
</body>
</html>
```

---

## 🎯 Résumé des Fichiers

| Fichier | Rôle |
|---------|------|
| `connexion.php` | Connexion à la base de données |
| `fonctions.php` | Logique métier (calculs) |
| `index.php` | Interface principale |
| `bulletin.php` | Génération du bulletin |
| `get_details.php` | API JSON pour AJAX |
| `css/style.css` | Styles visuels |
| `js/script.js` | Interactivité JavaScript |

---

## 💡 Conseils pour Progresser

1. **Testez chaque étape** : N'écrivez pas tout d'un coup, testez après chaque fonction
2. **Utilisez `var_dump()`** : Pour voir le contenu d'une variable PHP
3. **Regardez la console** : F12 dans le navigateur pour voir les erreurs JavaScript
4. **Sécurisez vos requêtes** : Toujours utiliser `prepare()` et `execute()` avec des paramètres

---

## 🚀 Pour Aller Plus Loin

- Ajouter un **système de login** pour sécuriser l'accès
- Créer une page pour **ajouter/modifier/supprimer** des employés
- Exporter les bulletins en **PDF** (avec la librairie FPDF ou TCPDF)
- Ajouter des **graphiques** avec Chart.js

---

**Bonne chance dans votre apprentissage ! 🎓**
