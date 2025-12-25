<?php
// api_employee.php - Handle Create & Delete operations via AJAX
require_once 'connexion.php';

header('Content-Type: application/json');

// Get the HTTP method and input data
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? '';

if ($method === 'POST') {
    
    // ==========================================
    // ADD EMPLOYEE
    // ==========================================
    if ($action === 'add') {
        $matricule = $_POST['matricule'] ?? '';
        $nom = $_POST['nom'] ?? '';
        $codeGr = $_POST['codeGr'] ?? '';
        $tel = $_POST['tel'] ?? '';

        if (empty($matricule) || empty($nom) || empty($codeGr)) {
            echo json_encode(['success' => false, 'message' => 'Veuillez remplir les champs obligatoires.']);
            exit;
        }

        try {
            // Check if matricule exists
            $check = $pdo->prepare("SELECT matricule FROM Employe WHERE matricule = ?");
            $check->execute([$matricule]);
            if ($check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Ce matricule existe déjà.']);
                exit;
            }

            // Insert new employee
            $stmt = $pdo->prepare("INSERT INTO Employe (matricule, nom, codeGr, tel) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$matricule, $nom, $codeGr, $tel]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Employé ajouté avec succès.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // DELETE EMPLOYEE
    // ==========================================
    elseif ($action === 'delete') {
        $matricule = $_POST['matricule'] ?? '';

        if (empty($matricule)) {
            echo json_encode(['success' => false, 'message' => 'Matricule manquant.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM Employe WHERE matricule = ?");
            $result = $stmt->execute([$matricule]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Employé supprimé.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer (contraintes d\'intégrité?). Erreur : ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action invalide.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
