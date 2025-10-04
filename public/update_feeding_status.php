<?php
try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Vérifie que la requête est POST et que des IDs de repas ont été sélectionnés
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['feeding_ids'])) {
        $feeding_ids = $_POST['feeding_ids'];
        $action = $_POST['action'] ?? 'given'; // Par défaut, 'given' si le bouton normal est cliqué
        
        // Crée la liste des ? pour la clause IN de la requête SQL
        $ids_placeholder = implode(',', array_fill(0, count($feeding_ids), '?'));

        switch ($action) {
            case 'given':
                // Action : Marquer comme donné (pending=0, refused=0)
                $stmt = $pdo->prepare("
                    UPDATE feedings 
                    SET pending = 0, refused = 0 
                    WHERE id IN ($ids_placeholder)
                ");
                $stmt->execute($feeding_ids);
                break;

            case 'refused':
                // Action : Marquer comme refusé (pending=0, refused=1)
                $stmt = $pdo->prepare("
                    UPDATE feedings 
                    SET pending = 0, refused = 1 
                    WHERE id IN ($ids_placeholder)
                ");
                $stmt->execute($feeding_ids);
                break;

            case 'cancel':
                // Action : Annuler / Supprimer (supprime la ligne du repas)
                $stmt = $pdo->prepare("
                    DELETE FROM feedings 
                    WHERE id IN ($ids_placeholder)
                ");
                $stmt->execute($feeding_ids);
                break;

            default:
                // Ne rien faire ou gérer une action inconnue
                // Pour l'instant, on ignore
                break;
        }
    }
} catch (PDOException $e) {
    // En cas d'erreur de base de données, affiche un message
    die("Erreur de base de données : " . $e->getMessage());
}

// Redirige toujours vers la page d'accueil
header('Location: ' . base_url('index.php'));
exit;
?>
